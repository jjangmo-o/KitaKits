<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

$next = trim($_GET['next'] ?? $_POST['next'] ?? '');
$patient_destination = preg_match('/^book_slot\.php\?id=[1-9][0-9]*$/', $next)
    ? $next
    : 'patient_portal.php';

if (current_patient_id()) {
    header('Location: ' . $patient_destination);
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (attempt_admin_login($identifier, $password)) {
        header('Location: ../admin/admin_dashboard.php');
        exit();
    }

    if (attempt_patient_login($identifier, $password)) {
        header('Location: ' . $patient_destination);
        exit();
    }

    $error = 'Incorrect email, contact number, or password. Please try again.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page-body">
    <?php kk_render_header(['section' => 'pages', 'active' => 'login']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Log In']]); ?>

    <main class="auth-page-main">
        <section class="auth-page-heading">
            <h1>Welcome back</h1>
            <p>Log in to manage your bookings and profile.</p>
        </section>

        <section class="auth-card figma-auth-card" aria-label="Login">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="next" value="<?php echo htmlspecialchars($next); ?>">
                <label for="identifier">Email address</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                    placeholder="you@example.com"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>
                <div class="password-field">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        required
                    >
                    <button type="button" aria-label="Show password" tabindex="-1">⊙</button>
                </div>

                <button type="submit" class="btn-primary auth-submit">Log In</button>
            </form>

            <div class="auth-switch">
                <span>Don't have an account?</span>
                <a href="register.php">Register here</a>
            </div>
        </section>

        <section class="demo-credentials" aria-label="Demo credentials">
            <strong>Demo credentials</strong>
            <span>Patient: <b>maria@example.com / password123</b></span>
            <span>Admin: <b>admin@kitakits.ph / admin2025</b></span>
        </section>
    </main>

    <?php kk_render_footer('pages'); ?>
    <script src="../assets/js/password-toggle.js"></script>
</body>
</html>
