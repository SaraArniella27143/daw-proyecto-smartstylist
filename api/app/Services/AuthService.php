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

    /**
     * @param  array{name: string, email: string, password: string}  $details
     * @return array{user: User, token: string}
     */
    public function register(array $details): array
    {
        $user = User::create([
            ...$details,
            'role' => 'user',
        ]);

        return ['user' => $user, 'token' => $this->guard()->login($user)];
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
