<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');
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
        <form id="uploadForm" action="../functions/insert-announcement.php" method="POST" enctype="multipart/form-data">
            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Announcement/ New </h1>
                    <div class="buttons">
                        <button class="create-btn-3" type="submit">Save</button>
                        <a href="admin-announcement.php" class="discard-btn">Discard</a>
                    </div>
                </div>
                <div class="widget-search"></div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <!--<button class="pass-btn" type="button">Sent Password Reset Instructions</button>-->
                </div>
                <div class="faculty-container-2">
                    <div class="picture-container">
                        <div id="image-preview">
                            <img id="preview" src="../../assets/images/add-image.jpg" alt="Image Preview">
                        </div>
                        <label for="picture_path" class="custom-file-upload">Choose Image</label>
                        <input type="file" id="picture_path" name="picture_path" accept="image/*"
                            onchange="previewImage(event)">
                    </div>

                    <h1 class="info-title">Announcement Detail</h1>
                    <hr>

                    <div class="faculty-name-container">
                        <div>
                            <label for="title">Title</label>
                            <input class="name-input" type="text" id="title" name="title" required>
                        </div>
                        <div>
                            <label for="content">Content</label>
                            <input class="name-input" type="text" id="content" name="content">
                        </div>
                        <div>
                            <label for="date">Date</label>
                            <input class="name-input-2" type="date" id="date" name="date" required>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()">↑</button>
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
    function previewImage(event) {
        const input = event.target;
        const preview = document.getElementById("preview");

        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            // Reset to default image if no file is selected
            preview.src = "../../assets/images/add-image.jpg";
        }
    }
</script>
<script>
    function uploadImage() {
        var formData = new FormData();
        var fileInput = document.getElementById("picture_path");

        if (fileInput.files.length === 0) {
            alert("Please select an image first.");
            return;
        }

        formData.append("picture_path", fileInput.files[0]);

        fetch("upload-image.php", {
            method: "POST",
            body: formData
        })
            .then(response => response.text())
            .then(data => alert(data)) // Display success or error message
            .catch(error => console.error("Error:", error));
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


</html>