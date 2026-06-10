<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_auth.php');

require_method('POST');
$admin_id = require_admin();

$input = read_request_input();
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : 0;
$new_status = trim($input['booking_status'] ?? '');
$notes = trim($input['admin_notes'] ?? '');
$allowed = ['booked', 'confirmed', 'rejected', 'cancelled', 'completed', 'no_show'];

if ($booking_id <= 0 || !in_array($new_status, $allowed, true)) {
    json_error('A valid booking id and status are required.', 422);
}

try {
    $conn->beginTransaction();

    $select = $conn->prepare("SELECT b.booking_id, b.booking_status, m.available_slots
                              FROM bookings b
                              JOIN missions m ON m.mission_id = b.mission_id
                              WHERE b.booking_id = :id
                              FOR UPDATE");
    $select->execute([':id' => $booking_id]);
    $booking = $select->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $conn->rollBack();
        json_error('Booking not found.', 404);
    }

    if ($booking['booking_status'] !== 'confirmed' && $new_status === 'confirmed' && (int)$booking['available_slots'] <= 0) {
        $conn->rollBack();
        json_error('No available slots to confirm this booking.', 409);
    }

    $update = $conn->prepare("UPDATE bookings
                              SET booking_status = :status,
                                  approved_by = :admin_id,
                                  admin_notes = :notes
                              WHERE booking_id = :id");
    $update->execute([
        ':status' => $new_status,
        ':admin_id' => $admin_id,
        ':notes' => $notes ?: null,
        ':id' => $booking_id
    ]);

    $conn->commit();

    json_success('Booking status updated.', [
        'booking_id' => $booking_id,
        'booking_status' => $new_status
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    json_error('Unable to update booking status.', 500);
}
?>
