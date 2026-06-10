<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../api/_auth.php');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contact = normalize_contact_number($_GET['contact'] ?? '');
$patient_id = current_patient_id();

if ($booking_id <= 0) {
    header('Location: ' . ($patient_id ? 'patient_portal.php' : 'login.php'));
    exit();
}

if ($contact !== '' && !contact_number_is_valid($contact)) {
    header('Location: login.php');
    exit();
}

if ($contact === '' && !$patient_id) {
    header('Location: login.php');
    exit();
}

$where = 'b.booking_id = :booking_id';
$params = [':booking_id' => $booking_id];

if ($contact !== '') {
    $where .= ' AND b.contact_number = :contact';
    $params[':contact'] = $contact;
} else {
    $where .= ' AND b.patient_id = :patient_id';
    $params[':patient_id'] = $patient_id;
}

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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header class="no-print">
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Booking Confirmation Slip</h1>
                    <p>Printable mission-day reference</p>
                </div>
                <div class="header-actions">
                    <nav class="header-nav">
                        <a href="patient_portal.php">Patient Portal</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container slip-container">
        <div class="no-print slip-actions">
            <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn-back">
                <span>&larr; </span>
                Back
            </a>
            <?php if ($slip['booking_status'] === 'confirmed'): ?>
                <button type="button" class="btn-primary compact-button" onclick="window.print()">Print This Slip</button>
            <?php endif; ?>
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
                    <h1>KitaKits Confirmation Slip</h1>
                    <p>Reference: <strong><?php echo htmlspecialchars($slip['booking_reference']); ?></strong></p>
                </div>
            </div>

            <div class="slip-status">
                Booking Status: <strong><?php echo htmlspecialchars(strtoupper($slip['booking_status'])); ?></strong>
            </div>

            <div class="slip-grid">
                <div>
                    <span>Mission</span>
                    <strong><?php echo htmlspecialchars($slip['mission_name']); ?></strong>
                </div>
                <div>
                    <span>Date</span>
                    <strong><?php echo date('F j, Y', strtotime($slip['mission_date'])); ?></strong>
                </div>
                <div>
                    <span>Organizer</span>
                    <strong><?php echo htmlspecialchars($slip['organizer_name']); ?></strong>
                </div>
                <div>
                    <span>Venue / Address</span>
                    <strong><?php echo htmlspecialchars($slip['full_address']); ?></strong>
                </div>
                <div>
                    <span>Patient</span>
                    <strong><?php echo htmlspecialchars($slip['patient_name']); ?></strong>
                </div>
                <div>
                    <span>Contact</span>
                    <strong><?php echo htmlspecialchars($slip['contact_number']); ?></strong>
                </div>
                <div>
                    <span>Companions</span>
                    <strong><?php echo htmlspecialchars($slip['companion_count']); ?></strong>
                </div>
                <div>
                    <span>Total Headcount</span>
                    <strong><?php echo htmlspecialchars($slip['total_headcount']); ?></strong>
                </div>
            </div>

            <div class="slip-instructions">
                <h2>Day-of Instructions</h2>
                <p><?php echo nl2br(htmlspecialchars($slip['day_of_instructions'] ?: 'Bring this slip, a valid ID, water, and any maintenance medication.')); ?></p>
            </div>

            <div class="slip-instructions">
                <h2>Coordinator Check-in</h2>
                <p>Present this reference number first: <strong><?php echo htmlspecialchars($slip['booking_reference']); ?></strong>. The coordinator can use it to find your approved booking faster.</p>
            </div>
        </section>
    </main>
</body>
</html>
