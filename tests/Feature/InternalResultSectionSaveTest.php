<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\sectionManage;
use App\Models\InternalResult;
use App\Http\Middleware\VerifyCsrfToken;

class InternalResultSectionSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_saves_assign_section_on_create(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $section = new sectionManage();
        $section->section = 'A';
        $section->save();

        $payload = [
            'title' => 'Test Internal Result',
            'assignClass' => null,
            'assignDepartment' => null,
            'assignSection' => $section->id,
            'assignSession' => null,
        ];

        $request = \Illuminate\Http\Request::create('/academic/internalResult/save', 'POST', $payload);
        $controller = app(\App\Http\Controllers\AcademicController::class);
        $controller->saveInternalResult($request);

        $item = InternalResult::first();
        $this->assertNotNull($item, 'InternalResult should be created');
        $this->assertSame($section->id, (int)($item->assignSection ?? 0), 'assignSection should persist');
    }

    public function test_it_saves_assign_section_on_update(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);

        $section1 = new sectionManage();
        $section1->section = 'A';
        $section1->save();
        $section2 = new sectionManage();
        $section2->section = 'B';
        $section2->save();

        $item = new InternalResult();
        $item->title = 'Initial';
        $item->assignSection = $section1->id;
        $item->save();

        $payload = [
            'itemId' => $item->id,
            'title' => 'Updated',
            'assignSection' => $section2->id,
        ];

        $request = \Illuminate\Http\Request::create('/academic/internalResult/save', 'POST', $payload);
        $controller = app(\App\Http\Controllers\AcademicController::class);
        $controller->saveInternalResult($request);

        $item->refresh();
        $this->assertSame($section2->id, (int)($item->assignSection ?? 0), 'assignSection should update');
    }
}
