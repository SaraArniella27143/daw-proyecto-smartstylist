<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class GarmentTest extends TestCase
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

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('colors', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80)->unique();
            $table->char('hex_code', 7)->nullable()->unique();
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
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->foreignId('style_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('season', 20);
            $table->string('image_url', 2048)->nullable();
            $table->timestamps();
        });

        $this->seedCatalogs();
    }

    public function test_user_can_create_list_show_update_and_delete_own_garments(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $category = Category::query()->where('name', 'Camisetas')->firstOrFail();
        $color = Color::query()->where('name', 'Blanco')->firstOrFail();
        $style = Style::query()->where('name', 'Casual')->firstOrFail();

        $created = $this->withToken($token)
            ->postJson('/api/garments', [
                'category_id' => $category->id,
                'color_id' => $color->id,
                'style_id' => $style->id,
                'name' => 'Camiseta de prueba',
                'season' => 'summer',
                'image_url' => 'https://example.test/garments/shirt.jpg',
            ]);

        $created->assertCreated()
            ->assertJsonPath('data.name', 'Camiseta de prueba')
            ->assertJsonPath('data.season', 'summer')
            ->assertJsonPath('data.image_url', 'https://example.test/garments/shirt.jpg')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.color.id', $color->id)
            ->assertJsonPath('data.style.id', $style->id);

        $garmentId = $created->json('data.id');
        $user = User::query()->where('email', 'owner@example.test')->firstOrFail();

        $this->assertDatabaseHas('garments', [
            'id' => $garmentId,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'color_id' => $color->id,
            'style_id' => $style->id,
        ]);

        $this->withToken($token)
            ->getJson('/api/garments')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $garmentId);

        $this->withToken($token)
            ->getJson("/api/garments/{$garmentId}")
            ->assertOk()
            ->assertJsonPath('data.id', $garmentId);

        $this->withToken($token)
            ->patchJson("/api/garments/{$garmentId}", [
                'season' => 'winter',
                'style_id' => null,
                'image_url' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.season', 'winter')
            ->assertJsonPath('data.style', null)
            ->assertJsonPath('data.image_url', null);

        $this->withToken($token)
            ->deleteJson("/api/garments/{$garmentId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('garments', ['id' => $garmentId]);
    }

    public function test_users_cannot_list_or_access_another_users_garments(): void
    {
        $owner = User::query()->create([
            'name' => 'Propietario',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $garment = $this->createGarmentFor($owner);
        $intruderToken = $this->tokenFor('intruder@example.test');

        $this->withToken($intruderToken)
            ->getJson('/api/garments')
            ->assertOk()
            ->assertExactJson(['data' => []]);

        foreach (['GET', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $path = "/api/garments/{$garment->id}";
            $response = match ($method) {
                'GET' => $this->withToken($intruderToken)->getJson($path),
                'PUT' => $this->withToken($intruderToken)->putJson($path, ['name' => 'Hacked']),
                'PATCH' => $this->withToken($intruderToken)->patchJson($path, ['name' => 'Hacked']),
                'DELETE' => $this->withToken($intruderToken)->deleteJson($path),
            };

            $this->assertProblemDetails(
                $response,
                404,
                $path,
                'No se encontró el recurso solicitado.',
            );
        }

        $this->assertDatabaseHas('garments', [
            'id' => $garment->id,
            'user_id' => $owner->id,
            'name' => 'Prenda de prueba',
        ]);
    }

    public function test_garment_endpoints_require_a_valid_jwt(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/garments'),
            401,
            '/api/garments',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/garments', []),
            401,
            '/api/garments',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_non_numeric_garment_ids_return_not_found(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $path = '/api/garments/not-a-number';

        $this->assertProblemDetails(
            $this->withToken($token)->getJson($path),
            404,
            $path,
            'No se encontró el recurso solicitado.',
        );
    }

    public function test_create_validates_required_fields_reference_ids_and_season(): void
    {
        $token = $this->tokenFor('owner@example.test');

        $this->withToken($token)
            ->postJson('/api/garments', [])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('status', 422)
            ->assertJsonValidationErrors(['category_id', 'color_id', 'name', 'season']);

        $this->withToken($token)
            ->postJson('/api/garments', [
                'category_id' => 999,
                'color_id' => 999,
                'style_id' => 999,
                'name' => 'Prenda inválida',
                'season' => 'fall',
                'image_url' => 'no-es-una-url',
            ])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'about:blank')
            ->assertJsonPath('title', 'Unprocessable Content')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('instance', '/api/garments')
            ->assertJsonValidationErrors(['category_id', 'color_id', 'style_id', 'season', 'image_url']);
    }

    public function test_client_cannot_assign_a_garment_to_another_user(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $category = Category::query()->firstOrFail();
        $color = Color::query()->firstOrFail();
        $otherUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->withToken($token)
            ->postJson('/api/garments', [
                'user_id' => $otherUser->id,
                'category_id' => $category->id,
                'color_id' => $color->id,
                'name' => 'Prenda suplantada',
                'season' => 'spring',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->assertDatabaseMissing('garments', ['name' => 'Prenda suplantada']);
    }

    public function test_update_rejects_empty_payload_and_nonexistent_catalog_references(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $garment = $this->createGarmentFor($owner);
        $path = "/api/garments/{$garment->id}";

        $this->withToken($token)
            ->patchJson($path, [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->putJson($path, [
                'category_id' => 999,
                'color_id' => 999,
                'season' => 'spring',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'color_id']);
    }

    private function seedCatalogs(): void
    {
        Category::query()->create(['name' => 'Camisetas']);
        Color::query()->create(['name' => 'Blanco', 'hex_code' => '#FFFFFF']);
        Style::query()->create(['name' => 'Casual']);
    }

    private function tokenFor(string $email): string
    {
        User::query()->create([
            'name' => 'Usuario de prueba',
            'email' => $email,
            'password' => 'password',
            'role' => 'user',
        ]);

        return $this->postJson('/api/login', [
            'email' => $email,
            'password' => 'password',
        ])->assertOk()->json('access_token');
    }

    private function createGarmentFor(User $user): Garment
    {
        return Garment::query()->create([
            'user_id' => $user->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'color_id' => Color::query()->firstOrFail()->id,
            'name' => 'Prenda de prueba',
            'season' => 'spring',
        ]);
    }
}