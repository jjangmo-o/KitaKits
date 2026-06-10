<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-content">
                <h1>Admin Login</h1>
                <p>Role-based access for coordinators</p>
            </div>
        </div>
    </header>

    <main class="container auth-container form-workspace">
        <a href="../index.php" class="btn-back">
            <span>&larr; </span>
            Back to Patient Page
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="auth-form">
            <label for="email">Admin Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo htmlspecialchars($_POST['email'] ?? 'admin@kitakits.local'); ?>"
                required
            >

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="admin123"
                required
            >

            <button type="submit">Log In</button>

            <p class="form-hint">Demo admin: admin@kitakits.local / admin123</p>
        </form>
    </main>
</body>
</html>
