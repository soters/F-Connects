<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Query for Faculty Grid View (Only Active Faculty)
$sql_grid = "SELECT 
                Faculty.rfid_no,  -- Added RFID number
                Faculty.fname, 
                Faculty.lname, 
                Faculty.email, 
                Faculty.picture_path, 
                Faculty.acc_type AS position, 
                Department.department_name
            FROM Faculty
            JOIN UserDepartment ON Faculty.rfid_no = UserDepartment.rfid_no
            JOIN Department ON UserDepartment.dept_id = Department.dept_id
            WHERE Faculty.archived = 0  -- Only fetch active faculty
            ORDER BY Faculty.lname ASC, Faculty.fname ASC";

$stmt_grid = sqlsrv_query($conn, $sql_grid);
if ($stmt_grid === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Query for Faculty Table View (Only Active Faculty)
$sql_table = "SELECT 
                Faculty.rfid_no,  -- Added RFID number
                Faculty.fname, 
                Faculty.lname, 
                Faculty.email, 
                Faculty.acc_type AS faculty_role, 
                Department.department_name AS faculty_department
            FROM Faculty
            JOIN UserDepartment ON Faculty.rfid_no = UserDepartment.rfid_no
            JOIN Department ON UserDepartment.dept_id = Department.dept_id
            WHERE Faculty.archived = 0  -- Only fetch active faculty
            ORDER BY Faculty.lname ASC, Faculty.fname ASC";

$stmt_table = sqlsrv_query($conn, $sql_table);
if ($stmt_table === false) {
    die(print_r(sqlsrv_errors(), true));
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
            <h1 class="title-text">Faculty Members</h1>
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

        <div class="action-widgets">
            <div class="widget-button">
                <h1 class="sub-title">Actions</h1>
                <div class="buttons">
                    <a href="admin-new-faculty.php">
                        <button class="create-btn" type="button">Create
                        </button>
                    </a>
                </div>
            </div>

            <div class="widget-search">
                <div class="search-box-faculty">
                    <input type="text" id="search-input" placeholder="Search..." oninput="filterWidgets()">
                    <button class='bx bx-search'></button>
                </div>
                <div class="dropdown-sort">
                    <select id="sort-dropdown" onchange="sortWidgets()">
                        <option value="" disabled selected>Filters</option>
                        <option value="position-asc">Position (A-Z)</option>
                        <option value="position-desc">Position (Z-A)</option>
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                    </select>
                </div>
                <button class="toggle-view-btn" id="toggle-view-btn" onclick="toggleView()">View as Table</button>
            </div>
        </div>

        <!-- 🔥 Message Box -->
        <div id="messageBox" class="message-box"></div>
        <div class="faculty-container">
            <div class="faculty-grid">
                <?php while ($faculty = sqlsrv_fetch_array($stmt_grid, SQLSRV_FETCH_ASSOC)): ?>
                    <div class="faculty-card" data-rfid="<?= htmlspecialchars($faculty['rfid_no']) ?>">
                        <img src="<?= htmlspecialchars($faculty['picture_path']) ?>" alt="Faculty Image"
                            class="faculty-img">
                        <div class="faculty-info">
                            <h3><?= htmlspecialchars($faculty['fname'] . ' ' . $faculty['lname']) ?></h3>
                            <p class="email"><?= htmlspecialchars($faculty['email']) ?></p>
                            <p class="language"><?= htmlspecialchars($faculty['position']) ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div id="no-data-message" style="display: none;">
                <img class="no-data-image" src="../../assets/images/data-not-found.png" alt="No Data Found">
                <h1 class="not-found-message">No Data found.</h1>
            </div>
        </div>

        <div class="faculty-table-container" id="faculty-table" style="display: none;">
            <div class="custom-table">
                <div class="custom-table-header">
                    <div class="custom-table-cell">Faculty Name</div>
                    <div class="custom-table-cell">Email</div>
                    <div class="custom-table-cell">Position</div>
                    <div class="custom-table-cell">Actions</div> <!-- New Actions column -->
                </div>
                <div class="custom-table-body">
                    <?php while ($faculty = sqlsrv_fetch_array($stmt_table, SQLSRV_FETCH_ASSOC)): ?>
                        <div class="custom-table-row">
                            <div class="custom-table-cell">
                                <?= htmlspecialchars($faculty['fname'] . ' ' . $faculty['lname']) ?>
                            </div>
                            <div class="custom-table-cell">
                                <?= htmlspecialchars($faculty['email']) ?>
                            </div>
                            <div class="custom-table-cell">
                                <?= htmlspecialchars($faculty['faculty_role']) ?>
                            </div>
                            <div class="custom-table-cell">
                                <!-- Action buttons inline -->
                                <!-- Archive Button (Opens Modal) -->
                                <button class="action-btn archive-btn"
                                    onclick="openArchiveModal('<?= htmlspecialchars($faculty['rfid_no']) ?>')">
                                    <i class="fas fa-archive"></i> <!-- Archive icon -->
                                </button>
                                <!--<button class="action-btn delete-btn"
                                    onclick="openDeleteModal(<?= htmlspecialchars($faculty['rfid_no']) ?>)">
                                    <i class="fas fa-trash"></i> 
                                </button>-->
                                <button class="action-btn update-btn"
                                    onclick="updateFaculty(<?= htmlspecialchars($faculty['rfid_no']) ?>)">
                                    <i class="fas fa-edit"></i> <!-- Edit icon -->
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div id="no-data-message-2" style="display: none;">
                <img class="no-data-image" src="../../assets/images/data-not-found.png" alt="No Data Found">
                <h1 class="not-found-message">No Data found.</h1>
            </div>
        </div>
    </div>

    <!-- Dark background overlay -->
    <div id="modalOverlay" class="modal-overlay"></div>

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

    <!-- Delete Confirmation Modal 
    <div id="deleteModal" class="custom-modal">
        <div class="modal-content">
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete this faculty member?</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div> -->

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
    function filterWidgets() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase();

        // Filter Grid View
        const facultyCards = document.querySelectorAll('.faculty-card');
        let found = false;

        facultyCards.forEach(card => {
            const facultyName = card.querySelector('h3').textContent.toLowerCase();
            const facultyEmail = card.querySelector('.email').textContent.toLowerCase();

            if (facultyName.includes(searchTerm) || facultyEmail.includes(searchTerm)) {
                card.style.display = ''; // Show the card
                found = true;
            } else {
                card.style.display = 'none'; // Hide the card
            }
        });

        // Filter Table View
        const tableRows = document.querySelectorAll('.custom-table-row');
        let tableFound = false;

        tableRows.forEach(row => {
            const facultyName = row.querySelector('.custom-table-cell:nth-child(2)').textContent.toLowerCase();
            const facultyEmail = row.querySelector('.custom-table-cell:nth-child(3)').textContent.toLowerCase();

            if (facultyName.includes(searchTerm) || facultyEmail.includes(searchTerm)) {
                row.style.display = ''; // Show the row
                tableFound = true;
            } else {
                row.style.display = 'none'; // Hide the row
            }
        });

        // Show or hide the "No Data" message based on the table contents
        const noDataMessage = document.getElementById('no-data-message');
        const noDataMessage2 = document.getElementById('no-data-message-2');
        const customTable = document.querySelector('.custom-table');

        if (!found) {
            noDataMessage.style.display = 'block'; // Show grid view no data message
        } else {
            noDataMessage.style.display = 'none'; // Hide grid view no data message
        }

        if (!tableFound) {
            noDataMessage2.style.display = 'block'; // Show table view no data message
            customTable.style.display = 'none'; // Hide the table
        } else {
            noDataMessage2.style.display = 'none'; // Hide table view no data message
            customTable.style.display = 'block'; // Show the table
        }
    }

    function sortWidgets() {
        const sortOption = document.getElementById('sort-dropdown').value;

        // Sort Grid View
        const facultyCards = Array.from(document.querySelectorAll('.faculty-card'));

        facultyCards.sort((a, b) => {
            const nameA = a.querySelector('h3').textContent.toLowerCase();
            const nameB = b.querySelector('h3').textContent.toLowerCase();
            const positionA = a.querySelector('.faculty-info p.language').textContent.toLowerCase();
            const positionB = b.querySelector('.faculty-info p.language').textContent.toLowerCase();

            switch (sortOption) {
                case 'position-asc':
                    return positionA.localeCompare(positionB);
                case 'position-desc':
                    return positionB.localeCompare(positionA);
                case 'name-asc':
                    return nameA.localeCompare(nameB);
                case 'name-desc':
                    return nameB.localeCompare(nameA);
                default:
                    return 0;
            }
        });

        // Re-append sorted cards to the container (Grid View)
        const facultyGrid = document.querySelector('.faculty-grid');
        facultyCards.forEach(card => {
            facultyGrid.appendChild(card);
        });

        // Sort Table View
        const tableRows = Array.from(document.querySelectorAll('.custom-table-row'));

        tableRows.sort((a, b) => {
            const nameA = a.querySelector('.custom-table-cell:nth-child(1)').textContent.toLowerCase();
            const nameB = b.querySelector('.custom-table-cell:nth-child(1)').textContent.toLowerCase();
            const positionA = a.querySelector('.custom-table-cell:nth-child(3)').textContent.toLowerCase();
            const positionB = b.querySelector('.custom-table-cell:nth-child(3)').textContent.toLowerCase();

            switch (sortOption) {
                case 'position-asc':
                    return positionA.localeCompare(positionB);
                case 'position-desc':
                    return positionB.localeCompare(positionA);
                case 'name-asc':
                    return nameA.localeCompare(nameB);
                case 'name-desc':
                    return nameB.localeCompare(nameA);
                default:
                    return 0;
            }
        });

        // Re-append sorted rows to the table (Table View)
        const tableBody = document.querySelector('.custom-table-body');
        tableRows.forEach(row => {
            tableBody.appendChild(row);
        });
    }
</script>
<script>
    function toggleView() {
        const gridView = document.querySelector('.faculty-container');
        const tableView = document.getElementById('faculty-table');
        const toggleButton = document.getElementById('toggle-view-btn');

        if (gridView.style.display === 'none') {
            gridView.style.display = 'block';
            tableView.style.display = 'none';
            toggleButton.textContent = 'View as Table';
        } else {
            gridView.style.display = 'none';
            tableView.style.display = 'block';
            toggleButton.textContent = 'View as Grid';
        }
    }
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

    /**function openDeleteModal(rfidNo) {
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
        window.location.href = `../functions/delete-faculty.php?rfid_no=${rfidNo}`; // Redirect with RFID
    }); **/
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
    function updateFaculty(rfid_no) {
        // Redirect to the update page with rfid_no as a query parameter
        window.location.href = `admin-update-faculty.php?rfid_no=${rfid_no}`;
    }
</script>
<script>
    document.querySelectorAll('.faculty-card').forEach(card => {
        card.addEventListener('click', () => {
            const rfidNo = card.getAttribute('data-rfid');
            window.location.href = `admin-update-faculty.php?rfid_no=${rfidNo}`;
        });
    });
</script>

</html>