<?php
session_start();
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Function to redirect with message
function redirectWithMessage($message, $type, $redirectPage, $sched_id = null) {
    $url = $redirectPage . "?message=" . urlencode($message) . "&type=" . urlencode($type);
    if ($sched_id !== null) {
        $url .= "&sched_id=" . urlencode($sched_id);
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

        // Sanitize and validate inputs
        $sched_id = isset($_POST['sched_id']) ? (int)$_POST['sched_id'] : 0;
        if ($sched_id <= 0) {
            redirectWithMessage("Invalid schedule ID.", "error", "../pages/admin-update-time-info.php");
        }

        $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $timed_in = isset($_POST['timed_in']) ? $_POST['timed_in'] : null;
        $timed_out = isset($_POST['timed_out']) ? $_POST['timed_out'] : null;

        // Validate required fields
        if (empty($status)) {
            redirectWithMessage("Status is required.", "error", "../pages/admin-update-time-info.php", $sched_id);
        }

        // Convert time strings to SQL Server time format
        $timed_in_sql = (!empty($timed_in)) ? date('H:i:s', strtotime($timed_in)) : null;
        $timed_out_sql = (!empty($timed_out)) ? date('H:i:s', strtotime($timed_out)) : null;

        // Update query
        $updateSql = "UPDATE Schedules 
                     SET status = ?, timed_in = ?, timed_out = ?
                     WHERE sched_id = ?";
        
        $params = array(
            $status,
            $timed_in_sql,
            $timed_out_sql,
            $sched_id
        );

        $stmt = sqlsrv_query($conn, $updateSql, $params);

        if ($stmt) {
            redirectWithMessage("Schedule updated successfully!", "success", "../pages/admin-update-time-info.php", $sched_id);
        } else {
            $errors = sqlsrv_errors();
            error_log("SQL Server Error: " . print_r($errors, true));
            redirectWithMessage("Failed to update schedule. Please try again.", "error", "../pages/admin-update-time-info.php", $sched_id);
        }

    } catch (Exception $e) {
        error_log("Exception: " . $e->getMessage());
        redirectWithMessage("An error occurred: " . $e->getMessage(), "error", "../pages/admin-update-time-info.php", $sched_id);
    } finally {
        if (isset($conn)) {
            sqlsrv_close($conn);
        }
    }
} else {
    // Not a POST request
    redirectWithMessage("Invalid request method.", "error", "../pages/admin-update-time-info.php");
}
?>