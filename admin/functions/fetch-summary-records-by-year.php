<?php
error_reporting(E_ALL);
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Input validation
if (!isset($_GET['year']) || !is_numeric($_GET['year'])) {
    die(json_encode(["error" => "Invalid year parameter."]));
}

$year = $_GET['year'];
$currentDate = new DateTime();
$currentYear = (int)$currentDate->format('Y');
$currentMonth = (int)$currentDate->format('n');
$months = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

// 1. Fetch all faculty members
$facultyQuery = "SELECT rfid_no, fname, lname, email, employment_type FROM Faculty";
$facultyStmt = sqlsrv_query($conn, $facultyQuery);

if ($facultyStmt === false) {
    die(json_encode(["error" => "Failed to fetch faculty members.", "details" => sqlsrv_errors()]));
}

$facultySummaries = [];
$monthlyBreakdown = [];

while ($faculty = sqlsrv_fetch_array($facultyStmt, SQLSRV_FETCH_ASSOC)) {
    $rfid_no = $faculty['rfid_no'];
    
    // Initialize yearly data
    $yearlyStatus = ["Present" => 0, "Late" => 0, "Absent" => 0];
    $yearlyScheduledDays = 0;
    $yearlyAttendancePercentage = 0;
    $monthCount = 0;
    $hasData = false; // Flag to track if faculty has any data

    // Process each month
    foreach ($months as $monthIndex => $monthName) {
        $monthNum = $monthIndex + 1;
        
        // Skip future months (months in the future of current year)
        if ($year == $currentYear && $monthNum > $currentMonth) {
            continue;
        }
        
        $monthStatus = ["Present" => 0, "Late" => 0, "Absent" => 0];
        $monthScheduledDays = 0;
        $monthFutureDays = 0;
        
        // Get scheduled dates for month
        $datesQuery = "
            SELECT DISTINCT CAST(start_date AS DATE) as schedule_date
            FROM Schedules
            WHERE 
                rfid_no = ?
                AND YEAR(start_date) = ?
                AND MONTH(start_date) = ?
                AND type IN ('Lecture', 'Laboratory', 'Consultation Time')
        ";
        
        $datesStmt = sqlsrv_query($conn, $datesQuery, [$rfid_no, $year, $monthNum]);
        $absentDates = [];
        
        if ($datesStmt !== false) {
            while ($dateRow = sqlsrv_fetch_array($datesStmt, SQLSRV_FETCH_ASSOC)) {
                $dateStr = $dateRow['schedule_date']->format('Y-m-d');
                $scheduleDate = new DateTime($dateStr);
                
                if ($scheduleDate <= $currentDate) {
                    $absentDates[$dateStr] = true;
                    $monthScheduledDays++;
                    $hasData = true; // Mark as having data
                } else {
                    $monthFutureDays++;
                }
            }
            sqlsrv_free_stmt($datesStmt);
        }
        
        $yearlyScheduledDays += $monthScheduledDays;
        
        // Get attendance for month (only for past dates)
        $attdQuery = "
            SELECT DISTINCT CAST(date_logged AS DATE) as attendance_date, status
            FROM AttendanceToday
            WHERE 
                rfid_no = ?
                AND YEAR(date_logged) = ?
                AND MONTH(date_logged) = ?
                AND date_logged <= ?
        ";
        
        $attdStmt = sqlsrv_query($conn, $attdQuery, [
            $rfid_no, 
            $year, 
            $monthNum,
            $currentDate->format('Y-m-d')
        ]);
        
        if ($attdStmt !== false) {
            while ($attdRow = sqlsrv_fetch_array($attdStmt, SQLSRV_FETCH_ASSOC)) {
                $dateStr = $attdRow['attendance_date']->format('Y-m-d');
                if (isset($absentDates[$dateStr])) {
                    unset($absentDates[$dateStr]);
                    if (isset($monthStatus[$attdRow['status']])) {
                        $monthStatus[$attdRow['status']]++;
                        $hasData = true; // Mark as having data
                    }
                }
            }
            sqlsrv_free_stmt($attdStmt);
        }
        
        // Count only past dates as absent
        $monthStatus['Absent'] = count($absentDates);
        
        // Add to yearly totals
        foreach ($monthStatus as $key => $value) {
            $yearlyStatus[$key] += $value;
        }
        
        // Calculate monthly percentage (only for past dates)
        $monthPresent = $monthStatus['Present'] + $monthStatus['Late'];
        $monthPercentage = $monthScheduledDays > 0 
            ? round(($monthPresent / $monthScheduledDays) * 100, 2)
            : 0;
        
        // Only include month if it has data or is in the past
        if ($monthScheduledDays > 0 || ($year == $currentYear && $monthNum < $currentMonth)) {
            $monthlyBreakdown[] = [
                'rfid_no' => $rfid_no,
                'month' => $monthName,
                'present' => $monthStatus['Present'],
                'late' => $monthStatus['Late'],
                'absent' => $monthStatus['Absent'],
                'future_days' => $monthFutureDays,
                'total_days' => $monthScheduledDays + $monthFutureDays,
                'evaluated_days' => $monthScheduledDays,
                'attendance_percentage' => $monthPercentage
            ];
            
            if ($monthScheduledDays > 0) {
                $monthCount++;
            }
        }
    }
    
    // Only include faculty if they have any data
    if ($hasData) {
        // Calculate yearly percentage (only evaluated days)
        $yearlyPresent = $yearlyStatus['Present'] + $yearlyStatus['Late'];
        $yearlyPercentage = $yearlyScheduledDays > 0
            ? round(($yearlyPresent / $yearlyScheduledDays) * 100, 2)
            : 0;
        
        // Store faculty summary
        $facultySummaries[] = [
            "rfid_no" => $rfid_no,
            "fname" => $faculty['fname'],
            "lname" => $faculty['lname'],
            "email" => $faculty['email'],
            "employment_type" => $faculty['employment_type'],
            "statusCounts" => $yearlyStatus,
            "totalScheduledDays" => $yearlyScheduledDays,
            "attendancePercentage" => $yearlyPercentage,
            "futureDays" => array_sum(array_column(
                array_filter($monthlyBreakdown, function($item) use ($rfid_no) {
                    return $item['rfid_no'] === $rfid_no;
                }), 
                'future_days'))
        ];
    }
}

sqlsrv_free_stmt($facultyStmt);

// Filter monthly breakdown to only include faculty that have data
$validRfidNos = array_column($facultySummaries, 'rfid_no');
$monthlyBreakdown = array_filter($monthlyBreakdown, function($item) use ($validRfidNos) {
    return in_array($item['rfid_no'], $validRfidNos);
});

$response = [
    "facultySummaries" => $facultySummaries,
    "monthlyBreakdown" => array_values($monthlyBreakdown), // Reindex array
    "currentDate" => $currentDate->format('Y-m-d'),
    "status" => empty($facultySummaries) ? "No data available for any faculty" : "Data retrieved"
];

echo json_encode($response, JSON_PRETTY_PRINT);
sqlsrv_close($conn);
?>