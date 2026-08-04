<?php

namespace Tests\Unit;

use App\Services\ResultCalculation\PublishedResultFinalizer;
use App\Services\ResultCalculation\StudentResult;
use App\Services\ResultCalculation\SubjectResult;
use App\Services\ResultPublicationVisibilityService;
use PHPUnit\Framework\TestCase;

class PublishedResultFinalizerTest extends TestCase
{
    public function test_final_publication_moves_missing_compulsory_subject_into_failed_list(): void
    {
        $service = new PublishedResultFinalizer($this->createMock(ResultPublicationVisibilityService::class));
        $missing = new SubjectResult('11', 'Compulsory', null, 100, null, '-', 0, 'Incomplete', false, true, [], true, ['11']);
        $passed = new SubjectResult('12', 'Compulsory', 80, 100, 80, 'A+', 5, 'Pass', false, true, [], false, ['12']);
        $result = new StudentResult([$missing, $passed], 5, 2, null, 0, [], ['11'], null, 'Incomplete');

        $final = $service->finalize($result);

        $this->assertSame('Fail', $final->status);
        $this->assertSame(0.0, $final->gpa);
        $this->assertSame(['11'], $final->failedCompulsorySubjects);
        $this->assertSame([], $final->missingCompulsorySubjects);
        $this->assertSame('Fail', $final->subjectResults[0]->status);
        $this->assertSame('F', $final->subjectResults[0]->letterGrade);
        $this->assertFalse($final->subjectResults[0]->missing);
        $this->assertNull($final->subjectResults[0]->obtainedMarks);
        $this->assertSame('Pass', $final->subjectResults[1]->status);
    }

    public function test_non_incomplete_result_is_not_changed(): void
    {
        $service = new PublishedResultFinalizer($this->createMock(ResultPublicationVisibilityService::class));
        $result = new StudentResult([], 5, 1, null, 0, [], [], 5, 'Pass');

        $this->assertSame($result, $service->finalize($result));
    }
}
