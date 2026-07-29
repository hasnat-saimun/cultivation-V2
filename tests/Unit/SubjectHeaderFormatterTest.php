<?php

namespace Tests\Unit;

use App\Services\ResultCalculation\SubjectHeaderFormatter;
use Tests\TestCase;

class SubjectHeaderFormatterTest extends TestCase
{
    public function test_it_normalizes_names_and_builds_abbreviations(): void
    {
        $this->assertSame('Bangladesh and Global Studies', SubjectHeaderFormatter::normalizeName('Bangladesh and Global Studies-150'));
        $this->assertSame('B.G.S.', SubjectHeaderFormatter::shortLabel('Bangladesh and Global Studies-150'));
        $this->assertSame('G.S.', SubjectHeaderFormatter::shortLabel('General Science-127'));
        $this->assertSame('H.M.', SubjectHeaderFormatter::shortLabel('Higher Mathematics-126'));
        $this->assertSame('English', SubjectHeaderFormatter::shortLabel('English-101'));
        $this->assertSame('ICT', SubjectHeaderFormatter::shortLabel('ICT-120'));
    }
}
