<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

require_http_method('POST');

$input = request_payload();
$email = strtolower(trim((string) ($input['email'] ?? '')));
$password = (string) ($input['password'] ?? '');
$errors = [];

if ($email === '') {
    $errors['email'] = 'Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
}

if ($password === '') {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
}

if ($errors !== []) {
    json_response([
        'ok' => false,
        'message' => 'Validation failed.',
        'errors' => $errors,
    ], 422);
}

$user = authenticate_demo_user($email, $password);

if ($user === null) {
    json_response([
        'ok' => false,
        'message' => 'Invalid login credentials.',
    ], 401);
}

login_user($user);

json_response([
    'ok' => true,
    'message' => 'Login successful.',
    'user' => $user,
    'available_demo_accounts' => [
        ['email' => 'admin@eldms.local', 'role' => 'admin'],
        ['email' => 'exporter@eldms.local', 'role' => 'exporter'],
        ['email' => 'logistics@eldms.local', 'role' => 'logistics'],
        ['email' => 'customs@eldms.local', 'role' => 'customs'],
    ],
]);
