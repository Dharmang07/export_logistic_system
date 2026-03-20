<?php
declare(strict_types=1);

function url(string $path = ''): string
{
    $base = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($base === '') {
        return $path === '' ? '/' : '/' . $path;
    }

    return $path === '' ? $base : $base . '/' . $path;
}

function redirect_to(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function is_logged_in(): bool
{
    return isset($_SESSION['user']['id']);
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function available_roles(bool $includeAdmin = false): array
{
    $roles = [
        'exporter' => 'Exporter',
        'logistics' => 'Logistics Company',
        'customs' => 'Customs Officer',
    ];

    if ($includeAdmin) {
        $roles = ['admin' => 'Admin'] + $roles;
    }

    return $roles;
}

function role_label(string $role): string
{
    $roles = available_roles(true);

    return $roles[$role] ?? ucfirst($role);
}

function dashboard_path(string $role): string
{
    $map = [
        'admin' => 'admin/admin_dashboard.php',
        'exporter' => 'exporter/exporter_dashboard.php',
        'logistics' => 'logistics/logistics_dashboard.php',
        'customs' => 'customs/customs_dashboard.php',
    ];

    return $map[$role] ?? 'index.php';
}

function require_login(array $roles = []): void
{
    if (!is_logged_in()) {
        set_flash('warning', 'Please log in to access the system.');
        redirect_to('login.php');
    }

    if ($roles !== [] && !in_array((string) current_user()['role'], $roles, true)) {
        set_flash('danger', 'You do not have access to that page.');
        redirect_to(dashboard_path((string) current_user()['role']));
    }
}

function sidebar_links_for_role(string $role): array
{
    $links = [
        'admin' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => 'admin/admin_dashboard.php'],
            ['label' => 'Manage Users', 'icon' => 'bi-people', 'path' => 'admin/manage_users.php'],
            ['label' => 'Reports', 'icon' => 'bi-bar-chart-line', 'path' => 'admin/reports.php'],
            ['label' => 'Compliance', 'icon' => 'bi-shield-check', 'path' => 'check_compliance.php'],
            ['label' => 'Documents', 'icon' => 'bi-folder2-open', 'path' => 'documents/view_documents.php'],
            ['label' => 'Tracking', 'icon' => 'bi-truck', 'path' => 'tracking/track_shipment.php'],
        ],
        'exporter' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => 'exporter/exporter_dashboard.php'],
            ['label' => 'Create Shipment', 'icon' => 'bi-plus-square', 'path' => 'exporter/create_shipment.php'],
            ['label' => 'View Shipments', 'icon' => 'bi-box-seam', 'path' => 'exporter/view_shipments.php'],
            ['label' => 'Upload Documents', 'icon' => 'bi-upload', 'path' => 'documents/upload_document.php'],
            ['label' => 'Documents', 'icon' => 'bi-folder2-open', 'path' => 'documents/view_documents.php'],
            ['label' => 'Compliance', 'icon' => 'bi-shield-check', 'path' => 'check_compliance.php'],
            ['label' => 'Tracking', 'icon' => 'bi-truck', 'path' => 'tracking/track_shipment.php'],
        ],
        'logistics' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => 'logistics/logistics_dashboard.php'],
            ['label' => 'Update Status', 'icon' => 'bi-arrow-repeat', 'path' => 'logistics/update_status.php'],
            ['label' => 'Documents', 'icon' => 'bi-folder2-open', 'path' => 'documents/view_documents.php'],
            ['label' => 'Compliance', 'icon' => 'bi-shield-check', 'path' => 'check_compliance.php'],
            ['label' => 'Tracking', 'icon' => 'bi-truck', 'path' => 'tracking/track_shipment.php'],
        ],
        'customs' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'path' => 'customs/customs_dashboard.php'],
            ['label' => 'Review Documents', 'icon' => 'bi-clipboard-check', 'path' => 'customs/review_documents.php'],
            ['label' => 'Documents', 'icon' => 'bi-folder2-open', 'path' => 'documents/view_documents.php'],
            ['label' => 'Compliance', 'icon' => 'bi-shield-check', 'path' => 'check_compliance.php'],
            ['label' => 'Tracking', 'icon' => 'bi-truck', 'path' => 'tracking/track_shipment.php'],
        ],
    ];

    return $links[$role] ?? [];
}

