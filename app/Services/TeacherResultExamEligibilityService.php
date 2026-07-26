<?php

namespace App\Services;

use App\Exceptions\ResultLifecycleException;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use Illuminate\Support\Collection;

class TeacherResultExamEligibilityService
{
    public const ALL_CLASSES_VALUE = '0';

    public function eligibleForClass(int $classId): Collection
    {
        return Exam::query()
            ->where(function ($query) use ($classId) {
                $query->where('className', (string) $classId)
                    ->orWhere('className', self::ALL_CLASSES_VALUE);
            })
            ->orderByDesc('id')
            ->get();
    }

    public function eligibleForClasses(array $classIds): Collection
    {
        $classes = collect($classIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($classes->isEmpty()) return collect();

        return Exam::query()
            ->where(function ($query) use ($classes) {
                $query->whereIn('className', $classes->map(fn ($id) => (string) $id)->all())
                    ->orWhere('className', self::ALL_CLASSES_VALUE);
            })
            ->orderByDesc('id')
            ->get();
    }

    public function resolve(CultivationAdmin $teacher, array $scope, int $examId): Exam
    {
        if (! $teacher->isTeacher()) {
            throw ResultLifecycleException::forbidden();
        }

        $classId = (int) ($scope['classId'] ?? 0);
        if ($classId <= 0 || $examId <= 0) {
            throw ResultLifecycleException::missing('The selected exam scope was not found.');
        }

        $exam = Exam::query()
            ->whereKey($examId)
            ->where(function ($query) use ($classId) {
                $query->where('className', (string) $classId)
                    ->orWhere('className', self::ALL_CLASSES_VALUE);
            })
            ->first();

        if (! $exam) {
            throw ResultLifecycleException::forbidden('The selected exam is not available for this assigned class.');
        }

        return $exam;
    }
}
