<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');

require_method('GET');

function format_mission($mission)
{
    $time_range = null;
    $effective_status = $mission['mission_status'];

    if ($mission['mission_status'] === 'completed' || $mission['mission_date'] < date('Y-m-d')) {
        $effective_status = 'completed';
    } elseif ($mission['mission_status'] === 'closed') {
        $effective_status = 'closed';
    } elseif ((int)$mission['available_slots'] <= 0) {
        $effective_status = 'full';
    }

    if (!empty($mission['start_time']) && !empty($mission['end_time'])) {
        $time_range = date('g:i A', strtotime($mission['start_time'])) . ' – ' . date('g:i A', strtotime($mission['end_time']));
    } elseif (!empty($mission['start_time'])) {
        $time_range = date('g:i A', strtotime($mission['start_time']));
    }

    return [
        'mission_id' => (int)$mission['mission_id'],
        'mission_name' => $mission['mission_name'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_long' => date('F j, Y', strtotime($mission['mission_date'])),
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'mission_time_range' => $time_range,
        'venue_name' => $mission['venue_name'],
        'location' => $mission['location'],
        'city_area' => $mission['city_area'],
        'full_address' => $mission['full_address'],
        'total_slots' => (int)$mission['total_slots'],
        'available_slots' => (int)$mission['available_slots'],
        'mission_status' => $effective_status
    ];
}

try {
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';
    $status = isset($_GET['status']) ? $_GET['status'] : 'all';
    $sort = isset($_GET['sort']) && $_GET['sort'] === 'slots' ? 'available_slots' : 'mission_date';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
    $city = isset($_GET['city']) ? trim($_GET['city']) : '';

    $where = [
        "mission_status IN ('open', 'closed', 'completed')"
    ];
    $params = [];

    if ($q !== '') {
        $where[] = "(mission_name LIKE :q_mission
            OR organizer_name LIKE :q_organizer
            OR location LIKE :q_location
            OR city_area LIKE :q_city
            OR full_address LIKE :q_address)";
        $search_term = '%' . $q . '%';
        $params[':q_mission'] = $search_term;
        $params[':q_organizer'] = $search_term;
        $params[':q_location'] = $search_term;
        $params[':q_city'] = $search_term;
        $params[':q_address'] = $search_term;
    }

    if ($city !== '') {
        $where[] = "(city_area LIKE :city_area OR location LIKE :city_location OR full_address LIKE :city_address)";
        $city_term = '%' . $city . '%';
        $params[':city_area'] = $city_term;
        $params[':city_location'] = $city_term;
        $params[':city_address'] = $city_term;
    }

    if ($status === 'completed') {
        $where[] = "(mission_status = 'completed' OR mission_date < CURDATE())";
    } elseif ($status !== 'all') {
        $where[] = "mission_date >= CURDATE()";
    }

    if ($status === 'available') {
        $where[] = "available_slots > 0";
        $where[] = "mission_status = 'open'";
    } elseif ($status === 'full') {
        $where[] = "(mission_status = 'closed' OR (mission_status = 'open' AND available_slots <= 0))";
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
        return in_array($mission['mission_status'], ['closed', 'full'], true);
    }));

    $completed = array_values(array_filter($missions, function ($mission) {
        return $mission['mission_status'] === 'completed' || strtotime($mission['mission_date']) < strtotime(date('Y-m-d'));
    }));

    json_success('Missions loaded.', [
        'available' => $available,
        'fully_booked' => $fully_booked,
        'completed' => $completed,
        'all' => $missions
    ]);
} catch (PDOException $e) {
    json_error('Unable to load missions right now. Please try again later.', 500);
}
?>
