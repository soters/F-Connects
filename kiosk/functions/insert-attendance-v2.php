<?php
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Get the RFID number from the request
$rfid_no = filter_input(INPUT_GET, 'rfid_no', FILTER_SANITIZE_STRING);

if (!$rfid_no) {
    die("RFID number is required.");
}

// Get the current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// ✅ NEW: Validate if faculty has schedule today and not past school hours
try {
    $scheduleQuery = "
        SELECT COUNT(*) AS count 
        FROM Schedules 
        WHERE rfid_no = ? AND start_date = ?
    ";
    $scheduleStmt = sqlsrv_query($conn, $scheduleQuery, [$rfid_no, $current_date]);

    if ($scheduleStmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    $scheduleResult = sqlsrv_fetch_array($scheduleStmt, SQLSRV_FETCH_ASSOC);

    if (!$scheduleResult || $scheduleResult['count'] == 0) {
        $_SESSION['error_message'] = "You do not have any scheduled classes for today.";
        sqlsrv_free_stmt($scheduleStmt);
        sqlsrv_close($conn);
        header("Location: ../kiosk-index.php");
        exit;
    }

    if ($current_time > '20:30:00') {
        $_SESSION['error_message'] = "School hours already ended.";
        sqlsrv_free_stmt($scheduleStmt);
        sqlsrv_close($conn);
        header("Location: ../kiosk-index.php");
        exit;
    }

    sqlsrv_free_stmt($scheduleStmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: ../kiosk-index.php");
    exit;
}

// Check if the user has already timed in and out for today
try {
    $attendanceQuery = "
        SELECT time_in, time_out 
        FROM AttendanceToday 
        WHERE rfid_no = ? AND CONVERT(DATE, date_logged) = ?
    ";
    $attendanceStmt = sqlsrv_query($conn, $attendanceQuery, [$rfid_no, $current_date]);

    if ($attendanceStmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    $attendanceResult = sqlsrv_fetch_array($attendanceStmt, SQLSRV_FETCH_ASSOC);

    if ($attendanceResult && $attendanceResult['time_in'] !== null && $attendanceResult['time_out'] !== null) {
        sqlsrv_free_stmt($attendanceStmt);
        sqlsrv_close($conn);
        header("Location: ../faculty/kiosk-continue-work.php?rfid_no=" . urlencode($rfid_no));
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
    header("Location: ../kiosk-index.php");
    exit;
}

// Check if the RFID already has a record in AttendanceToday
$check_query = "
    SELECT attd_ref, time_in, time_out
    FROM AttendanceToday
    WHERE rfid_no = ? AND date_logged = ?
";
$check_params = [$rfid_no, $current_date];
$check_stmt = sqlsrv_query($conn, $check_query, $check_params);

if ($check_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$attendance = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

if ($attendance && is_null($attendance['time_out'])) {
    // Faculty has already timed in but not timed out
    $end_time_query = "
        SELECT MAX(end_time) AS latest_end_time
        FROM Schedules
        WHERE rfid_no = ? AND start_date = ?
    ";
    $end_time_params = [$rfid_no, $current_date];
    $end_time_stmt = sqlsrv_query($conn, $end_time_query, $end_time_params);

    if ($end_time_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $end_time_data = sqlsrv_fetch_array($end_time_stmt, SQLSRV_FETCH_ASSOC);
    $latest_end_time = $end_time_data['latest_end_time'] ? $end_time_data['latest_end_time']->format('H:i:s') : '23:59:59';

    if (strtotime($current_time) < strtotime($latest_end_time)) {
        header("Location: ../faculty/kiosk-confirm-time-out.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attendance['attd_ref']));
        exit();
    }

    $update_query = "
        UPDATE AttendanceToday
        SET time_out = ?
        WHERE attd_ref = ?
    ";
    $update_params = [$current_time, $attendance['attd_ref']];
    $update_stmt = sqlsrv_query($conn, $update_query, $update_params);

    if ($update_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $update_appointments_query = "
        UPDATE Appointments
        SET 
            status = 'Declined',
            notif_time = GETDATE(),
            is_read = 0,
            is_read_student = 0
        WHERE 
            prof_rfid_no = ? 
            AND date_logged = ?
            AND status IN ('Pending', 'Accepted')
    ";
    $update_appointments_params = [$rfid_no, $current_date];
    $update_appointments_stmt = sqlsrv_query($conn, $update_appointments_query, $update_appointments_params);

    if ($update_appointments_stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_free_stmt($update_stmt);
    sqlsrv_free_stmt($update_appointments_stmt);
    sqlsrv_close($conn);

    header("Location: ../faculty/kiosk-time-out-info.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attendance['attd_ref']));
    exit();
}

// If no prior attendance record, process time-in logic
$query = "
    SELECT TOP 1 sched_id, start_time, start_date
    FROM Schedules
    WHERE rfid_no = ? AND start_date = ?
    ORDER BY start_time ASC
";
$params = [$rfid_no, $current_date];
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$schedule = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

// Use scheduled start time if available; otherwise use current time as fallback
$start_time = isset($schedule['start_time'])
    ? $schedule['start_time']->format('H:i:s')
    : $current_time;

// Add a 15-minute grace period
$grace_time = date('H:i:s', strtotime($start_time . ' +15 minutes'));

// Determine status
$status = (strtotime($current_time) > strtotime($grace_time)) ? 'Late' : 'Present';

// Insert attendance
$attd_ref = uniqid('ATTD_');
$insert_query = "
    INSERT INTO AttendanceToday (attd_ref, rfid_no, time_in, status, date_logged)
    VALUES (?, ?, ?, ?, ?)
";
$insert_params = [$attd_ref, $rfid_no, $current_time, $status, $current_date];
$insert_stmt = sqlsrv_query($conn, $insert_query, $insert_params);

if ($insert_stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_free_stmt($stmt);
sqlsrv_free_stmt($insert_stmt);
sqlsrv_close($conn);

header("Location: ../faculty/kiosk-time-in-info.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attd_ref));
exit();
?>
