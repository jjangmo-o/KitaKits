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
<body class="auth-flow-body">
    <main class="auth-flow-shell">
        <section class="auth-flow-visual" aria-label="Patient portal preview">
            <a href="../index.php" class="auth-brand">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
                <span>KitaKits</span>
            </a>
            <div class="auth-preview">
                <span class="eyebrow">Next step</span>
                <h1>Log in to continue to your patient portal.</h1>
                <p>Missions, booking requests, pre-screening, and printable slips live inside your dashboard.</p>
                <div class="auth-preview-steps">
                    <span class="active">Login</span>
                    <span>Find Mission</span>
                    <span>Book</span>
                    <span>Print</span>
                </div>
            </div>
        </section>

        <section class="auth-card" aria-label="Patient login">
            <div class="auth-card-header">
                <span class="eyebrow">Patient login</span>
                <h2>Welcome back</h2>
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
    </main>
</body>
</html>
