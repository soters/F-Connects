<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Function to redirect with a message, type, and appointment_code if provided
function redirectWithMessage($message, $type, $redirectPage, $appointment_code = null) {
    $url = $redirectPage . "?message=" . urlencode($message) . "&type=" . urlencode($type);
    if ($appointment_code !== null) {
        $url .= "&appointment_code=" . urlencode($appointment_code);
    }
    header("Location: " . $url);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Connect to the database using SQLSRV
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        // Retrieve and sanitize form data
        $appointment_code = filter_input(INPUT_POST, 'appointment_code', FILTER_SANITIZE_STRING);
        $agenda           = filter_input(INPUT_POST, 'agenda', FILTER_SANITIZE_STRING);
        $status           = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
        $start_time       = filter_input(INPUT_POST, 'start_time', FILTER_SANITIZE_STRING);
        $end_time         = filter_input(INPUT_POST, 'end_time', FILTER_SANITIZE_STRING);

        // Fetch the current appointment record to obtain its details
        $selectSql = "SELECT prof_rfid_no, date_logged 
                      FROM Appointments 
                      WHERE appointment_code = ?";
        $selectParams = array($appointment_code);
        $selectStmt = sqlsrv_query($conn, $selectSql, $selectParams);
        
        if ($selectStmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }
        
        $appointment = sqlsrv_fetch_array($selectStmt, SQLSRV_FETCH_ASSOC);
        
        if (!$appointment) {
            redirectWithMessage("Appointment not found.", "error", "../pages/admin-update-appointment.php", $appointment_code);
        }

        $prof_rfid_no = $appointment['prof_rfid_no'];
        $date_logged  = $appointment['date_logged'];

        // Ensure date_logged is formatted properly
        $date_logged_str = ($date_logged instanceof DateTime) ? $date_logged->format('Y-m-d') : $date_logged;

        // If the new status is "Accepted", perform the overlap check.
        if (strtolower($status) === 'accepted') {
            $checkSql = "SELECT appointment_code 
                         FROM Appointments 
                         WHERE prof_rfid_no = ? 
                           AND date_logged = ? 
                           AND status = 'Accepted'
                           AND (? < end_time AND ? > start_time)
                           AND appointment_code <> ?";
            $checkParams = array(
                $prof_rfid_no,
                $date_logged_str,
                $start_time,
                $end_time,
                $appointment_code
            );
            $checkStmt = sqlsrv_query($conn, $checkSql, $checkParams);
            
            if ($checkStmt === false) {
                die(print_r(sqlsrv_errors(), true));
            }
            
            if (sqlsrv_has_rows($checkStmt)) {
                redirectWithMessage("This professor already has an accepted appointment scheduled during that time.", "error", "../pages/admin-update-appointment.php", $appointment_code);
            }
        }

        // Proceed to update the appointment with the new agenda, status, start_time, and end_time.
        $updateSql = "UPDATE Appointments 
                      SET agenda = ?, status = ?, start_time = ?, end_time = ? 
                      WHERE appointment_code = ?";
        $updateParams = array($agenda, $status, $start_time, $end_time, $appointment_code);
        $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);
        
        if ($updateStmt) {
            redirectWithMessage("Appointment updated successfully!", "success", "../pages/admin-update-appointment.php", $appointment_code);
        } else {
            redirectWithMessage("Error updating appointment. Please try again.", "error", "../pages/admin-update-appointment.php", $appointment_code);
        }
    } catch (Exception $e) {
        redirectWithMessage("Database error: " . $e->getMessage(), "error", "../pages/admin-update-appointment.php", $appointment_code);
    }
    
    // Close the connection
    sqlsrv_close($conn);
}
?>
