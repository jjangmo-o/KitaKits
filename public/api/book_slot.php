<?php
require_once(__DIR__ . '/../../app/config/db.php');
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
    // Create booking without decrementing mission slots. Confirmation will decrement slots.
    try {
        $conn->beginTransaction();

        $select = $conn->prepare("SELECT mission_id, organizer_name, mission_date, location, available_slots
                                  FROM missions
                                  WHERE mission_id = :id");
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

        if ((int)$mission['available_slots'] <= 0) {
            $conn->rollBack();
            json_error('This mission is fully booked. Please choose another mission.', 409);
        }

        $insert = $conn->prepare("INSERT INTO bookings (mission_id, patient_name, contact_number, status)
                                  VALUES (:mission_id, :patient_name, :contact_number, 'booked')");
        $insert->execute([
            ':mission_id' => $mission_id,
            ':patient_name' => $patient_name,
            ':contact_number' => $contact_number
        ]);
        $booking_id = (int)$conn->lastInsertId();

        // generate booking reference: KK-YYYY-NNNNN
        $booking_ref = sprintf('KK-%s-%05d', date('Y'), $booking_id);

        // save booking_ref
        $updateRef = $conn->prepare("UPDATE bookings SET booking_ref = :booking_ref WHERE booking_id = :booking_id");
        $updateRef->execute([':booking_ref' => $booking_ref, ':booking_id' => $booking_id]);

        $conn->commit();

        json_success('Your slot request has been received. It will be confirmed by an admin.', [
            'booking_id' => $booking_id,
            'booking_ref' => $booking_ref,
            'mission_id' => $mission_id,
            'patient_name' => $patient_name,
            'contact_number' => $contact_number,
            'available_slots' => (int)$mission['available_slots'],
            'status' => 'booked'
        ], 201);
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        json_error('Unable to process the booking right now. Please try again later.', 500);
    }
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    json_error('Unable to process the booking right now. Please try again later.', 500);
}
?>
