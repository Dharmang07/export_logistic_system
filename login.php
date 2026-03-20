<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_to(dashboard_path((string) current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        set_flash('danger', 'Email and password are required.');
        redirect_to('login.php');
    }

    $stmt = $conn->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user || !password_verify($password, (string) $user['password'])) {
        set_flash('danger', 'Invalid login credentials.');
        redirect_to('login.php');
    }

    login_user($user);
    set_flash('success', 'Login successful.');
    redirect_to(dashboard_path((string) $user['role']));
}

$pageTitle = 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-5 col-md-8">
        <div class="auth-card card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <h2 class="h3">Access your ELDMS workspace</h2>
                    <p class="text-muted mb-0">Continue managing shipment records, document reviews, and delivery updates.</p>
                </div>
                <form method="post" class="d-grid gap-3">
                    <div>
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div>
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                </form>
                <p class="text-center text-muted mt-4 mb-0">
                    Need an account?
                    <a href="<?= e(url('register.php')) ?>" class="text-decoration-none">Create one here</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
