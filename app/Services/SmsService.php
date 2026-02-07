<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\SmsSetting;
use App\Gateways\AlphaSmsGateway;
use Twilio\Rest\Client as TwilioClient;

class SmsService
{
    public function send(string $to, string $message): bool
    {
        // Load provider settings: prefer DB overrides if present
        $db = [];
        try { $db = SmsSetting::pluck('value','key')->toArray(); } catch (\Exception $e) { /* ignore if table missing */ }

        $provider = $db['provider'] ?? config('sms.provider', 'http');

        try {
            if ($provider === 'http') {
                // Use local AlphaSmsGateway for HTTP/ALPHA sends
                $gateway = new AlphaSmsGateway($db);
                return $gateway->send($to, $message);
            }

            if ($provider === 'twilio') {
                $sid = $db['twilio_account_sid'] ?? config('sms.twilio_account_sid');
                $token = $db['twilio_auth_token'] ?? config('sms.twilio_auth_token');
                $from = $db['twilio_from'] ?? config('sms.twilio_from');
                if (empty($sid) || empty($token) || empty($from)){
                    Log::warning('Twilio credentials not configured.');
                    return false;
                }
                try {
                    $client = new TwilioClient($sid, $token);
                    $msg = $client->messages->create($to, [
                        'from' => $from,
                        'body' => $message,
                    ]);
                    if ($msg && isset($msg->sid)) { return true; }
                    Log::warning('Twilio send returned no sid', ['to' => $to]);
                    return false;
                } catch (\Exception $e) {
                    Log::warning('Twilio send exception: '.$e->getMessage());
                    return false;
                }
            }

            Log::warning('Unsupported SMS provider: ' . $provider);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS send exception: ' . $e->getMessage());
            return false;
        }
    }
}
