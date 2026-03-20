<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_http_method('GET');

$user = active_user();

if ($user === null) {
    json_response([
        'ok' => false,
        'message' => 'No active session.',
    ], 401);
}

json_response([
    'ok' => true,
    'user' => $user,
]);
