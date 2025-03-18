<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Function to redirect with message and type
function redirectWithMessage($message, $type, $redirectPage, $attd_ref = null) {
    $url = $redirectPage . "?message=" . urlencode($message) . "&type=" . urlencode($type);
    if ($attd_ref !== null) {
        $url .= "&attd_ref=" . urlencode($attd_ref);
    }
    header("Location: " . $url);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Database connection
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        // Sanitize form inputs
        $attd_ref = filter_input(INPUT_POST, 'attd_ref', FILTER_SANITIZE_STRING);
        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $time_in_raw = $_POST['time_in'] ?? '';
        $time_out_raw = $_POST['time_out'] ?? '';
        
        $time_in = empty($time_in_raw) ? null : $time_in_raw;
        $time_out = empty($time_out_raw) ? null : $time_out_raw;
        
        // Fetch rfid_no using attd_ref only (removed date_logged filter)
        $selectSql = "SELECT rfid_no FROM AttendanceToday WHERE attd_ref = ?";
        $selectParams = array($attd_ref);
        $selectStmt = sqlsrv_query($conn, $selectSql, $selectParams);

        if ($selectStmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        $attendance = sqlsrv_fetch_array($selectStmt, SQLSRV_FETCH_ASSOC);
        if (!$attendance) {
            redirectWithMessage("Attendance record not found.", "error", "../pages/admin-update-attendance.php", $attd_ref);
        }

        $rfid_no = $attendance['rfid_no'];

        // Update AttendanceToday table (removed date_logged from WHERE clause)
        $updateSql = "UPDATE AttendanceToday
                      SET status = ?, time_in = ?, time_out = ?
                      WHERE attd_ref = ? AND rfid_no = ?";
        $updateParams = array($status, $time_in, $time_out, $attd_ref, $rfid_no);

        $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);

        if ($updateStmt) {
            redirectWithMessage("Attendance updated successfully!", "success", "../pages/admin-update-attendance.php", $attd_ref);
        } else {
            redirectWithMessage("Failed to update attendance. Please try again.", "error", "../pages/admin-update-attendance.php", $attd_ref);
        }

    } catch (Exception $e) {
        redirectWithMessage("Database error: " . $e->getMessage(), "error", "../pages/admin-update-attendance.php", $attd_ref);
    }

    sqlsrv_close($conn);
}
?>
