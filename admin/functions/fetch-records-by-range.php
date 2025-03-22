<?php
error_reporting(E_ALL);
include('../../connection/connection.php');
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

// 1. Get SCHEDULED sessions for this date range
$schedQuery = "
    SELECT 
        sched_id, type, start_date, end_date, start_time, end_time, 
        status, timed_in, timed_out
    FROM Schedules
    WHERE 
        rfid_no = ?
        AND start_date >= ?
        AND end_date <= ?
        AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
";

$schedParams = [$rfid_no, $start_date, $end_date];
$schedStmt = sqlsrv_query($conn, $schedQuery, $schedParams);

if ($schedStmt === false) {
    die(json_encode(["error" => "Failed to fetch schedules", "details" => sqlsrv_errors()]));
}

$schedules = [];
$totalScheduledDays = 0;
$totalScheduledHours = 0;
$totalRenderedHours = 0;

while ($row = sqlsrv_fetch_array($schedStmt, SQLSRV_FETCH_ASSOC)) {
    $startTime = strtotime($row['start_time']->format('H:i:s'));
    $endTime = strtotime($row['end_time']->format('H:i:s'));
    $schedHours = ($endTime - $startTime) / 3600;

    $timedIn = $row['timed_in'] ? strtotime($row['timed_in']->format('H:i:s')) : null;
    $timedOut = $row['timed_out'] ? strtotime($row['timed_out']->format('H:i:s')) : null;

    $status = $row['status'] ?? null;
    $renderedHours = 0;

    // Only compute if status is not Absent or NULL
    if ($status !== 'Absent' && $status !== null && $timedIn && $timedOut) {
        // Cap timedOut to scheduled end time
        $actualOut = min($timedOut, $endTime);
        if ($actualOut > $timedIn) {
            $renderedHours = ($actualOut - $timedIn) / 3600;
            $totalRenderedHours += $renderedHours;
        }
    }

    $schedules[] = [
        'sched_id' => $row['sched_id'],
        'date' => $row['start_date']->format('Y-m-d'),
        'start_time' => $row['start_time']->format('H:i:s'),
        'end_time' => $row['end_time']->format('H:i:s'),
        'sched_hours' => number_format($schedHours, 2),
        'type' => $row['type'],
        'timed_in' => $row['timed_in'] ? $row['timed_in']->format('H:i:s') : null,
        'timed_out' => $row['timed_out'] ? $row['timed_out']->format('H:i:s') : null,
        'rendered_hours' => number_format($renderedHours, 2)
    ];

    $totalScheduledDays++;
    $totalScheduledHours += $schedHours;
}

sqlsrv_free_stmt($schedStmt);

// 2. Fetch TAP-INs from AttendanceToday
$attdQuery = "
    SELECT 
        time_in, time_out, status, date_logged
    FROM AttendanceToday
    WHERE 
        rfid_no = ?
        AND date_logged >= ?
        AND date_logged <= ?
";

$attdStmt = sqlsrv_query($conn, $attdQuery, [$rfid_no, $start_date, $end_date]);
if ($attdStmt === false) {
    die(json_encode(["error" => "Failed to fetch attendance", "details" => sqlsrv_errors()]));
}

$attendanceMap = [];
$statusCounts = ["Present" => 0, "Late" => 0, "Absent" => 0];
$actualWorkedHours = 0;

while ($row = sqlsrv_fetch_array($attdStmt, SQLSRV_FETCH_ASSOC)) {
    $date = $row['date_logged']->format('Y-m-d');
    $timeIn = $row['time_in'] ? $row['time_in']->format('H:i:s') : null;
    $timeOut = $row['time_out'] ? $row['time_out']->format('H:i:s') : null;

    // Calculate worked hours
    $hoursWorked = 0;
    if ($timeIn && $timeOut) {
        $hoursWorked = (strtotime($timeOut) - strtotime($timeIn)) / 3600;
        $actualWorkedHours += $hoursWorked;
    }

    // Count status
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']]++;
    }

    $attendanceMap[$date] = [
        'time_in' => $timeIn,
        'time_out' => $timeOut,
        'status' => $row['status'],
        'worked_hours' => number_format($hoursWorked, 2)
    ];
}
sqlsrv_free_stmt($attdStmt);

// 3. Get basic faculty info
$facultyQuery = "SELECT fname, lname, email, employment_type FROM Faculty WHERE rfid_no = ?";
$facultyStmt = sqlsrv_query($conn, $facultyQuery, [$rfid_no]);
$facultyInfo = [];

if ($facultyStmt && $row = sqlsrv_fetch_array($facultyStmt, SQLSRV_FETCH_ASSOC)) {
    $facultyInfo = [
        "rfid_no" => $rfid_no,
        "fname" => $row['fname'],
        "lname" => $row['lname'],
        "email" => $row['email'],
        "employment_type" => $row['employment_type']
    ];
}
sqlsrv_free_stmt($facultyStmt);

// 4. Merge schedule and attendance for report
$attendanceReport = [];
foreach ($schedules as $sched) {
    $date = $sched['date'];
    $attd = $attendanceMap[$date] ?? null;

    // Compute total_worked_hours based on timed_in/timed_out
    $timedIn = isset($sched['timed_in']) ? strtotime($sched['timed_in']) : null;
    $timedOut = isset($sched['timed_out']) ? strtotime($sched['timed_out']) : null;
    $schedEnd = strtotime($sched['end_time']);

    $totalWorkedHours = 0;

    $status = $attd['status'] ?? null;

    if ($status !== 'Absent' && $status !== null && $timedIn && $timedOut) {
        $actualOut = min($timedOut, $schedEnd);
        if ($actualOut > $timedIn) {
            $totalWorkedHours = ($actualOut - $timedIn) / 3600;
        }
    } else {
        $totalWorkedHours = 0;
    }

    $attendanceReport[] = [
        "date" => $date,
        "type" => $sched['type'],
        "start_time" => $sched['start_time'],
        "end_time" => $sched['end_time'],
        "sched_hours" => $sched['sched_hours'],
        "time_in" => $attd['time_in'] ?? null,
        "time_out" => $attd['time_out'] ?? null,
        "actual_hours" => $attd['worked_hours'] ?? 0,
        "status" => $attd['status'] ?? 'Absent',
        "total_worked_hours" => number_format($totalWorkedHours, 2) // <-- new field for jsPDF
    ];
}

// 5. Attendance Percentage
$presentDays = $statusCounts['Present'];
$attendancePercentage = $totalScheduledDays > 0
    ? number_format(($presentDays / $totalScheduledDays) * 100, 2)
    : 0;

$response = [
    "facultyInfo" => $facultyInfo,
    "attendanceReport" => $attendanceReport,
    "statusCounts" => $statusCounts,
    "totalScheduledDays" => $totalScheduledDays,
    "totalScheduledHours" => number_format($totalScheduledHours, 2),
    "actualWorkedHours" => number_format($actualWorkedHours, 2),
    "totalRenderedHours" => number_format($totalRenderedHours, 2),
    "attendancePercentage" => $attendancePercentage
];

echo json_encode($response, JSON_PRETTY_PRINT);

sqlsrv_close($conn);
?>