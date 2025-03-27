<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

$rfid_no = filter_input(INPUT_GET, 'rfid_no', FILTER_DEFAULT);
$attd_ref = filter_input(INPUT_GET, 'attd_ref', FILTER_DEFAULT);

// Debugging: Check if the values are received
if (!$rfid_no) {
    die("Error: RFID number is missing or invalid.");
}
if (!$attd_ref) {
    die("Error: Attendance reference is missing or invalid.");
}

// SQL Query
$query = "
    SELECT 
        f.rfid_no, 
        f.fname, 
        f.mname, 
        f.lname, 
        f.suffix, 
        f.picture_path,
        atd.date_logged AS attendance_date, 
        atd.time_in, 
        atd.status
    FROM Faculty f
    LEFT JOIN AttendanceToday atd 
    ON f.rfid_no = atd.rfid_no
    WHERE f.rfid_no = ? AND atd.date_logged = CONVERT(DATE, GETDATE())
";

// Prepare and execute the query
$params = [$rfid_no];
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true)); // Show actual SQL error
}

// Store fetched data
$data = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    // Ensure attendance_date is properly formatted
    if ($row['attendance_date'] instanceof DateTime) {
        $row['attendance_date'] = $row['attendance_date']->format('Y-m-d');
    }
    if ($row['time_in'] instanceof DateTime) {
        $row['time_in'] = $row['time_in']->format('H:i:s');
    }
    $data[] = $row;
}

// Free resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Check if data exists
if (!empty($data)) {

} else {
    die("No attendance data found for today. Debug RFID: " . htmlspecialchars($rfid_no));
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

    <?php
    if (!empty($_SESSION['error_message'])) {
        echo '<div id="error-message" class="error-message alert alert-danger" role="alert">';
        echo '<i class="bi bi-exclamation-triangle"></i> ';
        echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
        echo '</div>';
        echo '<br>';
        unset($_SESSION['error_message']); // Clear the error message after displaying it
    }
    ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>
    </nav>

    <div class="t-container">
        <div class="t-card">
            <h2 class="t-main-message">Confirm Time In</h2>
            <p class="t-sub-message">Time in and start timer</p>
            <div class="t-info-box">
                <img src="<?php echo htmlspecialchars($data[0]['picture_path'] ?? 'default.png'); ?>" alt="Profile Img"
                    class="t-avatar">
                <div class="details">
                    <h3 class="t-name">
                        <?php
                        echo htmlspecialchars(
                            ($data[0]['fname'] ?? '') . ' ' .
                            ($data[0]['mname'] ? $data[0]['mname'] . ' ' : '') . // Middle name handling
                            ($data[0]['lname'] ?? '') . ' ' .
                            ($data[0]['suffix'] ?? '')
                        );
                        ?>
                    </h3>
                    <div class="t-time-info">
                        <div class="t-date">
                            <span class="t-icon">&#x1F4C5;</span>
                            <span>
                                <?php
                                echo isset($data[0]['attendance_date'])
                                    ? date('l, d F Y', strtotime($data[0]['attendance_date']))
                                    : 'N/A';
                                ?>
                            </span>
                        </div>
                        <div class="t-time">
                            <span>
                                <?php
                                $timeIn = $data[0]['time_in'] ?? null;
                                echo $timeIn ? date("h:i A", strtotime($timeIn)) : 'N/A';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <a href="kiosk-success-record.php?rfid_no=<?= urlencode($rfid_no) ?>" class="no-underline">
                <button class="t-confirm-button">CONFIRM</button>
            </a>
        </div>
    </div>


    <!--<div id="top-right-button">
        <button type="button" class="small-button" data-bs-toggle="tooltip" title="Need help?" data-bs-placement="left">
            <i class="bi bi-question-lg"></i>
    </div>-->


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

</body>

</html>