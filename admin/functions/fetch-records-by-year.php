<?php
error_reporting(E_ALL);
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Input validation
if (!isset($_GET['year'], $_GET['rfid_no']) || empty($_GET['year']) || empty($_GET['rfid_no'])) {
    die(json_encode(["error" => "Missing required parameters."]));
}

$year = $_GET['year'];
$rfid_no = $_GET['rfid_no'];

// 1. Get all unique scheduled dates for this faculty and year
$datesQuery = "
    SELECT DISTINCT CAST(start_date AS DATE) as schedule_date
    FROM Schedules
    WHERE 
        rfid_no = ?
        AND YEAR(start_date) = ?
        AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    ORDER BY CAST(start_date AS DATE)
";

$datesStmt = sqlsrv_query($conn, $datesQuery, [$rfid_no, $year]);
if ($datesStmt === false) {
    die(json_encode(["error" => "Failed to fetch scheduled dates", "details" => sqlsrv_errors()]));
}

$allScheduledDates = [];
while ($row = sqlsrv_fetch_array($datesStmt, SQLSRV_FETCH_ASSOC)) {
    $allScheduledDates[] = $row['schedule_date']->format('Y-m-d');
}
sqlsrv_free_stmt($datesStmt);

// 2. Get all attendance records for this faculty and year
$attdQuery = "
    SELECT 
        time_in, time_out, status, CAST(date_logged AS DATE) as attendance_date
    FROM AttendanceToday
    WHERE 
        rfid_no = ?
        AND YEAR(date_logged) = ?
    ORDER BY CAST(date_logged AS DATE)
";

$attdStmt = sqlsrv_query($conn, $attdQuery, [$rfid_no, $year]);
if ($attdStmt === false) {
    die(json_encode(["error" => "Failed to fetch attendance records", "details" => sqlsrv_errors()]));
}

$attendanceRecords = [];
$attendanceDates = [];
while ($row = sqlsrv_fetch_array($attdStmt, SQLSRV_FETCH_ASSOC)) {
    $date = $row['attendance_date']->format('Y-m-d');
    $attendanceRecords[$date] = [
        'status' => $row['status'],
        'time_in' => $row['time_in'] ? $row['time_in']->format('H:i:s') : null,
        'time_out' => $row['time_out'] ? $row['time_out']->format('H:i:s') : null
    ];
    $attendanceDates[] = $date;
}
sqlsrv_free_stmt($attdStmt);

// 3. Get scheduled hours per month and calculate attendance
$monthlySummary = array_fill(1, 12, [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'total_days' => 0,
    'scheduled_hours' => 0,
    'worked_hours' => 0,
    'attendance_percentage' => 0
]);

$yearlyTotals = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'total_days' => 0,
    'scheduled_hours' => 0,
    'worked_hours' => 0,
    'attendance_percentage' => 0
];

// Process each scheduled date
foreach ($allScheduledDates as $date) {
    $dateObj = new DateTime($date);
    $month = (int)$dateObj->format('n'); // 1-12
    
    // Check if faculty attended this date
    if (isset($attendanceRecords[$date])) {
        $status = $attendanceRecords[$date]['status'];
        
        if ($status === 'Present') {
            $monthlySummary[$month]['present']++;
            $yearlyTotals['present']++;
        } elseif ($status === 'Late') {
            $monthlySummary[$month]['late']++;
            $yearlyTotals['late']++;
        }
    } else {
        $monthlySummary[$month]['absent']++;
        $yearlyTotals['absent']++;
    }
    
    $monthlySummary[$month]['total_days']++;
    $yearlyTotals['total_days']++;
}

// 4. Get scheduled hours and worked hours per month
$hoursQuery = "
    SELECT 
        MONTH(start_date) as month,
        SUM(DATEDIFF(MINUTE, start_time, end_time)/60.0) as scheduled_hours,
        SUM(CASE 
            WHEN status = 'Present' OR status = 'Late' THEN 
                DATEDIFF(MINUTE, 
                    timed_in, 
                    CASE WHEN timed_out > end_time THEN end_time ELSE timed_out END
                )/60.0
            ELSE 0
        END) as worked_hours
    FROM Schedules
    WHERE 
        rfid_no = ?
        AND YEAR(start_date) = ?
        AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
    GROUP BY MONTH(start_date)
    ORDER BY MONTH(start_date)
";

$hoursStmt = sqlsrv_query($conn, $hoursQuery, [$rfid_no, $year]);
if ($hoursStmt === false) {
    die(json_encode(["error" => "Failed to fetch hours data", "details" => sqlsrv_errors()]));
}

while ($row = sqlsrv_fetch_array($hoursStmt, SQLSRV_FETCH_ASSOC)) {
    $month = (int)$row['month'];
    $monthlySummary[$month]['scheduled_hours'] = (float)$row['scheduled_hours'];
    $monthlySummary[$month]['worked_hours'] = (float)$row['worked_hours'];
    
    $yearlyTotals['scheduled_hours'] += (float)$row['scheduled_hours'];
    $yearlyTotals['worked_hours'] += (float)$row['worked_hours'];
}
sqlsrv_free_stmt($hoursStmt);

// Calculate attendance percentages
foreach ($monthlySummary as $month => &$data) {
    if ($data['total_days'] > 0) {
        $data['attendance_percentage'] = 
            (($data['present'] + $data['late']) / $data['total_days']) * 100;
    }
}

if ($yearlyTotals['total_days'] > 0) {
    $yearlyTotals['attendance_percentage'] = 
        (($yearlyTotals['present'] + $yearlyTotals['late']) / $yearlyTotals['total_days']) * 100;
}

// 5. Get faculty info
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

// Prepare the response
$response = [
    "facultyInfo" => $facultyInfo,
    "monthlySummary" => array_values($monthlySummary), // Convert to 0-based array
    "yearlyTotals" => [
        'present' => $yearlyTotals['present'],
        'absent' => $yearlyTotals['absent'],
        'late' => $yearlyTotals['late'],
        'total_days' => $yearlyTotals['total_days'],
        'scheduled_hours' => number_format($yearlyTotals['scheduled_hours'], 2),
        'worked_hours' => number_format($yearlyTotals['worked_hours'], 2),
        'attendance_percentage' => number_format($yearlyTotals['attendance_percentage'], 2)
    ],
    "statusCounts" => [
        'Present' => $yearlyTotals['present'],
        'Absent' => $yearlyTotals['absent'],
        'Late' => $yearlyTotals['late']
    ],
    "grandTotalHours" => number_format($yearlyTotals['worked_hours'], 2)
];

header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

sqlsrv_close($conn);
?>