<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');

// Retrieve the RFID ID from POST data and sanitize it
$rfid_id = filter_input(INPUT_POST, 'rfid_id', FILTER_SANITIZE_STRING);

if (!$rfid_id) {
    setErrorAndRedirect('Please try again, RFID does not exist.');
}

try {
    // Check if RFID exists in the student table
    $query = "SELECT TOP 1 1 FROM Students WHERE rfid_no = ?";
    $stmt = sqlsrv_prepare($conn, $query, array(&$rfid_id));
    
    if (sqlsrv_execute($stmt) && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        setInfoAndRedirect('Hello Student! Kindly use the student verification to proceed.');
    }

    // Check if RFID exists in the faculty table
    $query = "SELECT TOP 1 1 FROM Faculty WHERE rfid_no = ?";
    $stmt = sqlsrv_prepare($conn, $query, array(&$rfid_id));

    if (sqlsrv_execute($stmt) && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        validateFacultyAttendanceAndSchedule($conn, $rfid_id);
        exit;
    }

    setErrorAndRedirect('RFID does not exist in either the student or faculty database.');
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
            // If past 8:30 PM, deny access
            if ($currentTime > '20:30:00') {
                $_SESSION['error_message'] = "School hours already ended.";
                header("Location: ../kiosk-index.php");
                exit;
            }

            // Redirect directly to password page
            header("Location: ../faculty/kiosk-faculty-password.php?rfid_no=" . urlencode($rfid_no));
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

function setInfoAndRedirect(string $message): void
{
    $_SESSION['info_message'] = $message;
    setPageRedirect('../kiosk-index.php');
    exit;
}

function setPageRedirect(string $location): void
{
    echo '
    <html>
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
    </html>
    ';
}
?>
