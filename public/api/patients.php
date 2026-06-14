<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_validation.php');
require_once(__DIR__ . '/_auth.php');

require_admin();

$method = $_SERVER['REQUEST_METHOD'];

function patient_payload($input)
{
    return [
        'first_name' => trim($input['first_name'] ?? ''),
        'middle_name' => trim($input['middle_name'] ?? ''),
        'last_name' => trim($input['last_name'] ?? ''),
        'suffix' => trim($input['suffix'] ?? ''),
        'birthdate' => trim($input['birthdate'] ?? ''),
        'sex' => trim($input['sex'] ?? ''),
        'contact_number' => normalize_contact_number($input['contact_number'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'full_address' => trim($input['full_address'] ?? ''),
        'barangay' => trim($input['barangay'] ?? ''),
        'city' => trim($input['city'] ?? ''),
        'province' => trim($input['province'] ?? ''),
        'emergency_contact_name' => trim($input['emergency_contact_name'] ?? ''),
        'emergency_contact_number' => normalize_contact_number($input['emergency_contact_number'] ?? '')
    ];
}

function validate_patient($data, $partial = false)
{
    if (!$partial || $data['first_name'] !== '') {
        if ($data['first_name'] === '') json_error('First name is required.', 422);
    }

    if (!$partial || $data['last_name'] !== '') {
        if ($data['last_name'] === '') json_error('Last name is required.', 422);
    }

    if (text_length($data['first_name']) > 30
        || text_length($data['middle_name']) > 30
        || text_length($data['last_name']) > 30) {
        json_error('First, middle, and last name must each be 30 characters or fewer.', 422);
    }

    if (!$partial && !patient_name_parts_are_valid($data['first_name'], $data['middle_name'], $data['last_name'])) {
        json_error('Patient name must be 65 characters or fewer combined.', 422);
    }

    if (!$partial || $data['contact_number'] !== '') {
        if ($data['contact_number'] === '' || !contact_number_is_valid($data['contact_number'])) {
            json_error('Enter an 11-digit mobile number starting with 09.', 422);
        }
    }

    if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        json_error('Enter a valid email address.', 422);
    }

    if ($data['emergency_contact_number'] !== '' && !contact_number_is_valid($data['emergency_contact_number'])) {
        json_error('Enter an 11-digit emergency mobile number starting with 09.', 422);
    }

    if (text_length($data['emergency_contact_name']) > 65) {
        json_error('Emergency contact name must be 65 characters or fewer.', 422);
    }
}

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

        $stmt = $conn->prepare("SELECT p.*,
                                       COUNT(b.booking_id) AS booking_count
                                FROM patients p
                                LEFT JOIN bookings b ON b.patient_id = p.patient_id
                                GROUP BY p.patient_id
                                ORDER BY p.created_at DESC");
        $stmt->execute();
        json_success('Patients loaded.', $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e) {
        json_error('Unable to load patients right now.', 500);
    }
} elseif ($method === 'POST') {
    $input = read_request_input();
    $data = patient_payload($input);
    validate_patient($data);

    try {
        $stmt = $conn->prepare("INSERT INTO patients
            (first_name, middle_name, last_name, suffix, birthdate, sex, contact_number, email, full_address, barangay, city, province, emergency_contact_name, emergency_contact_number)
            VALUES
            (:first_name, :middle_name, :last_name, :suffix, :birthdate, :sex, :contact_number, :email, :full_address, :barangay, :city, :province, :emergency_contact_name, :emergency_contact_number)");
        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':middle_name' => $data['middle_name'] ?: null,
            ':last_name' => $data['last_name'],
            ':suffix' => $data['suffix'] ?: null,
            ':birthdate' => $data['birthdate'] ?: null,
            ':sex' => $data['sex'] ?: null,
            ':contact_number' => $data['contact_number'],
            ':email' => $data['email'] ?: null,
            ':full_address' => $data['full_address'] ?: null,
            ':barangay' => $data['barangay'] ?: null,
            ':city' => $data['city'] ?: null,
            ':province' => $data['province'] ?: null,
            ':emergency_contact_name' => $data['emergency_contact_name'] ?: null,
            ':emergency_contact_number' => $data['emergency_contact_number'] ?: null
        ]);
        json_success('Patient created.', ['patient_id' => (int)$conn->lastInsertId()], 201);
    } catch (PDOException $e) {
        json_error('Unable to create patient now.', 500);
    }
} elseif ($method === 'PUT' || $method === 'PATCH') {
    $input = read_request_input();
    $id = isset($input['patient_id']) ? (int)$input['patient_id'] : 0;
    if ($id <= 0) json_error('patient_id is required.', 422);

    $allowed = ['first_name', 'middle_name', 'last_name', 'suffix', 'birthdate', 'sex', 'contact_number', 'email', 'full_address', 'barangay', 'city', 'province', 'emergency_contact_name', 'emergency_contact_number'];
    $data = patient_payload($input);
    validate_patient($data, true);

    $fields = [];
    $params = [':id' => $id];

    foreach ($allowed as $field) {
        if (array_key_exists($field, $input)) {
            $fields[] = $field . ' = :' . $field;
            $params[':' . $field] = $data[$field] !== '' ? $data[$field] : null;
        }
    }

    if (empty($fields)) {
        json_error('No fields to update.', 422);
    }

    try {
        $stmt = $conn->prepare("UPDATE patients SET " . implode(', ', $fields) . " WHERE patient_id = :id");
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
        json_error('Unable to delete patient now. Patients with bookings are kept for audit history.', 500);
    }
} else {
    json_error('Method not allowed.', 405);
}
?>
