<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CargoCreateRequest extends FormRequest
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
            'pickup_location' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'material_type' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'urgency_level' => 'required|string|max:255',
            'budget'       => 'nullable|numeric',
            'price_type'   => 'nullable|in:fixed,negotiable',
            'bid_deadline' => 'nullable|date|after:now',
            'status'       => 'sometimes|in:pending,matched,completed',
        ];
    }
}
