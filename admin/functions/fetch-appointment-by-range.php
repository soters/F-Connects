<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Validate required parameters
if (!isset($_GET['start']) || empty($_GET['start']) || !isset($_GET['end']) || empty($_GET['end'])) {
    echo json_encode(["error" => "Both start and end date parameters are required."]);
    exit;
}

$startDate = $_GET['start'];
$endDate = $_GET['end'];
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : null;

// Validate date format
if (!DateTime::createFromFormat('Y-m-d', $startDate) || !DateTime::createFromFormat('Y-m-d', $endDate)) {
    echo json_encode(["error" => "Invalid date format. Use YYYY-MM-DD."]);
    exit;
}

// Ensure start date is before end date
if (strtotime($startDate) > strtotime($endDate)) {
    echo json_encode(["error" => "Start date must be before end date."]);
    exit;
}

// Base query with proper date filtering
$sql = "SELECT 
            a.appointment_code, 
            f.fname AS prof_fname, 
            f.lname AS prof_lname, 
            s.fname AS stud_fname, 
            s.lname AS stud_lname, 
            a.start_time, 
            a.end_time, 
            a.agenda, 
            a.status, 
            FORMAT(a.date_logged, 'yyyy-MM-dd') AS date_logged,
            FORMAT(a.date_logged, 'MMMM dd, yyyy') AS formatted_date
        FROM Appointments a
        JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE a.date_logged BETWEEN ? AND ?
        ";

// Add faculty filter if provided
if ($faculty) {
    $sql .= " AND a.prof_rfid_no = ?";
    $params = [$startDate, $endDate, $faculty];
} else {
    $params = [$startDate, $endDate];
}

$sql .= " ORDER BY a.date_logged ASC, a.start_time ASC";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(["error" => "Database query failed.", "details" => sqlsrv_errors()]);
    exit;
}

$appointments = [];
$dailyAppointments = [];
$statusCounts = [];
$agendaCounts = [];
$grandTotal = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Format time values
    $row['start_time'] = isset($row['start_time']) ? $row['start_time']->format('H:i:s') : null;
    $row['end_time'] = isset($row['end_time']) ? $row['end_time']->format('H:i:s') : null;

    // Count statuses
    $status = $row['status'] ?? 'Unknown';
    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;

    // Count agendas
    $agenda = $row['agenda'] ?? 'Other';
    if (!isset($agendaCounts[$agenda])) {
        $agendaCounts[$agenda] = 0;
    }
    $agendaCounts[$agenda]++;

    // Group by date
    $date = $row['date_logged'];
    if (!isset($dailyAppointments[$date])) {
        $dailyAppointments[$date] = [];
    }
    $dailyAppointments[$date][] = $row;

    $appointments[] = $row;
    $grandTotal++;
}

// Convert counts to arrays for frontend
$statusCountsArray = array_map(function($status, $count) {
    return ["status" => $status, "count" => $count];
}, array_keys($statusCounts), $statusCounts);

$agendaCountsArray = array_map(function($agenda, $count) {
    return ["agenda" => $agenda, "count" => $count];
}, array_keys($agendaCounts), $agendaCounts);

// Prepare response
$response = [
    "success" => true,
    "dateRange" => [
        "start" => $startDate,
        "end" => $endDate,
        "days" => count($dailyAppointments)
    ],
    "appointments" => $appointments,
    "dailyAppointments" => $dailyAppointments,
    "statusCounts" => $statusCountsArray,
    "agendaCounts" => $agendaCountsArray,
    "grandTotal" => $grandTotal,
    "facultyFilter" => $faculty,
    "generatedAt" => date('Y-m-d H:i:s')
];

echo json_encode($response);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>