<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Outfit;
use App\Models\Style;
use App\Models\User;
use Database\Seeders\OutfitSeeder;
use Database\Seeders\ScheduleSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use AssertsProblemDetails;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'jwt.secret' => str_repeat('a', 32),
            'app.timezone' => 'Europe/Madrid',
        ]);

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

        Schema::create('schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outfit_id')->constrained()->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->timestampsTz();
        });

        Schema::create('outfit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outfit_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('used_at');
            $table->timestampsTz();
        });

        Category::query()->create(['name' => 'Camisetas']);
        Color::query()->create(['name' => 'Blanco', 'hex_code' => '#FFFFFF']);
        Style::query()->create(['name' => 'Casual']);
    }

    public function test_schedule_seeder_preserves_same_dates_for_different_users_and_is_idempotent(): void
    {
        foreach (['user1@cifpaviles.es', 'user2@cifpaviles.es'] as $email) {
            User::query()->create([
                'name' => 'Usuario de prueba',
                'email' => $email,
                'password' => 'password',
                'role' => 'user',
            ]);
        }

        (new OutfitSeeder)->run();
        $seeder = new ScheduleSeeder;

        $seeder->run();

        $this->assertDatabaseCount('schedules', 10);
        $this->assertSame(
            5,
            User::query()->where('email', 'user1@cifpaviles.es')->firstOrFail()->schedules()->count(),
        );
        $this->assertSame(
            5,
            User::query()->where('email', 'user2@cifpaviles.es')->firstOrFail()->schedules()->count(),
        );

        $seeder->run();

        $this->assertDatabaseCount('schedules', 10);
    }

    public function test_user_can_create_list_show_update_and_delete_schedules(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $outfit = $this->createCompleteOutfitFor($owner);

        $created = $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $outfit->id,
                'scheduled_date' => '2026-03-29',
            ])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_date', '2026-03-29')
            ->assertJsonPath('data.outfit.id', $outfit->id)
            ->assertJsonPath('data.outfit.is_complete', true);

        $scheduleId = $created->json('data.id');
        $this->assertTrue(
            DB::table('schedules')
                ->where('id', $scheduleId)
                ->where('outfit_id', $outfit->id)
                ->whereDate('scheduled_date', '2026-03-29')
                ->exists()
        );

        $this->withToken($token)
            ->getJson('/api/schedules?from=2026-03-01&to=2026-03-31')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $scheduleId);

        $this->withToken($token)
            ->getJson('/api/schedules?to=2026-03-29')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)
            ->getJson("/api/schedules/{$scheduleId}")
            ->assertOk()
            ->assertJsonPath('data.id', $scheduleId);

        $this->withToken($token)
            ->patchJson("/api/schedules/{$scheduleId}", ['scheduled_date' => '2026-10-25'])
            ->assertOk()
            ->assertJsonPath('data.scheduled_date', '2026-10-25');

        $this->withToken($token)
            ->putJson("/api/schedules/{$scheduleId}", ['outfit_id' => $outfit->id])
            ->assertOk()
            ->assertJsonPath('data.scheduled_date', '2026-10-25');

        $this->withToken($token)
            ->deleteJson("/api/schedules/{$scheduleId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('schedules', ['id' => $scheduleId]);
    }

    public function test_schedule_rejects_incomplete_or_foreign_outfits(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $incomplete = $owner->outfits()->create(['name' => 'Borrador']);

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $incomplete->id,
                'scheduled_date' => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outfit_id']);

        $otherUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $foreignOutfit = $this->createCompleteOutfitFor($otherUser);

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $foreignOutfit->id,
                'scheduled_date' => '2026-01-01',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outfit_id']);

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_schedule_rejects_duplicate_user_date_with_rfc9457_conflict(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $firstOutfit = $this->createCompleteOutfitFor($owner, 'Primero');
        $secondOutfit = $this->createCompleteOutfitFor($owner, 'Segundo');

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $firstOutfit->id,
                'scheduled_date' => '2026-07-14',
            ])
            ->assertCreated();

        $this->assertProblemDetails(
            $this->withToken($token)
                ->postJson('/api/schedules', [
                    'outfit_id' => $secondOutfit->id,
                    'scheduled_date' => '2026-07-14',
                ]),
            409,
            '/api/schedules',
            'Ya existe una planificación para esa fecha.',
        );

        $this->assertDatabaseCount('schedules', 1);
    }

    public function test_schedule_update_rejects_date_conflicts_and_incomplete_replacements(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $firstOutfit = $this->createCompleteOutfitFor($owner, 'Primero');
        $secondOutfit = $this->createCompleteOutfitFor($owner, 'Segundo');
        $incompleteOutfit = $owner->outfits()->create(['name' => 'Borrador']);
        $firstSchedule = $firstOutfit->schedules()->create([
            'scheduled_date' => '2026-07-14',
        ]);
        $secondSchedule = $secondOutfit->schedules()->create([
            'scheduled_date' => '2026-07-15',
        ]);

        $this->assertProblemDetails(
            $this->withToken($token)
                ->patchJson("/api/schedules/{$secondSchedule->id}", ['scheduled_date' => '2026-07-14']),
            409,
            "/api/schedules/{$secondSchedule->id}",
            'Ya existe una planificación para esa fecha.',
        );

        $this->withToken($token)
            ->patchJson("/api/schedules/{$secondSchedule->id}", ['outfit_id' => $incompleteOutfit->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outfit_id']);

        $this->assertDatabaseHas('schedules', [
            'id' => $secondSchedule->id,
            'outfit_id' => $secondOutfit->id,
        ]);
        $this->assertDatabaseHas('schedules', [
            'id' => $firstSchedule->id,
            'outfit_id' => $firstOutfit->id,
        ]);
    }

    public function test_user_cannot_access_another_users_schedules(): void
    {
        $owner = User::query()->create([
            'name' => 'Propietario',
            'email' => 'owner@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $outfit = $this->createCompleteOutfitFor($owner);
        $schedule = $outfit->schedules()->create([
            'scheduled_date' => '2026-05-01',
        ]);
        $intruderToken = $this->tokenFor('intruder@example.test');

        $this->withToken($intruderToken)
            ->getJson('/api/schedules')
            ->assertOk()
            ->assertExactJson(['data' => []]);

        foreach (['GET', 'PATCH', 'DELETE'] as $method) {
            $path = "/api/schedules/{$schedule->id}";
            $response = match ($method) {
                'GET' => $this->withToken($intruderToken)->getJson($path),
                'PATCH' => $this->withToken($intruderToken)->patchJson($path, ['scheduled_date' => '2026-05-02']),
                'DELETE' => $this->withToken($intruderToken)->deleteJson($path),
            };

            $this->assertProblemDetails(
                $response,
                404,
                $path,
                'No se encontró el recurso solicitado.',
            );
        }

        $this->assertTrue(
            DB::table('schedules')
                ->where('id', $schedule->id)
                ->where('outfit_id', $outfit->id)
                ->whereDate('scheduled_date', '2026-05-01')
                ->exists()
        );
    }

    public function test_schedule_validation_and_calendar_filters(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $outfit = $this->createCompleteOutfitFor($owner);

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $outfit->id,
                'scheduled_date' => '2026-02-30',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_date']);

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $outfit->id,
                'scheduled_date' => '2026-12-31',
                'user_id' => $owner->id + 1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->withToken($token)
            ->patchJson('/api/schedules/1', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['scheduled_date']);

        $this->withToken($token)
            ->getJson('/api/schedules?from=2026-04-02&to=2026-04-01')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);

        $this->withToken($token)
            ->postJson('/api/schedules', [
                'outfit_id' => $outfit->id,
                'scheduled_date' => '2026-12-31',
            ])
            ->assertCreated()
            ->assertJsonPath('data.scheduled_date', '2026-12-31');
    }

    public function test_schedule_endpoints_require_a_valid_jwt(): void
    {
        $this->assertProblemDetails(
            $this->getJson('/api/schedules'),
            401,
            '/api/schedules',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->postJson('/api/schedules', []),
            401,
            '/api/schedules',
            'La autenticación es necesaria para acceder a este recurso.',
        );
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

    private function createCompleteOutfitFor(User $user, string $name = 'Outfit completo'): Outfit
    {
        $garment = Garment::query()->create([
            'user_id' => $user->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'color_id' => Color::query()->firstOrFail()->id,
            'style_id' => Style::query()->firstOrFail()->id,
            'name' => "Prenda de {$name}",
            'season' => 'spring',
        ]);
        $outfit = $user->outfits()->create(['name' => $name]);
        $outfit->garments()->attach($garment->id, ['is_primary' => true]);

        return $outfit;
    }
}
