<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

require_once __DIR__ . '/schema.php';

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'eldms';

$appRoot = realpath(dirname(__DIR__)) ?: dirname(__DIR__);
$documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
$normalizedAppRoot = str_replace('\\', '/', $appRoot);
$normalizedDocumentRoot = $documentRoot ? rtrim(str_replace('\\', '/', $documentRoot), '/') : '';
$baseUrl = '/export-logistics-system';

if ($normalizedDocumentRoot !== '' && strpos($normalizedAppRoot, $normalizedDocumentRoot) === 0) {
    $relativePath = substr($normalizedAppRoot, strlen($normalizedDocumentRoot));
    $baseUrl = $relativePath !== '' ? $relativePath : '';
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', $appRoot);
}

if (!defined('BASE_URL')) {
    define('BASE_URL', $baseUrl);
}

$conn = connect_database();
$conn->set_charset('utf8mb4');

function connect_database(): mysqli
{
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    } catch (mysqli_sql_exception $exception) {
        if ((int) $exception->getCode() !== 1049) {
            throw $exception;
        }

        $conn = bootstrap_database(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    }

    ensure_database_schema($conn);

    return $conn;
}
