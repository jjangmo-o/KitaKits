<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/../api/_validation.php');
require_once(__DIR__ . '/../api/_schema_helpers.php');
require_once(__DIR__ . '/../api/_auth.php');

$patient_id = require_patient_page('login.php');

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$contact = normalize_contact_number($_GET['contact'] ?? '');
$error = '';
$success = '';

if ($booking_id <= 0) {
    header('Location: patient_portal.php');
    exit();
}

$booking_stmt = $conn->prepare("SELECT b.booking_id, b.patient_id, b.mission_id, b.patient_name, b.contact_number,
                                       m.mission_name, m.mission_date, m.full_address
                                FROM bookings b
                                JOIN missions m ON m.mission_id = b.mission_id
                                WHERE b.booking_id = :booking_id AND b.patient_id = :patient_id
                                LIMIT 1");
$booking_stmt->execute([
    ':booking_id' => $booking_id,
    ':patient_id' => $patient_id
]);
$booking = $booking_stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: patient_portal.php');
    exit();
}

$contact = normalize_contact_number($booking['contact_number']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST;
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
        $error = 'Consent is required so coordinators can review the intake before mission day.';
    } else {
        try {
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
                ':booking_id' => $booking['booking_id'],
                ':patient_id' => $booking['patient_id'],
                ':mission_id' => $booking['mission_id'],
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
            $success = 'Pre-screening saved for coordinator review.';
        } catch (PDOException $e) {
            $error = 'Unable to save pre-screening right now.';
        }
    }
}

$intake_stmt = $conn->prepare("SELECT *
                               FROM medical_intake_forms
                               WHERE booking_id = :booking_id
                               LIMIT 1");
$intake_stmt->execute([':booking_id' => $booking_id]);
$intake = $intake_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

function checked($intake, $field)
{
    return !empty($intake[$field]) ? 'checked' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-screening | KitaKits</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-logo">
                <img src="../assets/images/logo.png" alt="KitaKits Logo">
            </div>
            <div class="header-main">
                <div class="header-content">
                    <h1>Medical Pre-screening</h1>
                    <p>Disclose health details before mission day</p>
                </div>
                <div class="header-actions">
                    <nav class="header-nav">
                        <a href="patient_portal.php">Patient Portal</a>
                        <a href="patient_guide.php">Patient Guide</a>
                        <a href="faq.php">FAQ</a>
                    </nav>
                </div>
            </div>
        </div>
    </header>

    <main class="container">
        <a href="patient_portal.php#portal-bookings" class="btn-back">
            <span>&larr; </span>
            Back to Portal Bookings
        </a>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="booking-summary">
            <strong>Patient:</strong> <?php echo htmlspecialchars($booking['patient_name']); ?><br>
            <strong>Mission:</strong> <?php echo htmlspecialchars($booking['mission_name']); ?><br>
            <strong>Date:</strong> <?php echo date('F j, Y', strtotime($booking['mission_date'])); ?><br>
            <strong>Address:</strong> <?php echo htmlspecialchars($booking['full_address']); ?>
        </div>

        <form method="POST" action="" class="intake-form">
            <fieldset>
                <legend>Medical Conditions</legend>
                <label class="checkbox-row"><input type="checkbox" name="has_diabetes" value="1" <?php echo checked($intake, 'has_diabetes'); ?>> Diabetes</label>
                <label class="checkbox-row"><input type="checkbox" name="has_hypertension" value="1" <?php echo checked($intake, 'has_hypertension'); ?>> Hypertension</label>
                <label class="checkbox-row"><input type="checkbox" name="has_heart_disease" value="1" <?php echo checked($intake, 'has_heart_disease'); ?>> Heart disease</label>
                <label class="checkbox-row"><input type="checkbox" name="has_asthma" value="1" <?php echo checked($intake, 'has_asthma'); ?>> Asthma</label>
                <label class="checkbox-row"><input type="checkbox" name="has_bleeding_disorder" value="1" <?php echo checked($intake, 'has_bleeding_disorder'); ?>> Bleeding disorder</label>
                <label class="checkbox-row"><input type="checkbox" name="has_fever_or_infection" value="1" <?php echo checked($intake, 'has_fever_or_infection'); ?>> Fever or infection</label>
                <label class="checkbox-row"><input type="checkbox" name="is_pregnant" value="1" <?php echo checked($intake, 'is_pregnant'); ?>> Pregnant</label>
                <label class="checkbox-row"><input type="checkbox" name="previous_eye_surgery" value="1" <?php echo checked($intake, 'previous_eye_surgery'); ?>> Previous eye surgery</label>
            </fieldset>

            <label for="allergies">Allergies</label>
            <textarea id="allergies" name="allergies" rows="3"><?php echo htmlspecialchars($intake['allergies'] ?? ''); ?></textarea>

            <label for="current_medications">Current Medications</label>
            <textarea id="current_medications" name="current_medications" rows="3"><?php echo htmlspecialchars($intake['current_medications'] ?? ''); ?></textarea>

            <label for="other_conditions">Other Conditions</label>
            <textarea id="other_conditions" name="other_conditions" rows="3"><?php echo htmlspecialchars($intake['other_conditions'] ?? ''); ?></textarea>

            <label class="checkbox-row consent-row">
                <input type="checkbox" name="consent_to_share" value="1" <?php echo checked($intake, 'consent_to_share'); ?> required>
                I consent to share these details with mission coordinators for pre-screening review.
            </label>

            <button type="submit">Save Pre-screening</button>
        </form>
    </main>
</body>
</html>
