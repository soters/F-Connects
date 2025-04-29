<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_GET['year']) || empty($_GET['year'])) {
    echo json_encode(["error" => "Year parameter is required."]);
    exit;
}

$year = $_GET['year'];
$faculty = isset($_GET['faculty']) ? $_GET['faculty'] : null;

// Base query
$sql = "SELECT a.appointment_code, 
               f.fname AS prof_fname, f.lname AS prof_lname, 
               s.fname AS stud_fname, s.lname AS stud_lname, 
               a.start_time, a.end_time, a.agenda, a.status, 
               FORMAT(a.date_logged, 'yyyy-MM-dd') AS date_logged,
               FORMAT(a.date_logged, 'MMMM') AS month_name
        FROM Appointments a
        JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE FORMAT(a.date_logged, 'yyyy') = ?";

// Add faculty filter if provided
if ($faculty) {
    $sql .= " AND a.prof_rfid_no = ?";
    $params = [$year, $faculty];
} else {
    $params = [$year];
}

$sql .= " ORDER BY a.date_logged ASC";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    echo json_encode(["error" => "Query failed.", "details" => sqlsrv_errors()]);
    exit;
}

$appointments = [];
$monthlyAppointments = [];
$statusCounts = [];
$agendaCounts = [];
$grandTotal = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Format time correctly
    $row['start_time'] = isset($row['start_time']) ? $row['start_time']->format('H:i:s') : null;
    $row['end_time'] = isset($row['end_time']) ? $row['end_time']->format('H:i:s') : null;

    // Count Status
    $status = $row['status'] ?? 'Unknown';
    if (!isset($statusCounts[$status])) {
        $statusCounts[$status] = 0;
    }
    $statusCounts[$status]++;

    // Count Agenda
    $agenda = $row['agenda'] ?? 'Other';
    if (!isset($agendaCounts[$agenda])) {
        $agendaCounts[$agenda] = 0;
    }
    $agendaCounts[$agenda]++;

    // Group by month
    $month = $row['month_name'] ?? 'Unknown';
    if (!isset($monthlyAppointments[$month])) {
        $monthlyAppointments[$month] = [];
    }
    $monthlyAppointments[$month][] = $row;

    $appointments[] = $row;
    $grandTotal++;
}

// Convert counts to arrays for easier processing in JS
$statusCountsArray = [];
foreach ($statusCounts as $status => $count) {
    $statusCountsArray[] = ["status" => $status, "count" => $count];
}

$agendaCountsArray = [];
foreach ($agendaCounts as $agenda => $count) {
    $agendaCountsArray[] = ["agenda" => $agenda, "count" => $count];
}

// Response
$response = [
    "appointments" => $appointments,
    "monthlyAppointments" => $monthlyAppointments,
    "statusCounts" => $statusCountsArray,
    "agendaCounts" => $agendaCountsArray,
    "grandTotal" => $grandTotal,
    "year" => $year,
    "faculty" => $faculty
];

echo json_encode($response);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>