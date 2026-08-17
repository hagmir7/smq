<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Adjust according to your authorization logic (policies/gates)
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'code')->ignore($this->route('service')),
            ],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'The service name is required.',
            'code.required' => 'The PRS reference is required.',
            'code.unique' => 'This PRS reference is already in use.',
            'responsible_id.required' => 'A responsible user must be assigned.',
            'responsible_id.exists' => 'The selected responsible user does not exist.',
        ];
    }
}