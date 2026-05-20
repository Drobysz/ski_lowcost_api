<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoomRequest extends FormRequest
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
            'num' => ['sometimes', 'integer', 'min:1'],
            'nb_lits' => ['sometimes', 'integer', Rule::in([2, 4, 6])],
            'building_id' => ['sometimes', 'integer', 'min:1'],
            'floor' => ['sometimes', 'integer'],
            'surface' => ['sometimes', 'integer', 'min:1'],
            'view' => ['sometimes', Rule::in(['parking', 'mountains'])],
            'balcony' => ['sometimes', 'boolean'],
        ];
    }
}
