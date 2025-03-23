<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Get the attd_ref from query parameter
$attd_ref = filter_input(INPUT_GET, 'attd_ref', FILTER_SANITIZE_STRING);

// Ensure attd_ref is present
if (!$attd_ref) {
    echo "Invalid attendance reference.";
    exit();
}

// Connect to the database
$conn = sqlsrv_connect($serverName, $connectionOptions);
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch the attendance record
$query = "SELECT A.*, F.fname, F.lname, F.acc_type
          FROM AttendanceToday A
          INNER JOIN Faculty F ON A.rfid_no = F.rfid_no
          WHERE A.attd_ref = ?";
$params = array($attd_ref);
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$attendance = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$attendance) {
    echo "Attendance record not found.";
    exit();
}

// Define the full name variable
$facultyFullName = $attendance['fname'] . ' ' . $attendance['lname'];

// Format times if not null
$timeInFormatted = $attendance['time_in'] ? $attendance['time_in']->format('H:i') : 'N/A';
$timeOutFormatted = $attendance['time_out'] ? $attendance['time_out']->format('H:i') : 'N/A';
$status = $attendance['status'];
$dateLogged = $attendance['date_logged']->format('Y-m-d');

// You can now use $attendance['fname'], $attendance['lname'], etc. in your HTML
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="shortcut icon" href="../../assets/images/F-Connect.ico" type="image/x-icon" />
    <title>F - Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css'>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
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
        <form id="uploadForm" action="../functions/update-attendance.php" method="POST" enctype="multipart/form-data">
            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Attendance / Edit </h1>
                    <div class="buttons">
                        <button class="create-btn-3" type="submit">Update</button>
                        <a href="admin-index.php" class="discard-btn">Discard</a>
                    </div>
                </div>
                <div class="widget-search"></div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <a href="javascript:void(0);" class="red-btn"
                        onclick="openDeleteModal('<?= isset($_GET['attd_ref']) ? htmlspecialchars($_GET['attd_ref'], ENT_QUOTES, 'UTF-8') : '' ?>')">
                        Delete
                    </a>
                </div>
                <div class="faculty-container-2">

                    <h1 class="info-title">Attendance Information</h1>
                    <hr>
                    <div class="faculty-name-container">
                        <div>
                            <label for="faculty_name">Faculty Member</label>
                            <input class="name-input" type="text" id="faculty_name" name="faculty_name"
                                value="<?= htmlspecialchars($facultyFullName) ?>" readonly>
                        </div>

                        <div class="faculty-name-box">
                            <label for="status">Status</label>
                            <select id="status" name="status" class="name-input">
                                <option value="" disabled <?= empty($attendance['status']) ? 'selected' : '' ?>>Select
                                    Status</option>
                                <option value="Present" <?= ($attendance['status'] === 'Present') ? 'Present' : '' ?>>
                                    Present</option>
                                <option value="Late" <?= ($attendance['status'] === 'Late') ? 'selected' : '' ?>>
                                    Late</option>
                                <option value="Absent" <?= ($attendance['status'] === 'Absent') ? 'selected' : '' ?>>Absent
                                </option>
                            </select>
                        </div>
                        <input type="hidden" name="attd_ref" value="<?= htmlspecialchars($attd_ref); ?>">
                    </div>
                    <br>
                    <div class="faculty-name-container">
                        <div>
                            <label for="start-time">Time In</label>
                            <input class="name-input" type="time" id="time_in" name="time_in"
                                value="<?= $attendance['time_in'] ? $attendance['time_in']->format('H:i') : '' ?>"
                                min="07:00" max="20:00">

                        </div>
                        <div>
                            <label for="end-time">Time Out</label>
                            <input class="name-input" type="time" id="time_out" name="time_out"
                                value="<?= $attendance['time_out'] ? $attendance['time_out']->format('H:i') : '' ?>"
                                min="07:00" max="20:00">
                        </div>
                    </div>
                </div>
            </div>
        </form>

    </div>

    <!-- Dark background overlay -->
    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this attendance?</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
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
    document.addEventListener("DOMContentLoaded", function () {
        const deleteModal = document.getElementById("deleteModal");
        const modalOverlay = document.getElementById("modalOverlay");
        const confirmDeleteBtn = document.getElementById("confirmDelete");

        function openDeleteModal(attendanceCode) {
            if (!attendanceCode) {
                console.error("No attendance code provided for deletion.");
                return;
            }

            deleteModal.style.display = "block";
            modalOverlay.style.display = "block";
            confirmDeleteBtn.setAttribute("data-attendance-code", attendanceCode);
        }

        function closeDeleteModal() {
            deleteModal.style.display = "none";
            modalOverlay.style.display = "none";
            confirmDeleteBtn.removeAttribute("data-attendance-code");
        }

        confirmDeleteBtn.addEventListener("click", function () {
            const attendanceCode = this.getAttribute("data-attendance-code");
            if (attendanceCode) {
                window.location.href = `../functions/delete-attendance.php?attd_ref=${encodeURIComponent(attendanceCode)}`;
            } else {
                console.error("No attendance code found on confirm button.");
            }
        });

        // Make modal functions globally accessible
        window.openDeleteModal = openDeleteModal;
        window.closeDeleteModal = closeDeleteModal;
    });
</script>
</html>