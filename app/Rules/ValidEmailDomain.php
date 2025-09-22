<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidEmailDomain implements ValidationRule
{
    /**
     * List of blocked domains
     */
    private array $blockedDomains = [
        '10minutemail.com',
        'tempmail.org',
        'guerrillamail.com',
        'mailinator.com',
        'throwaway.email',
        'temp-mail.org'
    ];

    /**
     * List of allowed domains (if specified, only these domains are allowed)
     */
    private array $allowedDomains = [
        // 'company.com',
        // 'organization.org',
        // Leave empty to allow all domains except blocked ones
    ];

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $fail('Email không đúng định dạng.');
            return;
        }

        $domain = strtolower(substr(strrchr($value, "@"), 1));
        
        // Check if domain is blocked
        if (in_array($domain, $this->blockedDomains)) {
            $fail('Email từ domain này không được chấp nhận.');
            return;
        }
        
        // If allowed domains are specified, check if domain is in the list
        if (!empty($this->allowedDomains) && !in_array($domain, $this->allowedDomains)) {
            $fail('Email phải từ một trong các domain được phép: ' . implode(', ', $this->allowedDomains));
            return;
        }
        
        // Check for suspicious patterns
        if (preg_match('/\+.*@/', $value)) {
            // Allow plus addressing but could add restrictions
            // $fail('Email không được chứa ký tự +');
        }
        
        // Check domain has valid MX record (optional - can be slow)
        if (config('app.env') === 'production') {
            if (!checkdnsrr($domain, 'MX')) {
                $fail('Domain email không tồn tại hoặc không có MX record.');
            }
        }
    }
}
