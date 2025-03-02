<?php
error_reporting(E_ALL);
include('../../connection/connection.php'); // Ensure SQLSRV connection is properly set up
date_default_timezone_set('Asia/Manila');

// Validate required parameters
if (!isset($_GET['start_date']) || empty($_GET['start_date'])) {
    die(json_encode(["error" => "Start date parameter is required."]));
}

if (!isset($_GET['end_date']) || empty($_GET['end_date'])) {
    die(json_encode(["error" => "End date parameter is required."]));
}

if (!isset($_GET['rfid_no']) || empty($_GET['rfid_no'])) {
    die(json_encode(["error" => "RFID parameter is required."]));
}

$start_date = $_GET['start_date']; // Expected format: YYYY-MM-DD
$end_date = $_GET['end_date']; // Expected format: YYYY-MM-DD
$rfid_no = $_GET['rfid_no']; // Faculty RFID number

// Fetch attendance records for the given date range and RFID
$sql = "SELECT a.attd_ref, a.rfid_no, f.fname, f.lname, 
               a.time_in, a.time_out, a.status, a.date_logged
        FROM AttendanceRecords a
        JOIN Faculty f ON a.rfid_no = f.rfid_no
        WHERE a.date_logged BETWEEN ? AND ? AND a.rfid_no = ?
        ORDER BY a.date_logged ASC"; // Ordered by date

$params = [$start_date, $end_date, $rfid_no];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die(json_encode(["error" => "Query failed.", "details" => sqlsrv_errors()]));
}

$attendanceRecords = [];
$statusCounts = ["Present" => 0, "Absent" => 0, "Late" => 0];
$grandTotalHours = 0; // Grand total of all computed hours

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $row['date_logged'] = $row['date_logged']->format('Y-m-d'); // Format date

    // Ensure time values are properly formatted as strings
    $row['time_in'] = isset($row['time_in']) ? $row['time_in']->format('H:i:s') : null;
    $row['time_out'] = isset($row['time_out']) ? $row['time_out']->format('H:i:s') : null;

    // Calculate total hours
    $totalHours = 0;
    if ($row['time_in'] !== null && $row['time_out'] !== null) {
        $timeIn = strtotime($row['time_in']);
        $timeOut = strtotime($row['time_out']);
        $totalHours = ($timeOut - $timeIn) / 3600; // Convert seconds to hours
    }

    $row['total_hours'] = number_format($totalHours, 2); // Format to 2 decimal places
    $grandTotalHours += $totalHours; // Add to grand total

    // Count Status
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']]++;
    }

    $attendanceRecords[] = $row;
}

// Response
$response = [
    "attendanceRecords" => $attendanceRecords,
    "statusCounts" => $statusCounts,
    "grandTotalHours" => number_format($grandTotalHours, 2) // Format to 2 decimal places
];

echo json_encode($response);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
