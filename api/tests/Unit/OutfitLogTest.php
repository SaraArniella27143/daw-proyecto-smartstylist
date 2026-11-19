<?php

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Color;
use App\Models\Garment;
use App\Models\Outfit;
use App\Models\OutfitLog;
use App\Models\Style;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Support\AssertsProblemDetails;
use Tests\TestCase;

class OutfitLogTest extends TestCase
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

        Schema::create('outfit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outfit_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('used_at', 0);
            $table->timestampsTz();
        });

        Category::query()->create(['name' => 'Camisetas']);
        Color::query()->create(['name' => 'Blanco', 'hex_code' => '#FFFFFF']);
        Style::query()->create(['name' => 'Casual']);
    }

    public function test_user_can_record_a_use_with_second_precision_and_get_utc_response(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $outfit = $this->createOutfitFor($owner, 'Conjunto usado');

        $response = $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $outfit->id,
                'used_at' => '2026-03-29T03:15:00+02:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.outfit.id', $outfit->id)
            ->assertJsonPath('data.used_at', '2026-03-29T01:15:00Z');

        $log = OutfitLog::query()->findOrFail($response->json('data.id'));
        $this->assertSame('2026-03-29T01:15:00Z', $log->used_at->utc()->format('Y-m-d\\TH:i:s\\Z'));
        $this->assertSame($owner->id, $log->outfit->user_id);
        $this->assertSame($outfit->id, $log->outfit_id);
    }

    public function test_usage_rejects_foreign_outfits_missing_timezone_and_client_user_id(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $otherUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $foreignOutfit = $this->createOutfitFor($otherUser, 'Outfit ajeno');

        $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $foreignOutfit->id,
                'used_at' => '2026-06-02T18:30:00+00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['outfit_id']);

        $ownedOutfit = $this->createOutfitFor($owner, 'Outfit propio');
        $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $ownedOutfit->id,
                'used_at' => '2026-06-02 18:30:00',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['used_at']);

        $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $ownedOutfit->id,
                'used_at' => '2026-06-02T18:30:00.123456Z',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['used_at']);

        $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $ownedOutfit->id,
                'used_at' => '2026-06-02T18:30:00Z',
                'user_id' => $otherUser->id,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);

        $this->assertDatabaseCount('outfit_logs', 0);
    }

    public function test_statistics_handle_empty_history_and_include_unused_garments(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $first = $this->createGarmentFor($owner, 'Prenda sin uso');
        $second = $this->createGarmentFor($owner, 'Otra prenda sin uso');

        $this->withToken($token)
            ->getJson('/api/outfit-logs/statistics')
            ->assertOk()
            ->assertJsonPath('data.total_uses', 0)
            ->assertJsonCount(0, 'data.most_used_garments')
            ->assertJsonCount(2, 'data.least_used_garments')
            ->assertJsonPath('data.least_used_garments.0.garment.id', $first->id)
            ->assertJsonPath('data.least_used_garments.0.usage_count', 0)
            ->assertJsonPath('data.least_used_garments.1.garment.id', $second->id)
            ->assertJsonCount(0, 'data.frequent_combinations');
    }

    public function test_statistics_aggregate_repeated_uses_ties_unused_garments_and_identical_combinations(): void
    {
        $token = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $shirt = $this->createGarmentFor($owner, 'Camiseta usada');
        $trousers = $this->createGarmentFor($owner, 'Pantalón usado');
        $shoes = $this->createGarmentFor($owner, 'Zapatos usados');
        $unused = $this->createGarmentFor($owner, 'Prenda no usada');
        $firstOutfit = $this->createOutfitWithGarments($owner, 'Conjunto frecuente A', [$shirt, $trousers]);
        $secondOutfit = $this->createOutfitWithGarments($owner, 'Conjunto frecuente B', [$trousers, $shirt]);
        $otherOutfit = $this->createOutfitWithGarments($owner, 'Otro conjunto', [$shoes]);

        $this->recordUsage($token, $firstOutfit, '2026-07-01T10:00:00+00:00');
        $this->recordUsage($token, $firstOutfit, '2026-07-02T10:00:00+00:00');
        $this->recordUsage($token, $secondOutfit, '2026-07-03T10:00:00+00:00');
        $this->recordUsage($token, $otherOutfit, '2026-07-04T10:00:00+00:00');

        $response = $this->withToken($token)
            ->getJson('/api/outfit-logs/statistics')
            ->assertOk()
            ->assertJsonPath('data.total_uses', 4)
            ->assertJsonCount(2, 'data.most_used_garments')
            ->assertJsonPath('data.most_used_garments.0.usage_count', 3)
            ->assertJsonPath('data.most_used_garments.1.usage_count', 3)
            ->assertJsonCount(1, 'data.least_used_garments')
            ->assertJsonPath('data.least_used_garments.0.garment.id', $unused->id)
            ->assertJsonPath('data.least_used_garments.0.usage_count', 0)
            ->assertJsonCount(2, 'data.frequent_combinations')
            ->assertJsonPath('data.frequent_combinations.0.usage_count', 3)
            ->assertJsonCount(2, 'data.frequent_combinations.0.garments')
            ->assertJsonCount(2, 'data.frequent_combinations.0.outfits')
            ->assertJsonPath('data.frequent_combinations.1.usage_count', 1);

        $this->assertSame(
            [$shirt->id, $trousers->id],
            collect($response->json('data.frequent_combinations.0.garments'))->pluck('id')->all(),
        );
    }

    public function test_statistics_are_scoped_to_the_authenticated_user(): void
    {
        $ownerToken = $this->tokenFor('owner@example.test');
        $owner = User::query()->where('email', 'owner@example.test')->firstOrFail();
        $foreignUser = User::query()->create([
            'name' => 'Otro usuario',
            'email' => 'other@example.test',
            'password' => 'password',
            'role' => 'user',
        ]);
        $foreignOutfit = $this->createOutfitFor($foreignUser, 'Privado');
        OutfitLog::query()->create([
            'outfit_id' => $foreignOutfit->id,
            'used_at' => '2026-08-01T09:00:00+02:00',
        ]);

        $this->withToken($ownerToken)
            ->getJson('/api/outfit-logs/statistics')
            ->assertOk()
            ->assertJsonPath('data.total_uses', 0)
            ->assertJsonCount(0, 'data.most_used_garments');

        $this->assertSame(0, $owner->outfitLogs()->count());
    }

    public function test_usage_and_statistics_endpoints_require_a_valid_jwt(): void
    {
        $this->assertProblemDetails(
            $this->postJson('/api/outfit-logs', []),
            401,
            '/api/outfit-logs',
            'La autenticación es necesaria para acceder a este recurso.',
        );

        $this->assertProblemDetails(
            $this->getJson('/api/outfit-logs/statistics'),
            401,
            '/api/outfit-logs/statistics',
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

    private function createOutfitFor(User $user, string $name): Outfit
    {
        return $user->outfits()->create(['name' => $name]);
    }

    private function createGarmentFor(User $user, string $name): Garment
    {
        return Garment::query()->create([
            'user_id' => $user->id,
            'category_id' => Category::query()->firstOrFail()->id,
            'color_id' => Color::query()->firstOrFail()->id,
            'style_id' => Style::query()->firstOrFail()->id,
            'name' => $name,
            'season' => 'summer',
        ]);
    }

    /**
     * @param  list<Garment>  $garments
     */
    private function createOutfitWithGarments(User $user, string $name, array $garments): Outfit
    {
        $outfit = $this->createOutfitFor($user, $name);
        $pivot = [];

        foreach ($garments as $index => $garment) {
            $pivot[$garment->id] = ['is_primary' => $index === 0];
        }

        $outfit->garments()->attach($pivot);

        return $outfit;
    }

    private function recordUsage(string $token, Outfit $outfit, string $usedAt): void
    {
        $this->withToken($token)
            ->postJson('/api/outfit-logs', [
                'outfit_id' => $outfit->id,
                'used_at' => $usedAt,
            ])
            ->assertCreated();
    }
}
