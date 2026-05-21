<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterClientRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'age' => ['required', 'integer', 'min:0'],
            'address' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date'],
            'tel' => ['required', 'string', 'max:50', Rule::unique('clients', 'tel')],
            'skiing_level' => ['required', Rule::in(['beginner', 'medium', 'confirmed'])],
            'height' => ['required', 'numeric', 'min:0'],
            'weight' => ['required', 'integer', 'min:0'],
            'shoe_size' => ['required', 'integer', 'min:0'],
            'password' => ['required', 'string', 'min:8'],
        ];
    }
}
