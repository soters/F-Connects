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

        // Check if faculty already timed in and out today
        $attendanceQuery = "
            SELECT time_in, time_out 
            FROM AttendanceToday 
            WHERE rfid_no = ? AND CONVERT(DATE, date_logged) = ?
        ";
        $attendanceStmt = sqlsrv_query($conn, $attendanceQuery, [$rfid_no, $today]);

        if ($attendanceStmt === false) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $attendanceResult = sqlsrv_fetch_array($attendanceStmt, SQLSRV_FETCH_ASSOC);

        if ($attendanceResult && $attendanceResult['time_in'] !== null && $attendanceResult['time_out'] !== null) {
            header("Location: ../faculty/kiosk-continue-work.php?rfid_no=" . urlencode($rfid_no));
            exit;
        }

        // Check if faculty has a schedule for today
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

        if ($scheduleResult && $scheduleResult['count'] > 0) {
            if ($currentTime > '20:30:00') {
                $_SESSION['error_message'] = "School hours already ended.";
                header("Location: ../kiosk-index.php");
                exit;
            }

            // Check if RFID exists in FaceData table
            $faceDataQuery = "SELECT COUNT(*) AS count FROM FaceData WHERE rfid_no = ?";
            $faceDataStmt = sqlsrv_query($conn, $faceDataQuery, [$rfid_no]);

            if ($faceDataStmt === false) {
                throw new Exception(print_r(sqlsrv_errors(), true));
            }

            $faceDataResult = sqlsrv_fetch_array($faceDataStmt, SQLSRV_FETCH_ASSOC);

            if ($faceDataResult && $faceDataResult['count'] > 0) {
                header("Location: ../faculty/kiosk-detect-face.php?rfid_no=" . urlencode($rfid_no));
            } else {
                header("Location: ../webcam/kiosk-first-facial.php?rfid_no=" . urlencode($rfid_no));
            }
        } else {
            $_SESSION['error_message'] = "You do not have any scheduled classes for today.";
            header("Location: ../kiosk-index.php");
        }
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
