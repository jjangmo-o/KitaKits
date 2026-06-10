<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');

require_method('GET');

function format_mission($mission)
{
    return [
        'mission_id' => (int)$mission['mission_id'],
        'mission_name' => $mission['mission_name'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($mission['mission_date'])),
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'location' => $mission['location'],
        'city_area' => $mission['city_area'],
        'full_address' => $mission['full_address'],
        'total_slots' => (int)$mission['total_slots'],
        'available_slots' => (int)$mission['available_slots'],
        'mission_status' => $mission['mission_status']
    ];
}

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $sort = isset($_GET['sort']) && $_GET['sort'] === 'slots' ? 'available_slots' : 'mission_date';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';

    $where = [
        "mission_date >= CURDATE()",
        "mission_status IN ('open', 'closed')"
    ];
    $params = [];

    if ($q !== '') {
        $where[] = "(mission_name LIKE :q OR organizer_name LIKE :q OR location LIKE :q OR city_area LIKE :q OR full_address LIKE :q)";
        $params[':q'] = '%' . $q . '%';
    }

    if ($city !== '') {
        $where[] = "(city_area LIKE :city OR location LIKE :city OR full_address LIKE :city)";
        $params[':city'] = '%' . $city . '%';
    }

    if ($status === 'available') {
        $where[] = "available_slots > 0";
        $where[] = "mission_status = 'open'";
    } elseif ($status === 'full') {
        $where[] = "(available_slots = 0 OR mission_status = 'closed')";
    }

    $sql = "SELECT *
            FROM missions
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $sort $order, mission_id ASC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $missions = array_map('format_mission', $stmt->fetchAll(PDO::FETCH_ASSOC));

    $available = array_values(array_filter($missions, function ($mission) {
        return $mission['available_slots'] > 0 && $mission['mission_status'] === 'open';
    }));

    $fully_booked = array_values(array_filter($missions, function ($mission) {
        return $mission['available_slots'] <= 0 || $mission['mission_status'] === 'closed';
    }));

    json_success('Missions loaded.', [
        'available' => $available,
        'fully_booked' => $fully_booked,
        'all' => $missions
    ]);
} catch (PDOException $e) {
    json_error('Unable to load missions right now. Please try again later.', 500);
}
?>
