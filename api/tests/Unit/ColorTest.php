<?php

namespace Tests\Feature;

use App\Models\Color;
use App\Models\User;
use App\Services\ColorService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class ColorTest extends TestCase
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

        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->char('hex_code', 7)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('garments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function test_admin_can_create_update_list_and_delete_colors(): void
    {
        $token = $this->tokenFor('admin');

        $this->withToken($token)
            ->postJson('/api/colors', ['name' => 'Verde', 'hex_code' => '#00FF00'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Verde')
            ->assertJsonPath('data.hex_code', '#00FF00');

        $color = Color::query()->where('name', 'Verde')->firstOrFail();

        $this->withToken($token)
            ->getJson('/api/colors')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Verde');

        $this->withToken($token)
            ->getJson("/api/colors/{$color->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $color->id);

        $this->withToken($token)
            ->patchJson("/api/colors/{$color->id}", ['name' => 'Verde oscuro'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Verde oscuro')
            ->assertJsonPath('data.hex_code', '#00FF00');

        $this->withToken($token)
            ->deleteJson("/api/colors/{$color->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('colors', ['id' => $color->id]);
    }

    public function test_standard_users_can_read_colors_but_cannot_modify_them(): void
    {
        $token = $this->tokenFor('user');
        $color = Color::query()->create(['name' => 'Azul', 'hex_code' => '#0000FF']);

        $this->withToken($token)
            ->getJson('/api/colors')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Azul');

        $this->withToken($token)
            ->getJson("/api/colors/{$color->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $color->id);

        $this->withToken($token)
            ->postJson('/api/colors', ['name' => 'Rojo', 'hex_code' => '#FF0000'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                '/api/colors',
                'No tienes autorización para realizar esta operación.',
            ));

        $this->withToken($token)
            ->putJson("/api/colors/{$color->id}", ['name' => 'Rojo'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                "/api/colors/{$color->id}",
                'No tienes autorización para realizar esta operación.',
            ));

        $this->withToken($token)
            ->patchJson("/api/colors/{$color->id}", ['name' => 'Rojo'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                "/api/colors/{$color->id}",
                'No tienes autorización para realizar esta operación.',
            ));

        $this->withToken($token)
            ->deleteJson("/api/colors/{$color->id}")
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                "/api/colors/{$color->id}",
                'No tienes autorización para realizar esta operación.',
            ));

        $this->assertDatabaseHas('colors', ['id' => $color->id, 'name' => 'Azul']);
    }

    public function test_color_validation_rejects_duplicate_names_and_invalid_hex_codes(): void
    {
        $token = $this->tokenFor('admin');
        $color = Color::query()->create(['name' => 'Azul', 'hex_code' => '#0000FF']);

        $this->withToken($token)
            ->postJson('/api/colors', ['name' => 'Rojo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hex_code']);

        $this->withToken($token)
            ->patchJson("/api/colors/{$color->id}", ['name' => 'Azul oscuro', 'hex_code' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['hex_code']);

        $this->withToken($token)
            ->postJson('/api/colors', ['name' => 'Azul', 'hex_code' => 'blue'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                '/api/colors',
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name', 'hex_code']);

        $this->withToken($token)
            ->putJson("/api/colors/{$color->id}", [])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                "/api/colors/{$color->id}",
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name', 'hex_code']);
    }

    public function test_unknown_color_returns_not_found(): void
    {
        $this->withToken($this->tokenFor('admin'))
            ->getJson('/api/colors/999')
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                404,
                '/api/colors/999',
                'No se encontró el recurso solicitado.',
            ));

        $token = $this->tokenFor('admin');

        $this->withToken($token)
            ->putJson('/api/colors/999', ['name' => 'Inexistente'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                404,
                '/api/colors/999',
                'No se encontró el recurso solicitado.',
            ));

        $this->withToken($token)
            ->deleteJson('/api/colors/999')
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                404,
                '/api/colors/999',
                'No se encontró el recurso solicitado.',
            ));
    }

    public function test_color_catalog_requires_authentication_and_uses_problem_details(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/colors'),
            401,
            '/api/colors',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/colors', ['name' => 'No autorizado']),
            401,
            '/api/colors',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_it_rejects_requests_without_a_token(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/colors'),
            401,
            '/api/colors',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_it_returns_json_without_accept_header_for_unauthenticated_requests(): void
    {
        $this->assertProblemDetails(
            $this->get('/api/colors'),
            401,
            '/api/colors',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_it_rejects_invalid_tokens(): void
    {
        $this->assertProblemDetails(
            $this->withToken('invalid-token')->getJson('/api/colors'),
            401,
            '/api/colors',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_it_returns_colors_for_authenticated_users(): void
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

        $color = new Color(['name' => 'Blanco', 'hex_code' => '#FFFFFF']);
        $color->id = 1;

        $colorService = Mockery::mock(ColorService::class);
        $colorService->shouldReceive('listColors')
            ->once()
            ->andReturn(new Collection([$color]));

        $this->app->instance(ColorService::class, $colorService);

        $this->withToken($token)
            ->getJson('/api/colors')
            ->assertOk()
            ->assertExactJson([
                'data' => [
                    [
                        'id' => 1,
                        'name' => 'Blanco',
                        'hex_code' => '#FFFFFF',
                    ],
                ],
            ]);
    }


    private function tokenFor(string $role): string
    {
        static $userNumber = 0;
        $userNumber++;

        User::query()->create([
            'name' => ucfirst($role),
            'email' => "{$role}{$userNumber}@example.test",
            'password' => 'password',
            'role' => $role,
        ]);

        return $this->postJson('/api/login', [
            'email' => "{$role}{$userNumber}@example.test",
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }
}