function is_active_path(string $path): bool
{
    return basename((string) ($_SERVER['PHP_SELF'] ?? '')) === basename($path);
}

function current_page_query(array $changes = []): string
{
    $params = $_GET;

    foreach ($changes as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
            continue;
        }

        $params[$key] = $value;
    }

    $script = (string) ($_SERVER['PHP_SELF'] ?? '');
    $query = http_build_query($params);

    return $query === '' ? $script : $script . '?' . $query;
}

function alert_class(string $type): string
{
    $map = [
        'success' => 'alert-success',
        'danger' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info',
    ];

    return $map[$type] ?? 'alert-secondary';
}

function badge_class_for_status(string $status): string
{
    $map = [
        'Created' => 'secondary',
        'Documents Uploaded' => 'info',
        'Under Customs Review' => 'warning',
        'Approved' => 'primary',
        'Dispatched' => 'dark',
        'Delivered' => 'success',
    ];

    return $map[$status] ?? 'secondary';
}

function shipment_statuses(): array
{
    return [
        'Created',
        'Documents Uploaded',
        'Under Customs Review',
        'Approved',
        'Dispatched',
        'Delivered',
    ];
}

function tracking_statuses(): array
{
    return [
        'Created',
        'Documents Uploaded',
        'Under Customs Review',
        'Dispatched',
        'Delivered',
    ];
}

function allowed_document_types(): array
{
    return [
        'Commercial Invoice',
        'Packing List',
        'Bill of Lading',
        'Certificate of Origin',
        'Shipping Bill',
        'Export License',
    ];
}

function required_document_types(): array
{
    return [
        'Commercial Invoice',
        'Packing List',
        'Bill of Lading',
    ];
}

function upload_error_message(int $errorCode): string
{
    $map = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the server limit.',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the form limit.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Temporary upload folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'The uploaded file could not be saved.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension blocked the file upload.',
    ];

    return $map[$errorCode] ?? 'Unknown upload error.';
}

function save_uploaded_file(array $file, string $directory = 'uploads'): array
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($error));
    }

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('Invalid file upload.');
    }

    $allowedMimeTypes = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? (string) finfo_file($finfo, $file['tmp_name']) : '';

    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Only PDF and image files are allowed.');
    }

    $extension = $allowedMimeTypes[$mimeType];
    $targetDirectory = APP_ROOT . DIRECTORY_SEPARATOR . trim($directory, '/\\');

    if (!is_dir($targetDirectory) && !mkdir($targetDirectory, 0777, true) && !is_dir($targetDirectory)) {
        throw new RuntimeException('Upload directory could not be created.');
    }

    $filename = uniqid('document_', true) . '.' . $extension;
    $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new RuntimeException('The file could not be moved to uploads.');
    }

    return [
        'relative_path' => trim($directory, '/\\') . '/' . $filename,
        'mime_type' => $mimeType,
    ];
}

function document_url(string $path): string
{
    return url(str_replace('\\', '/', ltrim($path, '/\\')));
}

function document_is_previewable(string $path): bool
{
    $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));

    return in_array($extension, ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'], true);
}

