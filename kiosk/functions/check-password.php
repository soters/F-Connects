<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');

// Retrieve and sanitize input
$rfid_no = filter_input(INPUT_POST, 'rfid_no', FILTER_SANITIZE_STRING);
$password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);

if (!$password || !$rfid_no) {
    setErrorAndRedirect('Missing RFID or password. Please try again.');
}

try {
    // Check FacultyAccount using RFID to get the email and then match the password
    $query = "SELECT FA.password 
              FROM FacultyAccount FA 
              INNER JOIN Faculty F ON FA.email = F.email
              WHERE F.rfid_no = ?";
    $stmt = sqlsrv_query($conn, $query, [$rfid_no]);

    if ($stmt === false) {
        throw new Exception(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

    if ($row && password_verify($password, $row['password'])) {
        validateFacultyAttendance($conn, $rfid_no);
        exit;
    }

    setErrorAndRedirect('Invalid password. Please try again.');
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    setErrorAndRedirect('An error occurred. Please contact the administrator.');
}

// =============================
// Faculty Validation Function
// =============================
function validateFacultyAttendance($conn, string $rfid_no): void 
{
    try {
        $today = date('Y-m-d');

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
        } else {
            header("Location: ../functions/insert-attendance.php?rfid_no=" . urlencode($rfid_no));
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
