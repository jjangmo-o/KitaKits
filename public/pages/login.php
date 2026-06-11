<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');

if (current_patient_id()) {
    header('Location: patient_portal.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if (attempt_patient_login($identifier, $password)) {
        header('Location: patient_portal.php');
        exit();
    }

    $error = 'We could not sign you in. Check your email or contact number and password.';
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
    <header class="site-header">
        <div class="container site-header-inner">
            <a href="../index.php" class="site-brand">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
                <span>KitaKits</span>
            </a>
            <nav class="site-nav" aria-label="Patient navigation">
                <a href="../index.php">Home</a>
                <a href="patient_guide.php">Patient Guide</a>
                <a href="faq.php">FAQ</a>
                <a href="about_cataracts.php">About Cataracts</a>
            </nav>
            <div class="site-actions">
                <a href="login.php" class="site-link-active">Log In</a>
                <a href="register.php" class="site-primary">Register</a>
            </div>
        </div>
    </header>

    <main class="auth-page-main">
        <section class="auth-page-heading">
            <h1>Welcome back</h1>
            <p>Log in to manage your bookings and profile.</p>
        </section>

        <section class="auth-card figma-auth-card" aria-label="Patient login">
            <div class="auth-card-header">
                <span class="eyebrow">Patient login</span>
                <h2>Sign in</h2>
                <p>Use the email or contact number connected to your patient record.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="identifier">Email or Contact Number</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                    placeholder="e.g., 09111111111"
                    autocomplete="username"
                    required
                >

                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    required
                >

                <button type="submit" class="btn-primary auth-submit">Log In</button>
            </form>

            <div class="auth-switch">
                <span>No login details yet?</span>
                <a href="register.php">Sign up</a>
            </div>

            <p class="inline-note auth-note">Demo patient: 09111111111 / patient123</p>
        </section>

        <section class="demo-credentials" aria-label="Demo credentials">
            <strong>Demo credentials</strong>
            <span>Patient: 09111111111 / patient123</span>
            <span>Admin: admin@kitakits.local / admin123</span>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <strong>KitaKits</strong>
            <span>Connecting patients with free cataract surgery missions across the Philippines.</span>
        </div>
    </footer>
</body>
</html>
