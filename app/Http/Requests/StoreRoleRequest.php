<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRoleRequest extends FormRequest
{
    /**
     * Détermine si l'utilisateur est autorisé à effectuer cette requête.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Règles de validation applicables à la requête.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:roles',
        ];
    }

    /**
     * Messages d'erreur personnalisés pour les règles de validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.string'   => 'Le nom du rôle doit être une chaîne de caractères.',
            'name.unique'   => 'Ce nom de rôle existe déjà.',
        ];
    }

    /**
     * Noms d'attributs personnalisés utilisés dans les messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nom du rôle',
        ];
    }

    /**
     * Gère une tentative de validation échouée.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 203));
    }
}
