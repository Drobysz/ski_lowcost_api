<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailableRoomsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $filters = $this->input('filters', []);
        $sort = $this->input('sort', []);

        $this->merge([
            'view' => $this->input('view', data_get($filters, 'view')),
            'room_size' => $this->input('room_size', data_get($filters, 'room_size')),
            'beds_sort' => $this->input('beds_sort', data_get($sort, 'beds')),
        ]);
    }

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
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'view' => ['sometimes', 'nullable', 'string', Rule::in(['Slopes', 'Parking', 'slopes', 'parking', 'mountains'])],
            'room_size' => ['sometimes', 'nullable', 'integer', Rule::in([2, 4, 6])],
            'beds_sort' => ['sometimes', 'nullable', 'string', Rule::in(['up', 'down', 'asc', 'desc'])],
        ];
    }
}
