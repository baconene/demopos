<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventoryAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->can('manage inventory');
    }

    public function rules(): array
    {
        return [
            'ingredient_id' => 'required|integer|exists:ingredients,id',
            'quantity' => 'required|numeric',
            'type' => 'required|in:stock_in,stock_out,adjustment,waste',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ];
    }
}
