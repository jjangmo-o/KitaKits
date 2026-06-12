<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_schema_helpers.php');
require_once(__DIR__ . '/_auth.php');

require_method('POST');

$patient_id = current_patient_id();
if (!$patient_id) {
    json_error('Log in as a patient before submitting a booking request.', 401);
}

$input = read_request_input();
$mission_id = isset($input['mission_id']) ? (int)$input['mission_id'] : 0;
$patient_name = trim($input['patient_name'] ?? '');
$contact_number = normalize_contact_number($input['contact_number'] ?? '');
$companion_count = isset($input['companion_count']) ? (int)$input['companion_count'] : 0;
$patient_notes = trim($input['patient_notes'] ?? '');

$profile = [
    'user_id' => current_patient_user_id(),
    'email' => trim($input['email'] ?? ''),
    'birthdate' => trim($input['birthdate'] ?? ''),
    'sex' => trim($input['sex'] ?? ''),
    'full_address' => trim($input['full_address'] ?? ''),
    'barangay' => trim($input['barangay'] ?? ''),
    'city' => trim($input['city'] ?? ''),
    'province' => trim($input['province'] ?? '')
];

if ($mission_id <= 0) {
    json_error('Please choose a valid mission.', 422);
}

if ($patient_name === '' || $contact_number === '') {
    json_error('Please provide both your name and contact number.', 422);
}

if (strlen($patient_name) > 120) {
    json_error('Full name must be 120 characters or fewer.', 422);
}

if (!contact_number_is_valid($contact_number)) {
    json_error('Enter a valid contact number with 7 to 15 digits.', 422);
}

if ($profile['email'] !== '' && !filter_var($profile['email'], FILTER_VALIDATE_EMAIL)) {
    json_error('Enter a valid email address.', 422);
}

if ($companion_count < 0 || $companion_count > 10) {
    json_error('Companion count must be between 0 and 10.', 422);
}

try {
    $conn->beginTransaction();

    $select = $conn->prepare("SELECT mission_id, mission_name, organizer_name, mission_date, location, full_address,
                                     available_slots, mission_status
                              FROM missions
                              WHERE mission_id = :id
                              FOR UPDATE");
    $select->execute([':id' => $mission_id]);
    $mission = $select->fetch(PDO::FETCH_ASSOC);

    if (!$mission) {
        $conn->rollBack();
        json_error('Mission not found.', 404);
    }

    if ($mission['mission_date'] < date('Y-m-d')) {
        $conn->rollBack();
        json_error('This mission date has already passed.', 409);
    }

    if ($mission['mission_status'] !== 'open') {
        $conn->rollBack();
        json_error('This mission is not accepting booking requests.', 409);
    }

    if ((int)$mission['available_slots'] <= 0) {
        $conn->rollBack();
        json_error('This mission is fully booked. Please choose another mission.', 409);
    }

    $patient = update_patient_booking_profile($conn, $patient_id, $patient_name, $contact_number, $profile);
    if (!$patient) {
        $conn->rollBack();
        json_error('Patient profile not found.', 404);
    }

    $existing = $conn->prepare("SELECT booking_id, booking_reference, booking_status
                                FROM bookings
                                WHERE patient_id = :patient_id AND mission_id = :mission_id
                                LIMIT 1");
    $existing->execute([
        ':patient_id' => $patient_id,
        ':mission_id' => $mission_id
    ]);
    $booking = $existing->fetch(PDO::FETCH_ASSOC);

    if ($booking && !in_array($booking['booking_status'], ['cancelled', 'rejected'], true)) {
        $conn->rollBack();
        json_error('You already have a booking request for this mission. Please check your Patient Portal.', 409, [
            'booking_reference' => $booking['booking_reference'],
            'booking_status' => $booking['booking_status']
        ]);
    }

    if ($booking) {
        $update = $conn->prepare("UPDATE bookings
                                  SET patient_name = :patient_name,
                                      contact_number = :contact_number,
                                      booking_status = 'booked',
                                      companion_count = :companion_count,
                                      patient_notes = :patient_notes,
                                      cancelled_at = NULL,
                                      completed_at = NULL,
                                      confirmed_at = NULL
                                  WHERE booking_id = :booking_id");
        $update->execute([
            ':patient_name' => $patient_name,
            ':contact_number' => $contact_number,
            ':companion_count' => $companion_count,
            ':patient_notes' => $patient_notes ?: null,
            ':booking_id' => $booking['booking_id']
        ]);
        $booking_id = (int)$booking['booking_id'];
    } else {
        $insert = $conn->prepare("INSERT INTO bookings
            (mission_id, patient_id, patient_name, contact_number, booking_status, companion_count, patient_notes)
            VALUES
            (:mission_id, :patient_id, :patient_name, :contact_number, 'booked', :companion_count, :patient_notes)");
        $insert->execute([
            ':mission_id' => $mission_id,
            ':patient_id' => $patient_id,
            ':patient_name' => $patient_name,
            ':contact_number' => $contact_number,
            ':companion_count' => $companion_count,
            ':patient_notes' => $patient_notes ?: null
        ]);
        $booking_id = (int)$conn->lastInsertId();
    }

    $fetch = $conn->prepare("SELECT booking_id, booking_reference, booking_status, total_headcount
                             FROM bookings
                             WHERE booking_id = :booking_id");
    $fetch->execute([':booking_id' => $booking_id]);
    $saved = $fetch->fetch(PDO::FETCH_ASSOC);

    $conn->commit();

    json_success('Your booking request has been received. An admin must confirm it before your slot is secured.', [
        'booking_id' => $booking_id,
        'booking_reference' => $saved['booking_reference'],
        'mission_id' => $mission_id,
        'patient_id' => $patient_id,
        'patient_name' => $patient_name,
        'contact_number' => $contact_number,
        'available_slots' => (int)$mission['available_slots'],
        'remaining_slots' => (int)$mission['available_slots'],
        'booking_status' => $saved['booking_status'],
        'status' => $saved['booking_status'],
        'total_headcount' => (int)$saved['total_headcount']
    ], 201);
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    json_error('Unable to process the booking right now. Please try again later.', 500);
}
?>
