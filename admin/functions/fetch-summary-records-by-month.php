<?php
error_reporting(E_ALL);
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Input validation
if (!isset($_GET['month'], $_GET['year']) || empty($_GET['month']) || empty($_GET['year'])) {
    die(json_encode(["error" => "Missing required parameters."]));
}

// Extract year and month
$monthParts = explode('-', $_GET['month']);
if (count($monthParts) !== 2) {
    die(json_encode(["error" => "Invalid month format. Expected format: YYYY-MM"]));
}

$year = $monthParts[0];
$monthNumber = $monthParts[1];

// 1. Fetch all faculty members
$facultyQuery = "SELECT rfid_no, fname, lname, email, employment_type FROM Faculty";
$facultyStmt = sqlsrv_query($conn, $facultyQuery);

if ($facultyStmt === false) {
    die(json_encode(["error" => "Failed to fetch faculty members.", "details" => sqlsrv_errors()]));
}

$facultySummaries = [];

while ($faculty = sqlsrv_fetch_array($facultyStmt, SQLSRV_FETCH_ASSOC)) {
    $rfid_no = $faculty['rfid_no'];

    // Initialize data
    $statusCounts = ["Present" => 0, "Late" => 0, "Absent" => 0];
    $totalScheduledDays = 0;
    $totalScheduledHours = 0;
    $totalRenderedHours = 0;
    $actualWorkedHours = 0;
    $uniqueDates = [];
    $absentDates = [];
    $hasData = false; // Flag to track if faculty has any data

    // 2. Get all unique scheduled dates for this faculty in the month
    $datesQuery = "
        SELECT DISTINCT CAST(start_date AS DATE) as schedule_date
        FROM Schedules
        WHERE 
            rfid_no = ?
            AND YEAR(start_date) = ?
            AND MONTH(start_date) = ?
            AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    ";

    $datesStmt = sqlsrv_query($conn, $datesQuery, [$rfid_no, $year, $monthNumber]);
    if ($datesStmt !== false) {
        while ($dateRow = sqlsrv_fetch_array($datesStmt, SQLSRV_FETCH_ASSOC)) {
            $dateStr = $dateRow['schedule_date']->format('Y-m-d');
            $uniqueDates[] = $dateStr;
            $absentDates[$dateStr] = true; // Assume absent by default
            $hasData = true; // Found at least one scheduled day
        }
        sqlsrv_free_stmt($datesStmt);
    }
    $totalScheduledDays = count($uniqueDates);

    // 3. Get attendance records to determine present/late/absent
    $attdQuery = "
        SELECT DISTINCT CAST(date_logged AS DATE) as attendance_date, status
        FROM AttendanceToday
        WHERE 
            rfid_no = ?
            AND YEAR(date_logged) = ?
            AND MONTH(date_logged) = ?
    ";

    $attdStmt = sqlsrv_query($conn, $attdQuery, [$rfid_no, $year, $monthNumber]);
    if ($attdStmt !== false) {
        while ($attdRow = sqlsrv_fetch_array($attdStmt, SQLSRV_FETCH_ASSOC)) {
            $dateStr = $attdRow['attendance_date']->format('Y-m-d');
            if (isset($absentDates[$dateStr])) {
                unset($absentDates[$dateStr]); // Not absent since we found attendance
                if (isset($statusCounts[$attdRow['status']])) {
                    $statusCounts[$attdRow['status']]++;
                    $hasData = true; // Found at least one attendance record
                }
            }
        }
        sqlsrv_free_stmt($attdStmt);
    }

    // Skip faculty if no data was found
    if (!$hasData) {
        continue;
    }

    // Count remaining dates as absent
    $statusCounts['Absent'] = count($absentDates);

    // 4. Calculate scheduled hours and rendered hours
    $hoursQuery = "
        SELECT 
            start_time, end_time, timed_in, timed_out, status
        FROM Schedules
        WHERE 
            rfid_no = ?
            AND YEAR(start_date) = ?
            AND MONTH(start_date) = ?
            AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    ";

    $hoursStmt = sqlsrv_query($conn, $hoursQuery, [$rfid_no, $year, $monthNumber]);
    if ($hoursStmt !== false) {
        while ($hourRow = sqlsrv_fetch_array($hoursStmt, SQLSRV_FETCH_ASSOC)) {
            $startTime = new DateTime($hourRow['start_time']->format('H:i:s'));
            $endTime = new DateTime($hourRow['end_time']->format('H:i:s'));
            $schedHours = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600;
            $totalScheduledHours += $schedHours;

            // Calculate rendered hours if not absent
            if (
                $hourRow['status'] !== 'Absent' && $hourRow['status'] !== null &&
                $hourRow['timed_in'] && $hourRow['timed_out']
            ) {
                $timedIn = new DateTime($hourRow['timed_in']->format('H:i:s'));
                $timedOut = new DateTime($hourRow['timed_out']->format('H:i:s'));
                $actualOut = min($timedOut->getTimestamp(), $endTime->getTimestamp());
                if ($actualOut > $timedIn->getTimestamp()) {
                    $renderedHours = ($actualOut - $timedIn->getTimestamp()) / 3600;
                    $totalRenderedHours += $renderedHours;
                }
            }
        }
        sqlsrv_free_stmt($hoursStmt);
    }

    // 5. Calculate actual worked hours from AttendanceToday
    $workedQuery = "
        SELECT time_in, time_out
        FROM AttendanceToday
        WHERE 
            rfid_no = ?
            AND YEAR(date_logged) = ?
            AND MONTH(date_logged) = ?
    ";

    $workedStmt = sqlsrv_query($conn, $workedQuery, [$rfid_no, $year, $monthNumber]);
    if ($workedStmt !== false) {
        while ($workedRow = sqlsrv_fetch_array($workedStmt, SQLSRV_FETCH_ASSOC)) {
            if ($workedRow['time_in'] && $workedRow['time_out']) {
                $timeIn = strtotime($workedRow['time_in']->format('H:i:s'));
                $timeOut = strtotime($workedRow['time_out']->format('H:i:s'));
                $actualWorkedHours += ($timeOut - $timeIn) / 3600;
            }
        }
        sqlsrv_free_stmt($workedStmt);
    }

    // 6. Calculate attendance percentage (count both Present and Late as present)
    $presentAndLateDays = $statusCounts['Present'] + $statusCounts['Late'];
    $attendancePercentage = $totalScheduledDays > 0
        ? number_format(($presentAndLateDays / $totalScheduledDays) * 100, 2)
        : 0;

    // 7. Store faculty summary
    $facultySummaries[] = [
        "rfid_no" => $rfid_no,
        "fname" => $faculty['fname'],
        "lname" => $faculty['lname'],
        "email" => $faculty['email'],
        "employment_type" => $faculty['employment_type'],
        "statusCounts" => $statusCounts,
        "totalScheduledDays" => $totalScheduledDays,
        "totalScheduledHours" => number_format($totalScheduledHours, 2),
        "totalRenderedHours" => number_format($totalRenderedHours, 2),
        "actualWorkedHours" => number_format($actualWorkedHours, 2),
        "attendancePercentage" => $attendancePercentage
    ];
}

sqlsrv_free_stmt($facultyStmt);

$response = [
    "facultySummaries" => $facultySummaries,
    "status" => empty($facultySummaries) ? "No attendance data available for any faculty member this month" : "Success"
];

echo json_encode($response, JSON_PRETTY_PRINT);
sqlsrv_close($conn);
?>