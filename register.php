<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    redirect_to(dashboard_path((string) current_user()['role']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
    $role = (string) ($_POST['role'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '' || $role === '') {
        set_flash('danger', 'All registration fields are required.');
        redirect_to('register.php');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        set_flash('danger', 'Please provide a valid email address.');
        redirect_to('register.php');
    }

    if (!array_key_exists($role, available_roles(false))) {
        set_flash('danger', 'Invalid role selected.');
        redirect_to('register.php');
    }

    if ($password !== $confirmPassword) {
        set_flash('danger', 'Passwords do not match.');
        redirect_to('register.php');
    }

    if (strlen($password) < 8) {
        set_flash('danger', 'Password must be at least 8 characters long.');
        redirect_to('register.php');
    }

    $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $check->bind_param('s', $email);
    $check->execute();

    if ($check->get_result()->fetch_assoc()) {
        set_flash('warning', 'An account with that email already exists.');
        redirect_to('register.php');
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->bind_param('ssss', $name, $email, $hashedPassword, $role);
    $stmt->execute();

    set_flash('success', 'Registration complete. You can now log in.');
    redirect_to('login.php');
}

$pageTitle = 'Register';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-6 col-md-9">
        <div class="auth-card card border-0 shadow-sm">
            <div class="card-body p-4 p-lg-5">
                <div class="text-center mb-4">
                    <h2 class="h3">Open a new ELDMS account</h2>
                    <p class="text-muted mb-0">Choose the role that matches your part of the export workflow.</p>
                </div>
                <form method="post" class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                    </div>
                    <div class="col-12">
                        <label for="role" class="form-label">Choose Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="">Select role</option>
                            <?php foreach (available_roles(false) as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg w-100">Create Account</button>
                    </div>
                </form>
                <p class="text-center text-muted mt-4 mb-0">
                    Already registered?
                    <a href="<?= e(url('login.php')) ?>" class="text-decoration-none">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
