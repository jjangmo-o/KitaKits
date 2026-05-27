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

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$current_contact = isset($_GET['contact']) ? normalize_contact_number($_GET['contact']) : '';
$error = '';
$booking = null;

if ($booking_id <= 0 || $current_contact === '' || !contact_number_is_valid($current_contact)) {
    header("Location: my_bookings.php");
    exit();
}

$sql = "SELECT b.*, m.organizer_name, m.mission_date, m.location
        FROM bookings b
        JOIN missions m ON b.mission_id = m.mission_id
        WHERE b.booking_id = :id AND b.contact_number = :contact
        LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->execute([
    ':id' => $booking_id,
    ':contact' => $current_contact
]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header("Location: my_bookings.php?contact=" . urlencode($current_contact));
    exit();
}

if (isset($_POST['submit'])) {
    $patient_name = trim($_POST['patient_name']);
    $contact_number = normalize_contact_number($_POST['contact_number']);

    if (empty($patient_name) || empty($contact_number)) {
        $error = 'Please provide both your name and contact number.';
    } elseif (strlen($patient_name) > 100) {
        $error = 'Full name must be 100 characters or fewer.';
    } elseif (!contact_number_is_valid($contact_number)) {
        $error = 'Enter a valid contact number with 7 to 15 digits.';
    } else {
        try {
            $update = $conn->prepare("UPDATE bookings
                                      SET patient_name = :patient_name,
                                          contact_number = :contact_number
                                      WHERE booking_id = :id AND contact_number = :current_contact");
            $update->execute([
                ':patient_name' => $patient_name,
                ':contact_number' => $contact_number,
                ':id' => $booking_id,
                ':current_contact' => $current_contact
            ]);

            header("Location: my_bookings.php?contact=" . urlencode($contact_number) . "&updated=1");
            exit();

        } catch (PDOException $e) {
            $error = 'Error updating booking: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking | KitaKits</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="Kitakits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Edit Booking</h1>
                    <p>Update your reservation details</p>
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

    <main class="container">
        <a href="my_bookings.php?contact=<?php echo urlencode($current_contact); ?>" class="btn-back">
            <span>&larr; </span>
            Back to My Bookings
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error">Warning: <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="booking-summary">
            <strong>Mission:</strong> <?php echo htmlspecialchars($booking['organizer_name']); ?><br>
            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['mission_date'])); ?><br>
            <strong>Location:</strong> <?php echo htmlspecialchars($booking['location']); ?>
        </div>

        <form method="POST" action="">
            <label for="patient_name">Full Name</label>
            <input
                type="text"
                id="patient_name"
                name="patient_name"
                value="<?php echo isset($_POST['patient_name']) ? htmlspecialchars($_POST['patient_name']) : htmlspecialchars($booking['patient_name']); ?>"
                maxlength="100"
                required
                aria-label="Patient full name"
            >

            <label for="contact_number">Contact Number</label>
            <input
                type="text"
                id="contact_number"
                name="contact_number"
                value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : htmlspecialchars($booking['contact_number']); ?>"
                pattern="[\+0-9\s\-\(\)]{7,20}"
                required
                aria-label="Patient contact number"
            >

            <button type="submit" name="submit">Update Booking</button>
        </form>
    </main>
</body>
</html>
