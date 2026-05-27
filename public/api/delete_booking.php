<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    json_error('Method not allowed.', 405);
}

$input = $_SERVER['REQUEST_METHOD'] === 'DELETE' ? $_GET : $_POST;
$booking_id = isset($input['id']) ? (int)$input['id'] : 0;
$contact = normalize_contact_number($input['contact'] ?? '');

if ($booking_id <= 0 || $contact === '' || !contact_number_is_valid($contact)) {
    json_error('Invalid booking details provided.', 422);
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

    if (!$booking) {
        $conn->rollBack();
        json_error('Booking not found.', 404);
    }

    $delete = $conn->prepare("DELETE FROM bookings WHERE booking_id = :id AND contact_number = :contact");
    $delete->execute([
        ':id' => $booking_id,
        ':contact' => $contact
    ]);

    $update = $conn->prepare("UPDATE missions
                              SET available_slots = available_slots + 1
                              WHERE mission_id = :mission_id");
    $update->execute([':mission_id' => $booking['mission_id']]);

    $conn->commit();

    json_success('Booking cancelled successfully.', [
        'booking_id' => $booking_id,
        'contact' => $contact
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    json_error('Unable to cancel this booking right now. Please try again later.', 500);
}
?>
