<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->string('outfit_group', 20)->default('optional');
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
            $table->string('image_path')->nullable();
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
            ]);

        $created->assertCreated()
            ->assertJsonPath('data.name', 'Camiseta de prueba')
            ->assertJsonPath('data.season', 'summer')
            ->assertJsonPath('data.image_url', null)
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

    public function test_user_can_upload_update_serve_and_remove_a_private_garment_image(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);
        $token = $this->tokenFor('owner@example.test');
        $category = Category::query()->firstOrFail();
        $color = Color::query()->firstOrFail();

        $created = $this->withToken($token)
            ->post('/api/garments', [
                'category_id' => $category->id,
                'color_id' => $color->id,
                'name' => 'Prenda con foto',
                'season' => 'summer',
                'image' => $this->fakePng('garment.png'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Prenda con foto');

        $garmentId = $created->json('data.id');
        $firstImagePath = DB::table('garments')->where('id', $garmentId)->value('image_path');
        Storage::disk($disk)->assertExists($firstImagePath);
        $created->assertJsonPath('data.image_url', route('garments.image', $garmentId));
        $created->assertJsonMissingPath('data.image_path');

        $this->withToken($token)
            ->get("/api/garments/{$garmentId}/image")
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->withToken($token)
            ->post("/api/garments/{$garmentId}", [
                'image' => UploadedFile::fake()->create('not-an-image.txt', 10, 'text/plain'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
        $this->assertSame($firstImagePath, DB::table('garments')->where('id', $garmentId)->value('image_path'));
        Storage::disk($disk)->assertExists($firstImagePath);

        $this->withToken($token)
            ->post("/api/garments/{$garmentId}", [
                'image' => $this->fakePng('updated.png'),
            ])
            ->assertOk()
            ->assertJsonPath('data.image_url', route('garments.image', $garmentId));

        $updatedImagePath = DB::table('garments')->where('id', $garmentId)->value('image_path');
        $this->assertNotSame($firstImagePath, $updatedImagePath);
        Storage::disk($disk)->assertMissing($firstImagePath);
        Storage::disk($disk)->assertExists($updatedImagePath);

        $this->withToken($token)
            ->patchJson("/api/garments/{$garmentId}", ['image' => null])
            ->assertOk()
            ->assertJsonPath('data.image_url', null);

        Storage::disk($disk)->assertMissing($updatedImagePath);
        $this->assertNull(DB::table('garments')->where('id', $garmentId)->value('image_path'));

        $this->withToken($token)
            ->post("/api/garments/{$garmentId}", ['image' => $this->fakePng('deleted.png')])
            ->assertOk();
        $imagePathBeforeDelete = DB::table('garments')->where('id', $garmentId)->value('image_path');

        $this->withToken($token)->deleteJson("/api/garments/{$garmentId}")->assertNoContent();
        Storage::disk($disk)->assertMissing($imagePathBeforeDelete);
    }

    public function test_user_cannot_read_another_users_garment_image(): void
    {
        $disk = config('filesystems.default');
        Storage::fake($disk);
        $ownerToken = $this->tokenFor('owner@example.test');
        $created = $this->withToken($ownerToken)
            ->post('/api/garments', [
                'category_id' => Category::query()->firstOrFail()->id,
                'color_id' => Color::query()->firstOrFail()->id,
                'name' => 'Prenda privada',
                'season' => 'summer',
                'image' => $this->fakePng('private.png'),
            ])
            ->assertCreated();
        $intruderToken = $this->tokenFor('intruder@example.test');

        $this->withToken($intruderToken)
            ->getJson("/api/garments/{$created->json('data.id')}/image")
            ->assertNotFound();
    }

    public function test_image_url_cannot_be_submitted_directly(): void
    {
        $token = $this->tokenFor('owner@example.test');

        $this->withToken($token)
            ->postJson('/api/garments', [
                'category_id' => Category::query()->firstOrFail()->id,
                'color_id' => Color::query()->firstOrFail()->id,
                'name' => 'Prenda sin archivo',
                'season' => 'summer',
                'image_url' => 'https://example.test/image.jpg',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_url']);
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

        $this->assertProblemDetails(
            $this->getJson('/api/garments/1/image'),
            401,
            '/api/garments/1/image',
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
            ])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'about:blank')
            ->assertJsonPath('title', 'Unprocessable Content')
            ->assertJsonPath('status', 422)
            ->assertJsonPath('instance', '/api/garments')
            ->assertJsonValidationErrors(['category_id', 'color_id', 'style_id', 'season']);
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
        Category::query()->create([
            'name' => 'Camisetas',
            'outfit_group' => 'upper_body',
        ]);
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

    private function fakePng(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
        );
    }
}
