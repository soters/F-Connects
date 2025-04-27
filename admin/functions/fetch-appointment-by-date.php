<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_GET['date']) || empty($_GET['date'])) {
    die(json_encode(["error" => "Invalid or missing date parameter."]));
}

$date = $_GET['date'];
$facultyId = isset($_GET['faculty']) ? $_GET['faculty'] : null;

// Define all possible values based on your schema
$possibleAgendas = [
    "Internship or Practical Experience Advice",
    "Project Or Research Discussion",
    "Mentorship",
    "Personal Academic Concerns",
    "Others"
];

$possibleStatuses = [
    "Pending",
    "Accepted",
    "Completed",
    "Declined",
    "Cancelled"
];

// Initialize counts with all possible values set to 0
$statusCounts = array_fill_keys($possibleStatuses, 0);
$agendaCounts = array_fill_keys($possibleAgendas, 0);

// Add "Unknown" category for any unexpected values
$statusCounts['Unknown'] = 0;
$agendaCounts['Unknown'] = 0;

// Initialize grand total counter
$grandTotal = 0;

// Base query
$sql = "SELECT a.appointment_code, f.rfid_no AS faculty_rfid, f.fname, f.lname, 
               s.fname AS stud_fname, s.lname AS stud_lname, 
               a.start_time, a.end_time, a.agenda, a.status 
        FROM Appointments a
        JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE a.date_logged = ?";

// Add faculty filter if provided
if ($facultyId) {
    $sql .= " AND a.prof_rfid_no = ?";
    $params = array($date, $facultyId);
} else {
    $params = array($date);
}

$sql .= " ORDER BY a.start_time ASC";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(json_encode(["error" => "Query failed.", "details" => sqlsrv_errors()]));
}

$appointments = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $row['start_time'] = $row['start_time']->format('H:i:s');
    $row['end_time'] = $row['end_time']->format('H:i:s');

    // Process status
    $status = $row['status'] ?? 'Unknown';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    } else {
        $statusCounts['Unknown']++;
    }

    // Process agenda
    $agenda = $row['agenda'] ?? 'Unknown';
    if (isset($agendaCounts[$agenda])) {
        $agendaCounts[$agenda]++;
    } else {
        $agendaCounts['Unknown']++;
    }

    // Increment grand total
    $grandTotal++;

    $appointments[] = $row;
}

// Prepare counts in the required format
$formattedStatusCounts = [];
foreach ($statusCounts as $status => $count) {
    if ($count > 0) { // Only include statuses with counts > 0
        $formattedStatusCounts[] = [
            'status' => $status,
            'count' => $count
        ];
    }
}

$formattedAgendaCounts = [];
foreach ($agendaCounts as $agenda => $count) {
    if ($count > 0) { // Only include agendas with counts > 0
        $formattedAgendaCounts[] = [
            'agenda' => $agenda,
            'count' => $count
        ];
    }
}

$response = [
    "appointments" => $appointments,
    "statusCounts" => $formattedStatusCounts,
    "agendaCounts" => $formattedAgendaCounts,
    "grandTotal" => $grandTotal
];

header('Content-Type: application/json');
echo json_encode($response);

// Clean up
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>