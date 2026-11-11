<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Support\ProblemDetails;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
        $exceptions->render(
            fn (AuthenticationException $exception, Request $request) => ProblemDetails::response(
                401,
                'La autenticación es necesaria para acceder a este recurso.',
            ),
        );
        $exceptions->render(
            fn (ValidationException $exception, Request $request) => ProblemDetails::response(
                422,
                'Los datos enviados no son válidos.',
                ['errors' => $exception->errors()],
            ),
        );
    })->create();
