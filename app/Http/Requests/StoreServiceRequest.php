<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:services,code'],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du service est obligatoire.',
            'code.required' => 'La référence PRS est obligatoire.',
            'code.unique' => 'Cette référence PRS est déjà utilisée.',
            'responsible_id.required' => 'Un responsable doit être affecté.',
            'responsible_id.exists' => 'Le responsable sélectionné n’existe pas.',
        ];
    }
}