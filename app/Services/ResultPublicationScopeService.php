<?php

namespace App\Services;

use App\Exceptions\ResultPublicationException;
use App\Models\CultivationAdmin;
use App\Models\Exam;
use App\Models\classManage;
use App\Models\newAdmission;
use App\Models\sectionManage;
use App\Models\sessionManage;
use Illuminate\Support\Collection;

class ResultPublicationScopeService
{
    public function assertActor(?CultivationAdmin $actor): void
    {
        if (!$actor || $actor->isTeacher() || $actor->isCash()) {
            throw ResultPublicationException::forbidden();
        }
    }

    /** @return Collection<int,array{sessionId:int,classId:int,groupId:?int,examId:int}> */
    public function resolve(array $input): Collection
    {
        $examId = $this->positive($input['examId'] ?? null);
        $sessionId = $this->positive($input['sessionId'] ?? null);
        $session = $sessionId ? sessionManage::find($sessionId) : null;
        if (!$examId || !$session || !Exam::whereKey($examId)->exists()) {
            throw ResultPublicationException::missing('The selected exam or session does not exist.');
        }

        $classIds = ($input['classId'] ?? null) === 'all'
            ? classManage::query()->pluck('id')->all()
            : ($input['classIds'] ?? [$input['classId'] ?? null]);
        if (!is_array($classIds)) $classIds = [$classIds];
        $classIds = collect($classIds)->map(fn ($id) => $this->positive($id))->filter()->unique()->sort()->values();
        if ($classIds->isEmpty() || classManage::whereIn('id', $classIds)->count() !== $classIds->count()) {
            throw ResultPublicationException::missing('One or more selected classes do not exist.');
        }

        $requestedGroup = $this->positive($input['groupId'] ?? null);
        if ($requestedGroup && !sectionManage::whereKey($requestedGroup)->exists()) {
            throw ResultPublicationException::missing('The selected section does not exist.');
        }

        $scopes = collect();
        foreach ($classIds as $classId) {
            if (!empty($input['exact_scope']) && $requestedGroup === null) {
                $scopes->push(compact('sessionId', 'classId', 'examId') + ['groupId' => null]);
                continue;
            }
            if ($requestedGroup) {
                $belongs = newAdmission::query()
                    ->where('className', (string) $classId)
                    ->where(function ($query) use ($sessionId, $session) {
                        $query->where('sessName', (string) $sessionId)
                            ->orWhere('sessName', (string) $session->session);
                    })
                    ->where('sectionName', (string) $requestedGroup)
                    ->exists();
                if (!$belongs) throw ResultPublicationException::missing(
                    'The selected section has no students in this class/session scope.'
                );
                $scopes->push(compact('sessionId', 'classId', 'examId') + ['groupId' => $requestedGroup]);
                continue;
            }

            $sectionValues = newAdmission::query()
                ->where('className', (string) $classId)
                ->where(function ($query) use ($sessionId, $session) {
                    $query->where('sessName', (string) $sessionId)
                        ->orWhere('sessName', (string) $session->session);
                })
                ->pluck('sectionName');
            if ($sectionValues->isEmpty()) {
                throw ResultPublicationException::missing('No students were found in a selected class/session scope.');
            }
            $positiveSections = $sectionValues->map(fn ($id) => $this->positive($id))->filter()->unique()->sort()->values();
            foreach ($positiveSections as $sectionId) {
                $scopes->push(compact('sessionId', 'classId', 'examId') + ['groupId' => $sectionId]);
            }
            if ($sectionValues->contains(fn ($id) => $this->positive($id) === null)) {
                $scopes->push(compact('sessionId', 'classId', 'examId') + ['groupId' => null]);
            }
        }

        return $scopes->sortBy(fn ($scope) => $this->key($scope))->values();
    }

    public function key(array $scope): string
    {
        return implode(':', [
            $scope['examId'],
            $scope['sessionId'],
            $scope['classId'],
            $scope['groupId'] === null ? 'class' : 'section:'.$scope['groupId'],
        ]);
    }

    private function positive(mixed $value): ?int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
