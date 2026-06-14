<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../includes/layout.php');

if (current_patient_id()) {
    header('Location: patient_portal.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $contact_number = normalize_contact_number($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $city = trim($_POST['city'] ?? '');

    if ($first_name === '' || $last_name === '' || $contact_number === '' || $password === '') {
        $error = 'First name, last name, contact number, and password are required.';
    } elseif (!patient_name_parts_are_valid($first_name, '', $last_name)) {
        $error = 'First and last name must each be 30 characters or fewer and 65 characters or fewer combined.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter an 11-digit mobile number starting with 09.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $conn->beginTransaction();

            if ($email !== '') {
                $email_check = $conn->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
                $email_check->execute([':email' => $email]);

                if ($email_check->fetch(PDO::FETCH_ASSOC)) {
                    $conn->rollBack();
                    $error = 'That email is already registered.';
                }
            }

            if ($error === '') {
                $user_check = $conn->prepare("SELECT user_id, password_hash
                                              FROM users
                                              WHERE role = 'patient' AND contact_number = :contact
                                              LIMIT 1");
                $user_check->execute([':contact' => $contact_number]);
                $existing_user = $user_check->fetch(PDO::FETCH_ASSOC);

                if ($existing_user && !empty($existing_user['password_hash'])) {
                    $conn->rollBack();
                    $error = 'An account already exists for this contact number. Please log in instead.';
                } else {
                    $full_name = trim($first_name . ' ' . $last_name);
                    $hash = password_hash($password, PASSWORD_DEFAULT);

                    if ($existing_user) {
                        $user_id = (int)$existing_user['user_id'];
                        $update_user = $conn->prepare("UPDATE users
                                                       SET full_name = :full_name,
                                                           email = :email,
                                                           contact_number = :contact,
                                                           password_hash = :password_hash,
                                                           is_active = 1
                                                       WHERE user_id = :user_id");
                        $update_user->execute([
                            ':full_name' => $full_name,
                            ':email' => $email ?: null,
                            ':contact' => $contact_number,
                            ':password_hash' => $hash,
                            ':user_id' => $user_id
                        ]);
                    } else {
                        $insert_user = $conn->prepare("INSERT INTO users
                            (full_name, email, contact_number, password_hash, role, is_active)
                            VALUES
                            (:full_name, :email, :contact, :password_hash, 'patient', 1)");
                        $insert_user->execute([
                            ':full_name' => $full_name,
                            ':email' => $email ?: null,
                            ':contact' => $contact_number,
                            ':password_hash' => $hash
                        ]);
                        $user_id = (int)$conn->lastInsertId();
                    }

                    $patient_check = $conn->prepare("SELECT patient_id, user_id
                                                     FROM patients
                                                     WHERE contact_number = :contact
                                                     ORDER BY patient_id ASC
                                                     LIMIT 1");
                    $patient_check->execute([':contact' => $contact_number]);
                    $existing_patient = $patient_check->fetch(PDO::FETCH_ASSOC);

                    if ($existing_patient) {
                        if (!empty($existing_patient['user_id']) && (int)$existing_patient['user_id'] !== $user_id) {
                            $conn->rollBack();
                            $error = 'This contact number is already linked to another patient profile.';
                        } else {
                            $update_patient = $conn->prepare("UPDATE patients
                                                              SET user_id = :user_id,
                                                                  first_name = :first_name,
                                                                  last_name = :last_name,
                                                                  email = :email,
                                                                  birthdate = :birthdate,
                                                                  city = :city
                                                              WHERE patient_id = :patient_id");
                            $update_patient->execute([
                                ':user_id' => $user_id,
                                ':first_name' => $first_name,
                                ':last_name' => $last_name,
                                ':email' => $email ?: null,
                                ':birthdate' => $birthdate ?: null,
                                ':city' => $city ?: null,
                                ':patient_id' => (int)$existing_patient['patient_id']
                            ]);
                        }
                    } else {
                        $insert_patient = $conn->prepare("INSERT INTO patients
                            (user_id, first_name, last_name, contact_number, email, birthdate, city)
                            VALUES
                            (:user_id, :first_name, :last_name, :contact, :email, :birthdate, :city)");
                        $insert_patient->execute([
                            ':user_id' => $user_id,
                            ':first_name' => $first_name,
                            ':last_name' => $last_name,
                            ':contact' => $contact_number,
                            ':email' => $email ?: null,
                            ':birthdate' => $birthdate ?: null,
                            ':city' => $city ?: null
                        ]);
                    }

                    if ($error === '') {
                        $conn->commit();
                        session_regenerate_id(true);
                        $_SESSION['patient_user_id'] = $user_id;
                        header('Location: patient_portal.php?registered=1');
                        exit();
                    }
                }
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Unable to create your account right now. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="auth-page-body">
    <?php kk_render_header(['section' => 'pages', 'active' => 'register']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Register']]); ?>

    <main class="auth-page-main">
        <section class="auth-page-heading">
            <h1>Create an account</h1>
            <p>Register to book surgery slots and manage your appointments.</p>
        </section>

        <section class="auth-card register-card figma-auth-card figma-auth-card-wide" aria-label="Patient registration">
            <div class="auth-card-header">
                <span class="eyebrow">Patient sign up</span>
                <h2>Create account</h2>
                <p>Use accurate patient details so coordinators can cross-check your booking.</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-grid">
                    <div>
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" maxlength="30" autocomplete="given-name" required>
                    </div>
                    <div>
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" maxlength="30" autocomplete="family-name" required>
                    </div>
                </div>

                <label for="contact_number">Contact Number *</label>
                <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>" placeholder="09XXXXXXXXX" pattern="09[0-9]{9}" minlength="11" maxlength="11" inputmode="numeric" autocomplete="tel" required>

                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="you@example.com" maxlength="150" autocomplete="email">

                <div class="form-grid">
                    <div>
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="city">City / Area</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" maxlength="100">
                    </div>
                </div>

                <div class="form-grid">
                    <div>
                        <label for="password">Password *</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" required>
                            <button type="button" aria-label="Show password">Show</button>
                        </div>
                    </div>
                    <div>
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                            <button type="button" data-password-label="confirmation password" aria-label="Show confirmation password">Show</button>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn-primary auth-submit">Create Account</button>
            </form>

            <div class="auth-switch">
                <span>Already have an account?</span>
                <a href="login.php">Log in</a>
            </div>
        </section>
    </main>

    <?php kk_render_footer('pages'); ?>
    <script src="../assets/js/password-toggle.js"></script>
</body>
</html>
