<?php

session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

$admin_rfid_no = filter_input(INPUT_GET, 'rfid_no', FILTER_SANITIZE_STRING);

$sql = "
        SELECT 
            rfid_no,
            fname AS prof_fname, 
            mname AS prof_mname, 
            lname AS prof_lname, 
            suffix AS prof_suffix,
            email AS prof_email,
            phone_no AS prof_phone,
            acc_type AS prof_role,
            archived,
            date_created
        FROM Faculty
        WHERE archived = 0  -- Exclude archived faculty
        ORDER BY date_created DESC
    ";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die("Database error: " . print_r(sqlsrv_errors(), true));
}

// Get selected month and year from user input (default: current month and year)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch attendance status counts filtered by user-selected month and year
$sqlAttendanceCount = "SELECT status, COUNT(*) as count 
                        FROM AttendanceRecords 
                        WHERE status IN ('Present', 'Absent', 'Late') 
                        AND MONTH(date_logged) = ? 
                        AND YEAR(date_logged) = ? 
                        GROUP BY status";

$params = [$selectedMonth, $selectedYear];
$stmtAttendance = sqlsrv_query($conn, $sqlAttendanceCount, $params);

$attendanceCounts = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
while ($row = sqlsrv_fetch_array($stmtAttendance, SQLSRV_FETCH_ASSOC)) {
    $attendanceCounts[ucwords(strtolower(trim($row['status'])))] = $row['count'];
}

// Convert data to JSON for JavaScript (if needed)
$attendanceData = json_encode($attendanceCounts);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../../assets/images/F-Connect.ico" type="image/x-icon" />
    <title>F - Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="../../assets/css/admin-design.css">

</head>

<body>

    <!-- Sidebar/Navbar -->
    <div id="nav-bar">
        <input id="nav-toggle" type="checkbox" />
        <div id="nav-header">
            <img id="nav-logo" src="../../assets/images/F-Connect_L3.png" alt="F-CONNECT Logo" />
            <label for="nav-toggle"><span id="nav-toggle-burger"></span></label>
            <hr />
        </div>

        <div id="nav-content">
            <!-- Dashboard -->
            <div class="nav-button">
                <a href="admin-index.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <!-- Attendance Records -->
            <div class="nav-button">
                <a href="admin-attendance-records.php">
                    <i class="fas fa-clipboard"></i>
                    <span>Attendance Records</span>
                </a>
            </div>

            <!-- Appointment -->
            <div class="nav-button">
                <a href="admin-appointment.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointment</span>
                </a>
            </div>

            <!-- Announcement -->
            <div class="nav-button">
                <a href="admin-announcement.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcement</span>
                </a>
            </div>

            <!-- Faculty -->
            <div class="nav-button">
                <a href="admin-faculty.php">
                    <i class="fas fa-user"></i>
                    <span>Faculty Members</span>
                </a>
            </div>

            <!-- Schedule -->
            <div class="nav-button">
                <a href="admin-schedule.php">
                    <i class="fas fa-calendar"></i>
                    <span>Schedule</span>
                </a>
            </div>

            <!-- Sections -->
            <div class="nav-button">
                <a href="admin-sections.php">
                    <i class="fas fa-users"></i>
                    <span>Sections</span>
                </a>
            </div>

            <!-- Student -->
            <div class="nav-button">
                <a href="admin-student.php">
                    <i class="fas fa-users"></i>
                    <span>Student</span>
                </a>
            </div>

            <!-- Subjects -->
            <div class="nav-button">
                <a href="admin-subjects.php">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
            </div>

            <?php if ($acc_type === 'Super Admin'): ?>
                <!-- Admin Panel -->
                <div class="nav-button">
                    <a href="../authentication/admin-admins.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Admin Panel</span>
                    </a>
                </div>
            <?php endif; ?>

            <!-- Logout -->
            <div class="nav-button">
                <a href="../authentication/admin-logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>

            <div id="nav-content-highlight"></div>
        </div>

    </div>

    <!-- Main Content -->
    <div id="main-content" class="main-content">
        <div id="header">
            <h1 class="title-text">Attendance</h1>

            <!-- Display Current Date and Time -->
            <div id="current-datetime" class="current-datetime">
                <p id="date-time"></p> <!-- Updated id here -->
            </div>

            <div id="nav-footer">
                <div id="nav-footer-heading">
                    <div id="nav-footer-avatar"><img src="<?php echo htmlspecialchars($picture_path); ?>" /></div>
                    <div id="nav-footer-titlebox">
                        <?php echo htmlspecialchars($admin_fname); ?>
                        <span id="nav-footer-subtitle"><?php echo htmlspecialchars($acc_type); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="action-widgets-4">
            <div class="widget-button-4">
            </div>
            <div class="widget-search-4">
                <form method="GET" action="">
                    <select name="month" id="month">
                        <?php
                        $months = [
                            "01" => "January",
                            "02" => "February",
                            "03" => "March",
                            "04" => "April",
                            "05" => "May",
                            "06" => "June",
                            "07" => "July",
                            "08" => "August",
                            "09" => "September",
                            "10" => "October",
                            "11" => "November",
                            "12" => "December"
                        ];
                        foreach ($months as $key => $month) {
                            $selected = ($selectedMonth == $key) ? "selected" : "";
                            echo "<option value='$key' $selected>$month</option>";
                        }
                        ?>
                    </select>
                    <select name="year" id="year">
                        <?php
                        $currentYear = date('Y');
                        for ($i = $currentYear; $i >= $currentYear - 5; $i--) { // Last 5 years
                            $selected = ($selectedYear == $i) ? "selected" : "";
                            echo "<option value='$i' $selected>$i</option>";
                        }
                        ?>
                    </select>

                    <button class="fabtn" type="submit">Filter</button>
                </form>
            </div>
        </div>
        <div id="messageBox" class="message-box"></div>
        <div class="apt-dashboard-widgets">

            <div class="widget apt-table-design">
                <h2 class="tbl-title">Faculty Members</h2>
                <table id="attendanceTable" class="display">
                    <thead class="tbl-header">
                        <tr>
                            <th>RFID No</th>
                            <th>Professor</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <?php
                            $rfidNo = htmlspecialchars($row['rfid_no']);
                            $professor = htmlspecialchars(
                                $row['prof_fname'] .
                                ($row['prof_mname'] ? " " . $row['prof_mname'][0] . "." : "") .
                                " " . $row['prof_lname'] .
                                ($row['prof_suffix'] ? " " . $row['prof_suffix'] : "")
                            );
                            $email = htmlspecialchars($row['prof_email']);
                            $role = htmlspecialchars($row['prof_role']);
                            ?>
                            <tr class="tbl-row clickable-row" data-rfid="<?= $rfidNo ?>">
                                <td><?= $rfidNo ?></td>
                                <td class="small-text"><?= $professor ?></td>
                                <td><?= $email ?></td>
                                <td><?= $role ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const rows = document.querySelectorAll(".clickable-row");

                    rows.forEach(row => {
                        row.addEventListener("click", function () {
                            const rfidNo = this.getAttribute("data-rfid");
                            window.location.href = `admin-attendance-records-more.php?rfid_no=${rfidNo}`;
                        });
                    });
                });
            </script>


            <div class="widget widget-chart">
                <div class="widget-header">
                    <span class="widget-ttl">Attendance Trends</span>
                </div>
                <div class="widget-body">
                    <canvas id="attendanceChart"></canvas>
                </div>
                <br>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()">↑</button>

