<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

if (current_admin_user()) {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (attempt_admin_login($email, $password)) {
        header('Location: admin_dashboard.php');
        exit();
    }

    $error = 'Invalid admin credentials.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | KitaKits</title>
    <?php kk_render_favicon('admin'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page-body">
    <?php kk_render_header(['section' => 'admin', 'active' => 'login']); ?>
    <?php kk_render_breadcrumbs('admin', [['label' => 'Admin Login']]); ?>

    <main class="auth-page-main">
        <section class="auth-page-heading">
            <h1>Welcome back</h1>
            <p>Log in to manage missions, bookings, patients, and content.</p>
        </section>

        <section class="auth-card figma-auth-card" aria-label="Admin login">
        <a href="../index.php" class="btn-back">
            Back to Patient Page
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="email">Admin Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@kitakits.ph'); ?>"
                placeholder="admin@kitakits.ph"
                autocomplete="username"
                required
            >

            <label for="password">Password</label>
            <div class="password-field">
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="admin2025"
                    autocomplete="current-password"
                    required
                >
                <button type="button" aria-label="Show password">Show</button>
            </div>

            <button type="submit" class="btn-primary auth-submit">Log In</button>

            <p class="form-hint">Demo admin: admin@kitakits.ph / admin2025</p>
        </form>
        </section>

        <section class="demo-credentials" aria-label="Demo credentials">
            <strong>Demo credentials</strong>
            <span>Admin: admin@kitakits.ph / admin2025</span>
            <span>Legacy local: admin@kitakits.local / admin123</span>
        </section>
    </main>

    <?php kk_render_footer('admin', ['mode' => 'public']); ?>
    <script src="../assets/js/password-toggle.js"></script>
</body>
</html>
