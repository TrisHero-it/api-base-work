<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StageUpdateRequest extends FormRequest
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
            'name' => 'string',
            'workflow_id' => 'nullable|integer|exists:workflows,id',
            'description' => 'nullable|string',
            'expired_after_hours' => 'nullable|integer|between:1,60',
            'index' => 'nullable|integer'
        ];
    }
}
