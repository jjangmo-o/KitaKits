<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../api/_auth.php');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contact = isset($_GET['contact']) ? normalize_contact_number($_GET['contact']) : '';
$patient_id = current_patient_id();

if ($booking_id <= 0 || !$patient_id || $contact === '' || !contact_number_is_valid($contact)) {
    header("Location: ../pages/login.php");
    exit();
}

try {
    $conn->beginTransaction();

    $select = $conn->prepare("SELECT booking_id, mission_id, booking_status
                              FROM bookings
                              WHERE booking_id = :id
                                AND patient_id = :patient_id
                                AND contact_number = :contact
                                AND booking_status = 'booked'
                              FOR UPDATE");
    $select->execute([
        ':id' => $booking_id,
        ':patient_id' => $patient_id,
        ':contact' => $contact
    ]);
    $booking = $select->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $cancel = $conn->prepare("UPDATE bookings
                                  SET booking_status = 'cancelled'
                                  WHERE booking_id = :id
                                    AND patient_id = :patient_id
                                    AND contact_number = :contact
                                    AND booking_status = 'booked'");
        $cancel->execute([
            ':id' => $booking_id,
            ':patient_id' => $patient_id,
            ':contact' => $contact
        ]);
    }

    $conn->commit();
    header("Location: ../pages/patient_portal.php#portal-bookings");
    exit();

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header("Location: ../pages/patient_portal.php#portal-bookings");
    exit();
}
?>
