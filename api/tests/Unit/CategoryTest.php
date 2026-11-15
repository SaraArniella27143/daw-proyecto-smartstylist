<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class CategoryTest extends TestCase
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

        DB::statement("CREATE TABLE categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(80) NOT NULL UNIQUE,
            description VARCHAR(255) NULL,
            outfit_group VARCHAR(20) NOT NULL DEFAULT 'optional'
                CHECK (outfit_group IN ('upper_body', 'lower_body', 'full_body', 'optional')),
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        )");

        Schema::create('garments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('category_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_admin_can_create_update_list_and_delete_categories(): void
    {
        $token = $this->tokenFor('admin');

        $categoryResponse = $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Calzado',
                'description' => 'Prendas para los pies.',
                'outfit_group' => 'optional',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Calzado')
            ->assertJsonPath('data.description', 'Prendas para los pies.')
            ->assertJsonPath('data.outfit_group', 'optional');
        $categoryId = $categoryResponse->json('data.id');

        $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Calzado');

        $this->withToken($token)
            ->getJson("/api/categories/{$categoryId}")
            ->assertOk()
            ->assertJsonPath('data.id', $categoryId);

        $this->withToken($token)
            ->patchJson("/api/categories/{$categoryId}", ['description' => 'Calzado de diario.'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Calzado')
            ->assertJsonPath('data.description', 'Calzado de diario.');

        $this->withToken($token)
            ->deleteJson("/api/categories/{$categoryId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('categories', ['id' => $categoryId]);
    }

    public function test_standard_users_can_read_but_cannot_modify_categories(): void
    {
        $token = $this->tokenFor('user');
        $category = Category::query()->create(['name' => 'Calzado']);

        $this->withToken($token)
            ->getJson('/api/categories')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Calzado');

        $this->withToken($token)
            ->postJson('/api/categories', ['name' => 'Abrigos'])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                '/api/categories',
                'No tienes autorización para realizar esta operación.',
            ));

        foreach (['patch', 'put'] as $method) {
            $this->withToken($token)
                ->{$method.'Json'}("/api/categories/{$category->id}", ['name' => 'Accesorios'])
                ->tap(fn ($response) => $this->assertProblemDetails(
                    $response,
                    403,
                    "/api/categories/{$category->id}",
                    'No tienes autorización para realizar esta operación.',
                ));
        }

        $this->withToken($token)
            ->deleteJson("/api/categories/{$category->id}")
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                403,
                "/api/categories/{$category->id}",
                'No tienes autorización para realizar esta operación.',
            ));

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Calzado']);
    }

    public function test_duplicate_names_and_invalid_category_fields_are_rejected(): void
    {
        $token = $this->tokenFor('admin');
        $category = Category::query()->create(['name' => 'Calzado']);

        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Calzado',
                'outfit_group' => 'optional',
            ])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                '/api/categories',
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name']);

        $this->withToken($token)
            ->postJson('/api/categories', [
                'name' => 'Categoria invalida',
                'outfit_group' => 'not_a_group',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outfit_group']);

        $this->withToken($token)
            ->putJson("/api/categories/{$category->id}", ['name' => ''])
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                422,
                "/api/categories/{$category->id}",
                'Los datos enviados no superan la validación.',
            ))
            ->assertJsonValidationErrors(['name']);
    }

    public function test_database_rejects_unknown_outfit_groups(): void
    {
        $this->expectException(QueryException::class);

        Category::query()->create([
            'name' => 'Categoria invalida',
            'outfit_group' => 'not_a_group',
        ]);
    }

    public function test_category_catalog_requires_authentication(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/categories'),
            401,
            '/api/categories',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/categories', ['name' => 'No autorizado']),
            401,
            '/api/categories',
            'La autenticación es necesaria para acceder a este recurso.',
        );
    }

    public function test_unknown_category_returns_problem_details_not_found(): void
    {
        $token = $this->tokenFor('admin');
        $path = '/api/categories/999';

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

    public function test_admin_cannot_delete_category_used_by_garments(): void
    {
        $token = $this->tokenFor('admin');
        $category = Category::query()->create(['name' => 'Calzado']);

        DB::table('garments')->insert([
            'category_id' => $category->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withToken($token)
            ->deleteJson("/api/categories/{$category->id}")
            ->tap(fn ($response) => $this->assertProblemDetails(
                $response,
                409,
                "/api/categories/{$category->id}",
                'No se puede eliminar una categoría que está asociada a prendas.',
            ));

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
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
