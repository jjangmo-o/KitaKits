<?php
function split_patient_name($full_name)
{
    $clean = trim(preg_replace('/\s+/', ' ', (string)$full_name));

    if ($clean === '') {
        return ['', ''];
    }

    $parts = explode(' ', $clean);
    $first_name = array_shift($parts);
    $last_name = trim(implode(' ', $parts));

    if ($last_name === '') {
        $last_name = $first_name;
    }

    return [$first_name, $last_name];
}

function patient_display_name($patient)
{
    return trim(preg_replace('/\s+/', ' ', implode(' ', array_filter([
        $patient['first_name'] ?? '',
        $patient['middle_name'] ?? '',
        $patient['last_name'] ?? '',
        $patient['suffix'] ?? ''
    ]))));
}

function find_or_create_patient(PDO $conn, $full_name, $contact_number, $profile = [])
{
    $user_id = isset($profile['user_id']) ? (int)$profile['user_id'] : 0;

    $stmt = $conn->prepare("SELECT *
                            FROM patients
                            WHERE contact_number = :contact
                            ORDER BY patient_id ASC
                            LIMIT 1");
    $stmt->execute([':contact' => $contact_number]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    [$first_name, $last_name] = split_patient_name($full_name);

    if ($patient) {
        $updates = [];
        $params = [':id' => $patient['patient_id']];

        if ($first_name !== '' && $patient['first_name'] !== $first_name) {
            $updates[] = 'first_name = :first_name';
            $params[':first_name'] = $first_name;
        }

        if ($last_name !== '' && $patient['last_name'] !== $last_name) {
            $updates[] = 'last_name = :last_name';
            $params[':last_name'] = $last_name;
        }

        if ($user_id > 0 && empty($patient['user_id'])) {
            $updates[] = 'user_id = :user_id';
            $params[':user_id'] = $user_id;
        }

        $optional_fields = ['email', 'birthdate', 'sex', 'full_address', 'barangay', 'city', 'province'];

        foreach ($optional_fields as $field) {
            if (array_key_exists($field, $profile) && trim((string)$profile[$field]) !== '') {
                $updates[] = $field . ' = :' . $field;
                $params[':' . $field] = trim((string)$profile[$field]);
            }
        }

        if ($updates) {
            $sql = "UPDATE patients SET " . implode(', ', $updates) . " WHERE patient_id = :id";
            $update = $conn->prepare($sql);
            $update->execute($params);

            $stmt->execute([':contact' => $contact_number]);
            $patient = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $patient;
    }

    $insert = $conn->prepare("INSERT INTO patients
        (user_id, first_name, last_name, contact_number, email, birthdate, sex, full_address, barangay, city, province)
        VALUES
        (:user_id, :first_name, :last_name, :contact_number, :email, :birthdate, :sex, :full_address, :barangay, :city, :province)");
    $insert->execute([
        ':user_id' => $user_id > 0 ? $user_id : null,
        ':first_name' => $first_name,
        ':last_name' => $last_name,
        ':contact_number' => $contact_number,
        ':email' => trim((string)($profile['email'] ?? '')) ?: null,
        ':birthdate' => trim((string)($profile['birthdate'] ?? '')) ?: null,
        ':sex' => trim((string)($profile['sex'] ?? '')) ?: null,
        ':full_address' => trim((string)($profile['full_address'] ?? '')) ?: null,
        ':barangay' => trim((string)($profile['barangay'] ?? '')) ?: null,
        ':city' => trim((string)($profile['city'] ?? '')) ?: null,
        ':province' => trim((string)($profile['province'] ?? '')) ?: null
    ]);

    $patient_id = (int)$conn->lastInsertId();
    $stmt = $conn->prepare("SELECT * FROM patients WHERE patient_id = :id");
    $stmt->execute([':id' => $patient_id]);

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function intake_flags_from_input($input)
{
    $flags = [];
    $flag_fields = [
        'has_diabetes' => 'Diabetes',
        'has_hypertension' => 'Hypertension',
        'has_heart_disease' => 'Heart disease',
        'has_asthma' => 'Asthma',
        'has_bleeding_disorder' => 'Bleeding disorder',
        'has_fever_or_infection' => 'Fever or infection',
        'is_pregnant' => 'Pregnancy',
        'previous_eye_surgery' => 'Previous eye surgery'
    ];

    foreach ($flag_fields as $field => $label) {
        if (!empty($input[$field])) {
            $flags[] = $label;
        }
    }

    if (trim((string)($input['allergies'] ?? '')) !== '') {
        $flags[] = 'Allergies disclosed';
    }

    if (trim((string)($input['current_medications'] ?? '')) !== '') {
        $flags[] = 'Current medications disclosed';
    }

    return implode(', ', $flags);
}

function truthy_int($value)
{
    return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true) ? 1 : 0;
}
?>
