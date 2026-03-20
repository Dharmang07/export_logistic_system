<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_to(dashboard_path((string) current_user()['role']));
}

$pageTitle = 'Welcome';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero-section rounded-4 overflow-hidden">
    <div class="row g-0 align-items-stretch">
        <div class="col-lg-7 p-5 hero-content">
            <span class="hero-kicker">One workspace for export coordination</span>
            <h2 class="display-5 fw-semibold mt-3 mb-3">Manage shipment records, document review, and dispatch updates from one place.</h2>
            <p class="lead text-muted mb-4">
                ELDMS helps exporters and partner teams keep trade paperwork organized, move approvals faster, and follow each shipment stage without juggling separate tools.
            </p>
            <div class="d-flex flex-wrap gap-3">
                <a href="<?= e(url('login.php')) ?>" class="btn btn-primary btn-lg">Sign In</a>
                <a href="<?= e(url('register.php')) ?>" class="btn btn-outline-primary btn-lg">Create Account</a>
            </div>
            <div class="row g-3 mt-4">
                <div class="col-md-4">
                    <div class="hero-stat">
                        <strong>4</strong>
                        <span>Connected user roles</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="hero-stat">
                        <strong>6</strong>
                        <span>Accepted upload categories</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="hero-stat">
                        <strong>1</strong>
                        <span>Shared shipment timeline</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 hero-panel p-5">
            <div class="feature-card">
                <h3 class="h5">Designed for trade teams</h3>
                <ul class="list-unstyled mt-4 mb-0 d-grid gap-3">
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Create shipment records with role-based access</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Upload invoices, packing files, and transport proof</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Detect missing mandatory paperwork automatically</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Keep customs and logistics handoffs in a single flow</li>
                    <li><i class="bi bi-check-circle-fill text-success me-2"></i> Review platform activity through built-in dashboards</li>
                </ul>
            </div>
        </div>
    </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
