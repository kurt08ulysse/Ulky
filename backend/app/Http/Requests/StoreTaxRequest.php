<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTaxRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Géré par les Policies (TaxPolicy)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:taxes,name,'.($this->tax?->id ?? 'NULL'),
            'description' => 'nullable|string',
            'base_amount' => 'required|integer|min:0', // Montant de base en centimes
            'stamp_amount' => 'nullable|integer|min:0', // Montant du timbre en centimes
            'periodicity' => 'required|string|in:monthly,quarterly,yearly,one_time',
            'commune_id' => 'nullable|integer',
        ];
    }
}
