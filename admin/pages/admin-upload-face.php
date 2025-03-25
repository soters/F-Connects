<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

$rfid_no = isset($_GET['rfid_no']) ? $_GET['rfid_no'] : null;

if (!$rfid_no) {
    $message = "No RFID provided!";
    $type = "error";
    header("Location: admin-faculty.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit();
}

// Fetch faculty data
$sqlFaculty = "SELECT f.*, a.region, a.province, a.city, a.zip_code, a.address_dtl, ud.dept_id 
               FROM Faculty f
               LEFT JOIN FacultyAdresses a ON f.rfid_no = a.rfid_no
               LEFT JOIN UserDepartment ud ON f.rfid_no = ud.rfid_no
               WHERE f.rfid_no = ?";
$paramsFaculty = [$rfid_no];
$stmtFaculty = sqlsrv_query($conn, $sqlFaculty, $paramsFaculty);

if ($stmtFaculty === false || !sqlsrv_has_rows($stmtFaculty)) {
    $message = "Faculty data not found!";
    $type = "error";
    header("Location: admin-faculty.php?message=" . urlencode($message) . "&type=" . urlencode($type));
    exit();
}

$data = sqlsrv_fetch_array($stmtFaculty, SQLSRV_FETCH_ASSOC);

// Fetch departments for dropdown
$sqlDepartments = "SELECT dept_id, department_name FROM Department";
$stmtDepartments = sqlsrv_query($conn, $sqlDepartments);

$departments = [];
if ($stmtDepartments !== false) {
    while ($row = sqlsrv_fetch_array($stmtDepartments, SQLSRV_FETCH_ASSOC)) {
        $departments[] = $row;
    }
}

// Fetch faculty images from FaceData
$sqlFaceData = "SELECT image_path FROM FaceData WHERE rfid_no = ?";
$paramsFaceData = [$rfid_no];
$stmtFaceData = sqlsrv_query($conn, $sqlFaceData, $paramsFaceData);

$faceImages = [];
if ($stmtFaceData !== false) {
    while ($row = sqlsrv_fetch_array($stmtFaceData, SQLSRV_FETCH_ASSOC)) {
        $faceImages[] = $row['image_path'];
    }
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
    <style>
        #image-preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .preview {
            width: 100px;
            /* Adjust size as needed */
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .modal2 {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content2 {
            background: white;
            margin: 10% auto;
            padding: 20px;
            width: 50%;
            border-radius: 10px;
            text-align: center;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .image-upload-container {
            display: flex;
            justify-content: center;
            gap: 20px;
        }

        .upload-box {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .choose-btn {
            background-color: #4b3c88;
            color: white;
            padding: 5px 10px;
            border: none;
            cursor: pointer;
            margin-top: 5px;
        }

        .preview-2 {
            text-align: center;
            font-weight: 600;
            color: #322e36;
            font-family: "Poppins", sans-serif;
        }

        #uploadBtn {
            border-radius: 4px;
            margin-top: 20px;
            background-color: #2b5876;
            color: #ffc52d;
            font-family: "Poppins", sans-serif;
            font-weight: 700;
            padding: 10px;
            border: none;
            width: 100%;
        }

        input[type="file"] {
            display: none;
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
        <form id="updateForm" action="../functions/update-face-data.php" method="POST" enctype="multipart/form-data">
            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Faculty / Face Data</h1>
                    <div class="buttons">
                        <a href="admin-update-faculty.php?rfid_no=<?= isset($_GET['rfid_no']) ? urlencode($_GET['rfid_no']) : '' ?>"
                            class="discard-btn">Back</a>
                    </div>
                </div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <a href="javascript:void(0);" class="red-btn"
                        onclick="openDeleteModal(<?= isset($_GET['rfid_no']) ? htmlspecialchars($_GET['rfid_no']) : 'null'; ?>)">
                        Delete Face Data
                    </a>
                    <a href="javascript:void(0);" class="face-btn"
                        onclick="openUploadModal2(<?= isset($_GET['rfid_no']) ? htmlspecialchars($_GET['rfid_no']) : 'null'; ?>)">
                        Upload Face Data
                    </a>
                    <a href="../camera/kiosk-first-facial.php?rfid_no=<?= isset($_GET['rfid_no']) ? htmlspecialchars($_GET['rfid_no']) : ''; ?>"
                        class="arc-btn">
                        Open Camera
                    </a>
                    <!--<button class="pass-btn" type="button">Account Informations</button>-->
                </div>

                <div class="faculty-container-2">

                    <h1 class="info-title">Faculty Face Data</h1>
                    <hr>
                    <div class="picture-container">
                        <div id="image-preview">
                            <?php
                            if (!empty($faceImages)) {
                                foreach ($faceImages as $imagePath) {
                                    echo '<img class="preview" src="' . htmlspecialchars($imagePath) . '" alt="Faculty Image">';
                                }
                            } else {
                                // Default image if no images are found
                                echo '<p class="preview-2">No Face Data Available</p>';

                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div id="modalOverlay" class="modal-overlay"></div>

    <!-- Upload Face Data Modal -->
    <div id="uploadModal" class="modal2">
        <div class="modal-content2">
            <span class="close" onclick="closeUploadModal()">&times;</span>
            <h2>Upload Face Data</h2>
            <p>Select 3 images to upload.</p>

            <form id="uploadForm" action="../functions/update-face-data.php" method="POST"
                enctype="multipart/form-data">
                <input type="hidden" name="rfid_no" id="rfidInput">

                <div class="image-upload-container">
                    <div class="upload-box">
                        <label for="image1">
                            <img src="../../assets/images/add-image.jpg" id="preview1" class="preview" alt="Image 1">
                        </label>
                        <input type="file" name="images[]" id="image1" accept="image/*" onchange="previewImage(1)">

                    </div>
                    <div class="upload-box">
                        <label for="image2">
                            <img src="../../assets/images/add-image.jpg" id="preview2" class="preview" alt="Image 2">
                        </label>
                        <input type="file" name="images[]" id="image2" accept="image/*" onchange="previewImage(2)">

                    </div>
                    <div class="upload-box">
                        <label for="image3">
                            <img src="../../assets/images/add-image.jpg" id="preview3" class="preview" alt="Image 3">
                        </label>
                        <input type="file" name="images[]" id="image3" accept="image/*" onchange="previewImage(3)">
                    </div>
                    <input type="hidden" name="rfid_no" id="rfidInput"
                        value="<?php echo htmlspecialchars($rfid_no); ?>">
                </div>
                <button type="submit" id="uploadBtn" disabled>Upload</button>
            </form>
        </div>
    </div>

    <!-- Archive Confirmation Modal -->
    <div id="archiveModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Archive</h2>
            <p>Are you sure you want to archive this faculty member?</p>
            <div class="modal-actions">
                <button id="confirmArchive" class="btn-confirm">Yes, Archive</button>
                <button onclick="closeArchiveModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    Delete Confirmation Modal
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this face data?</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Reset Confirmation Modal -->
    <div id="resetModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Password Reset</h2>
            <p>Are you sure you want to reset the password of this faculty member?</p>
            <div class="modal-actions">
                <button id="confirmReset" class="btn-confirm">Yes, Reset</button>
                <button onclick="closeResetModal()" class="btn-cancel">Cancel</button>
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
        }, 5000);

        // Remove message from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
</script>
<script>
    function openArchiveModal(rfidNo) {
        document.getElementById("archiveModal").style.display = "block"; // Show modal
        document.getElementById("modalOverlay").style.display = "block"; // Show dark overlay
        document.getElementById("confirmArchive").setAttribute("data-rfid", rfidNo); // Store RFID
    }

    function closeArchiveModal() {
        document.getElementById("archiveModal").style.display = "none"; // Hide modal
        document.getElementById("modalOverlay").style.display = "none"; // Hide dark overlay
    }

    // When "Yes, Archive" button is clicked, redirect to archive_faculty.php
    document.getElementById("confirmArchive").addEventListener("click", function () {
        let rfidNo = this.getAttribute("data-rfid"); // Get stored RFID
        window.location.href = `../functions/archive-faculty.php?rfid_no=${rfidNo}`; // Redirect with RFID
    });

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
        window.location.href = `../functions/delete-face-data.php?rfid_no=${rfidNo}`; // Redirect with RFID
    });

    function openResetModal(rfidNo) {
        document.getElementById("resetModal").style.display = "block"; // Show modal
        document.getElementById("modalOverlay").style.display = "block"; // Show dark overlay
        document.getElementById("confirmReset").setAttribute("data-rfid", rfidNo); // Store RFID
    }

    function closeResetModal() {
        document.getElementById("resetModal").style.display = "none"; // Hide modal
        document.getElementById("modalOverlay").style.display = "none"; // Hide dark overlay
    }

    // When "Yes, Delete" button is clicked, redirect to reset-faculty-pass.php
    document.getElementById("confirmReset").addEventListener("click", function () {
        let rfidNo = this.getAttribute("data-rfid"); // Get stored RFID
        window.location.href = `../functions/reset-faculty-pass.php?rfid_no=${rfidNo}`; // Redirect with RFID
    });
</script>
<script>
    function openUploadModal2() {
        document.getElementById("uploadModal").style.display = "block";
    }
    function closeUploadModal() {
        document.getElementById("uploadModal").style.display = "none";
    }
    function previewImage(index) {
        let input = document.getElementById("image" + index);
        let preview = document.getElementById("preview" + index);
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(input.files[0]);
        }
        checkUploadStatus();
    }
    function checkUploadStatus() {
        let images = [
            document.getElementById("image1").files.length,
            document.getElementById("image2").files.length,
            document.getElementById("image3").files.length
        ];
        document.getElementById("uploadBtn").disabled = !(images[0] && images[1] && images[2]);
    }
</script>

</html>