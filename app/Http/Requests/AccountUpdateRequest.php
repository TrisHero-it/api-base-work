<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AccountUpdateRequest extends FormRequest
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
        // $id = $this->route('account');
        return [
            // 'email' => 'nullable|email|unique:accounts,email,'.$id,
            // 'password' => 'nullable|min:8',
            // 'username' => 'nullable|unique:accounts,username,'.$id,
            // 'full_name' => 'nullable|max:100',
            // 'position' => 'nullable|max:20',
            // 'phone'=> 'nullable|unique:accounts,phone,'.$id .'| regex:/^(\+84|0)(\d{9})$/',
            // 'birthday' => 'nullable|date',
            // 'address' => 'nullable|max:100',
            // 'manager_id'=> 'nullable|exists:account,id',
            // 'avatar' => 'nullable'
        ];
    }

}
