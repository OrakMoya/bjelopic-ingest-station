<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProjectRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:50'],
            'volume_id' => ['required', 'integer', 'exists:volumes,id']
        ];
    }

    public function messages(): array
    {
        return ['volume_id' => [
            'required' => 'A volume is required.',
            'exists:volumes,id' => 'This volume doesn\'t exist.'
        ]];
    }
}
