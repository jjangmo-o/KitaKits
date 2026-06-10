<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    try {
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :id");
            $stmt->execute([':id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) json_error('Patient not found.', 404);
            json_success('Patient loaded.', $row);
        }

        $stmt = $conn->prepare("SELECT * FROM patients ORDER BY created_at DESC");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        json_success('Patients loaded.', $rows);
    } catch (PDOException $e) {
        json_error('Unable to load patients right now.', 500);
    }
} elseif ($method === 'POST') {
    $input = read_request_input();
    $name = trim($input['full_name'] ?? '');
    $contact = normalize_contact_number($input['contact_number'] ?? '');
    $email = trim($input['email'] ?? '');
    $dob = trim($input['dob'] ?? null);

    if ($name === '' || $contact === '') {
        json_error('Name and contact number are required.', 422);
    }

    if (!contact_number_is_valid($contact)) {
        json_error('Enter a valid contact number.', 422);
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_error('Enter a valid email address.', 422);
    }

    try {
        $stmt = $conn->prepare("INSERT INTO patients (full_name, contact_number, email, dob) VALUES (:name, :contact, :email, :dob)");
        $stmt->execute([':name' => $name, ':contact' => $contact, ':email' => $email ?: null, ':dob' => $dob ?: null]);
        $id = (int)$conn->lastInsertId();
        json_success('Patient created.', ['patient_id' => $id], 201);
    } catch (PDOException $e) {
        json_error('Unable to create patient now.', 500);
    }
} elseif ($method === 'PUT' || $method === 'PATCH') {
    $input = read_request_input();
    $id = isset($input['patient_id']) ? (int)$input['patient_id'] : 0;
    if ($id <= 0) json_error('patient_id is required.', 422);

    $name = array_key_exists('full_name', $input) ? trim($input['full_name']) : null;
    $contact = isset($input['contact_number']) ? normalize_contact_number($input['contact_number']) : null;
    $email = array_key_exists('email', $input) ? trim($input['email']) : null;
    $dob = array_key_exists('dob', $input) ? trim($input['dob']) : null;

    $fields = [];
    $params = [':id' => $id];

    if ($name !== null) {
        if ($name === '') json_error('Full name cannot be empty.', 422);
        $fields[] = 'full_name = :name';
        $params[':name'] = $name;
    }

    if ($contact !== null) {
        if ($contact === '' || !contact_number_is_valid($contact)) {
            json_error('Enter a valid contact number.', 422);
        }
        $fields[] = 'contact_number = :contact';
        $params[':contact'] = $contact;
    }

    if ($email !== null) {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_error('Enter a valid email address.', 422);
        }
        $fields[] = 'email = :email';
        $params[':email'] = $email !== '' ? $email : null;
    }

    if ($dob !== null) {
        $fields[] = 'dob = :dob';
        $params[':dob'] = $dob !== '' ? $dob : null;
    }

    if (empty($fields)) {
        json_error('No fields to update.', 422);
    }

    try {
        $sql = "UPDATE patients SET " . implode(', ', $fields) . " WHERE patient_id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        json_success('Patient updated.', ['patient_id' => $id]);
    } catch (PDOException $e) {
        json_error('Unable to update patient now.', 500);
    }
} elseif ($method === 'DELETE') {
    $input = read_request_input();
    $id = isset($input['patient_id']) ? (int)$input['patient_id'] : 0;
    if ($id <= 0) json_error('patient_id is required.', 422);

    try {
        $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = :id");
        $stmt->execute([':id' => $id]);
        json_success('Patient deleted.', ['patient_id' => $id]);
    } catch (PDOException $e) {
        json_error('Unable to delete patient now.', 500);
    }
} else {
    json_error('Method not allowed.', 405);
}

