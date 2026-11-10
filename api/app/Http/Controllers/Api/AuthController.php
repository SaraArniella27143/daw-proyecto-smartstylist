<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $authService): JsonResponse
    {
        $result = $authService->login($request->validated());

        if ($result === null) {
            return ProblemDetails::response(401, 'El correo electrónico o la contraseña no son válidos.');
        }

        return $this->tokenResponse($result['user'], $result['token']);
    }

    public function logout(AuthService $authService): JsonResponse
    {
        $authService->logout();

        return response()->json(status: 204);
    }

    private function tokenResponse(User $user, string $token, int $status = 200): JsonResponse
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) config('jwt.ttl') * 60,
            'user' => $user->only(['id', 'name', 'email', 'role']),
        ], $status);
    }
}
