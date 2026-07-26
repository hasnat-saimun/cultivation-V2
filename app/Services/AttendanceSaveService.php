<?php

namespace App\Services;

use App\Jobs\SendSmsJob;
use App\Models\Attendance;
use App\Models\CultivationAdmin;
use App\Models\newAdmission;
use App\Models\ServerConfig;
use App\Models\SmsSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceSaveService
{
    public const STATUSES = ['Present', 'Absent', 'Late', 'Excused'];
    private ?array $smsSettings = null;
    private ServerConfig|false|null $serverConfig = null;

    /** @return array{created:int,updated:int,submitted:int} */
    public function save(
        string $date,
        int $classId,
        ?int $sectionId,
        ?int $sessionId,
        CultivationAdmin $actor,
        array $studentIds,
        array $statuses,
        Collection $population,
    ): array {
        if (count($studentIds) !== count($statuses)) {
            throw ValidationException::withMessages(['status' => 'Every student must have one attendance status.']);
        }

        $normalizedIds = array_map('intval', $studentIds);
        if (count($normalizedIds) !== count(array_unique($normalizedIds))) {
            throw ValidationException::withMessages(['studentId' => 'Duplicate student rows are not allowed.']);
        }

        $allowedIds = $population->pluck('id')->map(fn ($id) => (int) $id)->all();
        foreach ($normalizedIds as $index => $studentId) {
            if (!in_array($studentId, $allowedIds, true)) {
                throw ValidationException::withMessages(['studentId' => 'One or more students are outside the authorized attendance scope.']);
            }
            if (!in_array($statuses[$index], self::STATUSES, true)) {
                throw ValidationException::withMessages(['status' => 'An invalid attendance status was submitted.']);
            }
        }

        return DB::transaction(function () use ($date, $classId, $sectionId, $sessionId, $actor, $normalizedIds, $statuses, $population) {
            $created = 0;
            $updated = 0;
            $students = $population->keyBy(fn ($student) => (int) $student->id);

            foreach ($normalizedIds as $index => $studentId) {
                $attendance = Attendance::updateOrCreate(
                    [
                        'attendance_date' => $date,
                        'class_id' => $classId,
                        'section_id' => $sectionId,
                        'student_id' => $studentId,
                    ],
                    [
                        'session_id' => $sessionId,
                        'teacher_id' => (int) $actor->id,
                        'status' => $statuses[$index],
                    ],
                );
                $attendance->wasRecentlyCreated ? $created++ : $updated++;
                $this->dispatchSms($students->get($studentId), $date, $statuses[$index], $attendance);
            }

            return ['created' => $created, 'updated' => $updated, 'submitted' => count($normalizedIds)];
        });
    }

    private function dispatchSms(?newAdmission $student, string $date, string $status, Attendance $attendance): void
    {
        try {
            if (!$student || !($phone = ($student->gurdianPhone ?: $student->phone ?: null))) {
                return;
            }
            $settings = $this->smsSettings ??= SmsSetting::pluck('value', 'key')->toArray();
            if ($this->serverConfig === null) {
                $this->serverConfig = ServerConfig::query()->latest('id')->first() ?: false;
            }
            $server = $this->serverConfig ?: null;
            if ($server && $server->sm_on_off !== null && $server->sm_on_off !== ''
                && !filter_var($server->sm_on_off, FILTER_VALIDATE_BOOLEAN)) {
                return;
            }
            $key = strtolower($status) === 'present' ? 'present' : 'absent';
            $type = $server?->sms_type;
            if (in_array($type, ['present_only', 'absent_only', 'both'], true)) {
                if (($type === 'present_only' && $key !== 'present') || ($type === 'absent_only' && $key !== 'absent')) {
                    return;
                }
            } else {
                $enabledKey = "sms_on_{$key}";
                $enabled = isset($settings[$enabledKey])
                    ? filter_var($settings[$enabledKey], FILTER_VALIDATE_BOOLEAN)
                    : config("sms.{$enabledKey}", false);
                if (!$enabled) {
                    return;
                }
            }
            $templateKey = "sms_message_{$key}";
            $serverTemplate = $server?->{$key === 'present' ? 'sms_body_present' : 'sms_body_absent'};
            $template = $serverTemplate ?: ($settings[$templateKey] ?? config("sms.{$templateKey}", ''));
            $message = str_replace(
                ['{student}', '{date}', '{status}'],
                [$student->fullName ?? '', $date, $status],
                $template,
            );
            SendSmsJob::dispatch($phone, $message, $attendance->id);
        } catch (\Throwable) {
            // Attendance remains authoritative when optional notification infrastructure is unavailable.
        }
    }
}
