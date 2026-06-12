<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaxNoticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Géré par les Policies (TaxNoticePolicy)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tax_id' => 'required|exists:taxes,id',
            'user_id' => 'required|exists:users,id',

            // Montants optionnels si on souhaite surcharger le montant par défaut de la taxe
            'base_amount' => 'nullable|integer|min:0',
            'stamp_amount' => 'nullable|integer|min:0',

            'due_date' => 'required|date|after_or_equal:today',
            'commune_id' => 'nullable|integer',
        ];
    }
}
