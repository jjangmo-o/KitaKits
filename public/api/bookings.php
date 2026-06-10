<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

require_method('GET');

$contact = normalize_contact_number($_GET['contact'] ?? '');

if ($contact === '') {
    json_error('Enter the contact number you used for booking.', 422);
}

if (!contact_number_is_valid($contact)) {
    json_error('Enter a valid contact number with 7 to 15 digits.', 422);
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
            WHERE b.contact_number = :contact
            ORDER BY m.mission_date DESC, b.requested_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':contact' => $contact]);

    json_success('Bookings loaded.', [
        'contact' => $contact,
        'bookings' => array_map('format_booking', $stmt->fetchAll(PDO::FETCH_ASSOC))
    ]);
} catch (PDOException $e) {
    json_error('Unable to load bookings right now. Please try again later.', 500);
}
?>
