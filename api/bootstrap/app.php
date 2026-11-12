<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Middleware\UserHasRole;
use App\Support\ProblemDetails;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(null);
        $middleware->alias([
            'role' => UserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => true,
        );
        $exceptions->respond(function (Response $response, Throwable $exception): Response {
            if ($response->getStatusCode() < 400) {
                return $response;
            }

            $status = $response->getStatusCode();
            $detail = match ($status) {
                400 => 'La solicitud no es válida.',
                401 => 'La autenticación es necesaria para acceder a este recurso.',
                403 => 'No tienes autorización para realizar esta operación.',
                404 => 'No se encontró el recurso solicitado.',
                405 => 'El método HTTP no está permitido para este recurso.',
                409 => 'La solicitud entra en conflicto con el estado actual del recurso.',
                422 => 'Los datos enviados no superan la validación.',
                429 => 'Se ha excedido el límite de solicitudes.',
                default => $status >= 500
                    ? 'Se ha producido un error interno del servidor.'
                    : 'La solicitud no se puede procesar.',
            };

            $extensions = $status === 422 && $exception instanceof ValidationException
                ? ['errors' => $exception->errors()]
                : [];

            return ProblemDetails::response($status, $detail, $extensions);
        });
    })->create();
