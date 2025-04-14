<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Get sched_id from URL
$sched_id = filter_input(INPUT_GET, 'sched_id', FILTER_SANITIZE_NUMBER_INT);

if (!$sched_id) {
    die("Invalid schedule ID");
}

// Fetch schedule details
$query = "SELECT * FROM Schedules WHERE sched_id = ?";
$params = array($sched_id);
$stmt = sqlsrv_query($conn, $query, $params);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$schedule = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if (!$schedule) {
    die("Schedule not found");
}
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
            <!-- Admin Panel -->
            <div class="nav-button">
                <a href="admin-manage.php">
                    <i class="fas fa-user-tie"></i>
                    <span>Admin Panel</span>
                </a>
            </div>
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
        <form id="uploadForm" action="../functions/update-schedule.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="sched_id" value="<?= htmlspecialchars($sched_id) ?>">

            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Schedule / Update </h1>
                    <div class="buttons">
                        <button class="create-btn-3" type="submit">Save</button>
                        <a href="admin-schedule.php" class="discard-btn">Discard</a>
                    </div>
                </div>
                <div class="widget-search"></div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <a href="javascript:void(0);" class="red-btn"
                        onclick="openDeleteModal(<?= isset($_GET['sched_id']) ? htmlspecialchars($_GET['sched_id']) : 'null'; ?>)">
                        Delete
                    </a>
                    <?php if (isset($_GET['sched_id'])): ?>
                        <a href="admin-update-time-info.php?sched_id=<?= htmlspecialchars($_GET['sched_id']); ?>"
                            class="red-btn">
                            Attendance Information
                        </a>
                    <?php else: ?>
                        <a href="#" class="red-btn" onclick="alert('No schedule ID provided'); return false;">
                            Attendance Information
                        </a>
                    <?php endif; ?>
                    <!--<button class="pass-btn" type="button">Section Information</button>-->
                </div>
                <div class="faculty-container-2">
                    <h1 class="info-title-2">Schedule Details</h1>
                    <hr>

                    <div class="faculty-name-container">
                        <div>
                            <div class="faculty-name-box">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="name-input">
                                    <option value="Lecture" <?= $schedule['type'] == 'Lecture' ? 'selected' : '' ?>>Lecture
                                    </option>
                                    <option value="Laboratory" <?= $schedule['type'] == 'Laboratory' ? 'selected' : '' ?>>
                                        Laboratory</option>
                                    <option value="Break" <?= $schedule['type'] == 'Break' ? 'selected' : '' ?>>Break
                                    </option>
                                    <option value="Consultation Time" <?= $schedule['type'] == 'Consultation Time' ? 'selected' : '' ?>>Consultation Time</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="start-date">Start Date</label>
                            <input class="name-input" type="date" id="start_date" name="start_date"
                                value="<?= $schedule['start_date']->format('Y-m-d') ?>" required>
                        </div>
                    </div>

                    <h1 class="info-title">Time Details</h1>
                    <hr>

                    <div class="faculty-name-container">
                        <div>
                            <label for="start-time">Start Time</label>
                            <input class="name-input" type="time" id="start_time" name="start_time"
                                value="<?= $schedule['start_time']->format('H:i') ?>" min="07:00" max="20:00" required>
                        </div>
                        <div>
                            <label for="end-time">End Time</label>
                            <input class="name-input" type="time" id="end_time" name="end_time"
                                value="<?= $schedule['end_time']->format('H:i') ?>" min="07:00" max="20:00" required>
                        </div>
                    </div>

                    <h1 class="info-title">Faculty Information</h1>
                    <hr>

                    <div class="faculty-name-container">
                        <div>
                            <div class="faculty-name-box">
                                <label for="rfid_no">Faculty Member</label>
                                <select id="rfid_no" name="rfid_no" class="name-input-2">
                                    <?php
                                    include '../functions/fetch-faculty.php';
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h1 id="location" class="info-title">Location Details</h1>
                    <hr id="location-hr">

                    <div class="faculty-name-container">
                        <div>
                            <div class="faculty-name-box">
                                <label for="room_id">Room</label>
                                <select id="room_id" name="room_id" class="name-input-2">
                                    <?php include '../functions/fetch-room.php'; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h1 id="class-dtl" class="info-title">Class Details</h1>
                    <hr id="class-dtl-hr">

                    <div class="faculty-name-container">
                        <div>
                            <div class="faculty-name-box">
                                <label for="section_id">Section</label>
                                <select id="section_id" name="section_id" class="name-input">
                                    <?php include '../functions/fetch-section.php'; ?>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="faculty-name-box">
                                <label for="subject_code">Subject</label>
                                <select id="subject_code" name="subject_code" class="name-input">
                                    <?php include '../functions/fetch-subject.php'; ?>
                                </select>
                            </div>
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
            <p>Are you sure you want to delete this schedule?</p>
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
        const typeSelect = document.getElementById("type");
        const roomInput = document.getElementById("room_id");
        const sectionInput = document.getElementById("section_id");
        const subjectInput = document.getElementById("subject_code");

        const roomField = roomInput.closest("div");
        const sectionField = sectionInput.closest("div");
        const subjectField = subjectInput.closest("div");

        const locationTitle = document.getElementById("location");
        const locationHr = document.getElementById("location-hr");
        const classTitle = document.getElementById("class-dtl");
        const classHr = document.getElementById("class-dtl-hr");

        function toggleFields() {
            if (typeSelect.value === "Break" || typeSelect.value === "Consultation Time") {
                // Set values to "N/A"
                roomInput.value = "N/A";
                sectionInput.value = "N/A";
                subjectInput.value = "N/A";

                // Hide fields visually
                roomField.style.display = "none";
                sectionField.style.display = "none";
                subjectField.style.display = "none";
                locationTitle.style.display = "none";
                locationHr.style.display = "none";
                classTitle.style.display = "none";
                classHr.style.display = "none";

                // Ensure hidden inputs are still submitted
                roomInput.setAttribute("readonly", true);
                sectionInput.setAttribute("readonly", true);
                subjectInput.setAttribute("readonly", true);
            } else {
                // Show fields
                roomField.style.display = "";
                sectionField.style.display = "";
                subjectField.style.display = "";
                locationTitle.style.display = "";
                locationHr.style.display = "";
                classTitle.style.display = "";
                classHr.style.display = "";

                // Allow editing when fields are visible
                roomInput.removeAttribute("readonly");
                sectionInput.removeAttribute("readonly");
                subjectInput.removeAttribute("readonly");
            }
        }

        typeSelect.addEventListener("change", toggleFields);
        toggleFields(); // Call on page load to set correct state
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("rfid_no").value = "<?= $schedule['rfid_no'] ?>";
        document.getElementById("room_id").value = "<?= $schedule['room_id'] ?>";
        document.getElementById("section_id").value = "<?= $schedule['section_id'] ?>";
        document.getElementById("subject_code").value = "<?= $schedule['subject_code'] ?>";
    });
</script>
<script>
    function openDeleteModal(rfidNo) {
        document.getElementById("deleteModal").style.display = "block"; // Show modal
        document.getElementById("modalOverlay").style.display = "block"; // Show dark overlay
        document.getElementById("confirmDelete").setAttribute("data-rfid", rfidNo); // Store RFID
    }

    function closeDeleteModal() {
        document.getElementById("deleteModal").style.display = "none"; // Hide modal
        document.getElementById("modalOverlay").style.display = "none"; // Hide dark overlay
    }

    // When "Yes, Delete" button is clicked, redirect to delete_faculty.php
    document.getElementById("confirmDelete").addEventListener("click", function () {
        let rfidNo = this.getAttribute("data-rfid"); // Get stored RFID
        window.location.href = `../functions/delete-schedule.php?sched_id=${rfidNo}`; // Redirect with RFID
    });
</script>

</html>