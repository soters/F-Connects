<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (isset($_GET['rfid_no'])) {
    $rfid = $_GET['rfid_no'];

    // Retrieve the image path
    $sql_select = "SELECT image_path FROM FaceData WHERE rfid_no = ?";
    $stmt_select = sqlsrv_query($conn, $sql_select, array($rfid));

    if ($stmt_select && $row = sqlsrv_fetch_array($stmt_select, SQLSRV_FETCH_ASSOC)) {
        $image_path = $row['image_path'];

        // Check if file exists and delete it
        if ($image_path && file_exists("../../" . $image_path)) {
            unlink("../../" . $image_path);
        }

        // Delete the faculty record
        $sql_delete = "DELETE FROM FaceData WHERE rfid_no = ?";
        $stmt_delete = sqlsrv_query($conn, $sql_delete, array($rfid));

        if ($stmt_delete) {
            $message = "Face data deleted successfully!";
            $type = "success";
        } else {
            $message = "Error deleting face data!";
            $type = "error";
        }
    } else {
        // No face data found
        $message = "No face data available for this RFID!";
        $type = "warning";
    }
} else {
    $message = "No RFID provided!";
    $type = "error";
}

// Redirect with message and RFID number
header("Location: ../pages/admin-upload-face.php?message=" . urlencode($message) . "&type=" . urlencode($type) . "&rfid_no=" . urlencode($rfid));
exit();
?>
