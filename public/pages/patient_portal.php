<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../includes/layout.php');

$patient_id = require_patient_page();
$profile_error = '';

function portal_status_class($status)
{
    return 'status-' . str_replace('_', '-', (string)$status);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $first_name = trim($_POST['first_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $suffix = trim($_POST['suffix'] ?? '');
    $birthdate = trim($_POST['birthdate'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $contact_number = normalize_contact_number($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_address = trim($_POST['full_address'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = normalize_contact_number($_POST['emergency_contact_number'] ?? '');
    $allowed_sex = ['', 'Male', 'Female', 'Other', 'Prefer not to say'];

    if ($first_name === '' || $last_name === '' || $contact_number === '') {
        $profile_error = 'First name, last name, and contact number are required.';
    } elseif (!patient_name_parts_are_valid($first_name, $middle_name, $last_name)) {
        $profile_error = 'First, middle, and last name must each be 30 characters or fewer and 65 characters or fewer combined.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $profile_error = 'Enter an 11-digit mobile number starting with 09.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $profile_error = 'Enter a valid email address.';
    } elseif (!in_array($sex, $allowed_sex, true)) {
        $profile_error = 'Choose a valid sex option.';
    } elseif ($emergency_contact_number !== '' && !contact_number_is_valid($emergency_contact_number)) {
        $profile_error = 'Enter an 11-digit emergency mobile number starting with 09.';
    } elseif (text_length($emergency_contact_name) > 65) {
        $profile_error = 'Emergency contact name must be 65 characters or fewer.';
    } else {
        try {
            $conn->beginTransaction();

            if ($email !== '') {
                $email_check = $conn->prepare("SELECT user_id
                                               FROM users
                                               WHERE email = :email
                                                 AND user_id <> :user_id
                                               LIMIT 1");
                $email_check->execute([
                    ':email' => $email,
                    ':user_id' => current_patient_user_id()
                ]);

                if ($email_check->fetch(PDO::FETCH_ASSOC)) {
                    $conn->rollBack();
                    $profile_error = 'That email is already used by another account.';
                }
            }

            if ($profile_error === '') {
                $full_name = trim($first_name . ' ' . $last_name);
                $update_user = $conn->prepare("UPDATE users
                                               SET full_name = :full_name,
                                                   email = :email,
                                                   contact_number = :contact
                                               WHERE user_id = :user_id");
                $update_user->execute([
                    ':full_name' => $full_name,
                    ':email' => $email ?: null,
                    ':contact' => $contact_number,
                    ':user_id' => current_patient_user_id()
                ]);

                $update_patient = $conn->prepare("UPDATE patients
                                                  SET first_name = :first_name,
                                                      middle_name = :middle_name,
                                                      last_name = :last_name,
                                                      suffix = :suffix,
                                                      birthdate = :birthdate,
                                                      sex = :sex,
                                                      contact_number = :contact,
                                                      email = :email,
                                                      full_address = :full_address,
                                                      barangay = :barangay,
                                                      city = :city,
                                                      province = :province,
                                                      emergency_contact_name = :emergency_contact_name,
                                                      emergency_contact_number = :emergency_contact_number
                                                  WHERE patient_id = :patient_id");
                $update_patient->execute([
                    ':first_name' => $first_name,
                    ':middle_name' => $middle_name ?: null,
                    ':last_name' => $last_name,
                    ':suffix' => $suffix ?: null,
                    ':birthdate' => $birthdate ?: null,
                    ':sex' => $sex ?: null,
                    ':contact' => $contact_number,
                    ':email' => $email ?: null,
                    ':full_address' => $full_address ?: null,
                    ':barangay' => $barangay ?: null,
                    ':city' => $city ?: null,
                    ':province' => $province ?: null,
                    ':emergency_contact_name' => $emergency_contact_name ?: null,
                    ':emergency_contact_number' => $emergency_contact_number ?: null,
                    ':patient_id' => $patient_id
                ]);

                $conn->commit();
                header('Location: patient_portal.php?saved=1');
                exit();
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $profile_error = 'Unable to update your profile right now.';
        }
    }
}

$profile_stmt = $conn->prepare("SELECT p.*,
                                       u.full_name AS account_name,
                                       u.email AS account_email,
                                       u.contact_number AS account_contact
                                FROM patients p
                                INNER JOIN users u ON u.user_id = p.user_id
                                WHERE p.patient_id = :patient_id
                                LIMIT 1");
$profile_stmt->execute([':patient_id' => $patient_id]);
$profile = $profile_stmt->fetch(PDO::FETCH_ASSOC);

$bookings_stmt = $conn->prepare("SELECT b.booking_id,
                                        b.booking_reference,
                                        b.booking_status,
                                        b.requested_at,
                                        b.confirmed_at,
                                        b.companion_count,
                                        b.total_headcount,
                                        b.contact_number,
                                        m.mission_id,
                                        m.mission_name,
                                        m.organizer_name,
                                        m.mission_date,
                                        m.full_address,
                                        m.city_area,
                                        m.day_of_instructions,
                                        i.review_status AS intake_review_status
                                 FROM bookings b
                                 INNER JOIN missions m ON m.mission_id = b.mission_id
                                 LEFT JOIN medical_intake_forms i ON i.booking_id = b.booking_id
                                 WHERE b.patient_id = :patient_id
                                 ORDER BY m.mission_date DESC, b.requested_at DESC");
$bookings_stmt->execute([':patient_id' => $patient_id]);
$bookings = $bookings_stmt->fetchAll(PDO::FETCH_ASSOC);

$counts = [
    'booked' => 0,
    'confirmed' => 0,
    'completed' => 0
];

foreach ($bookings as $booking) {
    if (isset($counts[$booking['booking_status']])) {
        $counts[$booking['booking_status']]++;
    }
}

$patient_name = trim(implode(' ', array_filter([
    $profile['first_name'] ?? '',
    $profile['middle_name'] ?? '',
    $profile['last_name'] ?? '',
    $profile['suffix'] ?? ''
])));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard | KitaKits</title>
    <?php kk_render_favicon('pages'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="patient-dashboard-page">
    <?php kk_render_header(['section' => 'pages', 'active' => 'portal']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Patient Dashboard']]); ?>

    <main class="container portal-workspace">
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">Account created. Your patient dashboard is ready.</div>
        <?php endif; ?>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Profile updated.</div>
        <?php endif; ?>

        <?php if (isset($_GET['requested'])): ?>
            <div class="alert alert-success">Booking request submitted. Track its approval status below.</div>
        <?php endif; ?>

        <?php if ($profile_error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($profile_error); ?></div>
        <?php endif; ?>

        <section class="portal-hero workspace-section">
            <div class="portal-welcome">
                <span class="portal-welcome-icon" aria-hidden="true">
                    <img src="../assets/icons/users.svg" alt="">
                </span>
                <div>
                    <span class="eyebrow">Patient dashboard</span>
                    <h1>Welcome, <?php echo htmlspecialchars($patient_name); ?></h1>
                    <p>Find a cataract mission, manage your requests, and keep your patient information ready for your visit.</p>
                </div>
            </div>
            <div class="portal-stats">
                <div class="mini-stat">
                    <img src="../assets/icons/clock.svg" alt="" aria-hidden="true">
                    <span>Booked</span>
                    <strong><?php echo (int)$counts['booked']; ?></strong>
                </div>
                <div class="mini-stat">
                    <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                    <span>Confirmed</span>
                    <strong><?php echo (int)$counts['confirmed']; ?></strong>
                </div>
                <div class="mini-stat">
                    <img src="../assets/icons/calendar-days.svg" alt="" aria-hidden="true">
                    <span>Completed</span>
                    <strong><?php echo (int)$counts['completed']; ?></strong>
                </div>
            </div>
        </section>

        <nav class="portal-jump-nav" aria-label="Patient portal sections">
            <a href="#portal-missions">
                <img src="../assets/icons/search.svg" alt="" aria-hidden="true">
                <span>Find Missions</span>
            </a>
            <a href="#portal-bookings">
                <img src="../assets/icons/book-open.svg" alt="" aria-hidden="true">
                <span>My Bookings</span>
            </a>
            <a href="#portal-profile">
                <img src="../assets/icons/users-purple.svg" alt="" aria-hidden="true">
                <span>My Profile</span>
            </a>
        </nav>

        <section class="workspace-section portal-panel" id="portal-missions">
            <div class="section-header portal-section-header">
                <span class="portal-section-icon" aria-hidden="true">
                    <img src="../assets/icons/search.svg" alt="">
                </span>
                <div>
                    <span class="portal-section-kicker">Discover care near you</span>
                    <h2>Find Available Missions</h2>
                    <p>Search upcoming cataract outreach schedules and check remaining slots.</p>
                </div>
            </div>

            <form id="missionFilters" class="filter-bar portal-mission-filters" aria-label="Search and filter missions" data-api-url="../api/missions.php" data-page-prefix="" data-asset-prefix="../assets/">
                <div class="form-group">
                    <label for="missionSearch">Keyword</label>
                    <input type="search" id="missionSearch" name="q" placeholder="Organizer, mission, place">
                </div>
                <div class="form-group">
                    <label for="missionCity">City / Area</label>
                    <input type="text" id="missionCity" name="city" placeholder="e.g., Marikina">
                </div>
                <div class="form-group">
                    <label for="missionStatusFilter">Slots</label>
                    <select id="missionStatusFilter" name="status">
                        <option value="all">All missions</option>
                        <option value="available">Available only</option>
                        <option value="full">Fully booked only</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="missionSort">Sort</label>
                    <select id="missionSort" name="sort">
                        <option value="date">Nearest date</option>
                        <option value="slots">Most slots</option>
                    </select>
                </div>
                <button type="submit" class="btn-search">
                    <img src="../assets/icons/filter.svg" alt="" aria-hidden="true">
                    <span>Apply filters</span>
                </button>
            </form>

            <div class="portal-panel-content">
                <div id="missionsStatus" class="status-message" role="status" hidden></div>
                <div id="missionsLoading" class="loading-state" role="status" aria-live="polite" hidden></div>
                <p id="missionsEmptyState" class="empty-msg" hidden>No missions match your filters right now.</p>

                <div id="availableMissions" class="mission-grid portal-mission-grid" aria-live="polite"></div>

                <section id="fullyBookedSection" class="fully-booked-section" hidden>
                    <h2>Fully Booked Missions</h2>
                    <div id="fullyBookedMissions" class="mission-grid portal-mission-grid" aria-live="polite"></div>
                </section>
            </div>
        </section>

        <section class="workspace-section portal-panel" id="portal-bookings">
            <div class="section-header portal-section-header">
                <span class="portal-section-icon" aria-hidden="true">
                    <img src="../assets/icons/book-open.svg" alt="">
                </span>
                <div>
                    <span class="portal-section-kicker">Your care journey</span>
                    <h2>My Bookings</h2>
                    <p>Track approvals, pre-screening progress, and visit instructions.</p>
                </div>
                <a href="#portal-missions" class="btn-primary compact-button portal-section-action">
                    <img src="../assets/icons/search.svg" alt="" aria-hidden="true">
                    <span>Find Missions</span>
                </a>
            </div>

            <div class="portal-panel-content">
                <?php if (!$bookings): ?>
                    <p class="empty-msg portal-empty-state">No bookings yet. Browse missions and submit a booking request.</p>
                <?php else: ?>
                    <div class="bookings-list portal-bookings-list">
                        <?php foreach ($bookings as $booking): ?>
                            <?php
                                $status = $booking['booking_status'];
                                $intake_status = $booking['intake_review_status'] ?: 'not_submitted';
                            ?>
                            <article class="booking-card portal-booking-card">
                                <div class="booking-header">
                                    <div>
                                        <span class="portal-booking-label">Cataract mission</span>
                                        <h3><?php echo htmlspecialchars($booking['mission_name']); ?></h3>
                                    </div>
                                    <span class="booking-id"><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                                </div>

                                <div class="booking-details">
                                    <div class="detail-row">
                                        <span class="detail-label">Mission Date</span>
                                        <span class="detail-value"><?php echo date('F j, Y', strtotime($booking['mission_date'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Full Address</span>
                                        <span class="detail-value"><?php echo htmlspecialchars($booking['full_address']); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Booking Status</span>
                                        <span class="detail-value status-pill <?php echo htmlspecialchars(portal_status_class($status)); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $status))); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Pre-screening</span>
                                        <span class="detail-value status-pill <?php echo htmlspecialchars(portal_status_class($intake_status)); ?>"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $intake_status))); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <span class="detail-label">Headcount</span>
                                        <span class="detail-value"><?php echo (int)$booking['total_headcount']; ?> total</span>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <a href="pre_screening.php?id=<?php echo (int)$booking['booking_id']; ?>" class="btn-secondary compact-button">Pre-screening</a>
                                    <?php if ($status === 'confirmed'): ?>
                                        <a href="booking_slip.php?id=<?php echo (int)$booking['booking_id']; ?>" class="btn-primary compact-button">Print Slip</a>
                                    <?php else: ?>
                                        <a href="booking_slip.php?id=<?php echo (int)$booking['booking_id']; ?>" class="btn-secondary compact-button">View Slip Status</a>
                                    <?php endif; ?>
                                    <?php if (!in_array($status, ['cancelled', 'completed', 'no_show'], true)): ?>
                                        <a href="edit_booking.php?id=<?php echo (int)$booking['booking_id']; ?>" class="btn-edit compact-button">Edit Booking</a>
                                    <?php endif; ?>
                                    <?php if ($status === 'booked'): ?>
                                        <button type="button" class="btn-delete compact-button" data-cancel-booking data-id="<?php echo (int)$booking['booking_id']; ?>">Cancel Request</button>
                                    <?php elseif ($status === 'confirmed'): ?>
                                        <span class="table-subtext">Contact the coordinator to cancel a confirmed booking.</span>
                                    <?php endif; ?>
                                </div>

                                <p class="reminder">
                                    <img src="../assets/icons/info.svg" alt="" aria-hidden="true">
                                    <span>
                                        <?php if ($status === 'confirmed'): ?>
                                            Your slot is secured. Bring your printed slip, valid ID, and any maintenance medication.
                                        <?php else: ?>
                                            Booked means your request is received. The slot is secured only after admin confirmation.
                                        <?php endif; ?>
                                    </span>
                                </p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="workspace-section portal-panel" id="portal-profile">
            <div class="section-header portal-section-header">
                <span class="portal-section-icon" aria-hidden="true">
                    <img src="../assets/icons/users-purple.svg" alt="">
                </span>
                <div>
                    <span class="portal-section-kicker">Personal information</span>
                    <h2>Patient Profile</h2>
                    <p>Keep these details accurate so coordinators can contact and assist you.</p>
                </div>
            </div>

            <form method="POST" action="" class="portal-profile-form">
                <input type="hidden" name="action" value="update_profile">

                <div class="portal-form-section">
                    <div class="portal-form-heading">
                        <strong>Basic information</strong>
                        <span>Your legal name and personal details</span>
                    </div>
                <div class="form-grid form-grid-3">
                    <div>
                        <label for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" maxlength="30" required>
                    </div>
                    <div>
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($profile['middle_name'] ?? ''); ?>" maxlength="30">
                    </div>
                    <div>
                        <label for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" maxlength="30" required>
                    </div>
                </div>

                <div class="form-grid form-grid-3">
                    <div>
                        <label for="suffix">Suffix</label>
                        <input type="text" id="suffix" name="suffix" value="<?php echo htmlspecialchars($profile['suffix'] ?? ''); ?>" maxlength="20">
                    </div>
                    <div>
                        <label for="birthdate">Birthdate</label>
                        <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($profile['birthdate'] ?? ''); ?>">
                    </div>
                    <div>
                        <label for="sex">Sex</label>
                        <?php $selected_sex = $profile['sex'] ?? ''; ?>
                        <select id="sex" name="sex">
                            <option value="">Prefer not to say</option>
                            <?php foreach (['Male', 'Female', 'Other', 'Prefer not to say'] as $option): ?>
                                <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $selected_sex === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                </div>

                <div class="portal-form-section">
                    <div class="portal-form-heading">
                        <strong>Contact and address</strong>
                        <span>Where the mission team can reach you</span>
                    </div>
                <div class="form-grid">
                    <div>
                        <label for="contact_number">Contact Number *</label>
                        <input type="tel" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($profile['contact_number'] ?? ''); ?>" pattern="09[0-9]{9}" minlength="11" maxlength="11" inputmode="numeric" required>
                    </div>
                    <div>
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profile['email'] ?? ''); ?>" maxlength="150">
                    </div>
                </div>

                <label for="full_address">Full Address</label>
                <input type="text" id="full_address" name="full_address" value="<?php echo htmlspecialchars($profile['full_address'] ?? ''); ?>" maxlength="255">

                <div class="form-grid form-grid-3">
                    <div>
                        <label for="barangay">Barangay</label>
                        <input type="text" id="barangay" name="barangay" value="<?php echo htmlspecialchars($profile['barangay'] ?? ''); ?>" maxlength="100">
                    </div>
                    <div>
                        <label for="city">City / Area</label>
                        <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profile['city'] ?? ''); ?>" maxlength="100">
                    </div>
                    <div>
                        <label for="province">Province</label>
                        <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($profile['province'] ?? ''); ?>" maxlength="100">
                    </div>
                </div>
                </div>

                <div class="portal-form-section">
                    <div class="portal-form-heading">
                        <strong>Emergency contact</strong>
                        <span>A trusted person we can contact when needed</span>
                    </div>
                <div class="form-grid">
                    <div>
                        <label for="emergency_contact_name">Emergency Contact Name</label>
                        <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($profile['emergency_contact_name'] ?? ''); ?>" maxlength="65">
                    </div>
                    <div>
                        <label for="emergency_contact_number">Emergency Contact Number</label>
                        <input type="tel" id="emergency_contact_number" name="emergency_contact_number" value="<?php echo htmlspecialchars($profile['emergency_contact_number'] ?? ''); ?>" pattern="09[0-9]{9}" minlength="11" maxlength="11" inputmode="numeric">
                    </div>
                </div>
                </div>

                <div class="portal-profile-actions">
                    <button type="submit" class="btn-primary">
                        <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                        <span>Save Profile</span>
                    </button>
                </div>
            </form>
        </section>
    </main>
    <?php kk_render_footer('pages'); ?>
    <script src="../assets/js/api.js"></script>
    <script src="../assets/js/missions.js"></script>
    <script src="../assets/js/patient-portal.js"></script>
</body>
</html>
