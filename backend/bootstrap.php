<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_name('eldms_session');
    session_start();
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

function require_http_method(string $method): void
{
    if (strcasecmp((string) ($_SERVER['REQUEST_METHOD'] ?? ''), $method) !== 0) {
        json_response([
            'ok' => false,
            'message' => 'Method not allowed.',
            'expected_method' => strtoupper($method),
        ], 405);
    }
}

function request_payload(): array
{
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            json_response([
                'ok' => false,
                'message' => 'Invalid JSON request body.',
            ], 400);
        }

        return $decoded;
    }

    return $_POST;
}

function active_user(): ?array
{
    return isset($_SESSION['eldms_user']) && is_array($_SESSION['eldms_user'])
        ? $_SESSION['eldms_user']
        : null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['eldms_user'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'email' => (string) $user['email'],
        'role' => (string) $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

function demo_users(): array
{
    return [
        'admin@eldms.local' => [
            'id' => 1,
            'name' => 'Aarav Admin',
            'email' => 'admin@eldms.local',
            'role' => 'admin',
            'password_hash' => '$2y$10$/EZuW..1uhwMF/GxSks0cun3VL0sI5/VOeQ/Hz0sBSE3p5LN/26vS',
        ],
        'exporter@eldms.local' => [
            'id' => 2,
            'name' => 'Eva Exporter',
            'email' => 'exporter@eldms.local',
            'role' => 'exporter',
            'password_hash' => '$2y$10$2HeukLsMzKS3eJIJaQ56nuLAxqo9MSVqloyJ/CavblU83RCyXettG',
        ],
        'logistics@eldms.local' => [
            'id' => 3,
            'name' => 'Leo Logistics',
            'email' => 'logistics@eldms.local',
            'role' => 'logistics',
            'password_hash' => '$2y$10$xNdB1mz6VQ.2vrxDYXb0cOGOREHDJSWWO0uHQqZebyZJysBS.jG7m',
        ],
        'customs@eldms.local' => [
            'id' => 4,
            'name' => 'Cora Customs',
            'email' => 'customs@eldms.local',
            'role' => 'customs',
            'password_hash' => '$2y$10$Gcx7iSpxjhklsmQdFWVX.u5M9KE94u.2bBEqHo40fGk8dol1J8AbG',
        ],
    ];
}

function authenticate_demo_user(string $email, string $password): ?array
{
    $users = demo_users();
    $user = $users[strtolower($email)] ?? null;

    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
        return null;
    }

    unset($user['password_hash']);

    return $user;
}
