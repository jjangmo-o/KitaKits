<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

require_method('POST');

$input = read_request_input();
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : 0;
$patient_id = isset($input['patient_id']) ? (int)$input['patient_id'] : null;
$responses = $input['responses'] ?? $input['responses_text'] ?? '';

if ($booking_id <= 0) {
    json_error('booking_id is required.', 422);
}

if (is_array($responses) || is_object($responses)) {
    $responses = json_encode($responses);
} else {
    $responses = trim((string)$responses);
}

if ($responses === '') {
    json_error('Pre-screening responses are required.', 422);
}

try {
    $checkBooking = $conn->prepare("SELECT booking_id FROM bookings WHERE booking_id = :id");
    $checkBooking->execute([':id' => $booking_id]);
    if (!$checkBooking->fetch(PDO::FETCH_ASSOC)) {
        json_error('The booking referenced by booking_id was not found.', 404);
    }

    if ($patient_id !== null && $patient_id > 0) {
        $checkPatient = $conn->prepare("SELECT patient_id FROM patients WHERE patient_id = :id");
        $checkPatient->execute([':id' => $patient_id]);
        if (!$checkPatient->fetch(PDO::FETCH_ASSOC)) {
            json_error('The patient referenced by patient_id was not found.', 404);
        }
    } else {
        $patient_id = null;
    }

    $stmt = $conn->prepare("INSERT INTO pre_screenings (booking_id, patient_id, responses) VALUES (:booking_id, :patient_id, :responses)");
    $stmt->execute([':booking_id' => $booking_id, ':patient_id' => $patient_id, ':responses' => $responses]);
    $id = (int)$conn->lastInsertId();
    json_success('Pre-screening saved.', ['id' => $id], 201);
} catch (PDOException $e) {
    json_error('Unable to save pre-screening now.', 500);
}

