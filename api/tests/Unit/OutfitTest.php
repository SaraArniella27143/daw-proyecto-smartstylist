<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Outfit;
use App\Models\Style;
use App\Models\User;
use App\Services\OutfitService;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class OutfitTest extends TestCase
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
            $table->char('hex_code', 7)->nullable()->unique();
            $table->string('name', 80)->unique();
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

        Schema::create('outfits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('outfit_garments', function (Blueprint $table): void {
            $table->foreignId('outfit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garment_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('note', 255)->nullable();
            $table->primary(['outfit_id', 'garment_id']);
        });

        Category::query()->create(['name' => 'Camisetas']);
        Color::query()->create(['name' => 'Blanco', 'hex_code' => '#FFFFFF']);
        Style::query()->create(['name' => 'Casual']);
    }

    public function test_authenticated_user_can_manage_own_outfits_and_replace_garments(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $garment = $this->createGarmentFor($owner, 'Camiseta');

        $created = $this->withToken($token)
            ->postJson('/api/outfits', [
                'name' => 'Casual de primavera',
                'description' => 'Para el día a día',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Casual de primavera')
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(0, 'data.garments');

        $outfitId = $created->json('data.id');
        $this->assertDatabaseHas('outfits', ['id' => $outfitId, 'user_id' => $owner->id]);

        $this->withToken($token)
            ->putJson("/api/outfits/{$outfitId}/garments", [
                'garments' => [[
                    'garment_id' => $garment->id,
                    'is_primary' => true,
                    'note' => 'Pieza central',
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.garments.0.id', $garment->id)
            ->assertJsonPath('data.garments.0.is_primary', true)
            ->assertJsonPath('data.garments.0.note', 'Pieza central');

        $this->assertDatabaseHas('outfit_garments', [
            'outfit_id' => $outfitId,
            'garment_id' => $garment->id,
            'is_primary' => true,
            'note' => 'Pieza central',
        ]);

        $this->withToken($token)
            ->patchJson("/api/outfits/{$outfitId}", ['description' => null])
            ->assertOk()
            ->assertJsonPath('data.description', null)
            ->assertJsonPath('data.is_complete', true);

        $this->withToken($token)
            ->getJson('/api/outfits')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $outfitId);

        $this->withToken($token)
            ->getJson("/api/outfits/{$outfitId}")
            ->assertOk()
            ->assertJsonPath('data.garments.0.name', 'Camiseta');

        $this->withToken($token)
            ->putJson("/api/outfits/{$outfitId}/garments", ['garments' => []])
            ->assertOk()
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonCount(0, 'data.garments');

        $this->withToken($token)
            ->deleteJson("/api/outfits/{$outfitId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('outfits', ['id' => $outfitId]);
        $this->assertDatabaseMissing('outfit_garments', ['outfit_id' => $outfitId]);
    }

    public function test_outfit_endpoints_require_a_valid_jwt(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/outfits'),
            401,
            '/api/outfits',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/outfits', ['name' => 'Sin token']),
            401,
            '/api/outfits',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_user_cannot_access_another_users_outfits_or_garments(): void
    {
        $ownerToken = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $outfit = $owner->outfits()->create(['name' => 'Privado']);
        $garment = $this->createGarmentFor($owner, 'Prenda privada');
        $outfit->garments()->attach($garment->id, ['is_primary' => true]);
        $intruderToken = $this->tokenFor('intruder@example.test');

        $this->withToken($intruderToken)
            ->getJson('/api/outfits')
            ->assertOk()
            ->assertExactJson(['data' => []]);

        foreach (['GET', 'PATCH', 'PUT', 'DELETE'] as $method) {
            $path = "/api/outfits/{$outfit->id}";
            $requestPath = $method === 'PUT' ? "{$path}/garments" : $path;
            $response = match ($method) {
                'GET' => $this->withToken($intruderToken)->getJson($path),
                'PATCH' => $this->withToken($intruderToken)->patchJson($path, ['name' => 'Alterado']),
                'PUT' => $this->withToken($intruderToken)->putJson("{$path}/garments", ['garments' => []]),
                'DELETE' => $this->withToken($intruderToken)->deleteJson($path),
            };

            $this->assertProblemDetails(
                $response,
                404,
                $requestPath,
                'No se encontró el recurso solicitado.',
            );
        }

        $this->assertDatabaseHas('outfits', ['id' => $outfit->id, 'name' => 'Privado']);
    }

    public function test_assigning_foreign_duplicate_or_invalid_garments_is_rejected_without_changes(): void
    {
        $ownerToken = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $existingGarment = $this->createGarmentFor($owner, 'Existente');
        $outfit = $owner->outfits()->create(['name' => 'Mi outfit']);
        $outfit->garments()->attach($existingGarment->id, ['is_primary' => true]);

        $otherUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $foreignGarment = $this->createGarmentFor($otherUser, 'Ajena');
        $path = "/api/outfits/{$outfit->id}/garments";

        $this->withToken($ownerToken)
            ->putJson($path, [
                'garments' => [[
                    'garment_id' => $foreignGarment->id,
                    'is_primary' => true,
                ]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['garments.0.garment_id']);

        $this->withToken($ownerToken)
            ->putJson($path, [
                'garments' => [
                    ['garment_id' => $existingGarment->id, 'is_primary' => true],
                    ['garment_id' => $existingGarment->id, 'is_primary' => false],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['garments.1.garment_id']);

        $this->withToken($ownerToken)
            ->putJson($path, ['garments' => [['garment_id' => 999, 'is_primary' => true]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['garments.0.garment_id']);

        $this->assertDatabaseHas('outfit_garments', [
            'outfit_id' => $outfit->id,
            'garment_id' => $existingGarment->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseMissing('outfit_garments', [
            'outfit_id' => $outfit->id,
            'garment_id' => $foreignGarment->id,
        ]);
    }

    public function test_garment_replacement_rolls_back_when_a_pivot_insert_fails(): void
    {
        $owner = User::query()->create([
            'name' => 'Propietario',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $outfit = $owner->outfits()->create(['name' => 'Mi outfit']);
        $previousGarment = $this->createGarmentFor($owner, 'Prenda anterior');
        $replacementGarment = $this->createGarmentFor($owner, 'Prenda nueva');
        $outfit->garments()->attach($previousGarment->id, ['is_primary' => true]);

        DB::unprepared(
            "CREATE TRIGGER reject_outfit_garment_insert
            BEFORE INSERT ON outfit_garments
            WHEN NEW.garment_id = {$replacementGarment->id}
            BEGIN
                SELECT RAISE(ABORT, 'Forzar fallo de asociación');
            END"
        );

        try {
            app(OutfitService::class)->replaceGarments($owner, $outfit->id, [[
                'garment_id' => $replacementGarment->id,
                'is_primary' => true,
            ]]);
        } catch (QueryException) {
            $this->assertDatabaseHas('outfit_garments', [
                'outfit_id' => $outfit->id,
                'garment_id' => $previousGarment->id,
                'is_primary' => true,
            ]);
            $this->assertDatabaseMissing('outfit_garments', [
                'outfit_id' => $outfit->id,
                'garment_id' => $replacementGarment->id,
            ]);

            return;
        }

        $this->fail('Se esperaba que fallara la inserción de la relación del outfit.');
    }

    public function test_outfit_validation_rejects_empty_updates_and_client_owned_user_id(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $outfit = $owner->outfits()->create(['name' => 'Original']);
        $otherUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);

        $this->withToken($token)
            ->postJson('/api/outfits', ['name' => 'Mal asignado', 'user_id' => $otherUser->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->withToken($token)
            ->patchJson("/api/outfits/{$outfit->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->postJson('/api/outfits', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);
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

    private function createGarmentFor(
        User $user,
        string $name,
        string $categoryName = 'Camisetas',
        string $season = 'spring',
    ): Garment {
        $category = Category::query()->firstOrCreate(['name' => $categoryName]);

        return Garment::query()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'color_id' => Color::query()->firstOrFail()->id,
            'style_id' => Style::query()->firstOrFail()->id,
            'name' => $name,
            'season' => $season,
        ]);
    }
}
