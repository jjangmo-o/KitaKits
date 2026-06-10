<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_auth.php');

require_admin_page('../admin/login.php');

// get mission ID from the URL. e.g. actions/delete_mission.php?id=3
$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// if no valid ID, redirect back to dashboard
if ($mission_id === 0) {
    header("Location: ../admin/admin_dashboard.php");
    exit();
}

try {
    // Check the mission actually exists before trying to delete it
    $check = $conn->prepare("SELECT mission_id FROM missions WHERE mission_id = :id");
    $check->execute([':id' => $mission_id]);

    if ($check->rowCount() > 0) {
        // DELETE the mission; bookings are auto-deleted too (ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM missions WHERE mission_id = :id");
        $stmt->execute([':id' => $mission_id]);
    }

    // Redirect back to dashboard with a success flag
    header("Location: ../admin/admin_dashboard.php?deleted=1");
    exit();

} catch (PDOException $e) {
    // if something went wrong, redirect back with error info
    header("Location: ../admin/admin_dashboard.php?error=1");
    exit();
}
?>
