<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');

require_method('GET');

function format_mission($mission)
{
    return [
        'mission_id' => (int)$mission['mission_id'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($mission['mission_date'])),
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'location' => $mission['location'],
        'available_slots' => (int)$mission['available_slots']
    ];
}

try {
    $available = $conn->prepare("SELECT * FROM missions WHERE available_slots > 0 ORDER BY mission_date ASC");
    $available->execute();

    $fully_booked = $conn->prepare("SELECT * FROM missions WHERE available_slots = 0 ORDER BY mission_date ASC");
    $fully_booked->execute();

    json_success('Missions loaded.', [
        'available' => array_map('format_mission', $available->fetchAll(PDO::FETCH_ASSOC)),
        'fully_booked' => array_map('format_mission', $fully_booked->fetchAll(PDO::FETCH_ASSOC))
    ]);
} catch (PDOException $e) {
    json_error('Unable to load missions right now. Please try again later.', 500);
}
?>
