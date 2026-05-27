<?php
require_once('db.php');

$error = '';

if (isset($_POST['submit'])) {
    $organizer = trim($_POST['organizer_name']);
    $date      = trim($_POST['mission_date']);
    $location  = trim($_POST['location']);
    $slots     = trim($_POST['available_slots']);
    $date_check = DateTime::createFromFormat('Y-m-d', $date);
    
    if (empty($organizer) || empty($date) || empty($location) || empty($slots)) {
        $error = 'All fields are required. Please fill in every box.';
    } elseif (strlen($organizer) > 100) {
        $error = 'Organizer name must be 100 characters or fewer.';
    } elseif (strlen($location) > 255) {
        $error = 'Location must be 255 characters or fewer.';
    } elseif (!$date_check || $date_check->format('Y-m-d') !== $date) {
        $error = 'Please enter a valid mission date.';
    } elseif ($date < date('Y-m-d')) {
        $error = 'Mission date cannot be in the past.';
    } elseif (filter_var($slots, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
        $error = 'Available slots must be at least 1.';
    } else {
        try {
            $sql = "INSERT INTO missions (organizer_name, mission_date, location, available_slots) 
                    VALUES (:organizer, :date, :location, :slots)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':organizer' => $organizer,
                ':date'      => $date,
                ':location'  => $location,
                ':slots'     => (int)$slots
            ]);
            
            header("Location: admin_dashboard.php?added=1");
            exit();
            
        } catch (PDOException $e) {
            $error = 'Error saving mission: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Mission | KitaKits</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-content">
                <h1>Add New Mission</h1>
                <p>Create a new cataract surgery mission</p>
            </div>
        </div>
    </header>

    <div class="container">
        <a href="admin_dashboard.php" class="btn-back">
            <span>← </span>
            Back to Dashboard
        </a>
        
        <!-- display error message to the user -->
        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- mission form -->
        <form method="POST" action="">
            
            <label for="organizer_name">🏥 Organizer Name</label>
            <input 
                type="text"
                id="organizer_name" 
                name="organizer_name" 
                value="<?php echo isset($_POST['organizer_name']) ? htmlspecialchars($_POST['organizer_name']) : ''; ?>"
                placeholder="e.g., Marikina City Health Office"
                maxlength="100"
                required
                aria-label="Organization name organizing the mission"
            >
            
            <label for="mission_date">📅 Mission Date</label>
            <input 
                type="date" 
                id="mission_date" 
                name="mission_date"
                value="<?php echo isset($_POST['mission_date']) ? htmlspecialchars($_POST['mission_date']) : ''; ?>"
                min="<?php echo date('Y-m-d'); ?>"
                required
                aria-label="Date of the mission"
            >
            
            <label for="location">📍 Location</label>
            <input 
                type="text"
                id="location"
                name="location" 
                value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>"
                placeholder="e.g., Brgy. Concepcion Dos, Marikina"
                maxlength="255"
                required
                aria-label="Mission location or venue"
            >
            
            <label for="available_slots">💺 Available Slots</label>
            <input 
                type="number" 
                id="available_slots" 
                name="available_slots" 
                value="<?php echo isset($_POST['available_slots']) ? htmlspecialchars($_POST['available_slots']) : ''; ?>"
                min="1"
                placeholder="e.g., 50"
                required
                aria-label="Number of surgery slots available"
            >
            
            <button type="submit" name="submit">✓ Save Mission</button>
        </form>
    </div>
</body>
</html>