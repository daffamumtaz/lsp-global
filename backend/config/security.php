<?php

return [
    'admin_mfa_required' => env('ADMIN_MFA_REQUIRED'),
    'public_content_requests_per_minute' => (int) env('PUBLIC_CONTENT_REQUESTS_PER_MINUTE', 600),
    'content_directory_cache_seconds' => (int) env('CONTENT_DIRECTORY_CACHE_SECONDS', 30),
];
