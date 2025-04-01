<?php
error_reporting(E_ALL);
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Input validation - expects ISO week format (YYYY-Www)
if (!isset($_GET['week']) || empty($_GET['week'])) {
    die(json_encode(["error" => "Missing week parameter."]));
}

$weekInput = $_GET['week'];
if (!preg_match('/^(\d{4})-W(\d{2})$/', $weekInput, $matches)) {
    die(json_encode(["error" => "Invalid week format. Expected format: YYYY-Www"]));
}

$year = $matches[1];
$weekNum = $matches[2];

// Calculate week start and end dates
$weekStart = new DateTime();
$weekStart->setISODate($year, $weekNum);
$weekEnd = clone $weekStart;
$weekEnd->modify('+6 days');
$weekRange = $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y');

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
    $hasData = false; // Flag to check if faculty has any data

    // 2. Get scheduled dates for this week
    $datesQuery = "
        SELECT DISTINCT CAST(start_date AS DATE) as schedule_date
        FROM Schedules
        WHERE 
            rfid_no = ?
            AND start_date >= ?
            AND start_date <= ?
            AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    ";
    
    $datesParams = [
        $rfid_no,
        $weekStart->format('Y-m-d'),
        $weekEnd->format('Y-m-d')
    ];
    
    $datesStmt = sqlsrv_query($conn, $datesQuery, $datesParams);
    if ($datesStmt !== false) {
        while ($dateRow = sqlsrv_fetch_array($datesStmt, SQLSRV_FETCH_ASSOC)) {
            $dateStr = $dateRow['schedule_date']->format('Y-m-d');
            $uniqueDates[] = $dateStr;
            $absentDates[$dateStr] = true; // Assume absent by default
            $hasData = true; // We found at least one scheduled day
        }
        sqlsrv_free_stmt($datesStmt);
    }
    $totalScheduledDays = count($uniqueDates);

    // 3. Get weekly attendance records
    $attdQuery = "
        SELECT DISTINCT CAST(date_logged AS DATE) as attendance_date, status
        FROM AttendanceToday
        WHERE 
            rfid_no = ?
            AND date_logged >= ?
            AND date_logged <= ?
    ";
    
    $attdStmt = sqlsrv_query($conn, $attdQuery, $datesParams);
    if ($attdStmt !== false) {
        while ($attdRow = sqlsrv_fetch_array($attdStmt, SQLSRV_FETCH_ASSOC)) {
            $dateStr = $attdRow['attendance_date']->format('Y-m-d');
            if (isset($absentDates[$dateStr])) {
                unset($absentDates[$dateStr]);
                if (isset($statusCounts[$attdRow['status']])) {
                    $statusCounts[$attdRow['status']]++;
                    $hasData = true; // We found at least one attendance record
                }
            }
        }
        sqlsrv_free_stmt($attdStmt);
    }
    
    // Only proceed if faculty has data
    if (!$hasData) {
        continue; // Skip to next faculty member
    }
    
    // Count remaining dates as absent
    $statusCounts['Absent'] = count($absentDates);

    // 4. Calculate scheduled and rendered hours
    $hoursQuery = "
        SELECT 
            start_time, end_time, timed_in, timed_out, status
        FROM Schedules
        WHERE 
            rfid_no = ?
            AND start_date >= ?
            AND start_date <= ?
            AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    ";
    
    $hoursStmt = sqlsrv_query($conn, $hoursQuery, $datesParams);
    if ($hoursStmt !== false) {
        while ($hourRow = sqlsrv_fetch_array($hoursStmt, SQLSRV_FETCH_ASSOC)) {
            $startTime = new DateTime($hourRow['start_time']->format('H:i:s'));
            $endTime = new DateTime($hourRow['end_time']->format('H:i:s'));
            $schedHours = ($endTime->getTimestamp() - $startTime->getTimestamp()) / 3600;
            $totalScheduledHours += $schedHours;

            if ($hourRow['status'] !== 'Absent' && $hourRow['status'] !== null && 
                $hourRow['timed_in'] && $hourRow['timed_out']) {
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

    // 5. Calculate actual worked hours
    $workedQuery = "
        SELECT time_in, time_out
        FROM AttendanceToday
        WHERE 
            rfid_no = ?
            AND date_logged >= ?
            AND date_logged <= ?
    ";
    
    $workedStmt = sqlsrv_query($conn, $workedQuery, $datesParams);
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

    // 6. Calculate attendance percentage (Present + Late count as present)
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
    "weekRange" => $weekRange,
    "status" => empty($facultySummaries) ? "No attendance data available for any faculty member this week" : "Success"
];

echo json_encode($response, JSON_PRETTY_PRINT);
sqlsrv_close($conn);
?>