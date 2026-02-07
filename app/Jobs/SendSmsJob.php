<?php

namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $phone;
    public $message;
    public $attendanceId;

    public function __construct(string $phone, string $message, $attendanceId = null)
    {
        $this->phone = $phone;
        $this->message = $message;
        $this->attendanceId = $attendanceId;
    }

    public function handle()
    {
        $service = new SmsService();
        $ok = $service->send($this->phone, $this->message);
        if (! $ok) {
            Log::warning('SendSmsJob failed for phone ' . $this->phone);
        }

        // If an attendance record id was provided, update its sms_sent flag and timestamp
        if ($this->attendanceId) {
            try {
                $att = \App\Models\Attendance::find($this->attendanceId);
                if ($att) {
                    if ($ok) {
                        $att->sms_sent = 1;
                        $att->sms_sent_at = now();
                    } else {
                        $att->sms_sent = 0;
                    }
                    $att->save();
                }
            } catch (\Exception $e) {
                Log::warning('Failed to update attendance sms flag: '.$e->getMessage());
            }
        }
    }
}
