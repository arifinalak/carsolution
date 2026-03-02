<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        set_flash('Username and password are required.', 'error');
        redirect('/admin/login.php');
    }

    $stmt = db()->prepare('SELECT id, username, password_hash FROM admins WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        set_flash('Invalid credentials.', 'error');
        redirect('/admin/login.php');
    }

    $_SESSION['admin_id'] = $admin['id'];
    $_SESSION['admin_username'] = $admin['username'];
    set_flash('Welcome back, ' . $admin['username'] . '.', 'success');

    redirect('/admin/dashboard.php');
}

$title = 'Admin Login';
require __DIR__ . '/../includes/header.php';
?>

<section class="card small-card">
    <h2>Admin Login</h2>
    <form method="post">
        <label>Username
            <input type="text" name="username" required>
        </label>

        <label>Password
            <input type="password" name="password" required>
        </label>

        <button type="submit">Login</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php';
