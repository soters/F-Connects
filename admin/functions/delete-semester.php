<?php
include('../../connection/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester_id = filter_input(INPUT_POST, 'semester_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$semester_id) {
        $message = "Invalid semester ID!";
        $type = "error";
    } else {
        try {
            // Delete semester from the database
            $sql = "DELETE FROM Semester WHERE semester_id = ?";
            $stmt = sqlsrv_query($conn, $sql, [$semester_id]);

            if ($stmt) {
                $message = "Semester deleted successfully!";
                $type = "success";
            } else {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            $message = "Error deleting semester!";
            $type = "error";
        }
    }

    // Redirect with message in URL
    header("Location: ../pages/admin-repeating-schedule.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit();
}
?>
