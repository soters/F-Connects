<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;
$section_name = '';
$dept_id = '';
$department_name = '';

// Fetch Section and Department Details
if ($section_id) {
    $query = "SELECT s.section_name, s.dept_id, d.department_name 
              FROM Sections s
              JOIN Department d ON s.dept_id = d.dept_id
              WHERE s.section_id = ?";

    $stmt = sqlsrv_query($conn, $query, [$section_id]);

    if ($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        if ($row) {
            $section_name = htmlspecialchars($row['section_name']);
            $dept_id = $row['dept_id'];
            $department_name = htmlspecialchars($row['department_name']);
        }
    }
}


// Pagination settings
$limit = 16;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of students in the selected section
$total_students = 0;
if ($section_id) {
    $sql_count = "SELECT COUNT(*) AS total FROM Students WHERE archived = 0 AND section_id = ?";
    $params_count = array($section_id);
    $stmt_count = sqlsrv_query($conn, $sql_count, $params_count);

    if ($stmt_count) {
        $row_count = sqlsrv_fetch_array($stmt_count, SQLSRV_FETCH_ASSOC);
        $total_students = (int) $row_count['total'];
    }
}

// Calculate total pages
$total_pages = ($total_students > 0) ? ceil($total_students / $limit) : 1;

// Query for Paginated Students Grid View (Only Active Students in the Selected Section)
$students = [];
if ($section_id) {
    $sql_paginated = "SELECT 
                        Students.rfid_no, 
                        Students.student_number,
                        Students.fname, 
                        Students.lname, 
                        Students.email, 
                        Students.picture_path, 
                        Students.acc_type AS role, 
                        Sections.section_name
                    FROM Students
                    LEFT JOIN Sections ON Students.section_id = Sections.section_id
                    WHERE Students.archived = 0  
                    AND Students.section_id = ?  -- Filter by section_id
                    ORDER BY Students.lname ASC, Students.fname ASC
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";

    $params = array($section_id, $offset, $limit);
    $stmt_paginated = sqlsrv_query($conn, $sql_paginated, $params);

    if ($stmt_paginated) {
        while ($row = sqlsrv_fetch_array($stmt_paginated, SQLSRV_FETCH_ASSOC)) {
            $students[] = $row;
        }
    }
}

