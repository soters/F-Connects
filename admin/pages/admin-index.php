<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Queries to count rows in tables
$sqlFaculty = "SELECT COUNT(*) as count FROM Faculty";
$sqlSubjects = "SELECT COUNT(*) as count FROM Subjects";
$sqlSections = "SELECT COUNT(*) as count FROM Sections";
$sqlStudents = "SELECT COUNT(*) as count FROM Students"; // Ensure this table name is correct

try {
    // Execute the query for faculty count
    $stmtFaculty = sqlsrv_query($conn, $sqlFaculty);
    $facultyCount = ($stmtFaculty && $row = sqlsrv_fetch_array($stmtFaculty, SQLSRV_FETCH_ASSOC)) ? $row['count'] : 0;

    // Execute the query for subjects count
    $stmtSubjects = sqlsrv_query($conn, $sqlSubjects);
    $subjectsCount = ($stmtSubjects && $row = sqlsrv_fetch_array($stmtSubjects, SQLSRV_FETCH_ASSOC)) ? $row['count'] : 0;

    // Execute the query for students count
    $stmtStudents = sqlsrv_query($conn, $sqlStudents);
    $studentsCount = ($stmtStudents && $row = sqlsrv_fetch_array($stmtStudents, SQLSRV_FETCH_ASSOC)) ? $row['count'] : 0;

    // Execute the query for section count
    $stmtSections = sqlsrv_query($conn, $sqlSections);
    $sectionsCount = ($stmtSections && $row = sqlsrv_fetch_array($stmtSections, SQLSRV_FETCH_ASSOC)) ? $row['count'] : 0;

} catch (Exception $e) {
    // Handle any query execution errors
    error_log("Query Error: " . $e->getMessage());
    die("An error occurred while fetching the counts. Please contact the administrator.");
}

// Queries for Selecting Attendance today table
$today = date('Y-m-d');

$sql = "
    SELECT a.attd_ref, f.fname, f.lname, f.acc_type, 
           a.time_in, a.time_out, a.status 
    FROM AttendanceToday a
    JOIN Faculty f ON a.rfid_no = f.rfid_no
    WHERE a.date_logged = ?
    ORDER BY a.time_in DESC
";

$params = array($today);
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("Database error: " . print_r(sqlsrv_errors(), true));
}

// Get selected month and year from the request (default to current month and year)
$selectedMonth = isset($_GET['month']) ? $_GET['month'] : date('m');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Fetch status counts
$sqlStatusCount = "SELECT status, COUNT(*) as count 
                   FROM Appointments 
                   WHERE MONTH(date_logged) = ? 
                   AND YEAR(date_logged) = ? 
                   AND status IN ('Completed', 'Declined', 'Cancelled')
                   GROUP BY status";

$params = array($selectedMonth, $selectedYear);
$stmtStatus = sqlsrv_query($conn, $sqlStatusCount, $params);

$statusCounts = ['Completed' => 0, 'Declined' => 0, 'Cancelled' => 0];
while ($row = sqlsrv_fetch_array($stmtStatus, SQLSRV_FETCH_ASSOC)) {
    $statusCounts[ucwords(strtolower(trim($row['status'])))] = $row['count'];
}

// Fetch agenda counts
$sqlAgendaCount = "SELECT agenda, COUNT(*) as count 
                   FROM Appointments 
                   WHERE MONTH(date_logged) = ? 
                   AND YEAR(date_logged) = ? 
                   GROUP BY agenda";

$stmtAgenda = sqlsrv_query($conn, $sqlAgendaCount, $params);
$agendaCounts = [];

while ($row = sqlsrv_fetch_array($stmtAgenda, SQLSRV_FETCH_ASSOC)) {
    $agendaCounts[ucwords(strtolower(trim($row['agenda'])))] = $row['count'];
}

