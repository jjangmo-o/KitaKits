<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_auth.php');

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    json_error('Method not allowed.', 405);
}

$input = $_SERVER['REQUEST_METHOD'] === 'DELETE' ? $_GET : read_request_input();
$booking_id = isset($input['id']) ? (int)$input['id'] : (int)($input['booking_id'] ?? 0);
$contact = normalize_contact_number($input['contact'] ?? '');
$is_admin_action = false;
$admin_id = null;

if (isset($_SERVER['HTTP_X_ADMIN_TOKEN']) || isset($_GET['admin_token']) || current_admin_user()) {
    $admin_id = require_admin();
    $is_admin_action = true;
}

if ($booking_id <= 0) {
    json_error('Invalid booking details provided.', 422);
}

if (!$is_admin_action && ($contact === '' || !contact_number_is_valid($contact))) {
    json_error('Invalid booking details provided.', 422);
}

try {
    $conn->beginTransaction();

    $sql = "SELECT booking_id, mission_id, booking_status
            FROM bookings
            WHERE booking_id = :id" . ($is_admin_action ? "" : " AND contact_number = :contact") . "
            FOR UPDATE";
    $select = $conn->prepare($sql);
    $params = [':id' => $booking_id];

    if (!$is_admin_action) {
        $params[':contact'] = $contact;
    }

    $select->execute($params);
    $booking = $select->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $conn->rollBack();
        json_error('Booking not found.', 404);
    }

    if ($booking['booking_status'] === 'cancelled') {
        $conn->rollBack();
        json_error('Booking is already cancelled.', 409);
    }

    $update = $conn->prepare("UPDATE bookings
                              SET booking_status = 'cancelled',
                                  approved_by = :admin_id
                              WHERE booking_id = :id");
    $update->execute([
        ':admin_id' => $admin_id,
        ':id' => $booking_id
    ]);

    $conn->commit();

    json_success('Booking cancelled successfully.', [
        'booking_id' => $booking_id,
        'contact' => $contact,
        'booking_status' => 'cancelled'
    ]);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    json_error('Unable to cancel this booking right now. Please try again later.', 500);
}
?>
