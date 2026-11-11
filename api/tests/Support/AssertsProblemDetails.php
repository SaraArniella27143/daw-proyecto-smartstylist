<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

trait AssertsProblemDetails
{
    protected function assertProblemDetails(
        TestResponse $response,
        int $status,
        string $instance,
        ?string $detail = null,
    ): TestResponse {
        $titles = [
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict',
            422 => 'Unprocessable Content',
        ];

        $response->assertStatus($status)
            ->assertHeader('Content-Type', 'application/problem+json')
            ->assertJsonPath('type', 'about:blank')
            ->assertJsonPath('title', $titles[$status])
            ->assertJsonPath('status', $status)
            ->assertJsonPath('instance', $instance)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance']);

        if ($detail !== null) {
            $response->assertJsonPath('detail', $detail);
        }

        return $response;
    }
}
