<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomTypeRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'unique:classroom_types,name,' . $this->classroomType->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du type de classe est requis.',
            'name.unique' => 'Ce nom de type de classe existe déjà.',
            'description.max' => 'La description ne peut pas dépasser 1000 caractères.',
        ];
    }
}
