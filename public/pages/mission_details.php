<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

function kk_mission_time_range($mission)
{
    if (!empty($mission['start_time']) && !empty($mission['end_time'])) {
        return date('g:i A', strtotime($mission['start_time'])) . ' - ' . date('g:i A', strtotime($mission['end_time']));
    }

    if (!empty($mission['start_time'])) {
        return date('g:i A', strtotime($mission['start_time']));
    }

    return 'Time to be announced';
}

function kk_mission_status($mission)
{
    $mission_date = strtotime($mission['mission_date']);
    $today = strtotime(date('Y-m-d'));

    if ($mission['mission_status'] === 'cancelled') {
        return ['cancelled', 'Mission Cancelled'];
    }

    if ($mission_date < $today || $mission['mission_status'] === 'completed') {
        return ['completed', 'Mission Completed'];
    }

    if ((int)$mission['available_slots'] <= 0 || $mission['mission_status'] === 'closed') {
        return ['full', 'Fully Booked'];
    }

    return ['available', 'Accepting Bookings'];
}

function kk_slot_dots($available_slots, $total_slots)
{
    $dot_count = 30;
    $total_slots = max(1, (int)$total_slots);
    $available_slots = max(0, min((int)$available_slots, $total_slots));
    $booked_slots = max(0, $total_slots - $available_slots);
    $booked_dots = (int)round(($booked_slots / $total_slots) * $dot_count);
    $html = '';

    for ($i = 0; $i < $dot_count; $i++) {
        $html .= '<span class="' . ($i < $booked_dots ? 'is-booked' : 'is-available') . '"></span>';
    }

    return $html;
}

$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($mission_id === 0) {
    header('Location: ../index.php#missions');
    exit();
}

$stmt = $conn->prepare('SELECT * FROM missions WHERE mission_id = :id LIMIT 1');
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    header('Location: ../index.php#missions');
    exit();
}

[$status, $status_text] = kk_mission_status($mission);
$is_bookable = $status === 'available' && $mission['mission_date'] >= date('Y-m-d');
$available_slots = (int)$mission['available_slots'];
$total_slots = max(0, (int)$mission['total_slots']);
$booked_slots = max(0, $total_slots - $available_slots);
$date_label = date('F j, Y', strtotime($mission['mission_date']));
$time_label = kk_mission_time_range($mission);
$location_label = $mission['full_address'] ?: $mission['location'];
$short_location = trim(($mission['location'] ?: '') . (($mission['city_area'] ?? '') ? ', ' . $mission['city_area'] : ''));
$tagline = !empty($mission['venue_name'])
    ? $mission['venue_name']
    : 'Free cataract surgery mission for patients' . (!empty($mission['city_area']) ? ' in ' . $mission['city_area'] : '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mission Details | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="make-ui page-mission-details">
    <?php kk_render_header(['section' => 'pages', 'active' => '']); ?>
    <?php kk_render_breadcrumbs('pages', [['label' => 'Mission Details', 'href' => '../index.php#missions'], ['label' => 'm' . $mission_id]]); ?>

    <main class="container mission-flow-page">
        <a href="../index.php#missions" class="btn-back">
            <span>&larr;</span>
            Back to Missions
        </a>

        <section class="mission-figma-hero">
            <div>
                <h1><?php echo htmlspecialchars($mission['mission_name'] ?: $mission['organizer_name']); ?></h1>
                <p><?php echo htmlspecialchars($tagline); ?></p>
            </div>
            <span class="status-badge status-<?php echo htmlspecialchars($status); ?>"><?php echo htmlspecialchars($status_text); ?></span>
        </section>

        <section class="mission-stat-grid" aria-label="Mission facts">
            <article class="mission-stat-card">
                <span class="mission-stat-icon"><img src="../assets/icons/calendar-purple.svg" alt=""></span>
                <b>Date</b>
                <strong><?php echo htmlspecialchars($date_label); ?></strong>
            </article>
            <article class="mission-stat-card">
                <span class="mission-stat-icon"><img src="../assets/icons/clock-purple.svg" alt=""></span>
                <b>Time</b>
                <strong><?php echo htmlspecialchars($time_label); ?></strong>
            </article>
            <article class="mission-stat-card">
                <span class="mission-stat-icon"><img src="../assets/icons/map-pin.svg" alt=""></span>
                <b>Location</b>
                <strong><?php echo htmlspecialchars($short_location ?: $location_label); ?></strong>
            </article>
            <article class="mission-stat-card">
                <span class="mission-stat-icon"><img src="../assets/icons/users-purple.svg" alt=""></span>
                <b>Slots</b>
                <strong><?php echo htmlspecialchars($available_slots); ?> of <?php echo htmlspecialchars($total_slots); ?> left</strong>
            </article>
        </section>

        <section class="mission-detail-layout">
            <article class="mission-figma-card slot-availability-card">
                <div class="mission-card-title-row">
                    <h2>Slot Availability</h2>
                </div>
                <div class="slot-summary-row">
                    <strong><?php echo htmlspecialchars($available_slots); ?> slots remaining</strong>
                    <span><?php echo htmlspecialchars($booked_slots); ?> / <?php echo htmlspecialchars($total_slots); ?> booked</span>
                </div>
                <div class="slot-dot-grid" aria-hidden="true">
                    <?php echo kk_slot_dots($available_slots, $total_slots); ?>
                </div>
                <div class="slot-dot-legend">
                    <span><i class="is-available"></i>Available</span>
                    <span><i class="is-booked"></i>Booked</span>
                </div>
            </article>

            <article class="mission-figma-card">
                <h2>What to Expect</h2>
                <ul class="figma-check-list">
                    <li>Medical screening upon arrival</li>
                    <li>Surgery under local anesthesia (20-30 min)</li>
                    <li>Post-op monitoring (1-2 hours)</li>
                    <li>Free medications and eye shield provided</li>
                </ul>
            </article>

            <article class="mission-figma-card">
                <h2>Before You Come</h2>
                <ul class="figma-check-list">
                    <li>Valid government ID</li>
                    <li>Fasting 6 hours before surgery</li>
                    <li>Bring a companion (18+ years)</li>
                    <li>No contact lenses 1 week prior</li>
                    <?php if (!empty($mission['guidelines'])): ?>
                        <li><?php echo htmlspecialchars($mission['guidelines']); ?></li>
                    <?php endif; ?>
                </ul>
            </article>
        </section>

        <section class="mission-location-card">
            <div class="mission-location-copy">
                <strong>Location</strong>
                <span><?php echo htmlspecialchars($location_label); ?></span>
            </div>
            <div class="mission-map-placeholder" aria-hidden="true"></div>
        </section>

        <section class="mission-ready-card">
            <div>
                <h2>Ready to secure your slot?</h2>
                <p>Only <strong><?php echo htmlspecialchars($available_slots); ?> slots</strong> remaining. Book before they fill up.</p>
            </div>
            <?php if ($is_bookable): ?>
                <a href="book_slot.php?id=<?php echo urlencode($mission_id); ?>" class="btn-book">Book This Mission Now</a>
            <?php else: ?>
                <button class="btn-book disabled-action" disabled>No Slots Available</button>
            <?php endif; ?>
        </section>

        <section class="mission-help-card">
            <strong>Have questions before booking?</strong>
            <div>
                <a href="faq.php" class="compact-button">FAQ</a>
                <a href="patient_guide.php" class="compact-button">Patient Guide</a>
            </div>
        </section>
    </main>

    <?php kk_render_footer('pages'); ?>
</body>
</html>
