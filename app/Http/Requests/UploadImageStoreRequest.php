<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadImageStoreRequest extends FormRequest
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ];
    }

public function messages(): array
    {
        return [
            'image.required' => 'Ảnh không được để trống',
            'image.image' => 'Đây phải là file ảnh',
            'image.mimes' => 'Chỉ chấp nhận file có đuôi là jpeg,png,jpg,gif,svg,webp',
            'image.max' => 'File quá nặng (trên 2MB)'
        ];
    }


}
