<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('result-draft', fn (Request $request) =>
            Limit::perMinute(30)->by($this->resultActorKey($request, 'result-draft'))
        );

        RateLimiter::for('result-transition', fn (Request $request) =>
            Limit::perMinute(12)->by($this->resultActorKey($request, 'result-transition'))
        );

        RateLimiter::for('result-publication', fn (Request $request) =>
            Limit::perMinute(6)->by($this->resultActorKey($request, 'result-publication'))
        );
    }

    private function resultActorKey(Request $request, string $prefix): string
    {
        $sessionActor = $request->session()->get('cultivationAdmin');
        if (is_numeric($sessionActor) && (int) $sessionActor > 0) {
            return $prefix.':session-admin:'.$sessionActor;
        }

        $fallbackUser = $request->user();
        if ($fallbackUser) {
            return $prefix.':auth-user:'.$fallbackUser->getAuthIdentifier();
        }

        return $prefix.':ip:'.$request->ip();
    }
}
