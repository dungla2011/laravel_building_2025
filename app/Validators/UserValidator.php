<?php

namespace App\Validators;

use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;
use App\Rules\NoForbiddenWords;
use App\Rules\ValidEmailDomain;

class UserValidator
{
    /**
     * Get validation rules for creating a new user
     */
    public static function createRules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ỹ\s\-\.\']+$/u', // Support Vietnamese characters
                new NoForbiddenWords(),
            ],
            'email' => [
                'required',
                'string',
                'email:rfc', // Remove DNS check for now
                'max:255',
                'unique:users,email',
                new ValidEmailDomain(),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->when(config('app.env') === 'production', fn($rule) => $rule->uncompromised()),
            ],
        ];
    }

    /**
     * Get validation rules for updating a user
     */
    public static function updateRules(?int $userId = null): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ỹ\s\-\.\']+$/u', // Support Vietnamese characters
                new NoForbiddenWords(),
            ],
            'email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($userId),
                new ValidEmailDomain(),
            ],
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get validation rules for batch operations
     */
    public static function batchCreateRules(): array
    {
        return [
            'resources' => 'required|array|min:1|max:100', // Limit batch size
            'resources.*.name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ỹ\s\-\.\']+$/u',
                new NoForbiddenWords(),
            ],
            'resources.*.email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                'distinct', // Ensure no duplicate emails in batch
                'unique:users,email',
                new ValidEmailDomain(),
            ],
            'resources.*.password' => [
                'required',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get validation rules for batch update operations
     */
    public static function batchUpdateRules(): array
    {
        return [
            'resources' => 'required|array|min:1|max:100',
            'resources.*.name' => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:255',
                'regex:/^[a-zA-ZÀ-ỹ\s\-\.\']+$/u',
                new NoForbiddenWords(),
            ],
            'resources.*.email' => [
                'sometimes',
                'required',
                'string',
                'email:rfc',
                'max:255',
                'distinct',
                new ValidEmailDomain(),
                // Note: For batch updates, unique validation is more complex
                // and should be handled in the controller
            ],
            'resources.*.password' => [
                'sometimes',
                'nullable',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }

    /**
     * Get custom error messages
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Tên là bắt buộc.',
            'name.min' => 'Tên phải có ít nhất 2 ký tự.',
            'name.max' => 'Tên không được vượt quá 255 ký tự.',
            'name.regex' => 'Tên chỉ được chứa chữ cái, khoảng trắng, dấu gạch ngang, dấu chấm và dấu nháy đơn.',
            
            'email.required' => 'Email là bắt buộc.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'email.distinct' => 'Có email trùng lặp trong danh sách.',
            'email.max' => 'Email không được vượt quá 255 ký tự.',
            
            'password.required' => 'Mật khẩu là bắt buộc.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
            
            'resources.required' => 'Danh sách dữ liệu là bắt buộc.',
            'resources.array' => 'Danh sách dữ liệu phải là một mảng.',
            'resources.min' => 'Phải có ít nhất 1 item trong danh sách.',
            'resources.max' => 'Không được vượt quá 100 items trong một batch.',
        ];
    }

    /**
     * Get custom attributes
     */
    public static function attributes(): array
    {
        return [
            'name' => 'tên',
            'email' => 'email',
            'password' => 'mật khẩu',
            'resources' => 'danh sách dữ liệu',
        ];
    }

    /**
     * Validate user data
     */
    public static function validate(array $data, string $operation = 'create', ?int $userId = null): \Illuminate\Validation\Validator
    {
        $rules = match($operation) {
            'create' => self::createRules(),
            'update' => self::updateRules($userId),
            'batch_create' => self::batchCreateRules(),
            'batch_update' => self::batchUpdateRules(),
            default => self::createRules(),
        };

        return validator($data, $rules, self::messages(), self::attributes());
    }
}