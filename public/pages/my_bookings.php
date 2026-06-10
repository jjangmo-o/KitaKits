<?php
require_once(__DIR__ . '/../api/_auth.php');

if (current_patient_id()) {
    header('Location: patient_portal.php#portal-bookings');
    exit();
}

header('Location: login.php');
exit();
?>
