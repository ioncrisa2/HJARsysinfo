<?php

return [
    // Empty disables PIN access and restores Scramble's local/gate policy.
    'pin' => env('API_DOCS_PIN', ''),
    'session_minutes' => (int) env('API_DOCS_SESSION_MINUTES', 60),
];
