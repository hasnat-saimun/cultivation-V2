<?php

namespace App\Gateways;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsSetting;

class AlphaSmsGateway
{
    protected $apiUrl;
    protected $apiKey;
    protected $sender;
    protected $paramMap = [];

    public function __construct(array $overrides = [])
    {
        $db = [];
        try { $db = SmsSetting::pluck('value','key')->toArray(); } catch (\Exception $e) { }
        $this->apiUrl = $overrides['api_url'] ?? $db['api_url'] ?? config('sms.api_url');
        $this->apiKey = $overrides['api_key'] ?? $db['api_key'] ?? config('sms.api_key');
        $this->sender = $overrides['sender'] ?? $db['sender'] ?? config('sms.sender');
        $mapRaw = $overrides['http_param_map'] ?? $db['http_param_map'] ?? config('sms.http_param_map', []);
        $this->paramMap = is_array($mapRaw) ? $mapRaw : (@json_decode($mapRaw, true) ?: []);
    }

    /**
     * Send SMS using Alpha HTTP API.
     * Returns true on success, false otherwise.
     */
    public function send(string $to, string $message): bool
    {
        if (empty($this->apiUrl)) {
            Log::warning('AlphaSmsGateway: api_url is not configured');
            return false;
        }

        // Build payload using mapping if provided
        $payload = [];
        if (!empty($this->paramMap) && is_array($this->paramMap)) {
            foreach ($this->paramMap as $param => $template) {
                $val = $template;
                $val = str_replace(['{to}','{message}','{api_key}','{sender}'], [$to, $message, $this->apiKey, $this->sender], $val);
                // omit empty values
                if ($val !== null) $payload[$param] = $val;
            }
        } else {
            $payload = [
                'to' => $to,
                'message' => $message,
            ];
            if (!empty($this->apiKey)) $payload['api_key'] = $this->apiKey;
            if (!empty($this->sender)) $payload['sender'] = $this->sender;
        }

        // Try sending with a small number of retries
        $attempts = (int) config('sms.http_retries', 2);
        $timeout = (int) config('sms.timeout', 10);
        for ($i = 0; $i <= $attempts; $i++) {
            try {
                $resp = Http::timeout($timeout)->post($this->apiUrl, $payload);
                $body = $resp->body();
                // Try to interpret JSON body for provider-level errors
                $json = null;
                if (is_string($body) && strlen(trim($body)) && (strpos(trim($body), '{') === 0 || strpos(trim($body), '[') === 0)) {
                    $json = @json_decode($body, true);
                }

                // If HTTP status is successful, also ensure provider returned no error code
                if ($resp->successful()) {
                    if (is_array($json) && array_key_exists('error', $json) && intval($json['error']) !== 0) {
                        Log::warning('AlphaSmsGateway: provider returned error', ['to'=>$to,'status'=>$resp->status(),'body'=>$body]);
                        // If invalid sender, try resend once without sender param
                        if (intval($json['error']) === 413) {
                            Log::info('AlphaSmsGateway: attempting resend without sender due to error 413');
                            // remove sender keys from payload
                            $payloadNoSender = $payload;
                            // remove any param whose template included {sender} when paramMap was used
                            if (!empty($this->paramMap)) {
                                foreach ($this->paramMap as $param => $template) {
                                    if (is_string($template) && strpos($template, '{sender}') !== false) {
                                        unset($payloadNoSender[$param]);
                                    }
                                }
                            }
                            // also remove explicit sender key
                            foreach (['sender','from','source','mask'] as $k) { if (array_key_exists($k,$payloadNoSender)) unset($payloadNoSender[$k]); }
                            try {
                                $resp2 = Http::timeout($timeout)->post($this->apiUrl, $payloadNoSender);
                                $body2 = $resp2->body();
                                $json2 = null;
                                if (is_string($body2) && strlen(trim($body2)) && (strpos(trim($body2), '{') === 0 || strpos(trim($body2), '[') === 0)) {
                                    $json2 = @json_decode($body2, true);
                                }
                                if ($resp2->successful() && !(is_array($json2) && array_key_exists('error',$json2) && intval($json2['error']) !== 0)) {
                                    Log::info('AlphaSmsGateway: resend without sender succeeded', ['to'=>$to,'status'=>$resp2->status(),'body'=>$body2]);
                                    return true;
                                }
                                Log::warning('AlphaSmsGateway: resend without sender failed', ['to'=>$to,'status'=>$resp2->status(),'body'=>$body2]);
                            } catch (\Exception $e) {
                                Log::warning('AlphaSmsGateway: exception on resend without sender: '.$e->getMessage());
                            }
                        }
                        // treat provider-reported error as a send failure
                    } else {
                        Log::info('AlphaSmsGateway: send success', ['to'=>$to,'status'=>$resp->status(),'body'=>$body]);
                        return true;
                    }
                } else {
                    // non-2xx HTTP
                    Log::warning('AlphaSmsGateway: send failed', ['to'=>$to,'status'=>$resp->status(),'body'=>$body]);
                }
            } catch (\Exception $e) {
                Log::warning('AlphaSmsGateway: exception sending SMS: '.$e->getMessage());
            }
            // small backoff
            sleep(1);
        }

        return false;
    }
}
