<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\UserService;
use App\Support\ProblemDetails;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class UserController extends Controller
{
    public function destroy(User $user, UserService $userService): JsonResponse
    {
        if (! $userService->delete($user)) {
            return ProblemDetails::response(
                409,
                'No se puede eliminar al último administrador.',
            );
        }

        return response()->json(status: 204);
    }
}