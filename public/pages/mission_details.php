<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');

require_patient_page('login.php');

// Get the mission ID from the URL
$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// If no valid ID, redirect to homepage
if ($mission_id === 0) {
    header("Location: patient_portal.php");
    exit();
}

// Fetch the mission details
$sql = "SELECT * FROM missions WHERE mission_id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

// If mission doesn't exist, go back home
if (!$mission) {
    header("Location: patient_portal.php");
    exit();
}

// Calculate mission status
$mission_date = strtotime($mission['mission_date']);
$today = strtotime(date('Y-m-d'));
$days_until = ceil(($mission_date - $today) / 86400);

if ($mission['mission_status'] === 'cancelled') {
    $status = 'cancelled';
    $status_text = 'Mission Cancelled';
} elseif ($days_until < 0 || $mission['mission_status'] === 'completed') {
    $status = 'completed';
    $status_text = 'Mission Completed';
} elseif ($mission['available_slots'] <= 0 || $mission['mission_status'] === 'closed') {
    $status = 'full';
    $status_text = 'Full - No Slots Available';
} else {
    $status = 'available';
    $status_text = 'Accepting Bookings';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mission Details | KitaKits</title>
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
                    <h1>Mission Details</h1>
                    <p>Complete information about this cataract mission</p>
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

    <main class="container">
        <a href="patient_portal.php#portal-missions" class="btn-back">
            <span>←</span>
            Back to Portal Missions
        </a>

        <div class="mission-details-container">
            <div class="mission-details-header">
                <div>
                    <h1><?php echo htmlspecialchars($mission['mission_name'] ?? $mission['organizer_name']); ?></h1>
                    <p class="mission-tagline">Free Cataract Surgery Mission</p>
                </div>
                <div class="status-badge status-<?php echo $status; ?>">
                    <?php echo $status_text; ?>
                </div>
            </div>

            <div class="details-grid">
                <div class="detail-box">
                    <h3>📅 Mission Date & Time</h3>
                    <p class="detail-content">
                        <?php echo date('l, F j, Y', strtotime($mission['mission_date'])); ?>
                    </p>
                    <p class="detail-hint">
                        <?php if ($days_until > 0): ?>
                            This mission is in <strong><?php echo $days_until; ?></strong> day(s)
                        <?php else: ?>
                            This mission has already been completed
                        <?php endif; ?>
                    </p>
                </div>

                <div class="detail-box">
                    <h3>📍 Location</h3>
                    <p class="detail-content"><?php echo htmlspecialchars($mission['full_address'] ?: $mission['location']); ?></p>
                    <p class="detail-hint">Please arrive 30 minutes early to complete registration</p>
                </div>

                <div class="detail-box">
                    <h3>👥 Available Slots</h3>
                    <p class="detail-content">
                        <strong class="slots-number"><?php echo htmlspecialchars($mission['available_slots']); ?></strong>
                        slot(s) remaining
                    </p>
                    <p class="detail-hint">
                        <?php if ($mission['available_slots'] <= 5 && $mission['available_slots'] > 0): ?>
                            <span class="slot-warning">⚠️ Limited slots - Book now!</span>
                        <?php elseif ($mission['available_slots'] <= 0): ?>
                            <span class="slot-empty">❌ No slots available</span>
                        <?php else: ?>
                            <span class="slot-available">✅ Plenty of slots available</span>
                        <?php endif; ?>
                    </p>
                </div>

                <div class="detail-box">
                    <h3>🏥 Mission Organizer</h3>
                    <p class="detail-content"><?php echo htmlspecialchars($mission['organizer_name']); ?></p>
                    <p class="detail-hint">This mission is organized by a trusted healthcare provider</p>
                </div>
            </div>

            <?php if (!empty($mission['guidelines']) || !empty($mission['day_of_instructions'])): ?>
                <div class="mission-info-section">
                    <h2>Mission Guidelines</h2>
                    <?php if (!empty($mission['guidelines'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($mission['guidelines'])); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($mission['day_of_instructions'])): ?>
                        <h3>Day-of Instructions</h3>
                        <p><?php echo nl2br(htmlspecialchars($mission['day_of_instructions'])); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="mission-info-section">
                <h2>What to Expect</h2>
                <div class="info-list">
                    <div class="info-item">
                        <span class="info-icon">1️⃣</span>
                        <div>
                            <h4>Registration & Health Screening</h4>
                            <p>Upon arrival, you'll complete basic registration and health assessment to ensure you're a suitable candidate for the procedure.</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">2️⃣</span>
                        <div>
                            <h4>Eye Examination</h4>
                            <p>Our medical team will perform a comprehensive eye examination to assess your cataract and overall eye health.</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">3️⃣</span>
                        <div>
                            <h4>Cataract Surgery</h4>
                            <p>The procedure is usually brief and performed under local anesthesia, but the full visit takes longer because of registration, screening, preparation, recovery, and discharge instructions.</p>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="info-icon">4️⃣</span>
                        <div>
                            <h4>Post-Surgery Care & Instructions</h4>
                            <p>You'll receive detailed aftercare instructions and follow-up appointments information before leaving the mission site.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mission-requirements">
                <h2>Before You Come</h2>
                <div class="requirements-list">
                    <div class="requirement">✓ Bring a valid ID or identification document</div>
                    <div class="requirement">✓ Bring any existing medical records or eye prescriptions</div>
                    <div class="requirement">✓ Have someone accompany you (for support and transportation)</div>
                    <div class="requirement">✓ Follow any fasting instructions provided by the organizer</div>
                    <div class="requirement">✓ Wear comfortable, clean clothing</div>
                    <div class="requirement">✓ Arrive 30 minutes before the mission time</div>
                </div>
            </div>

            <div class="mission-action">
                <?php if ($mission['available_slots'] > 0 && $mission['mission_status'] === 'open' && $mission['mission_date'] >= date('Y-m-d')): ?>
                    <a href="book_slot.php?id=<?php echo urlencode($mission['mission_id']); ?>" class="btn-book btn-large">
                        <span class="btn-icon">📋</span>
                        Submit Booking Request
                    </a>
                <?php else: ?>
                    <button class="btn-book btn-large disabled-action" disabled>
                        <span class="btn-icon">❌</span>
                        No Slots Available
                    </button>
                <?php endif; ?>
            </div>

            <div class="mission-support">
                <h3>Need Help?</h3>
                <p>If you have questions about this mission or need assistance, visit our <a href="faq.php">FAQ page</a> or check the <a href="patient_guide.php">Patient Guide</a> for more information.</p>
            </div>
        </div>
    </main>
</body>
</html>
