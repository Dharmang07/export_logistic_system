<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'create') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? '');

        if ($name === '' || $email === '' || $password === '' || $role === '') {
            set_flash('danger', 'All user fields are required.');
            redirect_to('admin/manage_users.php');
        }

        if (!array_key_exists($role, available_roles(true))) {
            set_flash('danger', 'Invalid role selected.');
            redirect_to('admin/manage_users.php');
        }

        $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $check->bind_param('s', $email);
        $check->execute();

        if ($check->get_result()->fetch_assoc()) {
            set_flash('warning', 'A user with that email already exists.');
            redirect_to('admin/manage_users.php');
        }

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->bind_param('ssss', $name, $email, $hashedPassword, $role);
        $stmt->execute();

        set_flash('success', 'User created successfully.');
        redirect_to('admin/manage_users.php');
    }

    if ($action === 'update_role') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $role = (string) ($_POST['role'] ?? '');

        if ($userId <= 0 || !array_key_exists($role, available_roles(true))) {
            set_flash('danger', 'Invalid role update request.');
            redirect_to('admin/manage_users.php');
        }

        if ($userId === (int) current_user()['id'] && $role !== 'admin') {
            set_flash('warning', 'You cannot remove your own admin role.');
            redirect_to('admin/manage_users.php');
        }

        $stmt = $conn->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $role, $userId);
        $stmt->execute();

        set_flash('success', 'User role updated.');
        redirect_to('admin/manage_users.php');
    }
}

if (isset($_GET['delete'])) {
    $userId = (int) $_GET['delete'];

    if ($userId === (int) current_user()['id']) {
        set_flash('warning', 'You cannot delete your own account.');
        redirect_to('admin/manage_users.php');
    }

    $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
    $stmt->bind_param('i', $userId);
    $stmt->execute();

    set_flash('success', 'User deleted successfully.');
    redirect_to('admin/manage_users.php');
}

$users = $conn->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Manage Users';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">Create User</h2>
                <p class="page-subtitle mb-3">Add system users with role-specific access</p>
                <form method="post" class="d-grid gap-3">
                    <input type="hidden" name="action" value="create">
                    <div>
                        <label class="form-label" for="name">Name</label>
                        <input class="form-control" id="name" name="name" required>
                    </div>
                    <div>
                        <label class="form-label" for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div>
                        <label class="form-label" for="password">Password</label>
                        <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                    </div>
                    <div>
                        <label class="form-label" for="role">Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <?php foreach (available_roles(true) as $value => $label): ?>
                                <option value="<?= e($value) ?>"><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="panel-card card h-100">
            <div class="card-body">
                <h2 class="h5 mb-1">User Directory</h2>
                <p class="page-subtitle mb-3">Update access roles or remove obsolete accounts</p>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= e($user['name']) ?></td>
                                <td><?= e($user['email']) ?></td>
                                <td>
                                    <form method="post" class="d-flex gap-2 align-items-center">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?= e((string) $user['id']) ?>">
                                        <select class="form-select form-select-sm" name="role">
                                            <?php foreach (available_roles(true) as $value => $label): ?>
                                                <option value="<?= e($value) ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-outline-secondary btn-sm">Save</button>
                                    </form>
                                </td>
                                <td><?= e(date('d M Y', strtotime((string) $user['created_at']))) ?></td>
                                <td>
                                    <?php if ((int) $user['id'] !== (int) current_user()['id']): ?>
                                        <a href="<?= e(url('admin/manage_users.php?delete=' . $user['id'])) ?>" class="btn btn-outline-danger btn-sm" data-confirm-message="Delete this user?">Delete</a>
                                    <?php else: ?>
                                        <span class="badge text-bg-light">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
