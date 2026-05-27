<?php
require_once(__DIR__ . '/../db.php');
require_once(__DIR__ . '/_response.php');

require_method('GET');

function format_admin_mission($mission)
{
    return [
        'mission_id' => (int)$mission['mission_id'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'location' => $mission['location'],
        'available_slots' => (int)$mission['available_slots'],
        'total_bookings' => (int)$mission['total_bookings']
    ];
}

function format_admin_booking($booking)
{
    return [
        'booking_id' => (int)$booking['booking_id'],
        'patient_name' => $booking['patient_name'],
        'contact_number' => $booking['contact_number'],
        'organizer_name' => $booking['organizer_name'],
        'mission_date' => $booking['mission_date'],
        'mission_date_short' => date('M d, Y', strtotime($booking['mission_date']))
    ];
}

try {
    $missions = $conn->prepare("SELECT m.*, COUNT(b.booking_id) AS total_bookings
                                FROM missions m
                                LEFT JOIN bookings b ON m.mission_id = b.mission_id
                                GROUP BY m.mission_id
                                ORDER BY m.mission_date ASC");
    $missions->execute();

    $bookings = $conn->prepare("SELECT b.*, m.organizer_name, m.mission_date
                                FROM bookings b
                                JOIN missions m ON b.mission_id = m.mission_id
                                ORDER BY b.booking_id DESC");
    $bookings->execute();

    json_success('Admin dashboard loaded.', [
        'missions' => array_map('format_admin_mission', $missions->fetchAll(PDO::FETCH_ASSOC)),
        'bookings' => array_map('format_admin_booking', $bookings->fetchAll(PDO::FETCH_ASSOC))
    ]);
} catch (PDOException $e) {
    json_error('Unable to load admin dashboard data right now. Please try again later.', 500);
}
?>
