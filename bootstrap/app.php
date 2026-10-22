<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\ChickAdminOrSuperAdmin;
use App\Http\Middleware\ChickWaiter;
use App\Http\Middleware\ChickChief;
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'chick.admin-or-superadmin'=> ChickAdminOrSuperAdmin::class,
            'chick.waiter'=> ChickWaiter::class,
            'chick.chief'=> ChickChief::class,
            ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
