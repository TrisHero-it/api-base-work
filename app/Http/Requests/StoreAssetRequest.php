<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssetRequest extends FormRequest
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
            'code' => 'required|string|max:255',
            'name' => 'string|max:255',
            'category_id' => 'exists:asset_categories,id',
            'price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'status' => 'in:using,liquidated,broken,unused,warranty',
            'reason' => 'nullable|string',
            'buy_date' => 'required|date',
            'buyer_id' => 'required|exists:accounts,id',
            'seller_id' => 'nullable|exists:accounts,id',
            'brand_id' => 'nullable|exists:asset_brands,id',
            'account_id' => 'nullable|exists:accounts,id',
            'warranty_date' => 'required|date',
            'sell_date' => 'nullable|date',
            'sell_price' => 'nullable|numeric|min:0',
            'serial_number' => 'nullable|string',
            'brand_link' => 'nullable|string',
            'start_date' => 'nullable|date',
            'brand_name' => 'nullable|string',
        ];
    }
}
