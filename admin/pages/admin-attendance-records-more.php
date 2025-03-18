<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');
$appointment_code = filter_input(INPUT_GET, 'appointment_code', FILTER_SANITIZE_STRING);
$rfid_no = filter_input(INPUT_GET, 'rfid_no', FILTER_SANITIZE_STRING);

$sql = "
    SELECT 
        at.attd_ref,
        at.rfid_no,
        f.fname AS prof_fname, 
        f.lname AS prof_lname, 
        at.time_in, 
        at.time_out, 
        at.status, 
        at.date_logged
    FROM AttendanceToday at
    JOIN Faculty f ON at.rfid_no = f.rfid_no
    WHERE at.rfid_no = ?
    ORDER BY at.date_logged DESC, at.time_in ASC
";

$params = [$rfid_no];
$stmt = sqlsrv_query($conn, $sql, $params);

if ($stmt === false) {
    die("Database error: " . print_r(sqlsrv_errors(), true));
}

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
        <div class="action-widgets-2">
            <div class="widget-button">
                <h1 class="sub-title">Attendance Records</h1>
                <div class="buttons">
                    <a href="admin-attendance-records.php" class="discard-btn">Back</a>
                </div>
            </div>
            <div class="widget-search"></div>
        </div>
        <div id="messageBox" class="message-box"></div>
        <div class="apt-dashboard-widgets">
            <div class="widget apt-table-design">
                <table id="attendanceTable" class="display">
                    <thead class="tbl-header">
                        <tr>
                            <th>Professor</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Date</th>
                            <th>Action</th>
                            <th>Status</th> <!-- Moved to last column -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)): ?>
                            <?php
                            $statusClasses = [
                                'present' => 'status-present',
                                'late' => 'status-late',
                                'absent' => 'status-absent'
                            ];
                            $statusKey = strtolower(trim($row['status']));
                            $statusClass = $statusClasses[$statusKey] ?? 'status-default';

                            $professor = htmlspecialchars($row['prof_fname'] . " " . $row['prof_lname']);
                            $timeIn = $row['time_in'] ? $row['time_in']->format('h:i A') : 'N/A';
                            $timeOut = $row['time_out'] ? $row['time_out']->format('h:i A') : 'N/A';
                            $dateLogged = $row['date_logged'] ? $row['date_logged']->format('Y-m-d') : 'N/A';

                            $attdRef = htmlspecialchars($row['attd_ref']);
                            $rfidNo = htmlspecialchars($row['rfid_no']);
                            ?>
                            <tr class="tbl-row">
                                <td class="small-text"><?= $professor ?></td>
                                <td><?= $timeIn ?></td>
                                <td><?= $timeOut ?></td>
                                <td><?= $dateLogged ?></td>
                                <td>
                                    <!--<button class="archive-btn" data-attd-ref="<?= $attdRef ?>"
                                        data-rfid-no="<?= $rfidNo ?>">Archive</button>-->
                                    <button class="delete-btn action-btnn delete-btn" data-attd-ref="<?= $attdRef ?>"
                                        data-rfid-no="<?= $rfidNo ?>"><i class="fas fa-trash"></i></button>
                                </td>
                                <td class="attendance-status <?= $statusClass ?>"><?= ucwords($statusKey) ?></td>
                                <!-- Status at last column -->
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Dark background overlay -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Archive</h2>
            <p>Are you sure you want to archive this attendance record?</p>
            <div class="modal-actions">
                <button id="confirmArchive" class="btn-confirm">Yes, Archive</button>
                <button onclick="closeArchiveModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this attendance record?</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()">↑</button>
</body>

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
    // Trigger fade-out effect before navigating
    window.addEventListener('beforeunload', function () {
        document.body.classList.add('fade-out');
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
        }, 10000);

        // Remove message from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
</script>

<script>
    let selectedAttdRef = null;
    let selectedRfidNo = null;

    document.querySelectorAll(".archive-btn").forEach(button => {
        button.addEventListener("click", function () {
            selectedAttdRef = this.getAttribute("data-attd-ref");
            selectedRfidNo = this.getAttribute("data-rfid-no");
            openArchiveModal();
        });
    });

    document.querySelectorAll(".delete-btn").forEach(button => {
        button.addEventListener("click", function () {
            selectedAttdRef = this.getAttribute("data-attd-ref");
            selectedRfidNo = this.getAttribute("data-rfid-no");
            openDeleteModal();
        });
    });

    document.getElementById("confirmArchive").addEventListener("click", function () {
        window.location.href = "../functions/archive-attendance.php?attd_ref=" + selectedAttdRef + "&rfid_no=" + selectedRfidNo;
    });

    document.getElementById("confirmDelete").addEventListener("click", function () {
        window.location.href = "../functions/delete-attendance.php?attd_ref=" + selectedAttdRef + "&rfid_no=" + selectedRfidNo;
    });

    function openArchiveModal() {
        document.getElementById("modalOverlay").style.display = "block";
        document.getElementById("archiveModal").style.display = "block";
    }

    function closeArchiveModal() {
        document.getElementById("modalOverlay").style.display = "none";
        document.getElementById("archiveModal").style.display = "none";
    }

    function openDeleteModal() {
        document.getElementById("modalOverlay").style.display = "block";
        document.getElementById("deleteModal").style.display = "block";
    }

    function closeDeleteModal() {
        document.getElementById("modalOverlay").style.display = "none";
        document.getElementById("deleteModal").style.display = "none";
    }
</script>

</html>