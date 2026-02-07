<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SmsSetting;
use App\Services\SmsService;
use App\Models\ServerConfig;

class SmsSettingsController extends Controller
{
    public function edit()
    {
        $defaults = config('sms');
        $db = [];
        try { $db = SmsSetting::pluck('value','key')->toArray(); } catch (\Exception $e) { }
        $values = array_merge($defaults, $db);
        $serverConfig = null;
        try { $serverConfig = ServerConfig::orderBy('id','DESC')->first(); } catch (\Exception $e) { }
        $smsEnabled = true;
        if ($serverConfig && $serverConfig->sm_on_off !== null && $serverConfig->sm_on_off !== '') {
            $smsEnabled = filter_var($serverConfig->sm_on_off, FILTER_VALIDATE_BOOLEAN);
        }
        return view('sms.settings', ['values' => $values, 'smsEnabled' => $smsEnabled]);
    }

    /**
     * Try to turn an HTML/encoded/pretty-printed JSON-like string into an array.
     */
    private function parseJsonLikeString($s)
    {
        if (!is_string($s)) return null;
        $orig = $s;
        // Decode HTML entities and collapse whitespace
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5);
        $s = trim(preg_replace('/\s+/', ' ', $s));

        // Try plain json_decode
        $decoded = @json_decode($s, true);
        if (is_array($decoded)) return $decoded;

        // If the string is a JSON array like [ {...}, {...} ] try decode
        if (preg_match('/^\s*\[.*\]\s*$/s', $s)) {
            $arr = @json_decode($s, true);
            if (is_array($arr)) return $arr;
        }

        // Try to extract all {...} chunks (there may be multiple JSON objects separated by commas)
        if (preg_match_all('/\{.*?\}/s', $s, $m)) {
            $out = [];
            foreach ($m[0] as $chunk) {
                $decoded = @json_decode($chunk, true);
                if (is_array($decoded)) {
                    $out[] = $decoded;
                    continue;
                }
                // normalize smart quotes then try again
                $chunk2 = str_replace(["“","”","’","‘","\u201c","\u201d"], '"', $chunk);
                $decoded = @json_decode($chunk2, true);
                if (is_array($decoded)) {
                    $out[] = $decoded;
                }
            }
            if (count($out) === 1) return $out[0];
            if (count($out) > 1) return $out;
        }

        // Fallback: try replacing fancy quotes globally
        $s2 = str_replace(["“","”","’","‘"], ['"','"',"'","'"], $s);
        $decoded = @json_decode($s2, true);
        if (is_array($decoded)) return $decoded;

