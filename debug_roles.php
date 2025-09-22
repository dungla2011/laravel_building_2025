<?php

// Debug role extraction
$base_url = 'http://127.0.0.1:8000';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$base_url/admin/role-permissions");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";

if ($httpCode == 200) {
    // Test role regex patterns
    echo "\n=== TESTING ROLE REGEX PATTERNS ===\n";
    
    $patterns = [
        '/data-role-name="([^"]+)"[^>]*data-role-id="(\d+)"/',
        '/data-role-id="(\d+)"[^>]*data-role-name="([^"]+)"/',
        '/data-role-name="([^"]+)".*?data-role-id="(\d+)"/',
        '/data-role-id="(\d+)".*?data-role-name="([^"]+)"/'
    ];
    
    foreach ($patterns as $i => $pattern) {
        echo "\nPattern $i: $pattern\n";
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            echo "Found " . count($matches) . " matches:\n";
            foreach (array_slice($matches, 0, 5) as $match) {
                if (count($match) >= 3) {
                    echo "  - Role: {$match[1]}, ID: {$match[2]}\n";
                }
            }
        } else {
            echo "No matches found\n";
        }
    }
    
    // Look for any data-role attributes
    echo "\n=== ALL DATA-ROLE ATTRIBUTES ===\n";
    if (preg_match_all('/data-role-[^=]*="[^"]*"/', $html, $matches)) {
        foreach (array_slice($matches[0], 0, 10) as $match) {
            echo "Found: $match\n";
        }
    }
    
} else {
    echo "Failed to load page\n";
}