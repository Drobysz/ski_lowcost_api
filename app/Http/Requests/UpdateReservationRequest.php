<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReservationRequest extends FormRequest
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
            'client_id' => ['sometimes', 'exists:clients,id'],
            'check_in' => ['sometimes', 'date'],
            'check_out' => ['sometimes', 'date', 'after:check_in'],
            'purchase_date' => ['sometimes', 'nullable', 'date'],
            'status' => ['sometimes', Rule::in(['not paid', 'paid', 'approaching', 'in process', 'finished', 'cancelled'])],
            'total_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'stripe_session_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'paid_at' => ['sometimes', 'nullable', 'date'],
            'accommodations' => ['sometimes', 'array', 'min:1'],
            'accommodations.*.room_id' => ['required_with:accommodations', 'exists:rooms,id'],
            'accommodations.*.client_id' => ['required_with:accommodations', 'exists:clients,id'],
        ];
    }
}
