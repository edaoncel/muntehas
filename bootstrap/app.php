<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Giriş yapmamış kullanıcıları doğru login rotasına yönlendir
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('yonetim') || $request->is('yonetim/*')) {
                return route('admin.login'); // Admin giriş rotanızın adı neyse onu yazın
            }

            return route('login'); // Normal kullanıcı giriş rotanız varsa
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();