<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (isset($_GET['attd_ref'])) {
    $attdRef = $_GET['attd_ref'];

    // Delete the attendance record using only attd_ref
    $sql = "DELETE FROM AttendanceToday WHERE attd_ref = ?";
    $stmt = sqlsrv_query($conn, $sql, array($attdRef));

    if ($stmt) {
        $message = "Attendance record deleted successfully!";
        $type = "success";
    } else {
        $message = "Error deleting attendance record!";
        $type = "error";
    }
} else {
    $message = "Invalid attendance reference!";
    $type = "error";
}

// Redirect with message
header("Location: ../pages/admin-attendance-records.php?message=" . urlencode($message) . "&type=" . urlencode($type));
exit();
?>
