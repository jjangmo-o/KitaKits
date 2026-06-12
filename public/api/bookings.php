<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_auth.php');

require_method('GET');

$patient = current_patient_user();
if (!$patient || empty($patient['patient_id'])) {
    json_error('Patient authentication required.', 401);
}

function format_booking($booking)
{
    return [
        'booking_id' => (int)$booking['booking_id'],
        'mission_id' => (int)$booking['mission_id'],
        'booking_reference' => $booking['booking_reference'],
        'booking_ref' => $booking['booking_reference'],
        'booking_status' => $booking['booking_status'],
        'status' => $booking['booking_status'],
        'patient_name' => $booking['patient_name'],
        'contact_number' => $booking['contact_number'],
        'organizer_name' => $booking['organizer_name'],
        'mission_name' => $booking['mission_name'],
        'mission_date' => $booking['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($booking['mission_date'])),
        'location' => $booking['location'],
        'full_address' => $booking['full_address'],
        'available_slots' => (int)$booking['available_slots'],
        'companion_count' => (int)$booking['companion_count'],
        'total_headcount' => (int)$booking['total_headcount'],
        'intake_review_status' => $booking['intake_review_status'] ?: 'not_submitted',
        'day_of_instructions' => $booking['day_of_instructions']
    ];
}

try {
    $sql = "SELECT b.booking_id,
                   b.mission_id,
                   b.booking_reference,
                   b.booking_status,
                   b.patient_name,
                   b.contact_number,
                   b.companion_count,
                   b.total_headcount,
                   m.mission_name,
                   m.organizer_name,
                   m.mission_date,
                   m.location,
                   m.full_address,
                   m.available_slots,
                   m.day_of_instructions,
                   i.review_status AS intake_review_status
            FROM bookings b
            JOIN missions m ON b.mission_id = m.mission_id
            LEFT JOIN medical_intake_forms i ON i.booking_id = b.booking_id
            WHERE b.patient_id = :patient_id
            ORDER BY m.mission_date DESC, b.requested_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':patient_id' => (int)$patient['patient_id']]);

    json_success('Bookings loaded.', [
        'contact' => $patient['contact_number'],
        'bookings' => array_map('format_booking', $stmt->fetchAll(PDO::FETCH_ASSOC))
    ]);
} catch (PDOException $e) {
    json_error('Unable to load bookings right now. Please try again later.', 500);
}
?>
