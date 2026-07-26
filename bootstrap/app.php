<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ModeratorAdmin;
use App\Http\Middleware\SuperAdmin;
use App\Http\Middleware\BasicAdmin;
use App\Http\Middleware\DealerAdmin;
use App\Http\Middleware\adminGuard;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
        $middleware->alias([
            'moderator'     => ModeratorAdmin::class,
            'superAdmin'    => SuperAdmin::class,
            'basicAdmin'    => BasicAdmin::class,
            'dealerAdmin'   => DealerAdmin::class,
            'adminGuard'    => adminGuard::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
