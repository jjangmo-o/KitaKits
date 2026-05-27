<?php
require_once(__DIR__ . '/../../app/config/db.php');

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
    $organizer = trim($_POST['organizer_name']);
    $date      = trim($_POST['mission_date']);
    $location  = trim($_POST['location']);
    $slots     = trim($_POST['available_slots']);
    $date_check = DateTime::createFromFormat('Y-m-d', $date);

    if (empty($organizer) || empty($date) || empty($location) || $slots === '') {
        $error = 'All fields are required. Please fill in every box.';
    } elseif (strlen($organizer) > 100) {
        $error = 'Organizer name must be 100 characters or fewer.';
    } elseif (strlen($location) > 255) {
        $error = 'Location must be 255 characters or fewer.';
    } elseif (!$date_check || $date_check->format('Y-m-d') !== $date) {
        $error = 'Please enter a valid mission date.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Mission date cannot be in the past.';
    } elseif (filter_var($slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $error = 'Available slots cannot be negative.';
    } else {
        try {
            $sql = "UPDATE missions
                    SET organizer_name = :organizer,
                        mission_date = :date,
                        location = :location,
                        available_slots = :slots
                    WHERE mission_id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':organizer' => $organizer,
                ':date'      => $date,
                ':location'  => $location,
                ':slots'     => (int)$slots,
                ':id'        => $mission_id
            ]);

            header("Location: admin_dashboard.php?updated=1");
            exit();

        } catch (PDOException $e) {
            $error = 'Error updating mission: ' . $e->getMessage();
        }
    }
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

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-content">
                <h1>Edit Mission</h1>
                <p>Update mission details and schedule</p>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="admin_dashboard.php" class="btn-back">
            <span>&larr; </span>
            Back to Dashboard
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error">Warning: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($mission): ?>
            <form method="POST" action="">
                <label for="organizer_name">Organizer Name</label>
                <input
                    type="text"
                    id="organizer_name"
                    name="organizer_name"
                    value="<?php echo isset($_POST['organizer_name']) ? htmlspecialchars($_POST['organizer_name']) : htmlspecialchars($mission['organizer_name']); ?>"
                    placeholder="e.g., Marikina City Health Office"
                    maxlength="100"
                    required
                    aria-label="Organization name"
                >

                <label for="mission_date">Mission Date</label>
                <input
                    type="date"
                    id="mission_date"
                    name="mission_date"
                    value="<?php echo isset($_POST['mission_date']) ? htmlspecialchars($_POST['mission_date']) : htmlspecialchars($mission['mission_date']); ?>"
                    min="<?php echo date('Y-m-d'); ?>"
                    required
                    aria-label="Date of the mission"
                >

                <label for="location">Location</label>
                <input
                    type="text"
                    id="location"
                    name="location"
                    value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : htmlspecialchars($mission['location']); ?>"
                    placeholder="e.g., Marikina General Hospital"
                    maxlength="255"
                    required
                    aria-label="Mission location or venue"
                >

                <label for="available_slots">Available Slots</label>
                <input
                    type="number"
                    id="available_slots"
                    name="available_slots"
                    value="<?php echo isset($_POST['available_slots']) ? htmlspecialchars($_POST['available_slots']) : htmlspecialchars($mission['available_slots']); ?>"
                    placeholder="e.g., 50"
                    min="0"
                    required
                    aria-label="Number of surgery slots available"
                >

                <button type="submit" name="submit">Update Mission</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
