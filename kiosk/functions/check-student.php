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
        setPageRedirect("../student/kiosk-student.php?rfid_no=" . urlencode($rfid_id));
        exit;
    }

    // Check if RFID exists in the faculty table
    $query = "SELECT TOP 1 1 FROM Faculty WHERE rfid_no = ?";
    $stmt = sqlsrv_prepare($conn, $query, array(&$rfid_id));

    if (sqlsrv_execute($stmt) && sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        setInfoAndRedirect('Hello Professor! Kindly use the faculty verification to proceed.');
    }


    setErrorAndRedirect('RFID does not exist in either the student or faculty database.');
} catch (Exception $e) {
    error_log("Database Error: " . $e->getMessage());
    setErrorAndRedirect('An error occurred. Please contact the administrator.');
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