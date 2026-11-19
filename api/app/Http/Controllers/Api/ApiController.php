<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use LogicException;

abstract class ApiController extends Controller
{
    protected function authenticatedUser(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            throw new LogicException('El guard JWT autenticó sin devolver un usuario.');
        }

        return $user;
    }
}