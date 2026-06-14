<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_schema_helpers.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../includes/layout.php');

function kk_booking_time_range($mission)
{
    if (!empty($mission['start_time']) && !empty($mission['end_time'])) {
        return date('g:i A', strtotime($mission['start_time'])) . ' - ' . date('g:i A', strtotime($mission['end_time']));
    }

    if (!empty($mission['start_time'])) {
        return date('g:i A', strtotime($mission['start_time']));
    }

    return 'Time to be announced';
}

$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_patient_id = current_patient_id();
$admin_preview = !$current_patient_id && current_admin_user();
$current_profile = null;

if ($current_patient_id) {
    $profile_stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :patient_id LIMIT 1");
    $profile_stmt->execute([':patient_id' => $current_patient_id]);
    $current_profile = $profile_stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($mission_id === 0) {
    header('Location: ' . ($current_patient_id ? 'patient_portal.php' : '../index.php#missions'));
    exit();
}

$sql  = "SELECT * FROM missions WHERE mission_id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    header('Location: ' . ($current_patient_id ? 'patient_portal.php' : '../index.php#missions'));
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    if (!$current_patient_id) {
        header('Location: login.php?next=' . urlencode('book_slot.php?id=' . $mission_id));
        exit();
    }

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
    } elseif (text_length($patient_name) > 100) {
        $error = 'Full name must be 100 characters or fewer.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter an 11-digit mobile number starting with 09.';
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
                $patient = update_patient_booking_profile(
                    $conn,
                    $current_patient_id,
                    $patient_name,
                    $contact_number,
                    $profile
                );

                if (!$patient) {
                    $conn->rollBack();
                    $error = 'Patient profile not found.';
                }

                if ($error === '') {
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

<?php
$patient_name_value = $_POST['patient_name'] ?? trim(implode(' ', array_filter([
    $current_profile['first_name'] ?? '',
    $current_profile['middle_name'] ?? '',
    $current_profile['last_name'] ?? '',
    $current_profile['suffix'] ?? ''
])));
$selected_sex = $_POST['sex'] ?? ($current_profile['sex'] ?? '');
$date_label = date('F j, Y', strtotime($mission['mission_date']));
$time_label = kk_booking_time_range($mission);
$location_label = $mission['full_address'] ?: $mission['location'];
$is_bookable = (int)$mission['available_slots'] > 0 && $mission['mission_date'] >= date('Y-m-d') && $mission['mission_status'] === 'open';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Slot | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="make-ui page-book-slot">
    <?php kk_render_header(['section' => 'pages', 'active' => '']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Book Slot', 'href' => '../index.php#missions'], ['label' => 'm' . $mission_id]]); ?>

    <main class="container mission-flow-page">
        <a href="../index.php#missions" class="btn-back">
            <span>&larr;</span>
            Back to Available Missions
        </a>

        <section class="booking-page-heading">
            <h1>Book a Slot</h1>
            <p>Complete both steps to reserve your surgery slot.</p>
        </section>

        <nav class="booking-stepper" aria-label="Booking progress">
            <span class="is-active" data-step-indicator="1"><b>1</b> Your Info</span>
            <i></i>
            <span data-step-indicator="2"><b>2</b> Medical Intake</span>
        </nav>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!$current_patient_id): ?>
            <div class="preview-access-notice">
                <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                <div>
                    <strong><?php echo $admin_preview ? 'Patient preview mode' : 'Preview only'; ?></strong>
                    <span>You can review this booking flow, but a patient login is required to submit a request.</span>
                </div>
                <a href="login.php?next=<?php echo urlencode('book_slot.php?id=' . $mission_id); ?>" class="btn-primary compact-button">Patient Log In</a>
            </div>
        <?php endif; ?>

        <div id="bookingStatus" class="status-message" role="status" hidden></div>
        <div id="bookingSuccessActions" class="booking-actions success-actions" hidden></div>
        <div id="bookingLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
        <div id="bookingClosedMessage" class="alert alert-error" hidden></div>

        <section class="book-slot-layout">
            <div class="booking-form-card">
                <?php if ($is_bookable): ?>
                    <form method="POST" action="" id="bookingForm">
                        <input type="hidden" name="mission_id" value="<?php echo htmlspecialchars($mission_id); ?>">
                        <fieldset class="booking-preview-fieldset" <?php echo $current_patient_id ? '' : 'disabled'; ?>>

                        <div class="booking-step-panel" data-step-panel="1">
                            <h2>Your Information</h2>
                            <p>Enter the name and contact number you'll use to retrieve this booking.</p>

                            <label for="patient_name">Full Name <span>*</span></label>
                            <input
                                type="text"
                                id="patient_name"
                                name="patient_name"
                                value="<?php echo htmlspecialchars($patient_name_value); ?>"
                                placeholder="e.g. Maria Santos"
                                maxlength="100"
                                required
                            >

                            <label for="contact_number">Mobile Number <span>*</span></label>
                            <input
                                type="tel"
                                id="contact_number"
                                name="contact_number"
                                value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ($current_profile['contact_number'] ?? '')); ?>"
                                placeholder="e.g. 09171234567"
                                pattern="09[0-9]{9}"
                                minlength="11"
                                maxlength="11"
                                inputmode="numeric"
                                required
                            >
                            <small>Save this number - it's required to look up your booking.</small>

                            <label for="email">Email Address <span>*</span></label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ($current_profile['email'] ?? '')); ?>"
                                placeholder="optional"
                                maxlength="150"
                            >

                            <button type="button" class="btn-primary booking-next-step" data-next-step="2">
                                Continue to Medical Intake
                                <span>&rsaquo;</span>
                            </button>
                        </div>

                        <div class="booking-step-panel" data-step-panel="2" hidden>
                            <h2>Medical Intake</h2>
                            <p>Add the extra details coordinators use for screening and day-of support.</p>

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
                                    <input type="text" id="barangay" name="barangay" value="<?php echo htmlspecialchars($_POST['barangay'] ?? ($current_profile['barangay'] ?? '')); ?>" maxlength="100">
                                </div>
                                <div>
                                    <label for="city">City / Area</label>
                                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($_POST['city'] ?? ($current_profile['city'] ?? '')); ?>" maxlength="100">
                                </div>
                                <div>
                                    <label for="province">Province</label>
                                    <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($_POST['province'] ?? ($current_profile['province'] ?? '')); ?>" maxlength="100">
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

                            <div class="booking-step-actions">
                                <button type="button" class="btn-secondary compact-button" data-prev-step="1">Back</button>
                                <button type="submit" name="submit" id="bookingSubmit" class="btn-primary">Submit Booking Request</button>
                            </div>
                        </div>
                        </fieldset>
                    </form>
                <?php else: ?>
                    <div class="make-empty-state">
                        <strong>This mission is not accepting bookings.</strong>
                        <span>Please choose another available mission.</span>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="book-slot-sidebar" aria-label="Mission booking details">
                <section class="mission-summary-card">
                    <h2>Mission Summary</h2>
                    <dl>
                        <dt>Organizer</dt>
                        <dd><?php echo htmlspecialchars($mission['organizer_name']); ?></dd>
                        <dt>Date</dt>
                        <dd><?php echo htmlspecialchars($date_label); ?></dd>
                        <dt>Time</dt>
                        <dd><?php echo htmlspecialchars($time_label); ?></dd>
                        <dt>Location</dt>
                        <dd><?php echo htmlspecialchars($location_label); ?></dd>
                        <dt>Slots Remaining</dt>
                        <dd><span id="remainingSlotsValue"><?php echo htmlspecialchars($mission['available_slots']); ?></span> of <?php echo htmlspecialchars($mission['total_slots']); ?></dd>
                    </dl>
                </section>

                <section class="booking-reminders-card">
                    <h2>Important Reminders</h2>
                    <ul class="figma-check-list">
                        <li>Save your contact number - needed to retrieve your booking</li>
                        <li>Fast 6-8 hours before your surgery time</li>
                        <li>Bring a companion aged 18 or older</li>
                        <li>Wear comfortable, loose clothing</li>
                        <li>Arrive at least 30 minutes early</li>
                    </ul>
                </section>
            </aside>
        </section>
    </main>

    <?php kk_render_footer('pages'); ?>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/book-slot.js"></script>
</body>
</html>