</body>

<script>
    const navToggle = document.getElementById('nav-toggle');
    const mainContent = document.getElementById('main-content');

    // Add event listener to toggle the margin of main-content based on the sidebar state
    navToggle.addEventListener('change', function () {
        if (navToggle.checked) {
            // Sidebar is toggled open, adjust margin to the larger size
            mainContent.style.marginLeft = '100px';  // Adjust this to your default sidebar width
        } else {
            // Sidebar is toggled closed, adjust margin to the smaller size
            mainContent.style.marginLeft = '280px';  // Adjust this to your collapsed sidebar width
        }
    });
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.6.0/jspdf.plugin.autotable.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function () {
        $('#attendanceTable').DataTable({
            "paging": true,
            "searching": true,
            "ordering": true,
            "info": true
        });
    });
</script>

<script>
    const scrollToTopBtn = document.getElementById('scrollToTopBtn');

    // Show/hide the button based on scroll position
    window.onscroll = function () {
        if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
            scrollToTopBtn.style.display = 'block';
        } else {
            scrollToTopBtn.style.display = 'none';
        }
    };

    // Scroll smoothly to the top
    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    } 
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Attendance Bar Chart (Filters 'Present', 'Absent', and 'Late' counts)
    const attendanceCounts = <?= $attendanceData ?>;
    const attendanceLabels = Object.keys(attendanceCounts);
    const attendanceData = Object.values(attendanceCounts);
    const ctxAttendance = document.getElementById("attendanceChart").getContext("2d");

    // Check if all values are zero or empty (no attendance data for selected month)
    if (attendanceData.every(val => val === 0)) {
        ctxAttendance.clearRect(0, 0, ctxAttendance.canvas.width, ctxAttendance.canvas.height);
        ctxAttendance.font = "13px Poppins";
        ctxAttendance.fillStyle = "#666"; // Neutral color
        ctxAttendance.textAlign = "center";
        ctxAttendance.fillText(
            "No attendance available for this month",
            ctxAttendance.canvas.width / 2.39,
            ctxAttendance.canvas.height / 2
        );
    } else {
        new Chart(ctxAttendance, {
            type: "bar",
            data: {
                labels: attendanceLabels,
                datasets: [{
                    label: "Attendance Status Count",
                    data: attendanceData,
                    backgroundColor: ["#4caf50", "#f44336", "#ff9800"], // Green, Red, Orange
                    borderRadius: 5, // Rounded bar edges
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                aspectRatio: 2,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                family: "Poppins",
                                size: 12
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "Poppins",
                                size: 12
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                family: "Poppins",
                                size: 13
                            }
                        }
                    }
                }
            }
        });
    }
</script>


<script>
    // Trigger fade-out effect before navigating
    window.addEventListener('beforeunload', function () {
        document.body.classList.add('fade-out');
    });
</script>

<script>
    function updateDateTime() {
        // Get current date and time
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const date = now.toLocaleDateString('en-US', options);
        const time = now.toLocaleTimeString('en-US');

        // Display the date and time
        document.getElementById('date-time').textContent = date + ' | ' + time;
    }

    // Update the time every second
    setInterval(updateDateTime, 1000);

    // Initial call to display the current date and time
    updateDateTime();

</script>
<script>
    // Get message and type from URL
    const urlParams = new URLSearchParams(window.location.search);
    const message = urlParams.get("message");
    const type = urlParams.get("type");

    if (message) {
        let messageBox = document.getElementById("messageBox");

        // Set message text and styling
        messageBox.innerText = message;
        messageBox.classList.add(type === "success" ? "message-success" : "message-error");
        messageBox.style.display = "block";

        // Hide message after 2 seconds
        setTimeout(function () {
            messageBox.style.display = "none";
        }, 5000);

        // Remove message from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
</script>

</html>