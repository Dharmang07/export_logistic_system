<?php
declare(strict_types=1);

$pageTitle = $pageTitle ?? 'ELDMS';
$user = current_user();

if ($user !== null && isset($_GET['mark_notifications'])) {
    mark_notifications_as_read($conn, (int) $user['id']);
    header('Location: ' . current_page_query(['mark_notifications' => null]));
    exit;
}

$notifications = $user ? fetch_notifications($conn, (int) $user['id']) : [];
$unreadCount = $user ? unread_notification_count($conn, (int) $user['id']) : 0;
$flash = get_flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | ELDMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(url('assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="app-body">
<?php if ($user !== null): ?>
<div class="app-shell d-flex">
    <aside class="sidebar p-4">
        <a href="<?= e(url(dashboard_path((string) $user['role']))) ?>" class="brand d-flex align-items-center gap-3 mb-4 text-decoration-none">
            <span class="brand-mark"><i class="bi bi-globe2"></i></span>
            <span>
                <strong class="d-block text-white">ELDMS</strong>
                <small class="text-white-50">Trade Operations</small>
            </span>
        </a>
        <div class="sidebar-meta mb-4">
            <div class="small text-uppercase text-white-50">Signed in as</div>
            <div class="fw-semibold"><?= e($user['name']) ?></div>
            <div class="text-white-50"><?= e(role_label((string) $user['role'])) ?></div>
        </div>
        <nav class="nav flex-column gap-2">
            <?php foreach (sidebar_links_for_role((string) $user['role']) as $link): ?>
                <a class="nav-link sidebar-link <?= is_active_path($link['path']) ? 'active' : '' ?>" href="<?= e(url($link['path'])) ?>">
                    <i class="bi <?= e($link['icon']) ?>"></i>
                    <span><?= e($link['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer mt-auto pt-4">
            <a href="<?= e(url('logout.php')) ?>" class="btn btn-outline-light w-100">Logout</a>
        </div>
    </aside>
    <div class="main-panel flex-grow-1">
        <header class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3 px-4 py-3">
            <div>
                <h1 class="h4 mb-1"><?= e($pageTitle) ?></h1>
                <p class="text-muted mb-0">Shipment, document, and compliance workspace</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-light notification-button position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"><?= e((string) $unreadCount) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-menu p-0">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                            <strong>Notifications</strong>
                            <a href="<?= e(current_page_query(['mark_notifications' => 1])) ?>" class="small text-decoration-none">Clear unread</a>
                        </div>
                        <?php if ($notifications === []): ?>
                            <div class="p-3 text-muted">No updates yet.</div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="p-3 border-bottom notification-item <?= $notification['status'] === 'unread' ? 'unread' : '' ?>">
                                    <div class="small fw-semibold"><?= e($notification['message']) ?></div>
                                    <div class="text-muted small"><?= e(date('d M Y, h:i A', strtotime((string) $notification['created_at']))) ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="user-pill">
                    <span class="fw-semibold"><?= e($user['name']) ?></span>
                    <small><?= e(role_label((string) $user['role'])) ?></small>
                </div>
            </div>
        </header>
        <main class="container-fluid px-4 py-4">
            <?php if ($flash): ?>
                <div class="alert <?= e(alert_class((string) $flash['type'])) ?> alert-dismissible fade show" role="alert">
                    <?= e((string) $flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
<?php else: ?>
<div class="auth-page">
    <main class="container py-5">
        <?php if ($flash): ?>
            <div class="alert <?= e(alert_class((string) $flash['type'])) ?> alert-dismissible fade show" role="alert">
                <?= e((string) $flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
<?php endif; ?>