// Query for top 3 professors with highest "Completed" appointments in selected month and year
$sqlTopProf = "
    SELECT TOP 3 
        f.rfid_no, 
        CONCAT(f.fname, ' ', COALESCE(f.mname + ' ', ''), f.lname) AS fullname, 
        COUNT(a.appointment_code) AS completed_count
    FROM Appointments a
    JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
    WHERE a.status = 'Completed' 
    AND MONTH(a.date_logged) = ? 
    AND YEAR(a.date_logged) = ?
    AND f.employment_type = 'Full time'
    AND f.archived = 0
    GROUP BY f.rfid_no, f.fname, f.mname, f.lname
    ORDER BY completed_count DESC
";

$stmtTopProf = sqlsrv_query($conn, $sqlTopProf, $params);

$topProfs = [];
while ($row = sqlsrv_fetch_array($stmtTopProf, SQLSRV_FETCH_ASSOC)) {
    $topProfs[] = [
        'prof_rfid_no' => $row['rfid_no'],
        'fullname' => $row['fullname'],
        'completed_count' => $row['completed_count']
    ];
}

$sqlLeastEngagedProf = "
    SELECT TOP 3
        f.rfid_no,
        CONCAT(f.fname, ' ', COALESCE(f.mname + ' ', ''), f.lname) AS fullname,
        COUNT(a.appointment_code) AS completed_appointments
    FROM Faculty f
    LEFT JOIN Appointments a 
        ON a.prof_rfid_no = f.rfid_no
        AND a.status = 'Completed'
        AND MONTH(a.date_logged) = ?
        AND YEAR(a.date_logged) = ?
    WHERE f.employment_type = 'Full Time' AND f.archived = 0
    GROUP BY f.rfid_no, f.fname, f.mname, f.lname
    ORDER BY completed_appointments ASC
";

$stmtLeastEngagedProf = sqlsrv_query($conn, $sqlLeastEngagedProf, $params);

$leastEngagedProfs = [];
while ($row = sqlsrv_fetch_array($stmtLeastEngagedProf, SQLSRV_FETCH_ASSOC)) {
    $leastEngagedProfs[] = [
        'prof_rfid_no' => $row['rfid_no'],
        'fullname' => $row['fullname'],
        'completed_appointments' => $row['completed_appointments']
    ];
}

// Query for Top 3 Rated Faculty Based on Selected Month and Year
$sqlTopRatedFaculty = "
    SELECT TOP 3 
        f.rfid_no, 
        CONCAT(f.fname, ' ', COALESCE(f.mname + ' ', ''), f.lname) AS fullname, 
        f.picture_path,
        COUNT(a.appointment_code) AS total_appointments,
        AVG(CAST(a.rate AS FLOAT)) AS average_rating
    FROM Appointments a
    JOIN Faculty f ON a.prof_rfid_no = f.rfid_no
    WHERE a.rate IS NOT NULL 
        AND f.archived = 0
        AND MONTH(a.date_logged) = ?
        AND YEAR(a.date_logged) = ?
    GROUP BY f.rfid_no, f.fname, f.mname, f.lname, f.picture_path
    ORDER BY average_rating DESC, total_appointments DESC
";

$paramsTopRated = array($selectedMonth, $selectedYear);
$stmtTopRatedFaculty = sqlsrv_query($conn, $sqlTopRatedFaculty, $paramsTopRated);

$topRatedFaculty = [];
while ($row = sqlsrv_fetch_array($stmtTopRatedFaculty, SQLSRV_FETCH_ASSOC)) {
    $topRatedFaculty[] = [
        'rfid_no' => $row['rfid_no'],
        'fullname' => $row['fullname'],
        'picture_path' => $row['picture_path'] ?? '../../assets/images/Prof.png',
        'total_appointments' => $row['total_appointments'],
        'average_rating' => round($row['average_rating'], 1) // Rounded to 1 decimal place
    ];
}

