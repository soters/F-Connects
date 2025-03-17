<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Get the selected faculty's RFID and student RFID
$selected_rfid = filter_input(INPUT_POST, 'selected_rfid', FILTER_SANITIZE_STRING);
$stud_rf = filter_input(INPUT_POST, 'stud_rf', FILTER_SANITIZE_STRING);

$today = date('Y-m-d');
$currentTimestamp = time(); // Current server time in seconds

// Function to convert 24-hour time to 12-hour format
function convertTo12HourFormat($time)
{
    return date("g:i a", strtotime($time));
}

try {
    // Fetch faculty name
    $query_faculty_name = "SELECT fname, mname, lname, suffix FROM Faculty WHERE rfid_no = ?";
    $stmt_name = sqlsrv_query($conn, $query_faculty_name, array($selected_rfid));
    if ($stmt_name === false)
        die(print_r(sqlsrv_errors(), true));

    $faculty_name = '';
    if ($row = sqlsrv_fetch_array($stmt_name, SQLSRV_FETCH_ASSOC)) {
        $faculty_name = $row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname'] . ' ' . $row['suffix'];
    }

    // Fetch consultation schedule for today
    $query_schedule = "
        SELECT start_time, end_time
        FROM Schedules
        WHERE rfid_no = ? AND start_date = ? AND type = 'Consultation Time'
    ";
    $stmt_schedule = sqlsrv_query($conn, $query_schedule, array($selected_rfid, $today));
    if ($stmt_schedule === false)
        die(print_r(sqlsrv_errors(), true));

    $consultationTimes = [];
    while ($row = sqlsrv_fetch_array($stmt_schedule, SQLSRV_FETCH_ASSOC)) {
        $consultationTimes[] = [
            'start' => $row['start_time']->format('H:i'),
            'end' => $row['end_time']->format('H:i')
        ];
    }

    // Fetch all appointments for today
    $query_appointments = "
        SELECT start_time, end_time, status
        FROM Appointments
        WHERE prof_rfid_no = ? AND date_logged = ?
    ";
    $stmt_appointments = sqlsrv_query($conn, $query_appointments, array($selected_rfid, $today));
    if ($stmt_appointments === false)
        die(print_r(sqlsrv_errors(), true));

    $appointments = [];
    while ($row = sqlsrv_fetch_array($stmt_appointments, SQLSRV_FETCH_ASSOC)) {
        $appointments[] = [
            'start' => $row['start_time']->format('H:i'),
            'end' => $row['end_time']->format('H:i'),
            'status' => $row['status']
        ];
    }

    // Generate available time slots
    $availableTimes = [];
    // Generate available time slots
    $availableTimes = [];
    foreach ($consultationTimes as $consultation) {
        $start = strtotime($consultation['start']);
        $end = strtotime($consultation['end']);

        for ($currentSlot = $start; $currentSlot < $end; $currentSlot = strtotime("+1 hour", $currentSlot)) {
            $slotStart = date("H:i", $currentSlot);
            $slotEnd = date("H:i", strtotime("+1 hour", $currentSlot));

            $isAvailable = true;
            $isDisabled = false;

            $slotStartTime = strtotime($slotStart);
            $slotEndTime = strtotime($slotEnd);

            // HIDE the slot if end time is within 10 mins from now
            if (($slotEndTime - $currentTimestamp) <= 600) {
                continue; // skip adding this slot entirely
            }

            // Otherwise check for overlaps with appointments
            foreach ($appointments as $appointment) {
                $apptStart = strtotime($appointment['start']);
                $apptEnd = strtotime($appointment['end']);

                if (in_array($appointment['status'], ['Pending', 'Accepted', 'Started'])) {
                    if (
                        ($slotStartTime >= $apptStart && $slotStartTime < $apptEnd) ||
                        ($slotEndTime > $apptStart && $slotEndTime <= $apptEnd)
                    ) {
                        $isAvailable = false;
                        $isDisabled = true;
                        break;
                    }
                }

                // If status is Completed but within allowed time
                if ($appointment['status'] === 'Completed') {
                    $remainingTime = $apptEnd - $currentTimestamp;
                    if (
                        ($slotStartTime >= $apptStart && $slotStartTime < $apptEnd) &&
                        $remainingTime <= 900
                    ) {
                        $isAvailable = true;
                        $isDisabled = false;
                        break;
                    }
                }
            }

            // Still show the slot if available (but maybe disabled)
            if ($isAvailable || $isDisabled) {
                $availableTimes[] = [
                    'formatted' => convertTo12HourFormat($slotStart) . ' - ' . convertTo12HourFormat($slotEnd),
                    'start_time' => $slotStart,
                    'end_time' => $slotEnd,
                    'disabled' => $isDisabled
                ];
            }
        }
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin" />
    <!-- Custom Links -->
    <link rel="stylesheet" href="../../assets/css/kiosk-design.css" />
    <link rel="shortcut icon" href="../../assets/images/F-Connect.ico" type="image/x-icon" />
    <title>F - Connect</title>
</head>

<body class="fade-out">

    <!-- Display Error Message -->
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="error-message alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>
    </nav>

    <div id="appointment-container">
        <p id="action-message-info">Schedule Your Appointment</p>
        <i>
            <p id="action-message-info-small">Booking an Appointment with
                <strong><?= htmlspecialchars($faculty_name); ?></strong>
            </p>
        </i>
        <br>

        <form action="kiosk-choose-agenda.php" method="POST">
            <div class="time-cards">
                <?php if (!empty($availableTimes)): ?>
                    <?php foreach ($availableTimes as $timeSlot): ?>
                        <label>
                            <input type="radio" name="selected_time" value="<?= htmlspecialchars($timeSlot['start_time']); ?>"
                                <?= $timeSlot['disabled'] ? 'disabled' : '' ?> required
                                data-start-time="<?= htmlspecialchars($timeSlot['start_time']); ?>"
                                data-end-time="<?= htmlspecialchars($timeSlot['end_time']); ?>"
                                data-formatted="<?= htmlspecialchars($timeSlot['formatted']); ?>">
                            <div class="time-card <?= $timeSlot['disabled'] ? 'disabled-slot' : '' ?>">
                                <div class="info">
                                    <p class="time-name"><?= htmlspecialchars($timeSlot['formatted']); ?></p>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>

                    <!-- Submit Button -->
                    <button class="appoint-btn" type="submit">
                        <span class="btn-text">NEXT</span>
                    </button>

                    <!-- Hidden inputs -->
                    <input type="hidden" name="selected_rfid" value="<?= htmlspecialchars($selected_rfid); ?>">
                    <input type="hidden" name="stud_rf" value="<?= htmlspecialchars($stud_rf); ?>">
                    <input type="hidden" name="start_time" id="start_time">
                    <input type="hidden" name="end_time" id="end_time">
                <?php else: ?>
                    <div class="code-card">
                        <img src="../../assets/images/time_slot.png" alt="" class="faculty-icon-2">
                        <p class="code-message">
                            No consultation slots available for today.
                        </p>
                        <a href="kiosk-student.php?rfid_no=<?= isset($stud_rf) ? urlencode($stud_rf) : '' ?>"
                            class="no-underline">
                            <button class="appoint-btn" type="button">
                                <span class="btn-text">OKAY</span>
                            </button>
                        </a>
                    </div>
                    <script>
                        document.getElementById("action-message-info").style.display = "none";
                        document.getElementById("action-message-info-small").style.display = "none";
                    </script>
                <?php endif; ?>
            </div>
        </form>
    </div>


    <!--div id="top-right-button">
            <button type="button" class="small-button" data-bs-toggle="tooltip" title="Need help?"
                data-bs-placement="left">
                <i class="bi bi-question-lg"></i>
        </div>-->

    <div id="top-left-button">
        <a href="kiosk-personal-info.php?rfid_no=<?= isset($stud_rf) ? urlencode($stud_rf) : '' ?>"
            class="no-underline">
            <button type="button" class="small-button" data-bs-toggle="tooltip" title="Back" data-bs-placement="right">
                <i class="bi bi-arrow-left-short"></i>
            </button>
        </a>
    </div>

    <!--<footer>
        <p id="collaboration-text">In collaboration with Colegio de Sta. Teresa de Avila</p>
    </footer>-->

    <!-- Scripts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="../../assets/js/custom-javascript.js"></script>

    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
    <script>
        // Automatically hide the error message after 2 seconds
        setTimeout(() => {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.transition = 'opacity 0.5s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.remove(); // Remove the element completely after fade-out
                }, 500); // Delay to match the fade-out duration
            }
        }, 3000); // 2 seconds delay before hiding
    </script>

    <script>
        document.querySelectorAll('a.no-underline').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent immediate navigation
                const targetUrl = this.href; // Store the URL

                // Add the 'hidden' class to start the fade-out effect
                document.body.classList.add('hidden');

                // Wait for the transition to complete before navigating
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 500); // Match the CSS transition duration
            });
        });
    </script>

    <script>
        // Add an event listener to handle the selection of a radio button
        document.querySelectorAll('input[name="selected_time"]').forEach(function (radioButton) {
            radioButton.addEventListener('change', function () {
                // When a radio button is selected, update the hidden inputs with the corresponding times
                var startTime = this.getAttribute('data-start-time');
                var endTime = this.getAttribute('data-end-time');

                // Update the hidden fields with the selected times
                document.getElementById('start_time').value = startTime;
                document.getElementById('end_time').value = endTime;
            });
        });
    </script>
</body>

</html>