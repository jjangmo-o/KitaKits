<?php
require_once(__DIR__ . '/../db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

require_method('POST');

$input = read_request_input();
$mission_id = isset($input['mission_id']) ? (int)$input['mission_id'] : 0;
$patient_name = trim($input['patient_name'] ?? '');
$contact_number = normalize_contact_number($input['contact_number'] ?? '');

if ($mission_id <= 0) {
    json_error('Please choose a valid mission.', 422);
}

if ($patient_name === '' || $contact_number === '') {
    json_error('Please provide both your name and contact number.', 422);
}

if (strlen($patient_name) > 100) {
    json_error('Full name must be 100 characters or fewer.', 422);
}

if (!contact_number_is_valid($contact_number)) {
    json_error('Enter a valid contact number with 7 to 15 digits.', 422);
}

try {
    $conn->beginTransaction();

    $lock = $conn->prepare("SELECT mission_id, organizer_name, mission_date, location, available_slots
                            FROM missions
                            WHERE mission_id = :id
                            FOR UPDATE");
    $lock->execute([':id' => $mission_id]);
    $mission = $lock->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        $conn->rollBack();
        json_error('Mission not found.', 404);
    }

    if ($mission['mission_date'] < date('Y-m-d')) {
        $conn->rollBack();
        json_error('This mission date has already passed.', 409);
    }

    if ((int)$mission['available_slots'] <= 0) {
        $conn->rollBack();
        json_error('This mission is already fully booked.', 409);
    }

    $insert = $conn->prepare("INSERT INTO bookings (mission_id, patient_name, contact_number)
                              VALUES (:mission_id, :patient_name, :contact_number)");
    $insert->execute([
        ':mission_id' => $mission_id,
        ':patient_name' => $patient_name,
        ':contact_number' => $contact_number
    ]);
    $booking_id = (int)$conn->lastInsertId();

    $update = $conn->prepare("UPDATE missions
                              SET available_slots = available_slots - 1
                              WHERE mission_id = :mission_id AND available_slots > 0");
    $update->execute([':mission_id' => $mission_id]);

    if ($update->rowCount() !== 1) {
        $conn->rollBack();
        json_error('This mission is already fully booked.', 409);
    }

    $remaining_slots = (int)$mission['available_slots'] - 1;
    $conn->commit();

    json_success('Your slot has been booked successfully. See you at the mission.', [
        'booking_id' => $booking_id,
        'mission_id' => $mission_id,
        'patient_name' => $patient_name,
        'contact_number' => $contact_number,
        'remaining_slots' => $remaining_slots
    ], 201);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    json_error('Unable to process the booking right now. Please try again later.', 500);
}
?>
