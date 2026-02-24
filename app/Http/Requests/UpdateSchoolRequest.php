<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:schools,name,' . $this->school->id],
            'code' => ['required', 'string', 'max:50', 'unique:schools,code,' . $this->school->id],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de l\'école est requis.',
            'name.unique' => 'Ce nom d\'école existe déjà.',
            'code.required' => 'Le code de l\'école est requis.',
            'code.unique' => 'Ce code d\'école existe déjà.',
            'email.email' => 'L\'email doit être une adresse valide.',
        ];
    }
}