// Query for Students Table View (Only Active Students in the Selected Section)
$students_table = [];
if ($section_id) {
    $sql_table = "SELECT 
    Students.rfid_no, 
    Students.student_number,
    Students.fname, 
    Students.lname, 
    Students.email, 
    Students.sex,  
    Students.acc_type AS student_role, 
    Sections.section_name AS student_section
FROM Students
LEFT JOIN Sections ON Students.section_id = Sections.section_id
WHERE Students.archived = 0  
AND Students.section_id = ?  
ORDER BY Students.sex ASC, Students.lname ASC, Students.fname ASC";

    $stmt_table = sqlsrv_query($conn, $sql_table, [$section_id]);

    if ($stmt_table) {
        while ($row = sqlsrv_fetch_array($stmt_table, SQLSRV_FETCH_ASSOC)) {
            $students_table[] = $row;
        }
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
</head>

<body>

    <!-- Store section_name in a hidden span for JavaScript -->
    <span id="sectionName" style="display: none;"><?= $section_name ?></span>

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
        <form id="uploadForm" action="../functions/update-sections.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="section_id" value="<?php echo $section_id; ?>">
            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Section / Edit </h1>
                    <div class="buttons">
                        <button class="create-btn-3" type="submit">Save</button>
                        <a href="admin-sections.php" class="discard-btn">Discard</a>
                    </div>
                </div>
                <div class="widget-search"></div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <a href="javascript:void(0);" class="red-btn"
                        onclick="openDeleteModal(<?= isset($_GET['section_id']) ? htmlspecialchars($_GET['section_id']) : 'null'; ?>)">
                        Delete
                    </a>
                    <!--<button class="pass-btn" type="button">Section Information</button>-->
                </div>
                <div class="faculty-container-2">
                    <h1 class="info-title-2">Section Details</h1>
                    <hr>
                    <div class="faculty-name-container">
                        <div>
                            <label for="section_name">Section Name</label>
                            <input class="name-input" type="text" id="section_name" name="section_name"
                                value="<?php echo $section_name; ?>" required>
                        </div>
                        <div>
                            <div class="faculty-name-box">
                                <label for="dept_id">Departmen</label>
                                <select id="dept_id" name="dept_id" class="name-input">
                                    <?php
                                    include '../functions/fetch-department.php';
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="faculty-container-3">
                    <h1 class="info-title-3">Students</h1>
                    <div class="widget-button">
                        <div class="action-buttons">
                            <a id="exportExcel" class="excel-btn">Excel</a>
                            <a class="pdf-btn">PDF</a>
                        </div>
                        <div class="faculty-container" style="display: none;">
                            <?php if (!empty($students)): ?>
                                <div class="faculty-grid">
                                    <?php foreach ($students as $student): ?>
                                        <div class="faculty-card" data-rfid="<?= htmlspecialchars($student['rfid_no']) ?>">
                                            <img src="<?= htmlspecialchars($student['picture_path']) ?>" alt="Student Image"
                                                class="faculty-img">
                                            <div class="faculty-info">
                                                <h3><?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?></h3>
                                                <p class="email"><?= htmlspecialchars($student['email']) ?></p>
                                                <p class="section">
                                                    <?= htmlspecialchars($student['section_name'] ?: 'No Section Assigned') ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div id="no-data-message-3">
                                    <img class="no-data-image-3" src="../../assets/images/data-not-found.png"
                                        alt="No Data Found">
                                    <h1 class="not-found-message-3">No Student in here....</h1>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="faculty-table-container" id="faculty-table">
                            <?php if (!empty($students_table)): ?>
                                <div id="studentTable" class="custom-table-2">
                                    <div class="custom-table-header">
                                        <div class="custom-table-cell">Student No.</div>
                                        <div class="custom-table-cell">Student Name</div>
                                        <div class="custom-table-cell">Email</div>
                                        <div class="custom-table-cell">Gender</div>
                                    </div>
                                    <div class="custom-table-body">
                                        <?php foreach ($students_table as $student): ?>
                                            <div class="custom-table-row">
                                                <div class="custom-table-cell">
                                                    <?= htmlspecialchars($student['student_number']) ?>
                                                </div>
                                                <div class="custom-table-cell">
                                                    <?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?>
                                                </div>
                                                <div class="custom-table-cell email-cell"
                                                    title="<?= htmlspecialchars($student['email']) ?>">
                                                    <?= htmlspecialchars($student['email']) ?>
                                                </div>
                                                <div class="custom-table-cell">
                                                    <?= htmlspecialchars($student['sex']) ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div id="no-data-message-3">
                                    <img class="no-data-image-3" src="../../assets/images/data-not-found.png"
                                        alt="No Data Found">
                                    <h1 class="not-found-message-3">No Students in here...</h1>
                                </div>
                            <?php endif; ?>
                        </div>
                        <table id="studentTables" class="custom-table-2" style="display: none;">
                            <thead class="custom-table-header-2">
                                <tr>
                                    <th class="custom-table-cell">Student No.</th>
                                    <th class="custom-table-cell">Student Name</th>
                                    <th class="custom-table-cell">Email</th>
                                    <th class="custom-table-cell">Gender</th>
                                </tr>
                            </thead>
                            <tbody class="custom-table-body">
                                <?php foreach ($students_table as $student): ?>
                                    <tr class="custom-table-row">
                                        <td class="custom-table-cell"><?= htmlspecialchars($student['student_number']) ?>
                                        </td>
                                        <td class="custom-table-cell">
                                            <?= htmlspecialchars($student['fname'] . ' ' . $student['lname']) ?>
                                        </td>
                                        <td class="custom-table-cell"><?= htmlspecialchars($student['email']) ?></td>
                                        <td class="custom-table-cell"><?= htmlspecialchars($student['sex']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
            <p>Are you sure you want to delete this section?</p>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>



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
        window.location.href = `../functions/delete-sections.php?section_id=${rfidNo}`; // Redirect with RFID
    });
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        var noDataMessage = document.getElementById("no-data-message-3");
        var studentTitle = document.querySelector(".info-title-3");
        var actionButtons = document.querySelector(".action-buttons");

        if (noDataMessage && window.getComputedStyle(noDataMessage).display !== "none") {
            // Hide the title, buttons, and <hr> if "No Data Found" is visible
            if (studentTitle) studentTitle.style.display = "none";
            if (actionButtons) actionButtons.style.display = "none";
            if (divider) divider.style.display = "none";
        }
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let exportExcelButton = document.getElementById("exportExcel");
        if (exportExcelButton) {
            exportExcelButton.addEventListener("click", function () {
                let table = document.getElementById("studentTables"); // Ensure the correct ID

                // Debugging: Check if table exists
                if (!table) {
                    console.error("Error: Table with ID 'studentTable' not found.");
                    alert("Table not found!");
                    return;
                }

                // Debugging: Check table content
                let rows = table.querySelectorAll("tr");
                if (rows.length === 0) {
                    console.error("Error: Table is empty.");
                    alert("Table is empty!");
                    return;
                }

                // Convert table to Excel
                let sheet = XLSX.utils.table_to_sheet(table);
                let workbook = XLSX.utils.book_new();
                XLSX.utils.book_append_sheet(workbook, sheet, "Students");

                // Get section name from the hidden span
                let sectionName = document.getElementById("sectionName")?.innerText.trim() || "Section";

                // Replace spaces with underscores for a safe filename
                let fileName = `${sectionName.replace(/\s+/g, '_')}_Students.xlsx`;

                XLSX.writeFile(workbook, fileName);
            });
        }
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        let exportPDFButton = document.querySelector(".pdf-btn");
        if (exportPDFButton) {
            exportPDFButton.addEventListener("click", function () {
                const { jsPDF } = window.jspdf;
                let doc = new jsPDF();

                if (typeof doc.autoTable !== "function") {
                    console.error("Error: autoTable plugin not loaded. Check script imports.");
                    return;
                }

                let table = document.querySelector(".custom-table-2");
                if (!table) {
                    alert("Error: No student table found.");
                    return;
                }

                let rows = table.querySelectorAll(".custom-table-body .custom-table-row");

                let data = [];
                data.push(["Student No.", "Student Name", "Email", "Gender"]); // Table headers

                rows.forEach(row => {
                    let cells = row.querySelectorAll(".custom-table-cell");
                    let rowData = Array.from(cells).map(cell => cell.innerText);
                    data.push(rowData);
                });

                let sectionName = document.getElementById("sectionName")?.innerText.trim() || "BSIT 1-1";
                let fileName = `${sectionName.replace(/\s+/g, '_')}_Students.pdf`;

                // === HEADER ===
                doc.setFont("times", "bold");
                doc.setFontSize(18);
                doc.text("COLEGIO DE STA. TERESA DE AVILA", 105, 15, null, null, "center");

                doc.setFont("helvetica", "normal");
                doc.setFontSize(10);
                doc.text("6 Kingfisher St. cor. Skylark St., Zabarte Subd., Novaliches, Quezon City", 105, 20, null, null, "center");

                doc.setFont("helvetica", "bold");
                doc.setFontSize(16);
                doc.text("COLLEGE OF INFORMATION TECHNOLOGY", 105, 30, null, null, "center");

                doc.setFillColor(0, 0, 0); // Black bar
                doc.rect(15, 35, 180, 10, 'F');
                doc.setFontSize(14);
                doc.setTextColor(255, 255, 255);
                doc.text(sectionName, 105, 42, null, null, "center");

                doc.setTextColor(0, 0, 0); // Reset to black
                doc.setFont("helvetica", "bold");
                doc.setFontSize(10);
                doc.text("AY: 2024-2025", 105, 50, null, null, "center");
                doc.text("TERM : FIRST SEMESTER", 105, 55, null, null, "center");

                // === Generated Date ===
                let today = new Date();
                let generatedDate = today.toLocaleDateString('en-PH', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                doc.setFont("helvetica", "normal");
                doc.setFontSize(9);
                doc.text(`Generated on: ${generatedDate}`, 15, 63);

                // === STUDENT TABLE ===
                doc.autoTable({
                    startY: 68,
                    head: [data[0]],
                    body: data.slice(1),
                    theme: "grid", // Adds borders to the table
                    headStyles: {
                        fillColor: [255, 255, 255], // White background
                        textColor: [0, 0, 0],       // Black text
                        fontStyle: 'bold',
                        lineWidth: 0.1,             // Light border lines
                        lineColor: [0, 0, 0],
                        halign: 'center',           // Center align text
                        valign: 'middle',           // Vertically center text
                        cellPadding: 4             // Adjust padding for the header
                    },
                    bodyStyles: {
                        textColor: [0, 0, 0],
                        lineWidth: 0.1,
                        lineColor: [0, 0, 0]
                    },
                    styles: {
                        fontSize: 10,
                        halign: 'left'
                    }
                });


                // === FOOTER (LEFT SIDE) ===
                let finalY = doc.lastAutoTable.finalY + 20;

                doc.setFont("helvetica", "normal");
                doc.setFontSize(11);
                doc.text("Prepared by:", 15, finalY);

                doc.setFont("helvetica", "bold");
                doc.text("HAROLD R. LUCERO, MIT", 22, finalY + 10);

                // Line under name (adjusted width for left alignment)
                let lineWidth = 60;
                doc.setLineWidth(0.5);
                doc.line(15, finalY + 12, 15 + lineWidth, finalY + 12);

                doc.setFont("helvetica", "normal");
                doc.setFontSize(11);
                doc.text("Dean", 40, finalY + 18);

                // Save PDF
                doc.save(fileName);
            });
        }
    });
</script>

</html>