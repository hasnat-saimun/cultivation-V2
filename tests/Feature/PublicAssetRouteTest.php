<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicAssetRouteTest extends TestCase
{
    public function test_back_office_css_is_served_with_its_content_type_and_content(): void
    {
        $response = $this->get('/back-office/css/normalize.css');

        $response->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8')
            ->assertHeader('x-content-type-options', 'nosniff');
        $this->assertSame(
            file_get_contents(public_path('back-office/css/normalize.css')),
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }

    public function test_representative_dashboard_assets_are_served(): void
    {
        $this->get('/back-office/style.css')
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');
        $this->get('/back-office/js/main.js')
            ->assertOk()
            ->assertHeader('content-type', 'application/javascript; charset=UTF-8');
        $this->get('/back-office/img/logo.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->get('/back-office/fonts/Flaticon.woff2')
            ->assertOk()
            ->assertHeader('content-type', 'font/woff2');
    }

    public function test_missing_and_forbidden_assets_return_not_found(): void
    {
        foreach ([
            '/back-office/css/does-not-exist.css',
            '/back-office/%2e%2e/.env',
            '/back-office/%252e%252e%252f.env',
            '/back-office/C:%5CWindows%5Cwin.ini',
            '/back-office/http:%2F%2Fexample.test%2Fasset.css',
            '/public/index.php',
            '/public/.env',
            '/public/storage/logs/laravel.log',
        ] as $uri) {
            $this->get($uri)->assertNotFound();
        }
    }
}
