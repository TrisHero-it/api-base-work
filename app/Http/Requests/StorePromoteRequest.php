<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePromoteRequest extends FormRequest
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
            'account_id' => 'required|exists:accounts,id',
            'department_id' => 'required|exists:departments,id',
            'postion' => 'required|string',
            'personnel_class' => 'required|string',
            'basic_salary' => 'required|numeric|min:0',
            'travel_allowance' => 'required|numeric|min:0',
            'eat_allowance' => 'required|numeric|min:0',
            'kpi' => 'required|numeric|min:0',
        ];
    }
}
