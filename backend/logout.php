<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_http_method('POST');

if (active_user() === null) {
    json_response([
        'ok' => true,
        'message' => 'No active session to clear.',
    ]);
}

logout_user();

json_response([
    'ok' => true,
    'message' => 'Logout successful.',
]);