// Convert agenda and status count data to JSON for Chart.js
$statusData = json_encode($statusCounts);
$agendaData = json_encode($agendaCounts);
$statusHasData = array_sum($statusCounts) > 0 ? 'true' : 'false';
$agendaHasData = count($agendaCounts) > 0 ? 'true' : 'false';
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
    <style>
        .clickable-row {
            cursor: pointer;
        }

        .clickable-row:hover {
            background-color: #f0f0f0;
        }
    </style>

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
            <div class="nav-button" data-nav-id="dashboard">
                <a href="admin-index.php">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            <!-- Attendance Records -->
            <div class="nav-button" data-nav-id="attendance">
                <a href="admin-attendance-records.php">
                    <i class="fas fa-clipboard"></i>
                    <span>Attendance Records</span>
                </a>
            </div>
            <!-- Appointment -->
            <div class="nav-button" data-nav-id="appointment">
                <a href="admin-appointment.php">
                    <i class="fas fa-calendar-check"></i>
                    <span>Appointment</span>
                </a>
            </div>
            <!-- Announcement -->
            <div class="nav-button" data-nav-id="announcement">
                <a href="admin-announcement.php">
                    <i class="fas fa-bullhorn"></i>
                    <span>Announcement</span>
                </a>
            </div>
            <!-- Faculty -->
            <div class="nav-button" data-nav-id="faculty">
                <a href="admin-faculty.php">
                    <i class="fas fa-user"></i>
                    <span>Faculty Members</span>
                </a>
            </div>
            <!-- Schedule -->
            <div class="nav-button" data-nav-id="schedule">
                <a href="admin-schedule.php">
                    <i class="fas fa-calendar"></i>
                    <span>Schedule</span>
                </a>
            </div>
            <!-- Sections -->
            <div class="nav-button" data-nav-id="sections">
                <a href="admin-sections.php">
                    <i class="fas fa-users"></i>
                    <span>Sections</span>
                </a>
            </div>
            <!-- Student -->
            <div class="nav-button" data-nav-id="student">
                <a href="admin-student.php">
                    <i class="fas fa-users"></i>
                    <span>Student</span>
                </a>
            </div>
            <!-- Subjects -->
            <div class="nav-button" data-nav-id="subjects">
                <a href="admin-subjects.php">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
            </div>
            <!-- Admin Panel -->
            <div class="nav-button" data-nav-id="admin">
                <a href="admin-manage.php">
                    <i class="fas fa-user-tie"></i>
                    <span>Admin Panel</span>
                </a>
            </div>
            <!-- Logout -->
            <div class="nav-button" data-nav-id="logout">
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
            <h1 class="title-text">Dashboard</h1>

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


        <!-- First Widgets Lines For Different Counts -->
        <div class="dashboard-widgets">
            <div class="widget widget-faculty">
                <div class="widget-header faculty">
                    <i class="fas fa-solid fa-user"></i>
                    <span class="widget-title">Faculty Members</span>
                </div>
                <div class="widget-body">
                    <h2 class="widget-amount"><?php echo $facultyCount; ?></h2>
                </div>
                <div class="widget-footer">
                    <a href="admin-faculty.php" class="view-all-btn btn-faculty">
                        View All
                    </a>
                </div>
            </div>

            <div class="widget widget-student">
                <div class="widget-header student">
                    <i class="fas fa-solid fa-users"></i> <!-- Icon for the widget -->
                    <span class="widget-title">Students</span>
                </div>
                <div class="widget-body">
                    <h2 class="widget-amount"><?php echo $studentsCount; ?></h2>
                </div>
                <div class="widget-footer">
                    <a href="admin-student.php" class="view-all-btn btn-student">
                        View All
                    </a>
                </div>
            </div>

            <div class="widget widget-department">
                <div class="widget-header department">
                    <i class="fas fa-wallet"></i> <!-- Icon for the widget -->
                    <span class="widget-title">Sections</span>
                </div>
                <div class="widget-body">
                    <h2 class="widget-amount"><?php echo $sectionsCount; ?></h2>
                </div>
                <div class="widget-footer">
                    <a href="admin-sections.php" class="view-all-btn btn-dept">
                        View All
                    </a>
                </div>
            </div>

            <div class="widget widget-course">
                <div class="widget-header course">
                    <i class="fas fa-solid fa-book"></i> <!-- Icon for the widget -->
                    <span class="widget-title">Courses</span>
                </div>
                <div class="widget-body">
                    <h2 class="widget-amount"><?php echo $subjectsCount; ?></h2>
                </div>
                <div class="widget-footer">
                    <a href="admin-subjects.php" class="view-all-btn btn-course">
                        View All
                    </a>
                </div>
            </div>
        </div>


        <div class="action-widgets">
            <div class="widget-button">
                <div class="buttons">
                    <form method="GET" id="filterForm">

                        <select class="selector" name="month" id="month">
                            <?php
                            for ($i = 1; $i <= 12; $i++) {
                                $monthValue = str_pad($i, 2, "0", STR_PAD_LEFT);
                                echo "<option value='$monthValue' " . ($selectedMonth == $monthValue ? 'selected' : '') . ">" . date("F", mktime(0, 0, 0, $i, 1)) . "</option>";
                            }
                            ?>
                        </select>


                        <select class="selector" name="year" id="year">
                            <?php
                            $currentYear = date("Y");
                            for ($y = $currentYear; $y >= $currentYear - 5; $y--) {
                                echo "<option value='$y' " . ($selectedYear == $y ? 'selected' : '') . ">$y</option>";
                            }
                            ?>
                        </select>
                        <button class="fabtn" type="submit">Filter</button>
                    </form>

                    <script>
                        document.getElementById('month').addEventListener('change', function () {
                            document.getElementById('filterForm').submit();
                        });

                        document.getElementById('year').addEventListener('change', function () {
                            document.getElementById('filterForm').submit();
                        });
                    </script>
                </div>
            </div>
            <div class="widget-search"></div>
        </div>

        <div class="dashboard-widgets-charts">
            <div class="chart-widget">
                <canvas id="agendaChart"></canvas>
            </div>
            <div class="chart-widget">
                <canvas id="statusChart"></canvas>
            </div>
            <?php if (!empty($topProfs)): ?>
                <div class="chart-widget">
                    <h2 class="tbl-title-2">Most Engagement / month</h2>
                    <?php foreach ($topProfs as $prof): ?>
                        <div class="most-card">
                            <h3 class="prof-full-name"><?= htmlspecialchars($prof['fullname']) ?></h3>
                            <p class="completed-lbl"><i>Completed Appointments:</i>
                                <span class="count"><?= $prof['completed_count'] ?></span>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="chart-widget">
                    <p class="no-data-message-4">No data available for this month</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($topRatedFaculty)): ?>
                <div class="chart-widget">
                    <h2 class="tbl-title-2">Top Rated Faculty</h2>
                    <?php foreach ($topRatedFaculty as $faculty): ?>
                        <div class="faculty-item2">
                            <img src="<?php echo htmlspecialchars($faculty['picture_path']); ?>" alt="Profile"
                                class="profile-picture2">
                            <div class="faculty-details2">
                                <p class="prof-full-name"><?php echo htmlspecialchars($faculty['fullname']); ?></p>
                                <div class="rating-box2">
                                    <?php echo $faculty['average_rating']; ?>
                                    <?php
                                    $fullStars = floor($faculty['average_rating']);
                                    $halfStar = ($faculty['average_rating'] - $fullStars) >= 0.5 ? 1 : 0;

                                    for ($i = 0; $i < $fullStars; $i++) {
                                        echo '<i class="bi bi-star-fill"></i> ';
                                    }
                                    if ($halfStar) {
                                        echo '<i class="bi bi-star-half"></i> ';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="chart-widget">
                    <p class="no-data-message-4">No data available for this month</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Second Widgets Lines For Different Counts -->
        <div class="dashboard-widgets-second">
            <div class="dashboard-widget-first">
                <div class="widget widget-weather">
                    <div class="search-box">
                        <i class='bx bxs-map'></i>
                        <input type="text" placeholder="Enter your location" value="Manila">
                        <button class='bx bx-search'></button>
                    </div>
                    <div class="weather-box">
                        <div class="box">
                            <div class="info-weather">
                                <div class="weather">
                                    <img src="../../assets/images/cloud.png" alt="Weather Icon">
                                    <p class="temperature">0<span>°C</span></p>
                                    <p class="description">Description</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="weather-details">
                        <div class="humidity">
                            <i class='bx bx-water'></i>
                            <div class="text">
                                <span>0%</span>
                                <p>Humidity</p>
                            </div>
                        </div>
                        <div class="wind">
                            <i class='bx bx-wind'></i>
                            <div class="text">
                                <span>0 Km/h</span>
                                <p>Wind Speed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dashboard-widget-second">
                <div class="widget table-design">
                    <h2 class="tbl-title">Today's Attendance</h2>
                    <table id="attendanceTable" class="display">
                        <thead class="tbl-header">
                            <tr>
                                <th>Name</th>
                                <!--<th>Role</th>-->
                                <th>Time In</th>
                                <th>Time Out</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                                <?php
                                // Determine the status class
                                $statusClass = '';
                                switch (strtolower($row['status'])) {
                                    case 'present':
                                        $statusClass = 'status-present';
                                        break;
                                    case 'late':
                                        $statusClass = 'status-late';
                                        break;
                                    case 'absent':
                                        $statusClass = 'status-absent';
                                        break;
                                }
                                ?>
                                <tr class="tbl-row clickable-row"
                                    data-href="admin-update-attendance.php?attd_ref=<?= urlencode($row['attd_ref']) ?>">
                                    <td class="small-text"><?= htmlspecialchars($row['fname'] . " " . $row['lname']) ?></td>
                                    <td><?= $row['time_in'] ? $row['time_in']->format('h:i A') : 'N/A' ?></td>
                                    <td><?= $row['time_out'] ? $row['time_out']->format('h:i A') : 'N/A' ?></td>
                                    <td class="<?= $statusClass ?>"><?= htmlspecialchars($row['status']) ?></td>
                                </tr>

                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="dashboard-widget-third">
                <div class="widget widget-chart">
                    <div class="widget-header">
                        <span class="widget-ttl">Attendance Overview</span>
                    </div>
                    <div class="widget-body">
                        <canvas id="dataChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../assets/js/nav-highlight.js"></script>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rows = document.querySelectorAll('.clickable-row');
        rows.forEach(row => {
            row.addEventListener('click', () => {
                window.location.href = row.getAttribute('data-href');
            });
        });
    });
