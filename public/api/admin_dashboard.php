<?php
require_once(__DIR__ . '/../../app/config/db.php');
require_once(__DIR__ . '/_response.php');
require_once(__DIR__ . '/_auth.php');

require_method('GET');
require_admin();

function pct($value)
{
    return $value === null ? 0 : round(((float)$value) * 100, 1);
}

function format_admin_mission($mission)
{
    $effective_status = $mission['mission_status'];

    if ($mission['mission_status'] === 'completed'
        || ($mission['mission_status'] === 'open' && $mission['mission_date'] < date('Y-m-d'))) {
        $effective_status = 'completed';
    } elseif ($mission['mission_status'] === 'closed') {
        $effective_status = 'closed';
    } elseif ($mission['mission_status'] === 'open' && (int)$mission['available_slots'] <= 0) {
        $effective_status = 'full';
    }

    return [
        'mission_id' => (int)$mission['mission_id'],
        'mission_name' => $mission['mission_name'],
        'organizer_name' => $mission['organizer_name'],
        'mission_date' => $mission['mission_date'],
        'mission_date_short' => date('M d, Y', strtotime($mission['mission_date'])),
        'city_area' => $mission['city_area'],
        'full_address' => $mission['full_address'],
        'mission_status' => $effective_status,
        'total_slots' => (int)$mission['total_slots'],
        'available_slots' => (int)$mission['available_slots'],
        'total_bookings' => (int)$mission['total_bookings'],
        'booked_count' => (int)$mission['booked_count'],
        'confirmed_count' => (int)$mission['confirmed_count'],
        'completed_count' => (int)$mission['completed_count'],
        'cancelled_count' => (int)$mission['cancelled_count'],
        'rejected_count' => (int)$mission['rejected_count'],
        'no_show_count' => (int)$mission['no_show_count'],
        'confirmed_headcount' => (int)$mission['confirmed_headcount'],
        'completion_rate' => pct($mission['completion_rate'])
    ];
}

function format_admin_booking($booking)
{
    return [
        'booking_id' => (int)$booking['booking_id'],
        'booking_reference' => $booking['booking_reference'],
        'booking_status' => $booking['booking_status'],
        'requested_at' => $booking['requested_at'],
        'confirmed_at' => $booking['confirmed_at'],
        'companion_count' => (int)$booking['companion_count'],
        'total_headcount' => (int)$booking['total_headcount'],
        'patient_id' => (int)$booking['patient_id'],
        'patient_name' => $booking['patient_full_name'],
        'contact_number' => $booking['contact_number'],
        'email' => $booking['email'],
        'city' => $booking['city'],
        'barangay' => $booking['barangay'],
        'mission_id' => (int)$booking['mission_id'],
        'mission_name' => $booking['mission_name'],
        'mission_date' => $booking['mission_date'],
        'mission_date_short' => date('M d, Y', strtotime($booking['mission_date'])),
        'city_area' => $booking['city_area'],
        'full_address' => $booking['full_address'],
        'intake_review_status' => $booking['intake_review_status'] ?: 'not_submitted',
        'contraindication_flags' => $booking['contraindication_flags'],
        'coordinator_notes' => $booking['coordinator_notes']
    ];
}

try {
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $mission_id = isset($_GET['mission_id']) ? (int)$_GET['mission_id'] : 0;
    $date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
    $date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

    $missions = $conn->prepare("SELECT *
                                FROM v_admin_mission_analytics
                                ORDER BY mission_date ASC, mission_id ASC");
    $missions->execute();
    $mission_rows = array_map('format_admin_mission', $missions->fetchAll(PDO::FETCH_ASSOC));

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

    $booking_sql = "SELECT *
                    FROM v_admin_booking_directory" .
                    ($where ? " WHERE " . implode(' AND ', $where) : "") .
                    " ORDER BY mission_date DESC, requested_at DESC";
    $bookings = $conn->prepare($booking_sql);
    $bookings->execute($params);
    $booking_rows = array_map('format_admin_booking', $bookings->fetchAll(PDO::FETCH_ASSOC));

    $patients = $conn->prepare("SELECT p.patient_id,
                                       CONCAT_WS(' ', p.first_name, p.middle_name, p.last_name, p.suffix) AS patient_name,
                                       p.contact_number,
                                       p.email,
                                       p.city,
                                       COUNT(b.booking_id) AS booking_count
                                FROM patients p
                                LEFT JOIN bookings b ON b.patient_id = p.patient_id
                                GROUP BY p.patient_id, p.first_name, p.middle_name, p.last_name, p.suffix, p.contact_number, p.email, p.city
                                ORDER BY p.created_at DESC");
    $patients->execute();

    $content = $conn->prepare("SELECT page_id, page_key, title, body, status, published_at, updated_at
                               FROM content_pages
                               ORDER BY page_key ASC");
    $content->execute();

    $totals = [
        'missions' => count($mission_rows),
        'accepting_missions' => count(array_filter($mission_rows, function ($mission) {
            return $mission['mission_status'] === 'open' && $mission['available_slots'] > 0;
        })),
        'full_missions' => count(array_filter($mission_rows, function ($mission) {
            return in_array($mission['mission_status'], ['closed', 'full'], true);
        })),
        'completed_missions' => count(array_filter($mission_rows, function ($mission) {
            return $mission['mission_status'] === 'completed';
        })),
        'bookings' => array_sum(array_column($mission_rows, 'total_bookings')),
        'confirmed_headcount' => array_sum(array_column($mission_rows, 'confirmed_headcount')),
        'completed' => array_sum(array_column($mission_rows, 'completed_count')),
        'patients' => 0
    ];

    $patient_rows = array_map(function ($patient) {
        return [
            'patient_id' => (int)$patient['patient_id'],
            'patient_name' => trim($patient['patient_name']),
            'contact_number' => $patient['contact_number'],
            'email' => $patient['email'],
            'city' => $patient['city'],
            'booking_count' => (int)$patient['booking_count']
        ];
    }, $patients->fetchAll(PDO::FETCH_ASSOC));
    $totals['patients'] = count($patient_rows);

    json_success('Admin dashboard loaded.', [
        'summary' => $totals,
        'missions' => $mission_rows,
        'bookings' => $booking_rows,
        'patients' => $patient_rows,
        'content_pages' => $content->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    json_error('Unable to load admin dashboard data right now. Please try again later.', 500);
}
?>
