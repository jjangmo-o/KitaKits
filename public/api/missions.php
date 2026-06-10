<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');

require_method('GET');

function format_mission($mission)
{
    return [
        'mission_id' => (int)$mission['mission_id'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($mission['mission_date'])),
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'location' => $mission['location'],
        'available_slots' => (int)$mission['available_slots']
    ];
}

try {
    // Filters: q (keyword), status (available|full|all), sort (date|slots), order (asc|desc), city
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $sort = isset($_GET['sort']) && $_GET['sort'] === 'slots' ? 'available_slots' : 'mission_date';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';

    $where = [];
    $params = [];

    if ($q !== '') {
        $where[] = "(organizer_name LIKE :q OR location LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }

    if ($city !== '') {
        $where[] = "location LIKE :city";
        $params[':city'] = '%' . $city . '%';
    }

    if ($status === 'available') {
        $where[] = "available_slots > 0";
    } elseif ($status === 'full') {
        $where[] = "available_slots = 0";
    }

    $sql = "SELECT * FROM missions" . (count($where) ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $sort $order";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_success('Missions loaded.', array_map('format_mission', $missions));
} catch (PDOException $e) {
    json_error('Unable to load missions right now. Please try again later.', 500);
}
?>
