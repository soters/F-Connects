<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (!isset($_GET['date']) || empty($_GET['date'])) {
    die(json_encode(["error" => "Invalid or missing date parameter."]));
}

$date = $_GET['date'];

$sql = "SELECT a.appointment_code, f.rfid_no AS faculty_rfid, f.fname, f.lname, 
               s.fname AS stud_fname, s.lname AS stud_lname, 
               a.start_time, a.end_time, a.agenda, a.status 
        FROM Appointments a
        JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
        JOIN Students s ON a.stud_rfid_no = s.rfid_no
        WHERE a.date_logged = ?
        ORDER BY a.start_time ASC";

$params = array($date);
$stmt = sqlsrv_query($conn, $sql, $params);

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

// Track completed appointments by faculty RFID
$completedPerFaculty = [];

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $row['start_time'] = $row['start_time']->format('H:i:s');
    $row['end_time'] = $row['end_time']->format('H:i:s');

    // Count status
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']]++;
    }

    // Count agenda
    if (isset($agendaCounts[$row['agenda']])) {
        $agendaCounts[$row['agenda']]++;
    }

    // Count completed per faculty
    if ($row['status'] === "Completed") {
        $facultyKey = $row['faculty_rfid'];
        if (!isset($completedPerFaculty[$facultyKey])) {
            $completedPerFaculty[$facultyKey] = [
                "count" => 0,
                "name" => $row['fname'] . " " . $row['lname']
            ];
        }
        $completedPerFaculty[$facultyKey]["count"]++;
    }

    $appointments[] = $row;
}

// Determine the faculty member(s) with most completed appointments
$mostCompleted = [];
$maxCompleted = 0;

foreach ($completedPerFaculty as $faculty) {
    if ($faculty['count'] > $maxCompleted) {
        $maxCompleted = $faculty['count'];
        $mostCompleted = [$faculty['name']];
    } elseif ($faculty['count'] === $maxCompleted) {
        $mostCompleted[] = $faculty['name'];
    }
}

$response = [
    "appointments" => $appointments,
    "statusCounts" => $statusCounts,
    "agendaCounts" => $agendaCounts,
    "topCompletedFaculty" => $mostCompleted,
    "maxCompletedCount" => $maxCompleted
];

echo json_encode($response);

// Clean up
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
