<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$patient_id = current_patient_id();

if ($booking_id <= 0) {
    header('Location: ' . ($patient_id ? 'patient_portal.php' : 'login.php'));
    exit();
}

if (!$patient_id) {
    header('Location: login.php');
    exit();
}

$where = 'b.booking_id = :booking_id AND b.patient_id = :patient_id';
$params = [
    ':booking_id' => $booking_id,
    ':patient_id' => $patient_id
];

$stmt = $conn->prepare("SELECT b.booking_id,
                               b.booking_reference,
                               b.booking_status,
                               b.confirmed_at,
                               b.companion_count,
                               b.total_headcount,
                               b.contact_number,
                               m.mission_name,
                               m.organizer_name,
                               m.mission_date,
                               m.start_time,
                               m.end_time,
                               m.venue_name,
                               m.full_address,
                               COALESCE(NULLIF(b.patient_name, ''), CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix)) AS patient_name,
                               p.email,
                               m.day_of_instructions
                        FROM bookings b
                        INNER JOIN patients p ON p.patient_id = b.patient_id
                        INNER JOIN missions m ON m.mission_id = b.mission_id
                        WHERE " . $where . "
                        LIMIT 1");
$stmt->execute($params);
$slip = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$slip) {
    header('Location: ' . ($patient_id ? 'patient_portal.php' : 'login.php'));
    exit();
}

$back_url = $patient_id ? 'patient_portal.php#portal-bookings' : 'login.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Slip | KitaKits</title>
    <?php kk_render_favicon('pages'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="page-booking-confirmation">
    <?php kk_render_header(['section' => 'pages', 'active' => '', 'no_print' => true]); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Booking Confirmation', 'href' => 'my_bookings.php'], ['label' => $slip['booking_reference']]], ['no_print' => true]); ?>

    <main class="container slip-container">
        <div class="no-print slip-actions">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-back">Back</a>
            <div class="slip-action-group">
                <a href="my_bookings.php" class="btn-secondary compact-button">My Bookings</a>
                <?php if ($slip['booking_status'] === 'confirmed'): ?>
                    <button type="button" class="btn-primary compact-button" onclick="window.print()">Print Slip</button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($slip['booking_status'] !== 'confirmed'): ?>
            <div class="alert alert-error no-print">
                This booking is currently <?php echo htmlspecialchars($slip['booking_status']); ?>. The printable confirmation slip is available once an admin confirms the slot.
            </div>
        <?php endif; ?>

        <section class="print-slip">
            <div class="slip-header">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
                <div>
                    <h1>KitaKits</h1>
                    <p>Free Cataract Surgery Missions</p>
                </div>
                <div class="slip-reference">
                    <span>Booking Confirmation</span>
                    <strong><?php echo htmlspecialchars($slip['booking_reference']); ?></strong>
                </div>
            </div>

            <div class="slip-status">
                <img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true">
                <span>Booking status</span>
                <strong><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $slip['booking_status']))); ?></strong>
            </div>

            <h2 class="slip-section-title"><img src="../assets/icons/calendar-purple.svg" alt="" aria-hidden="true"> Mission Information</h2>
            <div class="slip-grid">
                <div>
                    <span>Organizer</span>
                    <strong><?php echo htmlspecialchars($slip['organizer_name']); ?></strong>
                </div>
                <div>
                    <span>Date</span>
                    <strong><?php echo date('F j, Y', strtotime($slip['mission_date'])); ?></strong>
                </div>
                <div>
                    <span>Time</span>
                    <strong><?php echo date('g:i A', strtotime($slip['start_time'])); ?> - <?php echo date('g:i A', strtotime($slip['end_time'])); ?></strong>
                </div>
                <div>
                    <span>Venue / Address</span>
                    <strong><?php echo htmlspecialchars($slip['full_address']); ?></strong>
                </div>
            </div>

            <h2 class="slip-section-title"><img src="../assets/icons/users-purple.svg" alt="" aria-hidden="true"> Patient Information</h2>
            <div class="slip-grid">
                <div>
                    <span>Patient Name</span>
                    <strong><?php echo htmlspecialchars($slip['patient_name']); ?></strong>
                </div>
                <div>
                    <span>Contact Number</span>
                    <strong><?php echo htmlspecialchars($slip['contact_number']); ?></strong>
                </div>
                <div>
                    <span>Affected Eye</span>
                    <strong>Both</strong>
                </div>
                <div>
                    <span>Companion</span>
                    <strong><?php echo htmlspecialchars($slip['total_headcount']); ?></strong>
                </div>
            </div>

            <div class="slip-instructions">
                <h2><img src="../assets/icons/info.svg" alt="" aria-hidden="true"> Day-of Instructions</h2>
                <p><?php echo nl2br(htmlspecialchars($slip['day_of_instructions'] ?: 'Bring this slip, a valid ID, water, and any maintenance medication.')); ?></p>
            </div>

            <div class="slip-instructions">
                <h2><img src="../assets/icons/shield-check.svg" alt="" aria-hidden="true"> Coordinator Check-in</h2>
                <p>Present this reference number first: <strong><?php echo htmlspecialchars($slip['booking_reference']); ?></strong>. The coordinator can use it to find your approved booking faster.</p>
            </div>
        </section>
    </main>
    <?php kk_render_footer('pages', ['no_print' => true]); ?>
</body>
</html>
