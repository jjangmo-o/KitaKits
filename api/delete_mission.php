<?php
require_once(__DIR__ . '/../db.php');
require_once(__DIR__ . '/_response.php');

if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'], true)) {
    json_error('Method not allowed.', 405);
}

$input = $_SERVER['REQUEST_METHOD'] === 'DELETE' ? $_GET : $_POST;
$mission_id = isset($input['id']) ? (int)$input['id'] : 0;

if ($mission_id <= 0) {
    json_error('Invalid mission ID provided.', 422);
}

try {
    $check = $conn->prepare("SELECT mission_id FROM missions WHERE mission_id = :id");
    $check->execute([':id' => $mission_id]);

    if (!$check->fetch(PDO::FETCH_ASSOC)) {
        json_error('Mission not found.', 404);
    }

    $delete = $conn->prepare("DELETE FROM missions WHERE mission_id = :id");
    $delete->execute([':id' => $mission_id]);

    json_success('Mission deleted successfully.', ['mission_id' => $mission_id]);
} catch (PDOException $e) {
    json_error('Unable to delete this mission right now. Please try again later.', 500);
}
?>
