<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function delete(User $user): bool
    {
        return DB::transaction(function () use ($user): bool {
            if ($user->role === 'admin') {
                $administrators = User::query()
                    ->where('role', 'admin')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id']);

                if ($administrators->count() <= 1) {
                    return false;
                }
            }

            $user->delete();

            return true;
        });
    }
}
