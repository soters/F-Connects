<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Validate RFID input
if (!isset($_GET['faculty']) || empty($_GET['faculty'])) {
    die(json_encode(["error" => "Invalid or missing faculty_rfid parameter."]));
}

$facultyRFID = $_GET['faculty'];

// Fetch faculty name
$facultySql = "SELECT fname, lname FROM Faculty WHERE rfid_no = ?";
$facultyStmt = sqlsrv_query($conn, $facultySql, [$facultyRFID]);

if ($facultyStmt === false || !($faculty = sqlsrv_fetch_array($facultyStmt, SQLSRV_FETCH_ASSOC))) {
    die(json_encode(["error" => "Faculty not found."]));
}

// Fetch faculty appointments
$sql = "SELECT a.appointment_code, s.fname AS stud_fname, s.lname AS stud_lname, 
               a.start_time, a.end_time, a.agenda, a.status, a.date_logged, a.rate
        FROM Appointments a
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE a.prof_rfid_no = ?
        ORDER BY a.start_time ASC";

$stmt = sqlsrv_query($conn, $sql, [$facultyRFID]);

if ($stmt === false) {
    die(json_encode(["error" => "Query failed.", "details" => sqlsrv_errors()]));
}

$appointments = [];
$statusCounts = ["Completed" => 0, "Cancelled" => 0, "Declined" => 0];
$agendaCounts = [
    "Internship or Practical Experience Advice" => 0,
    "Personal Academic Concerns" => 0,
    "Project/Research Discussion" => 0,
    "Mentorship" => 0
];

// Rating logic
$totalRating = 0;
$ratingCount = 0;

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Format time/date
    $row['start_time'] = $row['start_time']->format('H:i:s');
    $row['end_time'] = $row['end_time']->format('H:i:s');
    $row['date_logged'] = $row['date_logged']->format('Y-m-d');

    // Count appointment status
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']]++;
    }

    // Count agenda type
    if (isset($agendaCounts[$row['agenda']])) {
        $agendaCounts[$row['agenda']]++;
    }

    // Count only completed appointments with a valid rating
    if ($row['status'] === 'Completed' && is_numeric($row['rate'])) {
        $totalRating += $row['rate'];
        $ratingCount++;
    }

    $appointments[] = $row;
}

// Compute average rating
$averageRating = $ratingCount > 0 ? round($totalRating / $ratingCount, 1) : null;

// Final JSON response
$response = [
    "faculty" => $faculty,
    "appointments" => $appointments,
    "statusCounts" => $statusCounts,
    "agendaCounts" => $agendaCounts,
    "ratings" => [
        "average" => $averageRating,
        "count" => $ratingCount
    ]
];

echo json_encode($response);

// Cleanup
sqlsrv_free_stmt($facultyStmt);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>