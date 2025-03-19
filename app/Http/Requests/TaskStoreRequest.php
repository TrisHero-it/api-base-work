<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TaskStoreRequest extends FormRequest
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
            'code' => 'integer|unique:tasks,code',
            'description' => 'string|nullable',
            'account_id' => 'nullable|integer|exists:accounts,id',
            'name' => 'string|nullable',
            'link_youtube' => 'nullable|string|url',
        ];
    }
}
