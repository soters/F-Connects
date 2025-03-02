<?php
include('../../connection/connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $semester_name = trim(filter_input(INPUT_POST, 'semester_name', FILTER_SANITIZE_STRING));
    $start_date = trim(filter_input(INPUT_POST, 'start_date', FILTER_SANITIZE_STRING));
    $end_date = trim(filter_input(INPUT_POST, 'end_date', FILTER_SANITIZE_STRING));

    // Validate required fields
    if (empty($semester_name) || empty($start_date) || empty($end_date)) {
        $message = "All fields are required!";
        $type = "error";
    } 
    // Validate date format
    elseif (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $start_date) || !preg_match("/^\d{4}-\d{2}-\d{2}$/", $end_date)) {
        $message = "Invalid date format!";
        $type = "error";
    } 
    // Ensure start_date is before end_date
    elseif (strtotime($start_date) >= strtotime($end_date)) {
        $message = "Start date must be before end date!";
        $type = "error";
    } 
    else {
        try {
            // Check if semester already exists
            $check_sql = "SELECT COUNT(*) AS count FROM Semester WHERE semester_name = ?";
            $check_stmt = sqlsrv_query($conn, $check_sql, [$semester_name]);
            $row = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC);

            if ($row['count'] > 0) {
                $message = "Semester name already exists!";
                $type = "error";
            } else {
                // Insert new semester
                $sql = "INSERT INTO Semester (semester_name, start_date, end_date) VALUES (?, ?, ?)";
                $stmt = sqlsrv_query($conn, $sql, [$semester_name, $start_date, $end_date]);

                if ($stmt) {
                    $message = "Semester added successfully!";
                    $type = "success";
                } else {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }
            }
        } catch (Exception $e) {
            $message = "Error adding semester!";
            $type = "error";
        }
    }

    // Redirect with message in URL
    header("Location: ../pages/admin-repeating-schedule.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit();
}
?>
