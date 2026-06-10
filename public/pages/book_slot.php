<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_schema_helpers.php');
require_once(__DIR__ . '/../api/_auth.php');

function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim($contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^\+?[0-9]{7,15}$/', $contact) === 1;
}

$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_patient_id = require_patient_page('login.php');
$current_profile = null;

if ($current_patient_id) {
    $profile_stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :patient_id LIMIT 1");
    $profile_stmt->execute([':patient_id' => $current_patient_id]);
    $current_profile = $profile_stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($mission_id === 0) {
    header("Location: patient_portal.php");
    exit();
}

$sql  = "SELECT * FROM missions WHERE mission_id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    header("Location: patient_portal.php");
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    $patient_name   = trim($_POST['patient_name']);
    $contact_number = normalize_contact_number($_POST['contact_number']);
    $companion_count = isset($_POST['companion_count']) ? (int)$_POST['companion_count'] : 0;
    $patient_notes = trim($_POST['patient_notes'] ?? '');
    $profile = [
        'user_id' => current_patient_user_id(),
        'email' => trim($_POST['email'] ?? ''),
        'birthdate' => trim($_POST['birthdate'] ?? ''),
        'sex' => trim($_POST['sex'] ?? ''),
        'full_address' => trim($_POST['full_address'] ?? ''),
        'barangay' => trim($_POST['barangay'] ?? ''),
        'city' => trim($_POST['city'] ?? ''),
        'province' => trim($_POST['province'] ?? '')
    ];

    if (empty($patient_name) || empty($contact_number)) {
        $error = 'Please provide both your name and contact number.';
    } elseif (strlen($patient_name) > 120) {
        $error = 'Full name must be 120 characters or fewer.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter a valid contact number with 7 to 15 digits.';
    } elseif ($profile['email'] !== '' && !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif ($companion_count < 0 || $companion_count > 10) {
        $error = 'Companion count must be between 0 and 10.';
    } elseif ($mission['mission_date'] < date('Y-m-d')) {
        $error = 'This mission date has already passed.';
    } else {
        try {
            $conn->beginTransaction();

            $lock = $conn->prepare("SELECT mission_id, available_slots, mission_date, mission_status FROM missions WHERE mission_id = :id FOR UPDATE");
            $lock->execute([':id' => $mission_id]);
            $locked_mission = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$locked_mission) {
                $conn->rollBack();
                header("Location: ../index.php");
                exit();
            }

            if ($locked_mission['mission_date'] < date('Y-m-d')) {
                $conn->rollBack();
                $error = 'This mission date has already passed.';
            } elseif ($locked_mission['mission_status'] !== 'open') {
                $conn->rollBack();
                $error = 'This mission is not accepting booking requests.';
            } elseif ((int)$locked_mission['available_slots'] <= 0) {
                $conn->rollBack();
                $error = 'This mission is already fully booked.';
            } else {
                $patient = find_or_create_patient($conn, $patient_name, $contact_number, $profile);

                $existing = $conn->prepare("SELECT booking_id, booking_status
                                            FROM bookings
                                            WHERE patient_id = :patient_id AND mission_id = :mission_id
                                            LIMIT 1");
                $existing->execute([
                    ':patient_id' => $patient['patient_id'],
                    ':mission_id' => $mission_id
                ]);
                $booking = $existing->fetch(PDO::FETCH_ASSOC);

                if ($booking && !in_array($booking['booking_status'], ['cancelled', 'rejected'], true)) {
                    $conn->rollBack();
                    $error = 'You already have a booking request for this mission. Please check your Patient Portal.';
                } else {
                    if ($booking) {
                        $stmt_insert = $conn->prepare("UPDATE bookings
                                                       SET patient_name = :patient_name,
                                                           contact_number = :contact_number,
                                                           booking_status = 'booked',
                                                           companion_count = :companion_count,
                                                           patient_notes = :patient_notes,
                                                           cancelled_at = NULL,
                                                           completed_at = NULL,
                                                           confirmed_at = NULL
                                                       WHERE booking_id = :booking_id");
                        $stmt_insert->execute([
                            ':patient_name' => $patient_name,
                            ':contact_number' => $contact_number,
                            ':companion_count' => $companion_count,
                            ':patient_notes' => $patient_notes ?: null,
                            ':booking_id' => $booking['booking_id']
                        ]);
                    } else {
                        $stmt_insert = $conn->prepare("INSERT INTO bookings
                            (mission_id, patient_id, patient_name, contact_number, booking_status, companion_count, patient_notes)
                            VALUES
                            (:mission_id, :patient_id, :patient_name, :contact_number, 'booked', :companion_count, :patient_notes)");
                        $stmt_insert->execute([
                            ':mission_id' => $mission_id,
                            ':patient_id' => $patient['patient_id'],
                            ':patient_name' => $patient_name,
                            ':contact_number' => $contact_number,
                            ':companion_count' => $companion_count,
                            ':patient_notes' => $patient_notes ?: null
                        ]);
                    }

                    $conn->commit();
                    header("Location: patient_portal.php?requested=1");
                    exit();
                }
            }

        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'Error processing booking: ' . $e->getMessage();
        }
    }

    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Slot | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Book Your Surgery Slot</h1>
                    <p>Reserve your spot at the cataract mission</p>
                </div>
                <div class="header-actions" aria-label="Primary navigation">
                    <nav class="header-nav">
                        <a href="patient_portal.php">Patient Portal</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                        <a href="about_cataracts.php">About Cataracts</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="patient_portal.php#portal-missions" class="btn-back">
            <span>&larr; </span>
            Back to Portal Missions
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error">Warning: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($current_profile): ?>
            <div class="alert alert-success">You are booking as <?php echo htmlspecialchars(trim(($current_profile['first_name'] ?? '') . ' ' . ($current_profile['last_name'] ?? ''))); ?>. This request will appear in your patient dashboard.</div>
        <?php else: ?>
            <div class="alert alert-info">Already have an account? <a href="login.php">Log in</a> first so this booking appears in your patient dashboard.</div>
        <?php endif; ?>

        <div class="booking-summary">
            <strong>Mission Details:</strong><br><br>
            <strong>Mission:</strong> <?php echo htmlspecialchars($mission['mission_name'] ?? $mission['organizer_name']); ?><br>
            <strong>Organizer:</strong> <?php echo htmlspecialchars($mission['organizer_name']); ?><br>
            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($mission['mission_date'])); ?><br>
            <strong>Location:</strong> <?php echo htmlspecialchars($mission['full_address'] ?: $mission['location']); ?><br>
            <strong>Remaining Slots:</strong> <span id="remainingSlotsValue" class="slot-count"><?php echo htmlspecialchars($mission['available_slots']); ?></span>
        </div>

        <div id="bookingStatus" class="status-message" role="status" hidden></div>
        <div id="bookingSuccessActions" class="booking-actions success-actions" hidden></div>
        <div id="bookingLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
        <div id="bookingClosedMessage" class="alert alert-error" hidden></div>

        <?php if ((int)$mission['available_slots'] > 0 && $mission['mission_date'] >= date('Y-m-d')): ?>
            <form method="POST" action="" id="bookingForm">
                <input type="hidden" name="mission_id" value="<?php echo htmlspecialchars($mission_id); ?>">
                <label for="patient_name">Full Name *</label>
                <input
                    type="text"
                    id="patient_name"
                    name="patient_name"
                    value="<?php echo htmlspecialchars($_POST['patient_name'] ?? trim(implode(' ', array_filter([
                        $current_profile['first_name'] ?? '',
                        $current_profile['middle_name'] ?? '',
                        $current_profile['last_name'] ?? '',
                        $current_profile['suffix'] ?? ''
                    ])))); ?>"
                    placeholder="Enter your full name"
                    maxlength="120"
                    required
                    aria-label="Patient full name"
                >

                <label for="contact_number">Contact Number *</label>
                <input
                    type="text"
                    id="contact_number"
                    name="contact_number"
                    value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ($current_profile['contact_number'] ?? '')); ?>"
                    placeholder="e.g., 09123456789"
                    pattern="[\+0-9\s\-\(\)]{7,20}"
                    required
                    aria-label="Patient contact number"
                >

                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ($current_profile['email'] ?? '')); ?>"
                    placeholder="Optional"
                    maxlength="150"
                >

                <div class="form-grid">
                    <div>
                        <label for="birthdate">Birthdate</label>
                        <input
                            type="date"
                            id="birthdate"
                            name="birthdate"
                            value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ($current_profile['birthdate'] ?? '')); ?>"
                        >
                    </div>
                    <div>
                        <label for="sex">Sex</label>
                        <select id="sex" name="sex">
                            <?php $selected_sex = $_POST['sex'] ?? ($current_profile['sex'] ?? ''); ?>
                            <option value="">Prefer not to say</option>
                            <option value="Male" <?php echo $selected_sex === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo $selected_sex === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo $selected_sex === 'Other' ? 'selected' : ''; ?>>Other</option>
                            <option value="Prefer not to say" <?php echo $selected_sex === 'Prefer not to say' ? 'selected' : ''; ?>>Prefer not to say</option>
                        </select>
                    </div>
                </div>

                <label for="full_address">Patient Address</label>
                <input
                    type="text"
                    id="full_address"
                    name="full_address"
                    value="<?php echo htmlspecialchars($_POST['full_address'] ?? ($current_profile['full_address'] ?? '')); ?>"
                    placeholder="House no., street, barangay, city"
                    maxlength="255"
                >

                <div class="form-grid form-grid-3">
                    <div>
                        <label for="barangay">Barangay</label>
                        <input
                            type="text"
                            id="barangay"
                            name="barangay"
                            value="<?php echo htmlspecialchars($_POST['barangay'] ?? ($current_profile['barangay'] ?? '')); ?>"
                            maxlength="100"
                        >
                    </div>
                    <div>
                        <label for="city">City / Area</label>
                        <input
                            type="text"
                            id="city"
                            name="city"
                            value="<?php echo htmlspecialchars($_POST['city'] ?? ($current_profile['city'] ?? '')); ?>"
                            maxlength="100"
                        >
                    </div>
                    <div>
                        <label for="province">Province</label>
                        <input
                            type="text"
                            id="province"
                            name="province"
                            value="<?php echo htmlspecialchars($_POST['province'] ?? ($current_profile['province'] ?? '')); ?>"
                            maxlength="100"
                        >
                    </div>
                </div>

                <label for="companion_count">Companion Count</label>
                <input
                    type="number"
                    id="companion_count"
                    name="companion_count"
                    value="<?php echo isset($_POST['companion_count']) ? htmlspecialchars($_POST['companion_count']) : '0'; ?>"
                    min="0"
                    max="10"
                >

                <label for="patient_notes">Notes for Coordinator</label>
                <textarea
                    id="patient_notes"
                    name="patient_notes"
                    rows="3"
                    placeholder="Optional accessibility, schedule, or companion notes"
                ><?php echo isset($_POST['patient_notes']) ? htmlspecialchars($_POST['patient_notes']) : ''; ?></textarea>

                <button type="submit" name="submit" id="bookingSubmit">Submit Booking Request</button>
            </form>
        <?php else: ?>
            <div class="alert alert-error">This mission is not accepting bookings.</div>
        <?php endif; ?>

        <div class="important-info">
            <h3>Important Information</h3>
            <ul>
                <li>Your booking starts as <strong>booked</strong> and must be confirmed by an admin before the slot is secured</li>
                <li>Your patient profile is linked to this booking for coordinator cross-checking</li>
                <li>Please arrive 30 minutes early on the mission date</li>
                <li>Bring your ID and any relevant medical documents</li>
                <li>Complete the pre-screening form from your Patient Portal before mission day</li>
                <li>If you cannot attend, please notify the organizer as soon as possible</li>
            </ul>
        </div>
    </div>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/book-slot.js"></script>
</body>
</html>
