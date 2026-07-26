<?php

namespace Tests\Feature;

use Tests\TestCase;

class PreloaderLifecycleTest extends TestCase
{
    public function test_shared_layouts_render_exactly_one_preloader_element(): void
    {
        $files = [
            resource_path('views/cultivation/include.blade.php'),
            resource_path('views/account/include.blade.php'),
            resource_path('views/academic/include.blade.php'),
            resource_path('views/result/include.blade.php'),
            resource_path('views/result/singleinclude.blade.php'),
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertIsString($contents, $file);
            $this->assertSame(1, substr_count($contents, 'id="preloader"'), $file);
            $this->assertSame(1, substr_count($contents, 'js/main.js'), $file);
        }
    }

    public function test_main_js_registers_single_guarded_preloader_lifecycle(): void
    {
        $contents = file_get_contents(public_path('back-office/js/main.js'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('__pagePreloaderInitialized', $contents);
        $this->assertStringContainsString('hidePagePreloader', $contents);
        $this->assertStringContainsString("document.addEventListener('DOMContentLoaded', hidePagePreloader", $contents);
        $this->assertStringContainsString("window.addEventListener('load', hidePagePreloader", $contents);
        $this->assertStringContainsString("window.addEventListener('pageshow'", $contents);
        $this->assertStringContainsString('PRELOADER_FALLBACK_DELAY', $contents);
    }

    public function test_preloader_hidden_state_is_non_blocking(): void
    {
        $contents = file_get_contents(public_path('back-office/style.css'));

        $this->assertIsString($contents);
        $this->assertStringContainsString('#preloader.preloader-hidden', $contents);
        $this->assertStringContainsString('pointer-events: none', $contents);
        $this->assertStringContainsString('visibility: hidden', $contents);
        $this->assertStringContainsString("#preloader[aria-hidden='true']", $contents);
    }
}