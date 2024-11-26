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
        return [
            'email' => 'email|unique:accounts,email',
            'password' => 'min:8',
            'username' => 'unique:accounts,username',
            'full_name' => 'max:100',
            'position' => 'max:20',
            'phone'=> 'unique:accounts,phone | regex:/^(\+84|0)(\d{9})$/',
            'birthday' => 'date',
        ];
    }

}
