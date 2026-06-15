<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');
require_once(__DIR__ . '/../includes/layout.php');

$admin_id = require_admin_page();
$error = '';

if (isset($_POST['submit'])) {
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

    if ($mission_name === '' || $organizer === '' || $date === '' || $location === '' || $city_area === '' || $full_address === '' || $total_slots === '') {
        $error = 'Mission name, organizer, date, location, city/area, full address, and total slots are required.';
    } elseif (strlen($mission_name) > 100 || strlen($organizer) > 100 || strlen($location) > 255 || strlen($city_area) > 100 || strlen($full_address) > 255) {
        $error = 'Please keep mission text fields within their allowed lengths.';
    } elseif (!$date_check || $date_check->format('Y-m-d') !== $date) {
        $error = 'Please enter a valid mission date.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Mission date cannot be in the past.';
    } elseif (filter_var($total_slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $error = 'Total slots must be at least 1.';
    } elseif ($available_slots !== '' && filter_var($available_slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $error = 'Available slots cannot be negative.';
    } elseif ($available_slots !== '' && (int)$available_slots > (int)$total_slots) {
        $error = 'Available slots cannot exceed total slots.';
    } elseif (!in_array($mission_status, $allowed_status, true)) {
        $error = 'Please choose a valid mission status.';
    } else {
        try {
            $available = $available_slots === '' ? (int)$total_slots : (int)$available_slots;
            $sql = "INSERT INTO missions
                (mission_name, organizer_name, mission_date, start_time, end_time, venue_name, location, city_area,
                 full_address, total_slots, available_slots, mission_status, guidelines, day_of_instructions, created_by)
                VALUES
                (:mission_name, :organizer_name, :mission_date, :start_time, :end_time, :venue_name, :location, :city_area,
                 :full_address, :total_slots, :available_slots, :mission_status, :guidelines, :day_of_instructions, :created_by)";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':mission_name' => $mission_name,
                ':organizer_name' => $organizer,
                ':mission_date' => $date,
                ':start_time' => $start_time ?: null,
                ':end_time' => $end_time ?: null,
                ':venue_name' => $venue_name ?: null,
                ':location' => $location,
                ':city_area' => $city_area,
                ':full_address' => $full_address,
                ':total_slots' => (int)$total_slots,
                ':available_slots' => $available,
                ':mission_status' => $mission_status,
                ':guidelines' => $guidelines ?: null,
                ':day_of_instructions' => $day_of_instructions ?: null,
                ':created_by' => $admin_id
            ]);

            header("Location: admin_dashboard.php?added=1");
            exit();
        } catch (PDOException $e) {
            $error = 'Error saving mission: ' . $e->getMessage();
        }
    }
}

function old_value($field, $default = '')
{
    return htmlspecialchars($_POST[$field] ?? $default);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Mission | KitaKits</title>
    <?php kk_render_favicon('admin'); ?>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="admin-body">
    <?php kk_render_header(['section' => 'admin', 'active' => 'add_mission', 'mode' => 'admin']); ?>
    <?php kk_render_breadcrumbs('admin', [
        ['label' => 'Admin Dashboard', 'href' => 'admin_dashboard.php'],
        ['label' => 'Add Mission'],
    ]); ?>

    <main class="container admin-workspace form-workspace">
        <a href="admin_dashboard.php" class="btn-back">
            Back to Dashboard
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" class="wide-form">
            <label for="mission_name">Mission Name</label>
            <input type="text" id="mission_name" name="mission_name" value="<?php echo old_value('mission_name'); ?>" maxlength="100" required>

            <label for="organizer_name">Organizer Name</label>
            <input type="text" id="organizer_name" name="organizer_name" value="<?php echo old_value('organizer_name'); ?>" maxlength="100" required>

            <div class="form-grid form-grid-3">
                <div>
                    <label for="mission_date">Mission Date</label>
                    <input type="date" id="mission_date" name="mission_date" value="<?php echo old_value('mission_date'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div>
                    <label for="start_time">Start Time</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo old_value('start_time'); ?>">
                </div>
                <div>
                    <label for="end_time">End Time</label>
                    <input type="time" id="end_time" name="end_time" value="<?php echo old_value('end_time'); ?>">
                </div>
            </div>

            <label for="venue_name">Venue Name</label>
            <input type="text" id="venue_name" name="venue_name" value="<?php echo old_value('venue_name'); ?>" maxlength="150">

            <div class="form-grid">
                <div>
                    <label for="location">Short Location</label>
                    <input type="text" id="location" name="location" value="<?php echo old_value('location'); ?>" maxlength="255" required>
                </div>
                <div>
                    <label for="city_area">City / Area</label>
                    <input type="text" id="city_area" name="city_area" value="<?php echo old_value('city_area'); ?>" maxlength="100" required>
                </div>
            </div>

            <label for="full_address">Full Address</label>
            <input type="text" id="full_address" name="full_address" value="<?php echo old_value('full_address'); ?>" maxlength="255" required>

            <div class="form-grid form-grid-3">
                <div>
                    <label for="total_slots">Total Slots</label>
                    <input type="number" id="total_slots" name="total_slots" value="<?php echo old_value('total_slots'); ?>" min="1" required>
                </div>
                <div>
                    <label for="available_slots">Available Slots</label>
                    <input type="number" id="available_slots" name="available_slots" value="<?php echo old_value('available_slots'); ?>" min="0" placeholder="Defaults to total">
                </div>
                <div>
                    <label for="mission_status">Status</label>
                    <select id="mission_status" name="mission_status">
                        <?php $selected_status = $_POST['mission_status'] ?? 'open'; ?>
                        <?php foreach (['draft', 'open', 'closed', 'completed', 'cancelled'] as $status): ?>
                            <option value="<?php echo $status; ?>" <?php echo $selected_status === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="guidelines">Guidelines</label>
            <textarea id="guidelines" name="guidelines" rows="4"><?php echo old_value('guidelines'); ?></textarea>

            <label for="day_of_instructions">Day-of Instructions</label>
            <textarea id="day_of_instructions" name="day_of_instructions" rows="4"><?php echo old_value('day_of_instructions'); ?></textarea>

            <button type="submit" name="submit">Save Mission</button>
        </form>
    </main>

    <?php kk_render_footer('admin'); ?>
</body>
</html>
