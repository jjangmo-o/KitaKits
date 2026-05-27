<?php
require_once(__DIR__ . '/../db.php');
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
        'patient_name' => $booking['patient_name'],
        'contact_number' => $booking['contact_number'],
        'organizer_name' => $booking['organizer_name'],
        'mission_date' => $booking['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($booking['mission_date'])),
        'location' => $booking['location'],
        'available_slots' => (int)$booking['available_slots'],
        'status' => 'Confirmed'
    ];
}

try {
    $sql = "SELECT b.*, m.organizer_name, m.mission_date, m.location, m.available_slots
            FROM bookings b
            JOIN missions m ON b.mission_id = m.mission_id
            WHERE b.contact_number = :contact
            ORDER BY m.mission_date DESC";
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
