<?php

namespace App\Http\Middleware;

use App\Support\ProblemDetails;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role, $roles, true)) {
            return ProblemDetails::response(403, 'No tienes autorización para realizar esta operación.');
        }

        return $next($request);
    }
}