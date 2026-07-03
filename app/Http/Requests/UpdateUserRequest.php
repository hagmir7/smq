<?php
// app/Http/Requests/UpdateUserRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }



    public function rules(): array
    {
        $userId = $this->route('user');

        return [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $userId,
            'company_id' => 'nullable|numeric|exists:companies,id',
            'service_id' => 'nullable|numeric|exists:services,id',
            'code' => 'nullable|string|max:10|unique:users,code,' . $userId,
            'roles' => 'nullable|array',
            'roles.*' => 'string|exists:roles,name',
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'full_name' => 'Nom complet',
            'email' => 'Adresse e-mail',
            'company_id' => 'Société',
            'service_id' => 'Service',
            'code' => 'Matricule',
        ];

        if (is_array($this->input('roles'))) {
            foreach ($this->input('roles') as $index => $roleName) {
                $attributes["roles.$index"] = $roleName;
            }
        }

        return $attributes;
    }
}
