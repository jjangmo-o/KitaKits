<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

require_method('POST');

$input = read_request_input();
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : 0;

if ($booking_id <= 0) {
    json_error('Invalid booking id provided.', 422);
}

try {
    require_once(__DIR__ . '/_auth.php');
    require_admin();

    $conn->beginTransaction();

    $select = $conn->prepare("SELECT b.booking_id, b.status, b.mission_id, m.available_slots
                              FROM bookings b
                              JOIN missions m ON m.mission_id = b.mission_id
                              WHERE b.booking_id = :id
                              FOR UPDATE");
    $select->execute([':id' => $booking_id]);
    $row = $select->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $conn->rollBack();
        json_error('Booking not found.', 404);
    }

    if ($row['status'] === 'confirmed') {
        $conn->rollBack();
        json_error('Booking is already confirmed.', 409);
    }

    if ($row['status'] !== 'booked') {
        $conn->rollBack();
        json_error('Only a booked request can be confirmed.', 409);
    }

    if ((int)$row['available_slots'] <= 0) {
        $conn->rollBack();
        json_error('No available slots to confirm this booking.', 409);
    }

    $dec = $conn->prepare("UPDATE missions SET available_slots = available_slots - 1 WHERE mission_id = :mission_id AND available_slots > 0");
    $dec->execute([':mission_id' => $row['mission_id']]);

    if ($dec->rowCount() !== 1) {
        $conn->rollBack();
        json_error('Unable to reserve a slot for this booking.', 500);
    }

    $update = $conn->prepare("UPDATE bookings SET status = 'confirmed' WHERE booking_id = :id");
    $update->execute([':id' => $booking_id]);

    $conn->commit();

    json_success('Booking confirmed successfully.', ['booking_id' => $booking_id, 'status' => 'confirmed']);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    json_error('Unable to confirm booking now. Try again later.', 500);
}

?>
