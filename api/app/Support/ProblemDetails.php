<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProblemDetails
{
    public static function response(int $status, string $detail, array $extensions = []): JsonResponse
    {
        $problem = [
            ...$extensions,
            'type' => 'about:blank',
            'title' => self::title($status),
            'status' => $status,
            'detail' => $detail,
            'instance' => app(Request::class)->getRequestUri(),
        ];

        return response()->json($problem, $status)
            ->header('Content-Type', 'application/problem+json');
    }

    private static function title(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            410 => 'Gone',
            413 => 'Content Too Large',
            415 => 'Unsupported Media Type',
            422 => 'Unprocessable Content',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            default => 'HTTP Error',
        };
    }
}
