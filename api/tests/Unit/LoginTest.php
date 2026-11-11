<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use AssertsProblemDetails;

    protected function setUp(): void
    {
        parent::setUp();

        config(['jwt.secret' => str_repeat('a', 32)]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role', 20);
            $table->string('remember_token', 100)->nullable();
            $table->timestamps();
        });
    }

    public function test_valid_credentials_return_a_jwt_and_public_user_data(): void
    {
        User::create([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'password' => 'clave-de-prueba',
            'role' => 'user',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'usuario@example.test',
            'password' => 'clave-de-prueba',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 3600)
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token');

        $this->assertCount(3, explode('.', $response->json('access_token')));
    }

    public function test_logout_invalidates_the_current_jwt(): void
    {
        User::create([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'password' => 'clave-de-prueba',
            'role' => 'user',
        ]);
        $token = $this->postJson('/api/login', [
            'email' => 'usuario@example.test',
            'password' => 'clave-de-prueba',
        ])->assertOk()->json('access_token');

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertNoContent();

        $this->assertProblemDetails(
            $this->withToken($token)->postJson('/api/logout'),
            401,
            '/api/logout',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_demo_user_seeder_uses_a_known_password_in_all_environments_and_is_idempotent(): void
    {
        config(['app.env' => 'production']);
        app(UserSeeder::class)->run();

        $admin = User::query()->where('email', 'admin@cifpaviles.es')->firstOrFail();
        $this->assertTrue(Hash::check('Admin1234!', $admin->password));
        $this->assertFalse(Hash::check('', $admin->password));
        $this->assertDatabaseCount('users', 3);
        $this->assertSame(1, User::query()->where('email', 'admin@cifpaviles.es')->count());

        config(['app.env' => 'testing']);
        app(UserSeeder::class)->run();

        $this->assertDatabaseCount('users', 3);
        $this->assertSame(1, User::query()->where('email', 'admin@cifpaviles.es')->count());
    }

    public function test_invalid_credentials_return_a_generic_unauthorized_response(): void
    {
        User::create([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'password' => 'clave-correcta',
            'role' => 'user',
        ]);

        $this->postJson('/api/login', [
            'email' => 'usuario@example.test',
            'password' => 'clave-incorrecta',
        ])
            ->assertUnauthorized()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertExactJson([
                'type' => 'about:blank',
                'title' => 'Unauthorized',
                'status' => 401,
                'detail' => 'El correo electrónico o la contraseña no son válidos.',
                'instance' => '/api/login',
            ]);
    }

    public function test_missing_credentials_return_validation_errors(): void
    {
        $this->postJson('/api/login', [])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'about:blank')
            ->assertJsonPath('status', 422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_registration_creates_a_standard_user_and_returns_a_jwt(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Nueva usuaria',
            'email' => 'nueva@example.test',
            'password' => 'una-clave-segura-123',
            'password_confirmation' => 'una-clave-segura-123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => ['id', 'name', 'email', 'role'],
            ])
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 3600)
            ->assertJsonPath('user.name', 'Nueva usuaria')
            ->assertJsonPath('user.email', 'nueva@example.test')
            ->assertJsonPath('user.role', 'user')
            ->assertJsonMissingPath('user.password')
            ->assertJsonMissingPath('user.remember_token')
            ->assertDontSee('una-clave-segura-123');

        $user = User::query()->where('email', 'nueva@example.test')->firstOrFail();

        $this->assertSame('user', $user->role);
        $this->assertTrue(Hash::check('una-clave-segura-123', $user->password));
        $this->assertCount(3, explode('.', $response->json('access_token')));
    }

    public function test_registration_rejects_duplicate_email_and_short_password(): void
    {
        User::create([
            'name' => 'Usuario existente',
            'email' => 'existente@example.test',
            'password' => 'clave-existente',
            'role' => 'user',
        ]);

        $this->postJson('/api/register', [
            'name' => 'Otra usuaria',
            'email' => 'existente@example.test',
            'password' => 'corta',
        ])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_registration_does_not_allow_the_client_to_choose_a_role(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Admin no autorizado',
            'email' => 'intento-admin@example.test',
            'password' => 'una-clave-segura-123',
            'role' => 'admin',
        ])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonValidationErrors(['role']);

        $this->assertDatabaseMissing('users', ['email' => 'intento-admin@example.test']);
    }

    public function test_registration_requires_matching_password_confirmation_when_provided(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Nueva usuaria',
            'email' => 'nueva@example.test',
            'password' => 'una-clave-segura-123',
            'password_confirmation' => 'otra-clave-segura-456',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_allows_omitting_password_confirmation(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Nueva usuaria',
            'email' => 'sin-confirmacion@example.test',
            'password' => 'una-clave-segura-123',
        ])
            ->assertCreated()
            ->assertJsonPath('user.role', 'user');
    }
}
