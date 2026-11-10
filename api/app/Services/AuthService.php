<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use LogicException;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthService
{
    /**
     * @param  array{email: string, password: string}  $credentials
     * @return array{user: User, token: string}|null
     */
    public function login(array $credentials): ?array
    {
        $guard = $this->guard();
        $token = $guard->attempt($credentials);

        if (! is_string($token)) {
            return null;
        }

        $user = $guard->user();

        if (! $user instanceof User) {
            throw new LogicException('El guard JWT autenticó sin devolver un usuario.');
        }

        return ['user' => $user, 'token' => $token];
    }

    public function logout(): void
    {
        $this->guard()->logout();
    }

    private function guard(): JWTGuard
    {
        return Auth::guard('api');
    }
}
