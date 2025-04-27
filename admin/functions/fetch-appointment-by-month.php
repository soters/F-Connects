<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_GET['month']) || empty($_GET['month'])) {
    die(json_encode(["error" => "Invalid or missing month parameter. Expected format: YYYY-MM"]));
}

$month = $_GET['month'];
$facultyId = isset($_GET['faculty']) ? $_GET['faculty'] : null;

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    die(json_encode(["error" => "Invalid month format. Expected format: YYYY-MM"]));
}

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

// Base query - modified for month filter
$sql = "SELECT a.appointment_code, f.rfid_no AS faculty_rfid, f.fname, f.lname, 
               s.fname AS stud_fname, s.lname AS stud_lname, 
               a.date_logged, a.start_time, a.end_time, a.agenda, a.status 
        FROM Appointments a
        JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE CONVERT(varchar(7), a.date_logged, 120) = ?";

// Add faculty filter if provided
if ($facultyId) {
    $sql .= " AND a.prof_rfid_no = ?";
    $params = array($month, $facultyId);
} else {
    $params = array($month);
}

$sql .= " ORDER BY a.date_logged, a.start_time ASC";

$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(json_encode(["error" => "Query failed.", "details" => sqlsrv_errors()]));
}

$appointments = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Format dates and times
    $row['date_logged'] = $row['date_logged']->format('Y-m-d');
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