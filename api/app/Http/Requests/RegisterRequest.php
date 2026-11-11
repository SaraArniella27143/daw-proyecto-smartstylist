<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $passwordRules = ['required', 'string', 'min:12'];

        if ($this->has('password_confirmation')) {
            $passwordRules[] = 'confirmed';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => $passwordRules,
            'role' => ['prohibited'],
        ];
    }
}
