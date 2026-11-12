<?php

namespace Tests\Feature;

use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class StyleTest extends TestCase
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

        Schema::create('styles', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('garments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('style_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_create_update_list_and_delete_styles(): void
    {
        $token = $this->tokenFor('admin');

        $styleResponse = $this->withToken($token)
            ->postJson('/api/styles', ['name' => 'Casual', 'description' => 'Estilo informal.'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Casual')
            ->assertJsonPath('data.description', 'Estilo informal.');
        $styleId = $styleResponse->json('data.id');

        $this->withToken($token)
            ->getJson('/api/styles')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Casual');

        $this->withToken($token)
            ->getJson("/api/styles/{$styleId}")
            ->assertOk()
            ->assertJsonPath('data.id', $styleId);

        $this->withToken($token)
            ->putJson("/api/styles/{$styleId}", ['name' => 'Elegante', 'description' => null])
            ->assertOk()
            ->assertJsonPath('data.name', 'Elegante')
            ->assertJsonPath('data.description', null);

        $this->withToken($token)
            ->deleteJson("/api/styles/{$styleId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('styles', ['id' => $styleId]);
    }

    public function test_standard_users_can_read_but_cannot_modify_styles(): void
    {
        $token = $this->tokenFor('user');
        $style = Style::query()->create(['name' => 'Casual']);

        $this->withToken($token)
            ->getJson('/api/styles')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Casual');

        $this->withToken($token)
            ->postJson('/api/styles', ['name' => 'Formal'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                '/api/styles',
                'No tienes autorización para realizar esta operación.',
            ));

        foreach (['put', 'patch'] as $method) {
            $this->withToken($token)
                ->{$method.'Json'}("/api/styles/{$style->id}", ['name' => 'Deportivo'])
                ->tap(fn ($response) => $this->assertProblemDetails(
                    $response,
                    403,
                    "/api/styles/{$style->id}",
                    'No tienes autorización para realizar esta operación.',
                ));
        }

        $this->withToken($token)
            ->deleteJson("/api/styles/{$style->id}")
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                "/api/styles/{$style->id}",
                'No tienes autorización para realizar esta operación.',
            ));

        $this->assertDatabaseHas('styles', ['id' => $style->id, 'name' => 'Casual']);
    }

    public function test_duplicate_names_and_invalid_style_fields_are_rejected(): void
    {
        $token = $this->tokenFor('admin');
        $style = Style::query()->create(['name' => 'Casual']);
        Style::query()->create(['name' => 'Formal']);

        $this->withToken($token)
            ->postJson('/api/styles', ['name' => 'Casual', 'description' => str_repeat('a', 256)])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                '/api/styles',
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name', 'description']);

        $this->withToken($token)
            ->patchJson("/api/styles/{$style->id}", ['name' => 'Formal'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->patchJson("/api/styles/{$style->id}", [])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                "/api/styles/{$style->id}",
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->patchJson("/api/styles/{$style->id}", ['name' => 'Casual'])
            ->assertOk();
    }

    public function test_style_catalog_requires_authentication(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/styles'),
            401,
            '/api/styles',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/styles', ['name' => 'No autorizado']),
            401,
            '/api/styles',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_unknown_style_returns_problem_details_not_found(): void
    {
        $token = $this->tokenFor('admin');
        $path = '/api/styles/999';

        $this->assertProblemDetails(
            $this->withToken($token)->getJson($path),
            404,
            $path,
            'No se encontró el recurso solicitado.',
        );

        $this->assertProblemDetails(
            $this->withToken($token)->putJson($path, ['name' => 'Inexistente']),
            404,
            $path,
            'No se encontró el recurso solicitado.',
        );

        $this->assertProblemDetails(
            $this->withToken($token)->deleteJson($path),
            404,
            $path,
            'No se encontró el recurso solicitado.',
        );
    }

    public function test_admin_cannot_delete_style_used_by_garments(): void
    {
        $token = $this->tokenFor('admin');
        $style = Style::query()->create(['name' => 'Casual']);

        DB::table('garments')->insert([
            'style_id' => $style->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/styles/{$style->id}")
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                409,
                "/api/styles/{$style->id}",
                'No se puede eliminar un estilo que está asociado a prendas.',
            ));

        $this->assertDatabaseHas('styles', ['id' => $style->id]);
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
