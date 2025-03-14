<?php
declare(strict_types=1);
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

$rfid = $_POST['rfid'] ?? '';
$today = date('Y-m-d');
$availableSlots = [];

if (!empty($rfid)) {
    // Fetch consultation schedule for today
    $sql = "SELECT start_time, end_time FROM Schedules 
            WHERE rfid_no = ? AND CAST(start_date AS DATE) = ? AND type = 'Consultation Time'";
    $stmt = sqlsrv_query($conn, $sql, [$rfid, $today]);

    $consultation = [];
    if ($stmt !== false) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if ($row['start_time'] && $row['end_time']) {
                $consultation[] = [
                    'start' => date_format($row['start_time'], 'H:i'),
                    'end' => date_format($row['end_time'], 'H:i')
                ];
            }
        }
    }

    // Fetch existing appointments for today
    $sql2 = "SELECT start_time, end_time FROM Appointments 
             WHERE prof_rfid_no = ? AND CAST(date_logged AS DATE) = ?";
    $stmt2 = sqlsrv_query($conn, $sql2, [$rfid, $today]);

    $appointments = [];
    if ($stmt2 !== false) {
        while ($row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC)) {
            if ($row['start_time'] && $row['end_time']) {
                $appointments[] = [
                    'start' => date_format($row['start_time'], 'H:i'),
                    'end' => date_format($row['end_time'], 'H:i')
                ];
            }
        }
    }

    // Generate 1-hour intervals from consultation times
    foreach ($consultation as $con) {
        $start = DateTime::createFromFormat('H:i', $con['start']);
        $end = DateTime::createFromFormat('H:i', $con['end']);

        while ($start < $end) {
            $slotStart = clone $start;
            $slotEnd = clone $start;
            $slotEnd->modify('+1 hour');

            if ($slotEnd > $end) break; // Stop if the 1-hour slot exceeds consultation end

            $formattedStart = $slotStart->format('H:i');
            $formattedEnd = $slotEnd->format('H:i');

            // Check for overlapping appointments
            $isOverlapping = false;
            foreach ($appointments as $appt) {
                if (!($formattedEnd <= $appt['start'] || $formattedStart >= $appt['end'])) {
                    $isOverlapping = true;
                    break;
                }
            }

            if (!$isOverlapping) {
                $availableSlots[] = "$formattedStart - $formattedEnd";
            }

            $start->modify('+1 hour');
        }
    }
}

echo json_encode($availableSlots);
