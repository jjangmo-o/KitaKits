<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

require_admin_page();
$error = '';
$mission = null;
$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($mission_id > 0) {
    $sql = "SELECT * FROM missions WHERE mission_id = :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id' => $mission_id]);
    $mission = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        $error = 'Mission not found. Please check the mission ID.';
    }
} else {
    $error = 'Invalid mission ID provided.';
}

if (isset($_POST['submit']) && $mission) {
    $mission_name = trim($_POST['mission_name'] ?? '');
    $organizer = trim($_POST['organizer_name'] ?? '');
    $date = trim($_POST['mission_date'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');
    $venue_name = trim($_POST['venue_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $city_area = trim($_POST['city_area'] ?? '');
    $full_address = trim($_POST['full_address'] ?? '');
    $total_slots = trim($_POST['total_slots'] ?? '');
    $available_slots = trim($_POST['available_slots'] ?? '');
    $mission_status = trim($_POST['mission_status'] ?? 'open');
    $guidelines = trim($_POST['guidelines'] ?? '');
    $day_of_instructions = trim($_POST['day_of_instructions'] ?? '');
    $date_check = DateTime::createFromFormat('Y-m-d', $date);
    $allowed_status = ['draft', 'open', 'closed', 'completed', 'cancelled'];

    if ($mission_name === '' || $organizer === '' || $date === '' || $location === '' || $city_area === '' || $full_address === '' || $total_slots === '' || $available_slots === '') {
        $error = 'Mission name, organizer, date, location, city/area, full address, total slots, and available slots are required.';
    } elseif (strlen($mission_name) > 100 || strlen($organizer) > 100) {
        $error = 'Mission and organizer names must be 100 characters or fewer.';
    } elseif (!$date_check || $date_check->format('Y-m-d') !== $date) {
        $error = 'Please enter a valid mission date.';
    } elseif (filter_var($total_slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false || filter_var($available_slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $error = 'Slot counts cannot be negative.';
    } elseif ((int)$available_slots > (int)$total_slots) {
        $error = 'Available slots cannot exceed total slots.';
    } elseif (!in_array($mission_status, $allowed_status, true)) {
        $error = 'Please choose a valid mission status.';
    } else {
        try {
            $sql = "UPDATE missions
                    SET mission_name = :mission_name,
                        organizer_name = :organizer,
                        mission_date = :date,
                        start_time = :start_time,
                        end_time = :end_time,
                        venue_name = :venue_name,
                        location = :location,
                        city_area = :city_area,
                        full_address = :full_address,
                        total_slots = :total_slots,
                        available_slots = :available_slots,
                        mission_status = :mission_status,
                        guidelines = :guidelines,
                        day_of_instructions = :day_of_instructions
                    WHERE mission_id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':mission_name' => $mission_name,
                ':organizer' => $organizer,
                ':date' => $date,
                ':start_time' => $start_time ?: null,
                ':end_time' => $end_time ?: null,
                ':venue_name' => $venue_name ?: null,
                ':location' => $location,
                ':city_area' => $city_area,
                ':full_address' => $full_address,
                ':total_slots' => (int)$total_slots,
                ':available_slots' => (int)$available_slots,
                ':mission_status' => $mission_status,
                ':guidelines' => $guidelines ?: null,
                ':day_of_instructions' => $day_of_instructions ?: null,
                ':id' => $mission_id
            ]);

            header("Location: admin_dashboard.php?updated=1");
            exit();
        } catch (PDOException $e) {
            $error = 'Error updating mission: ' . $e->getMessage();
        }
    }
}

function mission_value($mission, $field)
{
    return htmlspecialchars($_POST[$field] ?? ($mission[$field] ?? ''));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Mission | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-body">
    <?php kk_render_header(['section' => 'admin', 'active' => 'admin', 'mode' => 'admin']); ?>
    <?php kk_render_breadcrumbs('admin', [
        ['label' => 'Admin Dashboard', 'href' => 'admin_dashboard.php'],
        ['label' => 'Edit Mission'],
    ]); ?>

    <main class="container admin-workspace form-workspace">
        <a href="admin_dashboard.php" class="btn-back">
            Back to Dashboard
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($mission): ?>
            <form method="POST" action="" class="wide-form">
                <label for="mission_name">Mission Name</label>
                <input type="text" id="mission_name" name="mission_name" value="<?php echo mission_value($mission, 'mission_name'); ?>" maxlength="100" required>

                <label for="organizer_name">Organizer Name</label>
                <input type="text" id="organizer_name" name="organizer_name" value="<?php echo mission_value($mission, 'organizer_name'); ?>" maxlength="100" required>

                <div class="form-grid form-grid-3">
                    <div>
                        <label for="mission_date">Mission Date</label>
                        <input type="date" id="mission_date" name="mission_date" value="<?php echo mission_value($mission, 'mission_date'); ?>" required>
                    </div>
                    <div>
                        <label for="start_time">Start Time</label>
                        <input type="time" id="start_time" name="start_time" value="<?php echo mission_value($mission, 'start_time'); ?>">
                    </div>
                    <div>
                        <label for="end_time">End Time</label>
                        <input type="time" id="end_time" name="end_time" value="<?php echo mission_value($mission, 'end_time'); ?>">
                    </div>
                </div>

                <label for="venue_name">Venue Name</label>
                <input type="text" id="venue_name" name="venue_name" value="<?php echo mission_value($mission, 'venue_name'); ?>" maxlength="150">

                <div class="form-grid">
                    <div>
                        <label for="location">Short Location</label>
                        <input type="text" id="location" name="location" value="<?php echo mission_value($mission, 'location'); ?>" maxlength="255" required>
                    </div>
                    <div>
                        <label for="city_area">City / Area</label>
                        <input type="text" id="city_area" name="city_area" value="<?php echo mission_value($mission, 'city_area'); ?>" maxlength="100" required>
                    </div>
                </div>

                <label for="full_address">Full Address</label>
                <input type="text" id="full_address" name="full_address" value="<?php echo mission_value($mission, 'full_address'); ?>" maxlength="255" required>

                <div class="form-grid form-grid-3">
                    <div>
                        <label for="total_slots">Total Slots</label>
                        <input type="number" id="total_slots" name="total_slots" value="<?php echo mission_value($mission, 'total_slots'); ?>" min="0" required>
                    </div>
                    <div>
                        <label for="available_slots">Available Slots</label>
                        <input type="number" id="available_slots" name="available_slots" value="<?php echo mission_value($mission, 'available_slots'); ?>" min="0" required>
                    </div>
                    <div>
                        <label for="mission_status">Status</label>
                        <select id="mission_status" name="mission_status">
                            <?php $selected_status = $_POST['mission_status'] ?? $mission['mission_status']; ?>
                            <?php foreach (['draft', 'open', 'closed', 'completed', 'cancelled'] as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $selected_status === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label for="guidelines">Guidelines</label>
                <textarea id="guidelines" name="guidelines" rows="4"><?php echo mission_value($mission, 'guidelines'); ?></textarea>

                <label for="day_of_instructions">Day-of Instructions</label>
                <textarea id="day_of_instructions" name="day_of_instructions" rows="4"><?php echo mission_value($mission, 'day_of_instructions'); ?></textarea>

                <button type="submit" name="submit">Update Mission</button>
            </form>
        <?php endif; ?>
    </main>

    <?php kk_render_footer('admin'); ?>
</body>
</html>
