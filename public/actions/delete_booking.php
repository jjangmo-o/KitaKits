<?php
require_once(__DIR__ . '/../../app/config/db.php');

function normalize_contact_number($contact)
{
    return preg_replace('/[\s\-\(\)]/', '', trim($contact));
}

function contact_number_is_valid($contact)
{
    return preg_match('/^\+?[0-9]{7,15}$/', $contact) === 1;
}

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contact = isset($_GET['contact']) ? normalize_contact_number($_GET['contact']) : '';

if ($booking_id <= 0 || $contact === '' || !contact_number_is_valid($contact)) {
    header("Location: ../pages/login.php");
    exit();
}

try {
    $conn->beginTransaction();

    $select = $conn->prepare("SELECT booking_id, mission_id, booking_status
                              FROM bookings
                              WHERE booking_id = :id AND contact_number = :contact
                              FOR UPDATE");
    $select->execute([
        ':id' => $booking_id,
        ':contact' => $contact
    ]);
    $booking = $select->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $cancel = $conn->prepare("UPDATE bookings
                                  SET booking_status = 'cancelled'
                                  WHERE booking_id = :id AND contact_number = :contact");
        $cancel->execute([
            ':id' => $booking_id,
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
