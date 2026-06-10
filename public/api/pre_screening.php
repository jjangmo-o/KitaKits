<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_schema_helpers.php');
require_once(__DIR__ . '/_auth.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
    $contact = normalize_contact_number($_GET['contact'] ?? '');

    if ($booking_id <= 0) {
        json_error('booking_id is required.', 422);
    }

    $is_admin = current_admin_user() || request_admin_token_is_valid();

    if (!$is_admin && ($contact === '' || !contact_number_is_valid($contact))) {
        json_error('A valid contact number is required.', 422);
    }

    try {
        $sql = "SELECT i.*, b.contact_number
                FROM medical_intake_forms i
                JOIN bookings b ON b.booking_id = i.booking_id
                WHERE i.booking_id = :booking_id" . ($is_admin ? "" : " AND b.contact_number = :contact") . "
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        $params = [':booking_id' => $booking_id];
        if (!$is_admin) {
            $params[':contact'] = $contact;
        }
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            json_success('No pre-screening submitted yet.', null);
        }

        json_success('Pre-screening loaded.', $row);
    } catch (PDOException $e) {
        json_error('Unable to load pre-screening now.', 500);
    }
}

require_method('POST');
$input = read_request_input();
$booking_id = isset($input['booking_id']) ? (int)$input['booking_id'] : 0;
$contact = normalize_contact_number($input['contact_number'] ?? '');
$action = trim($input['action'] ?? 'submit');

if ($booking_id <= 0) {
    json_error('booking_id is required.', 422);
}

if ($action === 'review') {
    $admin_id = require_admin();
    $review_status = trim($input['review_status'] ?? '');
    $coordinator_notes = trim($input['coordinator_notes'] ?? '');
    $allowed = ['pending', 'cleared', 'flagged', 'not_cleared'];

    if (!in_array($review_status, $allowed, true)) {
        json_error('A valid review status is required.', 422);
    }

    try {
        $stmt = $conn->prepare("UPDATE medical_intake_forms
                                SET review_status = :review_status,
                                    reviewed_by = :reviewed_by,
                                    reviewed_at = current_timestamp(),
                                    coordinator_notes = :notes
                                WHERE booking_id = :booking_id");
        $stmt->execute([
            ':review_status' => $review_status,
            ':reviewed_by' => $admin_id,
            ':notes' => $coordinator_notes ?: null,
            ':booking_id' => $booking_id
        ]);

        if ($stmt->rowCount() === 0) {
            json_error('Pre-screening form not found.', 404);
        }

        json_success('Pre-screening review updated.', [
            'booking_id' => $booking_id,
            'review_status' => $review_status
        ]);
    } catch (PDOException $e) {
        json_error('Unable to update pre-screening review.', 500);
    }
}

if ($contact === '' || !contact_number_is_valid($contact)) {
    json_error('A valid contact number is required.', 422);
}

try {
    $booking = $conn->prepare("SELECT booking_id, patient_id, mission_id
                               FROM bookings
                               WHERE booking_id = :booking_id AND contact_number = :contact
                               LIMIT 1");
    $booking->execute([
        ':booking_id' => $booking_id,
        ':contact' => $contact
    ]);
    $booking_row = $booking->fetch(PDO::FETCH_ASSOC);

    if (!$booking_row) {
        json_error('The booking referenced by booking_id was not found.', 404);
    }

    $data = [
        'has_diabetes' => truthy_int($input['has_diabetes'] ?? 0),
        'has_hypertension' => truthy_int($input['has_hypertension'] ?? 0),
        'has_heart_disease' => truthy_int($input['has_heart_disease'] ?? 0),
        'has_asthma' => truthy_int($input['has_asthma'] ?? 0),
        'has_bleeding_disorder' => truthy_int($input['has_bleeding_disorder'] ?? 0),
        'has_fever_or_infection' => truthy_int($input['has_fever_or_infection'] ?? 0),
        'is_pregnant' => truthy_int($input['is_pregnant'] ?? 0),
        'previous_eye_surgery' => truthy_int($input['previous_eye_surgery'] ?? 0),
        'allergies' => trim($input['allergies'] ?? ''),
        'current_medications' => trim($input['current_medications'] ?? ''),
        'other_conditions' => trim($input['other_conditions'] ?? ''),
        'consent_to_share' => truthy_int($input['consent_to_share'] ?? 0)
    ];

    if ($data['consent_to_share'] !== 1) {
        json_error('Consent to share pre-screening details with coordinators is required.', 422);
    }

    $flags = intake_flags_from_input($data);
    $review_status = $flags === '' ? 'pending' : 'flagged';

    $stmt = $conn->prepare("INSERT INTO medical_intake_forms
        (booking_id, patient_id, mission_id, has_diabetes, has_hypertension, has_heart_disease, has_asthma,
         has_bleeding_disorder, has_fever_or_infection, is_pregnant, previous_eye_surgery, allergies,
         current_medications, other_conditions, contraindication_flags, consent_to_share, review_status)
        VALUES
        (:booking_id, :patient_id, :mission_id, :has_diabetes, :has_hypertension, :has_heart_disease, :has_asthma,
         :has_bleeding_disorder, :has_fever_or_infection, :is_pregnant, :previous_eye_surgery, :allergies,
         :current_medications, :other_conditions, :contraindication_flags, :consent_to_share, :review_status)
        ON DUPLICATE KEY UPDATE
          has_diabetes = VALUES(has_diabetes),
          has_hypertension = VALUES(has_hypertension),
          has_heart_disease = VALUES(has_heart_disease),
          has_asthma = VALUES(has_asthma),
          has_bleeding_disorder = VALUES(has_bleeding_disorder),
          has_fever_or_infection = VALUES(has_fever_or_infection),
          is_pregnant = VALUES(is_pregnant),
          previous_eye_surgery = VALUES(previous_eye_surgery),
          allergies = VALUES(allergies),
          current_medications = VALUES(current_medications),
          other_conditions = VALUES(other_conditions),
          contraindication_flags = VALUES(contraindication_flags),
          consent_to_share = VALUES(consent_to_share),
          review_status = VALUES(review_status),
          reviewed_by = NULL,
          reviewed_at = NULL");
    $stmt->execute([
        ':booking_id' => $booking_row['booking_id'],
        ':patient_id' => $booking_row['patient_id'],
        ':mission_id' => $booking_row['mission_id'],
        ':has_diabetes' => $data['has_diabetes'],
        ':has_hypertension' => $data['has_hypertension'],
        ':has_heart_disease' => $data['has_heart_disease'],
        ':has_asthma' => $data['has_asthma'],
        ':has_bleeding_disorder' => $data['has_bleeding_disorder'],
        ':has_fever_or_infection' => $data['has_fever_or_infection'],
        ':is_pregnant' => $data['is_pregnant'],
        ':previous_eye_surgery' => $data['previous_eye_surgery'],
        ':allergies' => $data['allergies'] ?: null,
        ':current_medications' => $data['current_medications'] ?: null,
        ':other_conditions' => $data['other_conditions'] ?: null,
        ':contraindication_flags' => $flags ?: null,
        ':consent_to_share' => $data['consent_to_share'],
        ':review_status' => $review_status
    ]);

    json_success('Pre-screening saved for coordinator review.', [
        'booking_id' => $booking_id,
        'review_status' => $review_status,
        'contraindication_flags' => $flags
    ], 201);
} catch (PDOException $e) {
    json_error('Unable to save pre-screening now.', 500);
}
?>
