<?php
// app/Http/Requests/StoreUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    // public function authorize(): bool
    // {
    //     return $this->user()?->hasRole('admin') ?? false;
    // }

    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'full_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'company_id' => 'nullable|numeric|exists:companies,id',
            'service_id' => 'nullable|numeric|exists:services,id',
            'code' => 'nullable|string|max:10|unique:users,code',
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => "Nom d'utilisateur",
            'full_name' => 'Nom complet',
            'phone' => 'Téléphone',
            'email' => 'Adresse e-mail',
            'password' => 'Mot de passe',
            'company_id' => 'Société',
            'service_id' => 'Service',
            'code' => 'Matricule',
        ];
    }
}