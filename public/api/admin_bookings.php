<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_auth.php');

require_admin();

// filters: status, mission_id, date_from, date_to
$status = isset($_GET['status']) ? $_GET['status'] : null;
$mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

$where = [];
$params = [];

if ($status !== null) { $where[] = 'b.status = :status'; $params[':status'] = $status; }
if ($mission_id > 0) { $where[] = 'b.mission_id = :mid'; $params[':mid'] = $mission_id; }
if ($date_from) { $where[] = 'm.mission_date >= :df'; $params[':df'] = $date_from; }
if ($date_to) { $where[] = 'm.mission_date <= :dt'; $params[':dt'] = $date_to; }

$sql = "SELECT b.*, m.organizer_name, m.mission_date, m.location FROM bookings b JOIN missions m ON b.mission_id = m.mission_id" . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY m.mission_date DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json_success('Admin bookings loaded.', $rows);
} catch (PDOException $e) {
    json_error('Unable to load bookings.', 500);
}

?>
