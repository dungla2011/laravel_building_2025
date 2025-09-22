<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use App\Rules\NoForbiddenWords;
use App\Rules\ValidEmailDomain;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller/middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-Z\s\-\.\']+$/', // Only letters, spaces, hyphens, dots, apostrophes
                new NoForbiddenWords(), // Custom rule to check forbidden words
            ],
            'email' => [
                'required',
                'string',
                'email:rfc,dns', // Strict email validation with DNS check
                'max:255',
                'unique:users,email',
                'regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // Email format validation
                new ValidEmailDomain(), // Custom rule to validate domain
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase() // At least one uppercase and one lowercase letter
                    ->letters()   // Must contain letters
                    ->numbers()   // Must contain numbers
                    ->symbols()   // Must contain symbols
                    ->uncompromised(), // Check against compromised passwords database
            ],
            'password_confirmation' => [
                'required_with:password',
                'same:password'
            ]
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Tên là bắt buộc.',
            'name.min' => 'Tên phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',
            'name.regex' => 'Tên chỉ được chứa chữ cái, khoảng trắng, dấu gạch ngang, dấu chấm và dấu nháy đơn.',
            
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            'email.regex' => 'Email không đúng định dạng chuẩn.',
            
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password_confirmation.required_with' => 'Xác nhận mật khẩu là bắt buộc.',
            'password_confirmation.same' => 'Xác nhận mật khẩu không khớp.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'tên',
            'email' => 'email',
            'password' => 'mật khẩu',
            'password_confirmation' => 'xác nhận mật khẩu',
        ];
    }
}
