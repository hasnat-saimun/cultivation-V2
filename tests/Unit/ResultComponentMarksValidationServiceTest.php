<?php

namespace Tests\Unit;

use App\Exceptions\ResultLifecycleException;
use App\Models\Subject;
use App\Services\ResultComponentMarksValidationService;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ResultComponentMarksValidationServiceTest extends TestCase
{
    public static function validMarks(): array
    {
        return [['0'], ['4'], ['4.5'], ['15.50'], ['23.25']];
    }

    public static function invalidMarks(): array
    {
        return [['4.257'], ['15.123'], ['-1'], ['1e2'], ['4.5.1'], ['25abc'], ['২৫'], ['4 5']];
    }

    #[DataProvider('validMarks')]
    public function test_shared_contract_accepts_ascii_marks_with_up_to_two_decimal_places(string $mark): void
    {
        $service = app(ResultComponentMarksValidationService::class);
        $subject = new Subject(['CQ' => 25, 'MCQ' => 25, 'Practical' => 25]);
        $validator = Validator::make(['cqMarks' => [$mark]], $service->componentRules($subject));

        $this->assertFalse($validator->fails());
        $this->assertSame((float) $mark, $service->normalize($mark));
    }

    #[DataProvider('invalidMarks')]
    public function test_shared_contract_rejects_non_ascii_malformed_or_overprecision_marks(string $mark): void
    {
        $service = app(ResultComponentMarksValidationService::class);
        $subject = new Subject(['CQ' => 25, 'MCQ' => 25, 'Practical' => 25]);

        $this->assertTrue(Validator::make(['cqMarks' => [$mark]], $service->componentRules($subject))->fails());
        $this->expectException(ResultLifecycleException::class);
        $service->normalize($mark);
    }

    public function test_shared_contract_uses_configured_component_maximum(): void
    {
        $service = app(ResultComponentMarksValidationService::class);
        $subject = new Subject(['CQ' => 25, 'MCQ' => 15.5, 'Practical' => 10.25]);

        $this->assertFalse(Validator::make([
            'cqMarks' => ['25'], 'mcqMarks' => ['15.5'], 'practical' => ['10.25'],
        ], $service->componentRules($subject))->fails());
        $this->assertTrue(Validator::make([
            'cqMarks' => ['25.01'], 'mcqMarks' => ['15.51'], 'practical' => ['10.26'],
        ], $service->componentRules($subject))->fails());
    }
}
