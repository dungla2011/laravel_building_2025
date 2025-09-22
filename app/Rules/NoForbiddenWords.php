<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoForbiddenWords implements ValidationRule
{
    /**
     * List of forbidden words/patterns
     */
    private array $forbiddenWords = [
        'admin', 'administrator', 'root', 'system', 'test', 'demo',
        'null', 'undefined', 'spam', 'fake', 'bot', 'script'
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $lowerValue = strtolower($value);
        
        foreach ($this->forbiddenWords as $forbiddenWord) {
            if (str_contains($lowerValue, strtolower($forbiddenWord))) {
                $fail("Tên không được chứa từ khóa không được phép: {$forbiddenWord}");
                return;
            }
        }
        
        // Check for suspicious patterns
        if (preg_match('/\b(admin|root|system)\d*\b/i', $value)) {
            $fail('Tên không được chứa các từ khóa hệ thống.');
        }
        
        // Check for repeated characters (like "aaaa" or "1111")
        if (preg_match('/(.)\1{3,}/', $value)) {
            $fail('Tên không được chứa quá nhiều ký tự lặp lại.');
        }
    }
}