</script>


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

<?php
// Query to count Timed In and Not Timed In faculty based on rfid_no
$query = "
    SELECT 
        SUM(CASE WHEN A.rfid_no IS NOT NULL THEN 1 ELSE 0 END) AS Timed_In,
        SUM(CASE WHEN A.rfid_no IS NULL THEN 1 ELSE 0 END) AS Not_Timed_In
    FROM Faculty F
    LEFT JOIN AttendanceToday A ON F.rfid_no = A.rfid_no 
    AND A.date_logged = CONVERT(DATE, GETDATE())
";

$stmts = sqlsrv_query($conn, $query);

if ($stmts === false) {
    die(json_encode(['error' => sqlsrv_errors()]));
}

// Fetch the result
$result = sqlsrv_fetch_array($stmts, SQLSRV_FETCH_ASSOC);

// Store the counts in variables
$timedInCount = $result['Timed_In'] ?? 0; // Use 0 if no result
$notTimedInCount = $result['Not_Timed_In'] ?? 0; // Use 0 if no result

// Free statement and close connection
sqlsrv_free_stmt($stmts);
sqlsrv_close($conn);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Pass the PHP variables to JavaScript
    const timedInCount = <?php echo $timedInCount; ?>;
    const notTimedInCount = <?php echo $notTimedInCount; ?>;

    // Data for the chart
    const ctx = document.getElementById('dataChart').getContext('2d');
    const dataChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Timed In', 'Not Timed In'],
            datasets: [{
                label: 'Count',
                data: [timedInCount, notTimedInCount],
                backgroundColor: ['#28a745', '#ff790d'], // Bar colors
                borderColor: ['#1e7e34', '#d65a00'], // Border colors
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Allows custom sizing
            scales: {
                x: {
                    ticks: {
                        color: '#393242', // X-axis label color
                        font: {
                            size: 12, // X-axis font size
                            family: 'Poppins' // Font family
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        color: '#393242', // Y-axis label color
                        font: {
                            size: 12,
                            family: 'Poppins'
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    labels: {
                        color: '#393242', // Legend text color
                        font: {
                            size: 12,
                            family: 'Poppins'
                        }
                    }
                }
            }
        }
    });

</script>
<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    var statusData = <?php echo $statusData; ?>;
    var agendaData = <?php echo $agendaData; ?>;
    var statusHasData = <?php echo $statusHasData; ?>;
    var agendaHasData = <?php echo $agendaHasData; ?>;
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        // Agenda Pie Chart
        const agendaCounts = <?= $agendaData ?>;
        const agendaLabels = Object.keys(agendaCounts);
        const agendaData = Object.values(agendaCounts);
        const ctxAgenda = document.getElementById("agendaChart").getContext("2d");

        if (agendaData.length === 0 || agendaData.every(val => val === 0)) {
            ctxAgenda.font = "13.3px Poppins";

            ctxAgenda.fillText("No data available for this month", ctxAgenda.canvas.width / 6.2, ctxAgenda.canvas.height / 2);
        } else {
            new Chart(ctxAgenda, {
                type: "pie",
                data: {
                    labels: agendaLabels,
                    datasets: [{
                        data: agendaData,
                        backgroundColor: ["#4CAF50", "#FF9800", "#F44336", "#2196F3"], // Green, Orange, Red, Blue
                        borderColor: "#ffffff",
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    aspectRatio: 8,
                    plugins: {
                        legend: {
                            position: "top",
                            labels: {
                                font: {
                                    family: "Poppins",
                                    size: 11
                                },
                                color: "#393242"
                            }
                        },
                        title: {
                            display: true,
                            text: "Agenda Overview / month",
                            font: {
                                family: "Poppins",
                                size: 13,
                                weight: "bold"
                            },
                            color: "#393242",
                        }
                    }
                }
            });
        }

        // Status Bar Chart (Only 'Completed', 'Declined', and 'Cancelled')
        const statusCounts = <?= $statusData ?>;
        const statusLabels = Object.keys(statusCounts);
        const statusData = Object.values(statusCounts);
        const ctxStatus = document.getElementById("statusChart").getContext("2d");

        if (statusData.length === 0 || statusData.every(val => val === 0)) {
            ctxStatus.font = "13.3px Poppins";
            ctxStatus.textAlign = "center";
            ctxStatus.fillText("No data available for this month", ctxStatus.canvas.width / 1.9, ctxStatus.canvas.height / 2);
        } else {
            new Chart(ctxStatus, {
                type: "bar",
                data: {
                    labels: statusLabels,
                    datasets: [{
                        label: "Appointment Status Count",
                        data: statusData,
                        backgroundColor: ["#4caf50", "#f44336", "#ff9800"], // Green, Red, Orange
                        barThickness: 50,
                        borderRadius: 5, // Rounded bar edges
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
                                    size: 11
                                }
                            }
                        },
                        x: {
                            ticks: {
                                font: {
                                    family: "Poppins",
                                    size: 11
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: "Appointment Status Overview / month",
                            font: {
                                family: "Poppins",
                                size: 13,
                                weight: "bold"
                            },
                            color: "#393242",
                            padding: {
                                top: 10,
                                bottom: 20
                            }
                        },
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    });
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
    document.addEventListener("DOMContentLoaded", () => {
        const search = document.querySelector(".search-box button");
        const weatherBox = document.querySelector(".weather-box");
        const weatherDetails = document.querySelector(".weather-details");
        const image = document.querySelector(".weather-box img");
        const temperature = document.querySelector(".weather-box .temperature");
        const description = document.querySelector(".weather-box .description");
        const humidity = document.querySelector(".weather-details .humidity span");
        const wind = document.querySelector(".weather-details .wind span");

        const APIKey = "482a7b091702848c56325a40008f8854";

        function capitalizeWords(str) {
            return str
                .split(' ')
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(' ');
        }

        function fetchWeather(city) {
            fetch(`https://api.openweathermap.org/data/2.5/weather?q=${city}&units=metric&appid=${APIKey}`)
                .then(response => {
                    if (!response.ok) throw new Error("City not found");
                    return response.json();
                })
                .then(json => {
                    weatherBox.style.display = "block";
                    weatherDetails.style.display = "flex";

                    // Apply initial opacity to both boxes for smooth transition
                    weatherBox.style.opacity = "0";
                    weatherDetails.style.opacity = "0";

                    setTimeout(() => {
                        // Update weather icon and data
                        switch (json.weather[0].main) {
                            case 'Clear':
                                image.src = "../../assets/images/clear.png";
                                break;
                            case 'Rain':
                                image.src = "../../assets/images/rain.png";
                                break;
                            case 'Snow':
                                image.src = "../../assets/images/snow.png";
                                break;
                            case 'Clouds':
                                image.src = "../../assets/images/cloud.png";
                                break;
                            case 'Mist':
                            case 'Haze':
                                image.src = "../../assets/images/mist.png";
                                break;
                            default:
                                image.src = "../../assets/images/cloud.png";
                        }

                        temperature.innerHTML = `${Math.round(json.main.temp)}<span>°C</span>`;
                        description.innerHTML = capitalizeWords(json.weather[0].description);
                        humidity.innerHTML = `${json.main.humidity}%`;
                        wind.innerHTML = `${Math.round(json.wind.speed)} Km/h`;

                        // Fade in the weather box and details with transition
                        weatherBox.style.transition = "opacity 0.5s ease-in-out";
                        weatherDetails.style.transition = "opacity 0.5s ease-in-out";
                        weatherBox.style.opacity = "1";
                        weatherDetails.style.opacity = "1";
                    }, 300);
                })
                .catch(() => {
                    // Show error state: display 404 image and message, hide weather details
                    image.src = "../../assets/images/404.png";
                    description.innerHTML = "Oops! Location not found!";
                    temperature.innerHTML = "";  // Clear temperature data
                    humidity.innerHTML = "";     // Clear humidity data
                    wind.innerHTML = "";         // Clear wind data

                    // Apply transition for smooth visibility
                    weatherBox.style.transition = "opacity 0.5s ease-in-out, transform 0.3s ease-in-out";
                    weatherDetails.style.transition = "opacity 0.5s ease-in-out, transform 0.3s ease-in-out";

                    // Fade in the 404 image and error message
                    weatherBox.style.opacity = "1";
                    weatherDetails.style.opacity = "0";  // Hide weather details
                    weatherBox.style.transform = "translateY(20px)";
                    weatherDetails.style.transform = "translateY(20px)";
                });
        }

        search.addEventListener("click", () => {
            const city = document.querySelector(".search-box input").value.trim();
            if (city !== "") fetchWeather(city);
        });

        // Fetch weather for default location on page load
        fetchWeather("Manila");
    });
</script>


</html>