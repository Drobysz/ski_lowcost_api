<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateClientRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:50'],
            'last_name' => ['sometimes', 'string', 'max:50'],
            'age' => ['sometimes', 'integer', 'min:0'],
            'address' => ['sometimes', 'string', 'max:255'],
            'birth_date' => ['sometimes', 'date'],
            'tel' => ['sometimes', 'string', 'max:50', Rule::unique('clients', 'tel')->ignore($this->route('client'))],
            'skiing_level' => ['sometimes', Rule::in(['beginner', 'medium', 'confirmed'])],
            'height' => ['sometimes', 'numeric', 'min:0'],
            'weight' => ['sometimes', 'integer', 'min:0'],
            'shoe_size' => ['sometimes', 'integer', 'min:0'],
            'password' => ['sometimes', 'string', 'min:8'],
        ];
    }
}
