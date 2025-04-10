<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');

// Retrieve and sanitize input
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

if (!$email || !$password) {
    setErrorAndRedirect('Invalid email or password. Please try again.');
}

try {
    // Check StudentAccount
    $query = "SELECT SA.password, S.rfid_no FROM StudentAccount SA INNER JOIN Students S ON SA.email = S.email WHERE SA.email = ?";
    $stmt = sqlsrv_prepare($conn, $query, array(&$email));

    if (sqlsrv_execute($stmt) && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (password_verify($password, $row['password'])) {
            setPageRedirect("../student/kiosk-student.php?rfid_no=" . urlencode($row['rfid_no']));
            exit;
        }
    }

    // Check FacultyAccount
    $query = "SELECT FA.password, F.rfid_no FROM FacultyAccount FA INNER JOIN Faculty F ON FA.email = F.email WHERE FA.email = ?";
    $stmt = sqlsrv_prepare($conn, $query, array(&$email));

    if (sqlsrv_execute($stmt) && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (password_verify($password, $row['password'])) {
            validateFacultyAttendanceAndSchedule($conn, $row['rfid_no']);
            exit;
        }
    }

    setErrorAndRedirect('Invalid email or password. Please try again.');
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    setErrorAndRedirect('An error occurred. Please contact the administrator.');
}

// =============================
// Faculty Validation Function
// =============================
function validateFacultyAttendanceAndSchedule($conn, string $rfid_no): void
{
    try {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');

        // Validate if faculty has schedule today and not past school hours
        $scheduleQuery = "
            SELECT COUNT(*) AS count 
            FROM Schedules 
            WHERE rfid_no = ? AND start_date = ?
        ";
        $scheduleStmt = sqlsrv_query($conn, $scheduleQuery, [$rfid_no, $today]);

        if ($scheduleStmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $scheduleResult = sqlsrv_fetch_array($scheduleStmt, SQLSRV_FETCH_ASSOC);

        if (!$scheduleResult || $scheduleResult['count'] == 0) {
            $_SESSION['error_message'] = "You do not have any scheduled classes for today.";
            sqlsrv_free_stmt($scheduleStmt);
            header("Location: ../kiosk-index.php");
            exit;
        }

        if ($currentTime > '20:30:00') {
            $_SESSION['error_message'] = "School hours already ended.";
            sqlsrv_free_stmt($scheduleStmt);
            header("Location: ../kiosk-index.php");
            exit;
        }

        sqlsrv_free_stmt($scheduleStmt);

        // Check if the user has already timed in and out for today
        $attendanceQuery = "
            SELECT attd_ref, time_in, time_out 
            FROM AttendanceToday 
            WHERE rfid_no = ? AND CONVERT(DATE, date_logged) = ?
        ";
        $attendanceStmt = sqlsrv_query($conn, $attendanceQuery, [$rfid_no, $today]);

        if ($attendanceStmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $attendanceResult = sqlsrv_fetch_array($attendanceStmt, SQLSRV_FETCH_ASSOC);

        if ($attendanceResult) {
            if ($attendanceResult['time_in'] !== null && $attendanceResult['time_out'] !== null) {
                // Already timed in and out
                header("Location: ../faculty/kiosk-continue-work.php?rfid_no=" . urlencode($rfid_no));
                exit;
            } elseif ($attendanceResult['time_in'] !== null && $attendanceResult['time_out'] === null) {
                // Has timed in but not timed out
                // Check if current time is before latest schedule end time
                $end_time_query = "
                    SELECT MAX(end_time) AS latest_end_time
                    FROM Schedules
                    WHERE rfid_no = ? AND start_date = ?
                ";
                $end_time_stmt = sqlsrv_query($conn, $end_time_query, [$rfid_no, $today]);

                if ($end_time_stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }

                $end_time_data = sqlsrv_fetch_array($end_time_stmt, SQLSRV_FETCH_ASSOC);
                $latest_end_time = $end_time_data['latest_end_time'] ? $end_time_data['latest_end_time']->format('H:i:s') : '23:59:59';

                if (strtotime($currentTime) < strtotime($latest_end_time)) {
                    header("Location: ../faculty/kiosk-confirm-time-out.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attendanceResult['attd_ref']));
                    exit;
                }

                // Update time out
                $update_query = "
                    UPDATE AttendanceToday
                    SET time_out = ?
                    WHERE attd_ref = ?
                ";
                $update_stmt = sqlsrv_query($conn, $update_query, [$currentTime, $attendanceResult['attd_ref']]);

                if ($update_stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }

                // Update appointments
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
                $update_appointments_stmt = sqlsrv_query($conn, $update_appointments_query, [$rfid_no, $today]);

                if ($update_appointments_stmt === false) {
                    throw new Exception(print_r(sqlsrv_errors(), true));
                }

                sqlsrv_free_stmt($update_stmt);
                sqlsrv_free_stmt($update_appointments_stmt);
                header("Location: ../faculty/kiosk-time-out-info.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attendanceResult['attd_ref']));
                exit;
            }
        }

        // If no prior attendance record, process time-in logic
        $query = "
            SELECT TOP 1 sched_id, start_time, start_date
            FROM Schedules
            WHERE rfid_no = ? AND start_date = ?
            ORDER BY start_time ASC
        ";
        $stmt = sqlsrv_query($conn, $query, [$rfid_no, $today]);

        if ($stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $schedule = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // Use scheduled start time if available; otherwise use current time as fallback
        $start_time = isset($schedule['start_time'])
            ? $schedule['start_time']->format('H:i:s')
            : $currentTime;

        // Add a 15-minute grace period
        $grace_time = date('H:i:s', strtotime($start_time . ' +15 minutes'));

        // Determine status
        $status = (strtotime($currentTime) > strtotime($grace_time)) ? 'Late' : 'Present';

        // Insert attendance
        $attd_ref = uniqid('ATTD_');
        $insert_query = "
            INSERT INTO AttendanceToday (attd_ref, rfid_no, time_in, status, date_logged)
            VALUES (?, ?, ?, ?, ?)
        ";
        $insert_stmt = sqlsrv_query($conn, $insert_query, [$attd_ref, $rfid_no, $currentTime, $status, $today]);

        if ($insert_stmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        sqlsrv_free_stmt($stmt);
        sqlsrv_free_stmt($insert_stmt);

        // Redirect to time-in info page after successful insertion
        header("Location: ../faculty/kiosk-time-in-info.php?rfid_no=" . urlencode($rfid_no) . "&attd_ref=" . urlencode($attd_ref));
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        header("Location: ../kiosk-index.php");
        exit;
    }
}

// =============================
// Error Handling & Redirect Functions
// =============================
function setErrorAndRedirect(string $message): void
{
    $_SESSION['error_message'] = $message;
    setPageRedirect('../kiosk-index.php');
    exit;
}

function setPageRedirect(string $location): void
{
    echo '<html>
    <head>
        <style>
            body {
                background-color: #2B5876;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                overflow: hidden;
            }
            .loading-spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body onload="redirectAfterLoading()">
        <div class="loading-spinner"></div>
        <script>
            function redirectAfterLoading() {
                setTimeout(function() {
                    window.location.href = "' . $location . '";
                }, 700);
            }
        </script>
    </body>
    </html>';
}
