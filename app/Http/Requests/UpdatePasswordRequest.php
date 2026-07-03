<?php
// app/Http/Requests/UpdatePasswordRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any authenticated user can change their own password
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required',
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}