        return null;
    }

    public function save(Request $req)
    {
        $data = $req->validate([
            'provider' => 'required|string',
            'sms_on_present' => 'nullable|boolean',
            'sms_on_absent' => 'nullable|boolean',
            'sms_message_present' => 'nullable|string',
            'sms_message_absent' => 'nullable|string',
            'api_url' => 'nullable|string',
            'api_key' => 'nullable|string',
            'alpha_rate_url' => 'nullable|string',
            'sender' => 'nullable|string',
            'http_param_map' => 'nullable|string',
            'twilio_account_sid' => 'nullable|string',
            'twilio_auth_token' => 'nullable|string',
            'twilio_from' => 'nullable|string',
            'sms_settings_enabled' => 'nullable|boolean',
        ]);

        $toSave = [
            'provider' => $data['provider'] ?? '',
            'sms_on_present' => isset($data['sms_on_present']) ? ($data['sms_on_present'] ? '1' : '0') : '0',
            'sms_on_absent' => isset($data['sms_on_absent']) ? ($data['sms_on_absent'] ? '1' : '0') : '0',
            'sms_message_present' => $data['sms_message_present'] ?? '',
            'sms_message_absent' => $data['sms_message_absent'] ?? '',
            'api_url' => $data['api_url'] ?? '',
            'api_key' => $data['api_key'] ?? '',
            'alpha_rate_url' => $data['alpha_rate_url'] ?? '',
            'sender' => $data['sender'] ?? '',
            'http_param_map' => $data['http_param_map'] ?? '',
            'twilio_account_sid' => $data['twilio_account_sid'] ?? '',
            'twilio_auth_token' => $data['twilio_auth_token'] ?? '',
            'twilio_from' => $data['twilio_from'] ?? '',
        ];

        foreach($toSave as $key => $val){
            SmsSetting::updateOrCreate(['key' => $key], ['value' => $val]);
        }
        try {
            $serverConfig = ServerConfig::orderBy('id','DESC')->first();
            if (!$serverConfig) {
                $serverConfig = new ServerConfig();
            }
            $serverConfig->sm_on_off = isset($data['sms_settings_enabled']) && $data['sms_settings_enabled'] ? '1' : '0';
            $serverConfig->save();
        } catch (\Exception $e) { }
        return redirect()->route('sms.settings')->with('success','SMS settings saved.');
    }

    public function toggleEnabled(Request $req)
    {
        $data = $req->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $serverConfig = ServerConfig::orderBy('id','DESC')->first();
            if (!$serverConfig) {
                $serverConfig = new ServerConfig();
            }
            $serverConfig->sm_on_off = $data['enabled'] ? '1' : '0';
            $serverConfig->save();
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'enabled' => (bool)$data['enabled']]);
    }

    public function status()
    {
        try {
            $serverConfig = ServerConfig::orderBy('id','DESC')->first();
            $enabled = true;
            if ($serverConfig && $serverConfig->sm_on_off !== null && $serverConfig->sm_on_off !== '') {
                $enabled = filter_var($serverConfig->sm_on_off, FILTER_VALIDATE_BOOLEAN);
            }
            return response()->json(['ok' => true, 'enabled' => (bool)$enabled]);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function test(Request $req)
    {
        $data = $req->validate([
            'phone' => 'required|string',
            'message' => 'nullable|string',
        ]);
        $phone = $data['phone'];
        $message = $data['message'] ?? null;
        if (empty($message)) {
            $db = [];
            try { $db = SmsSetting::pluck('value','key')->toArray(); } catch (\Exception $e) { }
            $message = $db['sms_message_absent'] ?? $db['sms_message_present'] ?? config('sms.sms_message_absent');
        }
        try {
            $svc = new SmsService();
            $ok = $svc->send($phone, $message);
            if ($ok) return redirect()->route('sms.settings')->with('success','Test SMS sent.');
            return redirect()->route('sms.settings')->with('error','Failed to send test SMS. Check logs.');
        } catch (\Exception $e) {
            return redirect()->route('sms.settings')->with('error','Exception sending test SMS: '.$e->getMessage());
        }
    }

    // Fetch live Alpha SMS plans/rates from configured endpoint and return JSON
    public function alphaRate()
    {
        $db = [];
        try { $db = SmsSetting::pluck('value','key')->toArray(); } catch (\Exception $e) { }
        $url = $db['alpha_rate_url'] ?? $db['api_rate_url'] ?? config('sms.alpha_rate_url');
        $apiKey = $db['api_key'] ?? config('sms.api_key');
                if (empty($url)) {
            return response()->json(['ok' => false, 'message' => 'Alpha rate URL not configured.'], 400);
        }
        try {
            $query = [];
            if (!empty($apiKey)) { $query['api_key'] = $apiKey; }
            $resp = \Illuminate\Support\Facades\Http::timeout(10)->get($url, $query);
            if ($resp->successful()) {
                $body = $resp->body();
                $json = json_decode($body, true);
                if (is_array($json)) {
                    return response()->json(['ok' => true, 'url' => $url, 'data' => $json]);
                }

                // Attempt to scrape the first meaningful HTML table of plans
                $plans = [];
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                if (@$dom->loadHTML($body)) {
                    $xpath = new \DOMXPath($dom);
                    $tables = $xpath->query('//table');
                    foreach ($tables as $t) {
                        // extract header cells
                        $headers = [];
                        $hdrNodes = $xpath->query('.//thead//th', $t);
                        if ($hdrNodes->length === 0) {
                            // try first row as header
                            $firstRowTh = $xpath->query('.//tr[1]//th', $t);
                            if ($firstRowTh->length) $hdrNodes = $firstRowTh;
                            else {
                                $firstRowTd = $xpath->query('.//tr[1]//td', $t);
                                if ($firstRowTd->length) {
                                    foreach ($firstRowTd as $cell) {
                                        $headers[] = trim(preg_replace('/\s+/', ' ', $cell->textContent));
                                    }
                                }
                            }
                        }
                        if ($hdrNodes->length) {
                            foreach ($hdrNodes as $h) {
                                $headers[] = trim(preg_replace('/\s+/', ' ', $h->textContent));
                            }
                        }

                        // collect rows
                        $rowNodes = $xpath->query('.//tbody//tr', $t);
                        if ($rowNodes->length === 0) {
                            $rowNodes = $xpath->query('.//tr[position()>1]', $t);
                        }
                        $rows = [];
                        foreach ($rowNodes as $r) {
                            $cells = $xpath->query('.//th|.//td', $r);
                            $cellsArr = [];
                            foreach ($cells as $c) { $cellsArr[] = trim(preg_replace('/\s+/', ' ', $c->textContent)); }
                            if (count($headers) && count($cellsArr) === count($headers)) {
                                $item = [];
                                for ($i = 0; $i < count($headers); $i++) { $item[$headers[$i]] = $cellsArr[$i]; }
                                $rows[] = $item;
                            } elseif (count($cellsArr) > 0) {
                                $rows[] = $cellsArr;
                            }
                        }

                        if (count($rows) > 0) {
                            $plans = $rows;
                            break; // use first useful table
                        }
                    }
                }
                libxml_clear_errors();

                if (!empty($plans)) {
                    // Try to expand any JSON-like cells (e.g. tier mappings) into structured plan rows
                    $expanded = [];
                    foreach ($plans as $prow) {
                        $foundJson = false;
                        // associative row (headers => value)
                        if (is_array($prow) && array_values($prow) !== $prow) {
                            // if this associative row itself looks like a tier mapping (Details, Platinum, Enterprise, ...), expand it directly
                            $keys = array_keys($prow);
                            $isTierMap = false;
                            foreach ($keys as $k) {
                                if (preg_match('/Details|Platinum|Enterprise|Business|Standard|Basic|Minimum|Validity|SIGN UP/i', $k)) { $isTierMap = true; break; }
                            }
                            if ($isTierMap) {
                                $category = $prow['Details'] ?? null;
                                foreach ($prow as $tier => $val) {
                                    if ($tier === 'Details') continue;
                                    if ($val === null || $val === '') continue;
                                    $priceRaw = trim($val);
                                    $currency = '';
                                    if (mb_strpos($priceRaw, '৳') !== false) { $currency = 'BDT'; }
                                    $num = preg_replace('/[^0-9\.,\-]/u','', $priceRaw);
                                    $num = str_replace(',', '', $num);
                                    $perSms = $num;
                                    $name = $tier;
                                    if ($category) { $name = $category . ' - ' . $tier; }
                                            $expanded[] = [
                                                'provider' => 'ALPHA',
                                                'per_sms' => $perSms,
                                                'currency' => $currency,
                                                'name' => $name,
                                                'raw_value' => $priceRaw,
                                            ];
                                }
                                continue; // processed this row
                            }
                            foreach ($prow as $colName => $colVal) {
                                if (!is_string($colVal)) continue;
                                $s = trim($colVal);
                                        if (strlen($s) > 1 && $s[0] === '{' && (strpos($s, '"') !== false || strpos($s, ':') !== false)) {
                                            $decoded = $this->parseJsonLikeString($s);
                                            if (is_array($decoded)) {
                                                $foundJson = true;
                                                // If decoded is a numeric-indexed list of objects, iterate each
                                                $decodedItems = (array_values($decoded) === $decoded && isset($decoded[0]) && is_array($decoded[0])) ? $decoded : [$decoded];
                                                foreach ($decodedItems as $d) {
                                                    $category = $d['Details'] ?? null;
                                                    foreach ($d as $tier => $val) {
                                                        if ($tier === 'Details') continue;
                                                        if ($val === null || $val === '') continue;
                                                        $priceRaw = trim($val);
                                                        $currency = '';
                                                        if (mb_strpos($priceRaw, '৳') !== false) { $currency = 'BDT'; }
                                                        $num = preg_replace('/[^0-9\.,\-]/u','', $priceRaw);
                                                        $num = str_replace(',', '', $num);
                                                        $perSms = $num;
                                                        $name = $tier;
                                                        if ($category) { $name = $category . ' - ' . $tier; }
                                                        $expanded[] = [
                                                            'provider' => 'ALPHA',
                                                                'per_sms' => $perSms,
                                                            'currency' => $currency,
                                                            'name' => $name,
                                                                'raw_value' => $priceRaw,
                                                            ];
                                                    }
                                                }
                                            }
                                }
                            }
                        }
                        // numeric-indexed row (no headers)
                        if (!$foundJson && is_array($prow) && array_values($prow) === $prow) {
                            foreach ($prow as $colVal) {
                                if (!is_string($colVal)) continue;
                                $s = trim($colVal);
                                if (strlen($s) > 1 && $s[0] === '{' && (strpos($s, '"') !== false || strpos($s, ':') !== false)) {
                                    $decoded = $this->parseJsonLikeString($s);
                                    if (is_array($decoded)) {
                                        $foundJson = true;
                                        $decodedItems = (array_values($decoded) === $decoded && isset($decoded[0]) && is_array($decoded[0])) ? $decoded : [$decoded];
                                        foreach ($decodedItems as $d) {
                                            $category = $d['Details'] ?? null;
                                            foreach ($d as $tier => $val) {
                                                if ($tier === 'Details') continue;
                                                if ($val === null || $val === '') continue;
                                                $priceRaw = trim($val);
                                                $currency = '';
                                                if (mb_strpos($priceRaw, '৳') !== false) { $currency = 'BDT'; }
                                                $num = preg_replace('/[^0-9\.,\-]/u','', $priceRaw);
                                                $num = str_replace(',', '', $num);
                                                $perSms = $num;
                                                $name = $tier;
                                                if ($category) { $name = $category . ' - ' . $tier; }
                                                $expanded[] = [
                                                    'provider' => 'ALPHA',
                                                        'per_sms' => $perSms,
                                                    'currency' => $currency,
                                                    'name' => $name,
                                                        'raw_value' => $priceRaw,
                                                    ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (!$foundJson) {
                            $expanded[] = $prow;
                        }
                    }
                    return response()->json(['ok' => true, 'url' => $url, 'data' => $expanded]);
                }

                // fallback: return raw body when scraping failed
                return response()->json(['ok' => true, 'url' => $url, 'data' => $body]);
            }
            return response()->json(['ok' => false, 'status' => $resp->status(), 'body' => $resp->body(), 'url' => $url], 500);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