function fetch_notifications(mysqli $conn, int $userId, int $limit = 6): array
{
    $stmt = $conn->prepare('SELECT id, message, status, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bind_param('ii', $userId, $limit);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function unread_notification_count(mysqli $conn, int $userId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM notifications WHERE user_id = ? AND status = 'unread'");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    return (int) ($result['total'] ?? 0);
}

function mark_notifications_as_read(mysqli $conn, int $userId): void
{
    $stmt = $conn->prepare("UPDATE notifications SET status = 'read' WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
}

function create_notification(mysqli $conn, int $userId, string $message): void
{
    $status = 'unread';
    $stmt = $conn->prepare('INSERT INTO notifications (user_id, message, status, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('iss', $userId, $message, $status);
    $stmt->execute();
}

function get_user_ids_by_roles(mysqli $conn, array $roles): array
{
    if ($roles === []) {
        return [];
    }

    $placeholders = implode(', ', array_fill(0, count($roles), '?'));
    $types = str_repeat('s', count($roles));
    $stmt = $conn->prepare("SELECT id FROM users WHERE role IN ($placeholders)");
    $stmt->bind_param($types, ...$roles);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];

    while ($row = $result->fetch_assoc()) {
        $ids[] = (int) $row['id'];
    }

    return $ids;
}

function notify_users(mysqli $conn, array $userIds, string $message): void
{
    $uniqueIds = array_values(array_unique(array_map('intval', $userIds)));

    foreach ($uniqueIds as $userId) {
        create_notification($conn, $userId, $message);
    }
}

function notify_shipment_stakeholders(mysqli $conn, int $shipmentId, string $message, array $roles = []): void
{
    $stmt = $conn->prepare('SELECT exporter_id FROM shipments WHERE shipment_id = ? LIMIT 1');
    $stmt->bind_param('i', $shipmentId);
    $stmt->execute();
    $shipment = $stmt->get_result()->fetch_assoc();

    if (!$shipment) {
        return;
    }

    $userIds = [(int) $shipment['exporter_id']];

    if ($roles !== []) {
        $userIds = array_merge($userIds, get_user_ids_by_roles($conn, $roles));
    }

    notify_users($conn, $userIds, $message);
}

function fetch_shipment(mysqli $conn, int $shipmentId): ?array
{
    $stmt = $conn->prepare(
        'SELECT s.*, u.name AS exporter_name, u.email AS exporter_email
         FROM shipments s
         JOIN users u ON u.id = s.exporter_id
         WHERE s.shipment_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('i', $shipmentId);
    $stmt->execute();
    $shipment = $stmt->get_result()->fetch_assoc();

    return $shipment ?: null;
}

function can_access_shipment(array $user, array $shipment): bool
{
    if (in_array($user['role'], ['admin', 'logistics', 'customs'], true)) {
        return true;
    }

    return $user['role'] === 'exporter' && (int) $shipment['exporter_id'] === (int) $user['id'];
}

function run_compliance_check(mysqli $conn, int $shipmentId): array
{
    $stmt = $conn->prepare('SELECT DISTINCT document_type FROM documents WHERE shipment_id = ?');
    $stmt->bind_param('i', $shipmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $available = [];

    while ($row = $result->fetch_assoc()) {
        $available[] = $row['document_type'];
    }

    $missing = array_values(array_diff(required_document_types(), $available));
    $status = $missing === [] ? 'Compliant' : 'Non-Compliant';
    $missingDocuments = $missing === [] ? 'None' : implode(', ', $missing);

    $upsert = $conn->prepare(
        'INSERT INTO compliance_checks (shipment_id, missing_documents, compliance_status)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE missing_documents = VALUES(missing_documents), compliance_status = VALUES(compliance_status)'
    );
    $upsert->bind_param('iss', $shipmentId, $missingDocuments, $status);
    $upsert->execute();

    if ($missing === []) {
        $update = $conn->prepare(
            "UPDATE shipments
             SET shipment_status = CASE
                WHEN shipment_status = 'Created' THEN 'Documents Uploaded'
                ELSE shipment_status
             END
             WHERE shipment_id = ?"
        );
        $update->bind_param('i', $shipmentId);
        $update->execute();
    }

    return [
        'shipment_id' => $shipmentId,
        'missing_documents' => $missing,
        'compliance_status' => $status,
    ];
}

function ensure_compliance_records(mysqli $conn): void
{
    $result = $conn->query('SELECT shipment_id FROM shipments');

    while ($shipment = $result->fetch_assoc()) {
        run_compliance_check($conn, (int) $shipment['shipment_id']);
    }
}
