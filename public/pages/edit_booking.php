<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_schema_helpers.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

$patient_id = require_patient_page('login.php');

function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim($contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^\+?[0-9]{7,15}$/', $contact) === 1;
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_contact = isset($_GET['contact']) ? normalize_contact_number($_GET['contact']) : '';
$error = '';
$booking = null;

if ($booking_id <= 0) {
    header("Location: patient_portal.php");
    exit();
}

$sql = "SELECT b.*, p.email, p.birthdate, p.sex, p.full_address, p.barangay, p.city, p.province,
               m.mission_name, m.organizer_name, m.mission_date, m.location, m.full_address AS mission_address
        FROM bookings b
        JOIN patients p ON p.patient_id = b.patient_id
        JOIN missions m ON b.mission_id = m.mission_id
        WHERE b.booking_id = :id AND b.patient_id = :patient_id
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $booking_id,
    ':patient_id' => $patient_id
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: patient_portal.php");
    exit();
}

$current_contact = normalize_contact_number($booking['contact_number']);

if (isset($_POST['submit'])) {
    $patient_name = trim($_POST['patient_name']);
    $contact_number = normalize_contact_number($_POST['contact_number']);
    $email = trim($_POST['email'] ?? '');
    $full_address = trim($_POST['full_address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $province = trim($_POST['province'] ?? '');

    if (empty($patient_name) || empty($contact_number)) {
        $error = 'Please provide both your name and contact number.';
    } elseif (strlen($patient_name) > 120) {
        $error = 'Full name must be 120 characters or fewer.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter a valid contact number with 7 to 15 digits.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        try {
            [$first_name, $last_name] = split_patient_name($patient_name);
            $conn->beginTransaction();

            $patient_update = $conn->prepare("UPDATE patients
                                              SET first_name = :first_name,
                                                  last_name = :last_name,
                                                  contact_number = :contact_number,
                                                  email = :email,
                                                  full_address = :full_address,
                                                  city = :city,
                                                  barangay = :barangay,
                                                  province = :province
                                              WHERE patient_id = :patient_id");
            $patient_update->execute([
                ':first_name' => $first_name,
                ':last_name' => $last_name,
                ':contact_number' => $contact_number,
                ':email' => $email ?: null,
                ':full_address' => $full_address ?: null,
                ':city' => $city ?: null,
                ':barangay' => $barangay ?: null,
                ':province' => $province ?: null,
                ':patient_id' => $booking['patient_id']
            ]);

            $update = $conn->prepare("UPDATE bookings
                                      SET patient_name = :patient_name,
                                          contact_number = :contact_number
                                      WHERE booking_id = :id AND patient_id = :patient_id");
            $update->execute([
                ':patient_name' => $patient_name,
                ':contact_number' => $contact_number,
                ':id' => $booking_id,
                ':patient_id' => $patient_id
            ]);

            $conn->commit();
            header("Location: patient_portal.php?saved=1#portal-bookings");
            exit();

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Error updating booking: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="page-edit-booking">
    <?php kk_render_header(['section' => 'pages', 'active' => 'portal']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Patient Dashboard', 'href' => 'patient_portal.php'], ['label' => 'Edit Booking']]); ?>

    <main class="container workflow-page workflow-form-page">
        <a href="patient_portal.php#portal-bookings" class="btn-back">
            <span>&larr; </span>
            Back to Portal Bookings
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error">Warning: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="workflow-hero workflow-hero-compact">
            <span class="workflow-hero-icon" aria-hidden="true">
                <img src="../assets/icons/file-text.svg" alt="">
            </span>
            <div>
                <span class="workflow-kicker">Booking details</span>
                <h1>Edit Your Booking</h1>
                <p>Update the contact information connected to this mission request.</p>
            </div>
        </section>

        <div class="workflow-form-layout">
            <aside class="booking-summary workflow-summary-card">
                <div class="workflow-card-heading">
                    <img src="../assets/icons/calendar-purple.svg" alt="" aria-hidden="true">
                    <div>
                        <h2>Mission Summary</h2>
                        <p>Your selected outreach schedule</p>
                    </div>
                </div>
                <dl class="workflow-summary-list">
                    <div><dt>Mission</dt><dd><?php echo htmlspecialchars($booking['mission_name'] ?: $booking['organizer_name']); ?></dd></div>
                    <div><dt>Date</dt><dd><?php echo date('F j, Y', strtotime($booking['mission_date'])); ?></dd></div>
                    <div><dt>Location</dt><dd><?php echo htmlspecialchars($booking['mission_address'] ?: $booking['location']); ?></dd></div>
                    <div><dt>Status</dt><dd><span class="status-pill status-<?php echo htmlspecialchars(str_replace('_', '-', $booking['booking_status'])); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $booking['booking_status']))); ?></span></dd></div>
                </dl>
            </aside>

        <form method="POST" action="" class="workflow-form-card">
            <div class="workflow-card-heading">
                <img src="../assets/icons/users-purple.svg" alt="" aria-hidden="true">
                <div>
                    <h2>Patient Information</h2>
                    <p>Fields marked by the browser as required must be completed.</p>
                </div>
            </div>
            <label for="patient_name">Full Name</label>
            <input
                type="text"
                id="patient_name"
                name="patient_name"
                value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : htmlspecialchars($booking['patient_name']); ?>"
                maxlength="120"
                required
                aria-label="Patient full name"
            >

            <label for="contact_number">Contact Number</label>
            <input
                type="text"
                id="contact_number"
                name="contact_number"
                value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : htmlspecialchars($booking['contact_number']); ?>"
                pattern="[\+0-9\s\-\(\)]{7,20}"
                required
                aria-label="Patient contact number"
            >

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : htmlspecialchars($booking['email'] ?? ''); ?>"
                maxlength="150"
            >

            <label for="full_address">Patient Address</label>
            <input
                type="text"
                id="full_address"
                name="full_address"
                value="<?php echo isset($_POST['full_address']) ? htmlspecialchars($_POST['full_address']) : htmlspecialchars($booking['full_address'] ?? ''); ?>"
                maxlength="255"
            >

            <div class="form-grid form-grid-3">
                <div>
                    <label for="barangay">Barangay</label>
                    <input
                        type="text"
                        id="barangay"
                        name="barangay"
                        value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : htmlspecialchars($booking['barangay'] ?? ''); ?>"
                        maxlength="100"
                    >
                </div>
                <div>
                    <label for="city">City / Area</label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : htmlspecialchars($booking['city'] ?? ''); ?>"
                        maxlength="100"
                    >
                </div>
                <div>
                    <label for="province">Province</label>
                    <input
                        type="text"
                        id="province"
                        name="province"
                        value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : htmlspecialchars($booking['province'] ?? ''); ?>"
                        maxlength="100"
                    >
                </div>
            </div>

            <button type="submit" name="submit">
                <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                <span>Update Booking</span>
            </button>
        </form>
        </div>
    </main>
    <?php kk_render_footer('pages'); ?>
</body>
</html>
