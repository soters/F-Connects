<?php
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Sanitize input
        $prof_rfid_no = htmlspecialchars($_POST['prof_rfid_no']);
        $stud_rfid_no = htmlspecialchars($_POST['stud_rfid_no']);
        $start_time = htmlspecialchars($_POST['start_time']);
        $end_time = htmlspecialchars($_POST['end_time']);
        $agenda = htmlspecialchars($_POST['agenda']);
        $today_date = date('Y-m-d');

        // 1. Ensure appointment is not in the past
        $start_timestamp = strtotime($today_date . ' ' . $start_time);
        if ($start_timestamp < time()) {
            $message = "The appointment start time has already passed. Please select a future time.";
            $type = "error";
            header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
            exit;
        }

        // 2. Limit: Max 2 appointments per student per day
        $count_sql = "SELECT COUNT(*) AS appointment_count FROM Appointments 
                      WHERE stud_rfid_no = ? AND CONVERT(date, date_logged) = ? 
                      AND status NOT IN ('Cancelled', 'Declined')";
        $count_stmt = sqlsrv_query($conn, $count_sql, [$stud_rfid_no, $today_date]);

        if (!$count_stmt) {
            throw new Exception("Error checking appointment count.");
        }

        $count_result = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);
        if ($count_result && $count_result['appointment_count'] >= 2) {
            $message = "The selected student already has 2 appointments today.";
            $type = "error";
            header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
            exit;
        }

        // 3. Check for existing appointment with same professor today
        $check_sql = "SELECT appointment_code, status FROM Appointments 
                      WHERE prof_rfid_no = ? AND stud_rfid_no = ? 
                      AND CONVERT(date, date_logged) = ? 
                      AND status IN ('Pending', 'Accepted')";
        $check_stmt = sqlsrv_query($conn, $check_sql, [$prof_rfid_no, $stud_rfid_no, $today_date]);

        if (!$check_stmt) {
            throw new Exception("Error checking existing appointments.");
        }

        if ($row = sqlsrv_fetch_array($check_stmt, SQLSRV_FETCH_ASSOC)) {
            $message = "The selected student already have an appointment with this professor today (Status: " . $row['status'] . ").";
            $type = "error";
            header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
            exit;
        }

        // 4. Check overlapping appointments (student side)
        $overlap_sql = "SELECT 1 FROM Appointments 
                        WHERE stud_rfid_no = ? AND CONVERT(date, date_logged) = ? 
                        AND status NOT IN ('Cancelled', 'Declined') 
                        AND (? < end_time AND ? > start_time)";
        $overlap_stmt = sqlsrv_query($conn, $overlap_sql, [$stud_rfid_no, $today_date, $start_time, $end_time]);

        if (!$overlap_stmt) {
            throw new Exception("Error checking overlapping appointments.");
        }

        if (sqlsrv_has_rows($overlap_stmt)) {
            $message = "The student already has an overlapping appointment. Please try a different time.";
            $type = "error";
            header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
            exit;
        }

        // 5. Generate appointment code and insert into database
        $appointment_code = str_pad(mt_rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        $notif_time = date('Y-m-d H:i:s'); // Get current timestamp

        $insert_sql = "INSERT INTO Appointments 
               (appointment_code, prof_rfid_no, stud_rfid_no, start_time, end_time, agenda, status, date_logged, notif_time) 
               VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?, ?)";

        $params = [
            $appointment_code,
            $prof_rfid_no,
            $stud_rfid_no,
            $start_time,
            $end_time,
            $agenda,
            $today_date,
            $notif_time // Add notif_time here
        ];

        $insert_stmt = sqlsrv_query($conn, $insert_sql, $params);
        

        if ($insert_stmt) {
            $message = "Appointment successfully created!";
            $type = "success";
        } else {
            $message = "Failed to insert appointment.";
            $type = "error";
        }

        header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
        exit;

    } catch (Exception $e) {
        $message = "System error: " . $e->getMessage();
        $type = "error";
        header("Location: ../pages/admin-new-appointment.php?message=" . urlencode($message) . "&type=" . $type);
        exit;
    }
}
?>