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
$contact = isset($_GET['contact']) ? normalize_contact_number($_GET['contact']) : '';

if ($booking_id <= 0 || $contact === '' || !contact_number_is_valid($contact)) {
    header("Location: my_bookings.php");
    exit();
}

try {
    $conn->beginTransaction();

    $select = $conn->prepare("SELECT booking_id, mission_id
                              FROM bookings
                              WHERE booking_id = :id AND contact_number = :contact
                              FOR UPDATE");
    $select->execute([
        ':id' => $booking_id,
        ':contact' => $contact
    ]);
    $booking = $select->fetch(PDO::FETCH_ASSOC);

    if ($booking) {
        $delete = $conn->prepare("DELETE FROM bookings WHERE booking_id = :id AND contact_number = :contact");
        $delete->execute([
            ':id' => $booking_id,
            ':contact' => $contact
        ]);

        $update = $conn->prepare("UPDATE missions
                                  SET available_slots = available_slots + 1
                                  WHERE mission_id = :mission_id");
        $update->execute([':mission_id' => $booking['mission_id']]);
    }

    $conn->commit();
    header("Location: my_bookings.php?contact=" . urlencode($contact) . "&deleted=1");
    exit();

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    header("Location: my_bookings.php?contact=" . urlencode($contact));
    exit();
}
?>
