<?php

namespace Tests\Feature;

use App\Http\Controllers\TeacherController;
use App\Models\ServerConfig;
use Illuminate\View\View;
use Tests\TestCase;

class TeacherControllerTest extends TestCase
{
    public function test_add_teacher_page_renders_when_no_server_config_exists(): void
    {
        ServerConfig::query()->delete();

        $response = (new TeacherController())->addTeacher();

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('cultivation.add-teacher', $response->name());
        $this->assertNotEmpty($response->render());
    }
}
