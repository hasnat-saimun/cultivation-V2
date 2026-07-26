<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        RateLimiter::for('result-draft', fn (Request $request) =>
            Limit::perMinute(30)->by($this->resultActorKey($request)));
        RateLimiter::for('result-transition', fn (Request $request) =>
            Limit::perMinute(12)->by($this->resultActorKey($request)));
        RateLimiter::for('result-publication', fn (Request $request) =>
            Limit::perMinute(6)->by($this->resultActorKey($request)));
        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
            ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));
        });
    }

    private function resultActorKey(Request $request): string
    {
        return 'admin:'.((string) $request->session()->get('cultivationAdmin', 'guest')).'|'.$request->ip();
    }
}
