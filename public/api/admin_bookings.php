<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_auth.php');

require_method('GET');
require_admin();

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : 0;
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where = [];
$params = [];

if ($status !== '') {
    $where[] = 'booking_status = :status';
    $params[':status'] = $status;
}

if ($mission_id > 0) {
    $where[] = 'mission_id = :mission_id';
    $params[':mission_id'] = $mission_id;
}

if ($date_from !== '') {
    $where[] = 'mission_date >= :date_from';
    $params[':date_from'] = $date_from;
}

if ($date_to !== '') {
    $where[] = 'mission_date <= :date_to';
    $params[':date_to'] = $date_to;
}

$sql = "SELECT * FROM v_admin_booking_directory" .
       ($where ? ' WHERE ' . implode(' AND ', $where) : '') .
       " ORDER BY mission_date DESC, requested_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    json_success('Admin bookings loaded.', $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    json_error('Unable to load bookings.', 500);
}
?>
