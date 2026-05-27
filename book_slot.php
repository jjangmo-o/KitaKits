<?php
require_once('db.php');

function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim($contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^\+?[0-9]{7,15}$/', $contact) === 1;
}

$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($mission_id === 0) {
    header("Location: index.php");
    exit();
}

$sql  = "SELECT * FROM missions WHERE mission_id = :id LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    header("Location: index.php");
    exit();
}

$error = '';

if (isset($_POST['submit'])) {
    $patient_name   = trim($_POST['patient_name']);
    $contact_number = normalize_contact_number($_POST['contact_number']);

    if (empty($patient_name) || empty($contact_number)) {
        $error = 'Please provide both your name and contact number.';
    } elseif (strlen($patient_name) > 100) {
        $error = 'Full name must be 100 characters or fewer.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter a valid contact number with 7 to 15 digits.';
    } elseif ($mission['mission_date'] < date('Y-m-d')) {
        $error = 'This mission date has already passed.';
    } else {
        try {
            $conn->beginTransaction();

            $lock = $conn->prepare("SELECT available_slots, mission_date FROM missions WHERE mission_id = :id FOR UPDATE");
            $lock->execute([':id' => $mission_id]);
            $locked_mission = $lock->fetch(PDO::FETCH_ASSOC);

            if (!$locked_mission) {
                $conn->rollBack();
                header("Location: index.php");
                exit();
            }

            if ($locked_mission['mission_date'] < date('Y-m-d')) {
                $conn->rollBack();
                $error = 'This mission date has already passed.';
            } elseif ((int)$locked_mission['available_slots'] <= 0) {
                $conn->rollBack();
                $error = 'This mission is already fully booked.';
            } else {
                $sql_insert = "INSERT INTO bookings (mission_id, patient_name, contact_number)
                               VALUES (:mission_id, :patient_name, :contact_number)";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->execute([
                    ':mission_id'     => $mission_id,
                    ':patient_name'   => $patient_name,
                    ':contact_number' => $contact_number
                ]);

                $sql_update = "UPDATE missions
                               SET available_slots = available_slots - 1
                               WHERE mission_id = :mission_id AND available_slots > 0";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([':mission_id' => $mission_id]);

                if ($stmt_update->rowCount() !== 1) {
                    $conn->rollBack();
                    $error = 'This mission is already fully booked.';
                } else {
                    $conn->commit();
                    header("Location: index.php?success=1");
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
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Book Your Surgery Slot</h1>
                    <p>Reserve your spot at the cataract mission</p>
                </div>
                <div class="header-actions" aria-label="Primary navigation">
                    <nav class="header-nav">
                        <a href="index.php">Home (Missions)</a>
                        <a href="my_bookings.php">My Bookings</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                        <a href="about_cataracts.php">About Cataracts</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="index.php" class="btn-back">
            <span>&larr; </span>
            Back to Available Missions
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error">Warning: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="booking-summary">
            <strong>Mission Details:</strong><br><br>
            <strong>Organizer:</strong> <?php echo htmlspecialchars($mission['organizer_name']); ?><br>
            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($mission['mission_date'])); ?><br>
            <strong>Location:</strong> <?php echo htmlspecialchars($mission['location']); ?><br>
            <strong>Remaining Slots:</strong> <span id="remainingSlotsValue" class="slot-count"><?php echo htmlspecialchars($mission['available_slots']); ?></span>
        </div>

        <div id="bookingStatus" class="status-message" role="status" hidden></div>
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
                    value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : ''; ?>"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                    aria-label="Patient full name"
                >

                <label for="contact_number">Contact Number *</label>
                <input
                    type="text"
                    id="contact_number"
                    name="contact_number"
                    value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>"
                    placeholder="e.g., 09123456789"
                    pattern="[\+0-9\s\-\(\)]{7,20}"
                    required
                    aria-label="Patient contact number"
                >

                <button type="submit" name="submit" id="bookingSubmit">Confirm My Booking</button>
            </form>
        <?php else: ?>
            <div class="alert alert-error">This mission is not accepting bookings.</div>
        <?php endif; ?>

        <div class="important-info">
            <h3>Important Information</h3>
            <ul>
                <li>Your booking will be confirmed immediately after submission</li>
                <li>You will receive details to your contact number</li>
                <li>Please arrive 30 minutes early on the mission date</li>
                <li>Bring your ID and any relevant medical documents</li>
                <li>If you cannot attend, please notify the organizer as soon as possible</li>
            </ul>
        </div>
    </div>
    <script src="assets/js/api.js"></script>
    <script src="assets/js/book-slot.js"></script>
</body>
</html>
