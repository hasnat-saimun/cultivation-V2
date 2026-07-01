<?php

namespace Tests\Feature;

use App\Http\Controllers\DesignationController;
use App\Models\Designation;
use Illuminate\Http\Request;
use Tests\TestCase;

class DesignationControllerTest extends TestCase
{
    public function test_store_accepts_designation_without_throwing_validation_exception(): void
    {
        $name = 'Test Designation ' . uniqid();

        $request = Request::create('/designations/store', 'POST', [
            'name' => $name,
            'type' => 'teacher',
        ]);

        $response = (new DesignationController())->store($request);

        $this->assertNotNull($response);
        $this->assertTrue(Designation::where('name', $name)->where('type', 'teacher')->exists());

        Designation::where('name', $name)->where('type', 'teacher')->delete();
    }
}
