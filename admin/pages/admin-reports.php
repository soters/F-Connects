<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

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
                        FROM AttendanceToday 
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
    <style>
        /* Modal styling with Poppins font */
        .modal-1 {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            font-family: 'Poppins', sans-serif;
            /* Added Poppins */
        }

        .modal-content-1 {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border-radius: 5px;
            width: 80%;
            max-width: 400px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            font-family: 'Poppins', sans-serif;
            /* Added Poppins */
        }

        .close-modal-1 {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            /* Added Poppins */
        }

        .close-modal-1:hover {
            /* Fixed missing dot */
            color: black;
        }

        .close-summary-modal {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            /* Added Poppins */
        }

        .close-summary-modal-1:hover {
            /* Fixed missing dot */
            color: black;
        }

        #exportModalMessage {
            text-align: center;
            padding: 20px 0;
            font-size: 15px;
            color: #333;
            font-family: 'Poppins', sans-serif;
            /* Added Poppins */
        }

        /* Make success messages bold (matches your JS) */
        #exportModalMessage strong {
            font-weight: 600;
            /* Poppins semi-bold */
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
                <i class="fas bi-arrow-left-short"></i>
                    <span>To Dashboard</span>
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
            <h1 class="title-text">Reports</h1>

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
            <div class="navbar-1">
                <a href="admin-manage.php" class="nav-link" onclick="setActive(this)">Admins</a>
                <a href="admin-reports.php" class="nav-link active" onclick="setActive(this)">Reports</a>
                <a href="admin-locations.php" class="nav-link" onclick="setActive(this)">Room</a>
                <a href="../../kiosk/kiosk-index.php" class="nav-link" onclick="setActive(this)">Go to Kiosk</a>
            </div>
        </div>

        <div id="messageBox" class="message-box"></div>
        <div class="faculty-table-container" id="faculty-table">
            <div class="custom-table">
                <div class="custom-table-header">
                    <div class="custom-table-cell">Report Name</div>
                    <div class="custom-table-cell">Action</div>
                </div>
                <div class="custom-table-body">
                    <div class="custom-table-row">
                        <div class="custom-table-cell">Attendance Records</div>
                        <div class="custom-table-cell"> <button class="abtn" type="button" id="openModal">Generate
                                Attendance Report</button></div>
                    </div>
                    <div class="custom-table-row">
                        <div class="custom-table-cell">Attendance Summary / All </div>
                        <div class="custom-table-cell"> <button class="wbtn" type="button"
                                id="openSummaryModal">Generate
                                Summary Report</button></div>
                    </div>
                    <div class="custom-table-row">
                        <div class="custom-table-cell">Appointment Records</div>
                        <div class="custom-table-cell"> <button class="gabtn-red" id="openAppointmentModal">Generate
                                Appointment Report</button></div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()">↑</button>

    <div id="customModal1" class="modal-overlays">
        <div class="modal-container1">
            <div class="modal-header1">
                <h3>Attendance Records</h3>
                <span class="close-modal1">&times;</span>
            </div>
            <div class="modal-body1">
                <label for="facultySelect">Select Faculty:</label>
                <select id="facultySelect" name="rfid_no" class="form-select">
                    <?php include '../functions/fetch-faculty.php'; ?>
                </select>

                <label for="reportType" class="mt-3">Select Report Type:</label>
                <select class="form-select" id="reportType">
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom Date Range</option> <!-- New Option -->
                </select>

                <div id="extraFields" class="mt-3"></div> <!-- Dynamic Fields -->

                <button class="generate-btn mt-3" id="generatePdf">Generate Report</button>
            </div>
        </div>
    </div>

    <!-- New Summary Modal -->
    <div id="summaryModal" class="modal-overlays">
        <div class="modal-container1">
            <div class="modal-header1">
                <h3>Attendance Summary</h3>
                <span class="close-summary-modal">&times;</span>
            </div>
            <div class="modal-body1">
                <label for="summaryReportType" class="mt-3">Select Report Type:</label>
                <select class="form-select" id="summaryReportType">
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom Date Range</option>
                </select>

                <div id="summaryExtraFields" class="mt-3"></div>

                <button class="generate-btn mt-3" id="generateSummaryPdf">Generate Summary</button>
            </div>
        </div>
    </div>

    <!-- Appointment Modal -->
    <div id="appointmentModal" class="modal-overlays">
        <div class="modal-container1">
            <div class="modal-header1">
                <h3>Generate Appointment Report</h3>
                <span class="close-appointment-modal">&times;</span>
            </div>
            <div class="modal-body1">

                <label for="facultySelector">Select Faculty:</label>
                <select id="facultySelector" name="rfid_no" class="form-select">
                    <?php include '../functions/fetch-faculty.php'; ?>
                </select>

                <label for="appointmentReportType">Select Report Type:</label>
                <select class="form-select" id="appointmentReportType">
                    <option value="daily">Daily</option>
                    <option value="monthly">Monthly</option>
                    <option value="yearly">Yearly</option>
                    <option value="custom">Custom Date Range</option>
                </select>

                <div id="appointmentExtraFields" class="mt-3"></div> <!-- Dynamic Fields -->

                <button class="generate-btn" id="generateAppointmentPdf">Generate Report</button>
            </div>
        </div>
    </div>
 
</body>

<div id="exportModal" class="modal-1" style="display:none;">
    <div class="modal-content-1">
        <span class="close-modal-1"></span>
        <div id="exportModalMessage"></div>
    </div>
</div>

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
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const appointmentModal = document.getElementById("appointmentModal");
        const openAppointmentModal = document.getElementById("openAppointmentModal");
        const closeAppointmentModal = document.querySelector(".close-appointment-modal");
        const appointmentReportType = document.getElementById("appointmentReportType");
        const appointmentExtraFields = document.getElementById("appointmentExtraFields");
        const generateAppointmentPdf = document.getElementById("generateAppointmentPdf");
        const facultySelector = document.getElementById("facultySelector");

        // Open & Close Modal
        openAppointmentModal?.addEventListener("click", () => appointmentModal.style.display = "flex");
        closeAppointmentModal?.addEventListener("click", () => appointmentModal.style.display = "none");
        window.addEventListener("click", (event) => {
            if (event.target === appointmentModal) appointmentModal.style.display = "none";
        });

        // Handle report type selection
        appointmentReportType.addEventListener("change", function () {
            appointmentExtraFields.innerHTML = "";
            let selectedType = this.value;

            if (selectedType === "daily") {
                appointmentExtraFields.innerHTML = '<label>Select Date:</label><input type="date" id="appointmentReportDate" class="form-control">';
                generateAppointmentPdf.setAttribute("data-type", "daily");

            } else if (selectedType === "monthly") {
                appointmentExtraFields.innerHTML = `
                <label>Select Month:</label>
                <input type="month" id="appointmentReportMonth" class="form-control">
            `;
                generateAppointmentPdf.setAttribute("data-type", "monthly");

            } else if (selectedType === "yearly") {
                appointmentExtraFields.innerHTML = `
                <label>Select Year:</label>
                <select id="appointmentReportYear" class="form-select">
                    ${generateYearOptions()}
                </select>
            `;
                generateAppointmentPdf.setAttribute("data-type", "yearly");

            } else if (selectedType === "custom") {
                appointmentExtraFields.innerHTML = `
                <label>Start Date:</label>
                <input type="date" id="appointmentStartDate" class="form-control mb-2">
                <label>End Date:</label>
                <input type="date" id="appointmentEndDate" class="form-control">
            `;
                generateAppointmentPdf.setAttribute("data-type", "custom");
            }
        });

        // Function to generate year options dynamically
        function generateYearOptions() {
            let currentYear = new Date().getFullYear();
            let options = '<option value="" disabled selected>Select a Year</option>';
            for (let year = currentYear; year >= currentYear - 10; year--) {
                options += `<option value="${year}">${year}</option>`;
            }
            return options;
        }

        // Call the correct function based on report type
        generateAppointmentPdf.addEventListener("click", function () {
            const facultyId = facultySelector.value;
            if (!facultyId) {
                showModal("Please select a faculty member.", false);
                return;
            }

            let reportType = this.getAttribute("data-type");
            if (reportType === "daily") {
                generateAppointmentDailyReport(facultyId);
            } else if (reportType === "monthly") {
                generateAppointmentMonthlyReport(facultyId);
            } else if (reportType === "yearly") {
                generateAppointmentYearlyReport(facultyId);
            } else if (reportType === "custom") {
                generateAppointmentCustomReport(facultyId);
            } else {
                showModal("Please select a valid report type.", false);
            }
        });

        // ------------------------ APPOINTMENT DAILY REPORT FUNCTION ------------------------
        function generateAppointmentDailyReport(facultyId) {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let dateInput = document.getElementById("appointmentReportDate")?.value;

            if (!dateInput) {
                showModal("Please select a valid date.", false);
                return;
            }

            showModal("Generating report... Please wait.", true);

            fetch(`../functions/fetch-appointment-by-date.php?date=${dateInput}&faculty=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        showModal("No appointments found for this date.", false);
                        return;
                    }

                    let formattedDate = new Date(dateInput).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    addReportHeaderApt(doc, "Appointment Report", `Daily Report - ${formattedDate}`);

                    // Table data
                    let tableData = [
                        ["FACULTY", "Student", "Time", "Agenda", "Status"]
                    ];

                    data.appointments.forEach(appointment => {
                        let timeRange = `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`;
                        tableData.push([
                            `${appointment.fname || ""} ${appointment.lname || ""}`.trim(),
                            `${appointment.stud_fname || ""} ${appointment.stud_lname || ""}`.trim(),
                            timeRange,
                            appointment.agenda || "N/A",
                            appointment.status || "N/A"
                        ]);
                    });

                    doc.autoTable({
                        startY: 68,
                        head: [tableData[0]],
                        body: tableData.slice(1),
                        theme: "grid",
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.2,
                            lineColor: [0, 0, 0]  // Black border for header
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.2,
                            lineColor: [0, 0, 0]  // Black border for body
                        },
                        styles: {
                            fontSize: 8.5,
                            lineColor: [0, 0, 0]  // Ensures all other lines are black too
                        },
                        tableLineColor: [0, 0, 0],  // Main table borders
                        tableLineWidth: 0.2         // Border width
                    });
                    let currentY = doc.autoTable.previous.finalY + 15;

                    // Status Summary Table
                    if (data.statusCounts && data.statusCounts.length > 0) {
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text("Status Summary", 14, currentY);
                        currentY += 7;

                        let statusTableData = [["Status", "Count"]];
                        data.statusCounts.forEach(item => {
                            statusTableData.push([item.status, item.count]);
                        });
                        doc.autoTable({
                            startY: currentY,
                            head: [statusTableData[0]],  // Header row
                            body: statusTableData.slice(1),  // Data rows
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]  // Black border for header
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],  // White background
                                textColor: [0, 0, 0],        // Black text
                                halign: 'left',               // Left-aligned
                                lineWidth: 0.2,               // Border width
                                lineColor: [0, 0, 0]          // Black border for body
                            },
                            styles: {
                                fontSize: 8.5,                // Consistent font size
                                lineColor: [0, 0, 0]          // Black lines everywhere
                            },
                            columnStyles: {
                                0: { cellWidth: 60 },  // Status column width
                                1: { cellWidth: 30 }   // Count column width
                            }
                        });
                        currentY = doc.autoTable.previous.finalY + 15;
                    }

                    // Agenda Summary Table
                    if (data.agendaCounts && data.agendaCounts.length > 0) {
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text("Agenda Summary", 14, currentY);
                        currentY += 7;

                        let agendaTableData = [["Agenda", "Count"]];
                        data.agendaCounts.forEach(item => {
                            agendaTableData.push([item.agenda, item.count]);
                        });

                        doc.autoTable({
                            startY: currentY,
                            head: [agendaTableData[0]],  // Header row
                            body: agendaTableData.slice(1),  // Data rows
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]  // Black border for header
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],  // White background
                                textColor: [0, 0, 0],        // Black text
                                halign: 'left',               // Left-aligned
                                lineWidth: 0.2,               // Border width
                                lineColor: [0, 0, 0]          // Black border for body
                            },
                            styles: {
                                fontSize: 8.5,                // Consistent font size
                                lineColor: [0, 0, 0]          // Black lines everywhere
                            },
                            columnStyles: {
                                0: { cellWidth: 100 },  // Agenda column (wider)
                                1: { cellWidth: 30 }    // Count column
                            }
                        });
                        currentY = doc.autoTable.previous.finalY + 10;
                    }

                    // Add Grand Total after the summary tables
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Total Appointments: ${data.grandTotal || 0}`, 14, currentY);
                    currentY += 10;

                    // Add footer
                    addReportFooter(doc, currentY);

                    // Save PDF
                    const fileName = `Appointment_Daily_Report_${dateInput}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style='color: #333; font-weight: 600;'>Appointment Report Generated Successfully!</strong>
                <br><br>Daily report for <i>${formattedDate}</i> has been DOWNLOADED.
                <br><br>Total Appointments: <strong>${data.grandTotal || 0}</strong>
                <br><br>File: <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    showModal("Error generating report. Please try again.", false);
                });
        }

        // ------------------------ APPOINTMENT MONTHLY REPORT FUNCTION ------------------------
        function generateAppointmentMonthlyReport(facultyId) {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            // Get input values
            let selectedMonth = document.getElementById("appointmentReportMonth")?.value;
            let facultyName = facultySelector?.options[facultySelector?.selectedIndex]?.text || "All Faculty";

            // Validate inputs
            if (!selectedMonth) {
                showModal("Please select a month.", false);
                return;
            }
            if (!facultyId) {
                showModal("Please select a faculty member.", false);
                return;
            }

            // Extract year and month
            const [selectedYear, selectedMonthOnly] = selectedMonth.split("-");
            if (!selectedYear || !selectedMonthOnly) {
                showModal("Invalid month format. Expected format: YYYY-MM", false);
                return;
            }

            showModal(`Generating monthly report for ${facultyName}...`, true);

            fetch(`../functions/fetch-appointment-by-month.php?month=${selectedMonth}&faculty=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        showModal(`No appointments found for ${facultyName} in the selected month.`, false);
                        return;
                    }

                    // Format month display
                    const formattedMonth = new Date(selectedYear, selectedMonthOnly - 1).toLocaleDateString('en-PH', {
                        month: 'long',
                        year: 'numeric'
                    });

                    // Group appointments by date
                    let groupedAppointments = {};
                    data.appointments.forEach(appointment => {
                        let date = appointment.date_logged;
                        if (!groupedAppointments[date]) {
                            groupedAppointments[date] = [];
                        }
                        groupedAppointments[date].push(appointment);
                    });

                    // Main report pages
                    let currentY = 70; // Initial Y position for first page
                    let pageCount = 1;

                    // Add header only to first page
                    const reportTitle = `Monthly Report - ${formattedMonth} (${facultyName})`;
                    addReportHeaderApt(doc, "Appointment Report", reportTitle);

                    // First process all daily appointment tables
                    Object.keys(groupedAppointments).forEach((date) => {
                        // Check if we need a new page before adding content
                        const estimatedHeight = 10 + (groupedAppointments[date].length * 8);
                        if (currentY + estimatedHeight > doc.internal.pageSize.height - 20) {
                            doc.addPage();
                            currentY = 20;
                            pageCount++;
                        }

                        // Date header
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Appointments for ${date}`, 14, currentY);
                        currentY += 5;

                        // Table data - using initials for student names
                        let tableData = [
                            ["STUDENT", "TIME", "AGENDA", "STATUS"]
                        ];

                        groupedAppointments[date].forEach(appointment => {
                            // Format student name as "J. Diocena"
                            const studentName = appointment.stud_fname && appointment.stud_lname
                                ? `${appointment.stud_fname.substring(0, 1)}. ${appointment.stud_lname}`
                                : "N/A";

                            tableData.push([
                                studentName,
                                `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`,
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        // Main table with optimized column widths
                        doc.autoTable({
                            startY: currentY,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5,
                                lineColor: [0, 0, 0]
                            },
                            tableLineColor: [0, 0, 0],
                            tableLineWidth: 0.2,
                            margin: { left: 15, right: 15 },
                            columnStyles: {
                                0: { cellWidth: 30 },  // Student (initials)
                                1: { cellWidth: 'auto' },  // Time (now fits in one line)
                                2: { cellWidth: 60 },  // Agenda
                                3: { cellWidth: 25 }   // Status
                            },
                            pageBreak: 'avoid'
                        });

                        currentY = doc.autoTable.previous.finalY + 7;
                        addPageNumbers(doc, pageCount);
                    });

                    // Now add the summary section after all daily tables
                    // Check if we need a new page for the summary
                    if (currentY > doc.internal.pageSize.height - 100) {
                        doc.addPage();
                        currentY = 20;
                        pageCount++;
                    }

                    // Summary title
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text("Monthly Summary", 14, currentY);
                    currentY += 10;

                    // Status Summary Table
                    if (data.statusCounts && data.statusCounts.length > 0) {
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text("Status Summary", 14, currentY);
                        currentY += 7;

                        let statusTableData = [["Status", "Count"]];
                        data.statusCounts.forEach(item => {
                            statusTableData.push([item.status, item.count]);
                        });

                        doc.autoTable({
                            startY: currentY,
                            head: [statusTableData[0]],
                            body: statusTableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5,
                                lineColor: [0, 0, 0]
                            },
                            columnStyles: {
                                0: { cellWidth: 60 },
                                1: { cellWidth: 30 }
                            }
                        });
                        currentY = doc.autoTable.previous.finalY + 15;
                    }

                    // Agenda Summary Table
                    if (data.agendaCounts && data.agendaCounts.length > 0) {
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text("Agenda Summary", 14, currentY);
                        currentY += 7;

                        let agendaTableData = [["Agenda", "Count"]];
                        data.agendaCounts.forEach(item => {
                            agendaTableData.push([item.agenda.substring(0, 25), item.count]);
                        });

                        doc.autoTable({
                            startY: currentY,
                            head: [agendaTableData[0]],
                            body: agendaTableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5,
                                lineColor: [0, 0, 0]
                            },
                            columnStyles: {
                                0: { cellWidth: 100 },
                                1: { cellWidth: 30 }
                            }
                        });
                        currentY = doc.autoTable.previous.finalY + 15;
                    }

                    // Grand Total
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Total Appointments: ${data.grandTotal || 0}`, 14, currentY);
                    currentY += 10;

                    // Add footer
                    addReportFooter(doc, currentY);

                    // Save PDF
                    const fileName = `Appointments_${selectedMonth}_${facultyName.replace(/\s+/g, '_')}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Report Generated!</strong><br>
                ${formattedMonth} (${facultyName})<br>
                Total: <strong>${data.grandTotal || 0}</strong> appointments<br>
                <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(error => {
                    console.error("Error:", error);
                    showModal("Error generating report. Please try again.", false);
                });
        }

        // Helper function to format time (assuming this exists)
        function formatTime(timeString) {
            if (!timeString) return "N/A";
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return `${hour12}:${minutes} ${ampm}`;
        }

        // Helper function to add page numbers (assuming this exists)
        function addPageNumbers(doc, pageCount) {
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text(`Page ${i} of ${pageCount}`, doc.internal.pageSize.width - 25, doc.internal.pageSize.height - 10);
            }
        }

        // Helper function to add report header (assuming this exists)
        function addReportHeaderApt(doc, title, subtitle) {
            doc.setFontSize(16);
            doc.setFont("helvetica", "bold");
            doc.text(title, 105, 20, { align: "center" });

            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            doc.text(subtitle, 105, 28, { align: "center" });

            // Add line separator
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(0.5);
            doc.line(20, 35, 190, 35);
        }

        // Helper function to add report footer (assuming this exists)
        function addReportFooter(doc, yPosition) {
            const currentDate = new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            doc.setFontSize(9);
            doc.setFont("helvetica", "italic");
            doc.text(`Generated on ${currentDate}`, 14, yPosition);
        }


        // ------------------------ APPOINTMENT YEARLY REPORT FUNCTION ------------------------
        function generateAppointmentYearlyReport(facultyId) {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let selectedYear = document.getElementById("appointmentReportYear")?.value;
            let facultyName = facultySelector?.options[facultySelector?.selectedIndex]?.text || "All Faculty";

            if (!selectedYear) {
                showModal("Please select a year.", false);
                return;
            }

            showModal("Generating yearly report... Please wait.", true);

            fetch(`../functions/fetch-appointment-by-year.php?year=${selectedYear}&faculty=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        showModal("No appointments found for this year.", false);
                        return;
                    }

                    // Group appointments by month
                    let groupedAppointments = {};
                    data.appointments.forEach(appointment => {
                        let month = new Date(appointment.date_logged).toLocaleString('default', { month: 'long' });
                        if (!groupedAppointments[month]) {
                            groupedAppointments[month] = [];
                        }
                        groupedAppointments[month].push(appointment);
                    });

                    let currentY = 70; // Initial Y position for first page
                    let pageCount = 1;

                    // Add header only to first page
                    const reportTitle = `Yearly Report - ${selectedYear} (${facultyName})`;
                    addReportHeaderApt(doc, "Appointment Report", reportTitle);

                    // Process all monthly appointment tables
                    Object.keys(groupedAppointments).forEach((month) => {
                        // Check remaining space before adding content
                        const estimatedHeight = 15 + (groupedAppointments[month].length * 7);
                        const remainingSpace = doc.internal.pageSize.height - currentY - 20;

                        // Only add new page if absolutely necessary
                        if (estimatedHeight > remainingSpace && currentY > 50) {
                            doc.addPage();
                            currentY = 20;
                            pageCount++;
                        }

                        // Month header
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Appointments for ${month}`, 14, currentY);
                        currentY += 7;

                        // Table data
                        let tableData = [
                            ["CODE", "STUDENT", "TIME", "AGENDA", "STATUS"]
                        ];

                        groupedAppointments[month].forEach(appointment => {
                            const studentName = appointment.stud_fname && appointment.stud_lname
                                ? `${appointment.stud_fname.substring(0, 1)}. ${appointment.stud_lname}`
                                : "N/A";

                            tableData.push([
                                appointment.appointment_code || "N/A",
                                studentName,
                                `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`,
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        // Generate table with optimized space usage
                        doc.autoTable({
                            startY: currentY,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5,
                                lineColor: [0, 0, 0]
                            },
                            tableLineColor: [0, 0, 0],
                            tableLineWidth: 0.2,
                            margin: { left: 15, right: 15 },
                            columnStyles: {
                                0: { cellWidth: 30 },  // Code
                                1: { cellWidth: 25 },  // Student
                                2: { cellWidth: 'auto' },  // Time
                                3: { cellWidth: 60 },  // Agenda
                                4: { cellWidth: 20 }   // Status
                            },
                            pageBreak: 'avoid'
                        });

                        currentY = doc.autoTable.previous.finalY + 10;
                        addPageNumbers(doc, pageCount);
                    });

                    // Add summary section on current page if space allows, otherwise new page
                    const summaryHeight = 100; // Estimated height needed for summary section
                    if (currentY + summaryHeight > doc.internal.pageSize.height - 20) {
                        doc.addPage();
                        currentY = 20;
                        pageCount++;
                    }

                    // Summary header
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text("Yearly Summary", 14, currentY);
                    currentY += 10;

                    // Status and Agenda tables side by side
                    let summaryTableStartY = currentY;
                    let leftTableHeight = 0;
                    let rightTableHeight = 0;

                    // Status Summary Table (left)
                    if (data.statusCounts && Array.isArray(data.statusCounts)) {
                        let statusTableData = [["Status", "Count"]];
                        data.statusCounts.forEach(item => {
                            if (item && item.status && item.count) {
                                statusTableData.push([item.status, item.count]);
                            }
                        });

                        if (statusTableData.length > 1) {
                            doc.setFontSize(11);
                            doc.setFont("helvetica", "bold");
                            doc.text("Status Summary", 14, summaryTableStartY - 5);

                            doc.autoTable({
                                startY: summaryTableStartY,
                                head: [statusTableData[0]],
                                body: statusTableData.slice(1),
                                theme: "grid",
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                styles: {
                                    fontSize: 8.5,
                                    lineColor: [0, 0, 0]
                                },
                                columnStyles: {
                                    0: { cellWidth: 40 },
                                    1: { cellWidth: 20 }
                                },
                                margin: { left: 14 },
                                tableWidth: 70,
                                pageBreak: 'avoid'
                            });
                            leftTableHeight = doc.autoTable.previous.finalY - summaryTableStartY;
                        }
                    }

                    // Agenda Summary Table (right)
                    if (data.agendaCounts && Array.isArray(data.agendaCounts)) {
                        let agendaTableData = [["Agenda", "Count"]];
                        data.agendaCounts.forEach(item => {
                            if (item && item.agenda && item.count) {
                                agendaTableData.push([item.agenda.substring(0, 25), item.count]);
                            }
                        });

                        if (agendaTableData.length > 1) {
                            doc.setFontSize(11);
                            doc.setFont("helvetica", "bold");
                            doc.text("Agenda Summary", 100, summaryTableStartY - 5);

                            doc.autoTable({
                                startY: summaryTableStartY,
                                head: [agendaTableData[0]],
                                body: agendaTableData.slice(1),
                                theme: "grid",
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                styles: {
                                    fontSize: 8.5,
                                    lineColor: [0, 0, 0]
                                },
                                columnStyles: {
                                    0: { cellWidth: 60 },
                                    1: { cellWidth: 20 }
                                },
                                margin: { left: 100 },
                                tableWidth: 90,
                                pageBreak: 'avoid'
                            });
                            rightTableHeight = doc.autoTable.previous.finalY - summaryTableStartY;
                        }
                    }

                    // Update currentY to the bottom of whichever table is taller
                    currentY = summaryTableStartY + Math.max(leftTableHeight, rightTableHeight) + 10;

                    // Grand Total
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Total Appointments: ${data.appointments.length}`, 14, currentY);
                    currentY += 10;

                    // Add footer
                    addReportFooter(doc, currentY);
                    addPageNumbers(doc, pageCount);

                    // Save PDF
                    const fileName = `Yearly_Appointment_Report_${selectedYear}_${facultyName.replace(/\s+/g, '_')}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Yearly Report Generated!</strong><br>
                Report for year <i>${selectedYear}</i> has been downloaded.<br>
                Total: <strong>${data.appointments.length}</strong> appointments<br>
                <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    showModal("Error generating yearly report. Please try again.", false);
                });
        }

        // ------------------------ CUSTOM DATE RANGE REPORT FUNCTION ------------------------
        function generateAppointmentCustomReport(facultyId) {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let startDate = document.getElementById("appointmentStartDate")?.value;
            let endDate = document.getElementById("appointmentEndDate")?.value;
            let facultyName = facultySelector?.options[facultySelector?.selectedIndex]?.text || "All Faculty";

            if (!startDate || !endDate) {
                showModal("Please select both start and end dates.", false);
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                showModal("Start date must be before end date.", false);
                return;
            }

            showModal("Generating custom report... Please wait.", true);

            fetch(`../functions/fetch-appointment-by-range.php?start=${startDate}&end=${endDate}&faculty=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        showModal("No appointments found for this date range.", false);
                        return;
                    }

                    // Format dates for display
                    const formattedStart = new Date(startDate).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    const formattedEnd = new Date(endDate).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    // Group appointments by date
                    let groupedAppointments = {};
                    data.appointments.forEach(appointment => {
                        let date = appointment.date_logged;
                        if (!groupedAppointments[date]) {
                            groupedAppointments[date] = [];
                        }
                        groupedAppointments[date].push(appointment);
                    });

                    let currentY = 70; // Initial Y position for first page
                    let pageCount = 1;

                    // Add header only to first page
                    const reportTitle = `Custom Report - ${formattedStart} to ${formattedEnd} (${facultyName})`;
                    addReportHeaderApt(doc, "Appointment Report", reportTitle);

                    // Process all daily appointment tables
                    Object.keys(groupedAppointments).forEach((date) => {
                        // Check remaining space before adding content
                        const estimatedHeight = 15 + (groupedAppointments[date].length * 7);
                        const remainingSpace = doc.internal.pageSize.height - currentY - 20;

                        // Only add new page if absolutely necessary
                        if (estimatedHeight > remainingSpace && currentY > 50) {
                            doc.addPage();
                            currentY = 20;
                            pageCount++;
                        }

                        // Date header
                        doc.setFontSize(11);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Appointments for ${date}`, 14, currentY);
                        currentY += 7;

                        // Table data
                        let tableData = [
                            ["CODE", "STUDENT", "TIME", "AGENDA", "STATUS"]
                        ];

                        groupedAppointments[date].forEach(appointment => {
                            const studentName = appointment.stud_fname && appointment.stud_lname
                                ? `${appointment.stud_fname.substring(0, 1)}. ${appointment.stud_lname}`
                                : "N/A";

                            tableData.push([
                                appointment.appointment_code || "N/A",
                                studentName,
                                `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`,
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        // Generate table with optimized space usage
                        doc.autoTable({
                            startY: currentY,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.2,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5,
                                lineColor: [0, 0, 0]
                            },
                            tableLineColor: [0, 0, 0],
                            tableLineWidth: 0.2,
                            margin: { left: 15, right: 15 },
                            columnStyles: {
                                0: { cellWidth: 30 },  // Code
                                1: { cellWidth: 25 },  // Student
                                2: { cellWidth: 'auto' },  // Time
                                3: { cellWidth: 60 },  // Agenda
                                4: { cellWidth: 20 }   // Status
                            },
                            pageBreak: 'avoid'
                        });

                        currentY = doc.autoTable.previous.finalY + 10;
                        addPageNumbers(doc, pageCount);
                    });

                    // Add summary section on current page if space allows, otherwise new page
                    const summaryHeight = 100; // Estimated height needed for summary section
                    if (currentY + summaryHeight > doc.internal.pageSize.height - 20) {
                        doc.addPage();
                        currentY = 20;
                        pageCount++;
                    }

                    // Summary header
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text("Custom Report Summary", 14, currentY);
                    currentY += 10;

                    // Status and Agenda tables side by side
                    let summaryTableStartY = currentY;
                    let leftTableHeight = 0;
                    let rightTableHeight = 0;

                    // Status Summary Table (left)
                    if (data.statusCounts && Array.isArray(data.statusCounts)) {
                        let statusTableData = [["Status", "Count"]];
                        data.statusCounts.forEach(item => {
                            if (item && item.status && item.count) {
                                statusTableData.push([item.status, item.count]);
                            }
                        });

                        if (statusTableData.length > 1) {
                            doc.setFontSize(11);
                            doc.setFont("helvetica", "bold");
                            doc.text("Status Summary", 14, summaryTableStartY - 5);

                            doc.autoTable({
                                startY: summaryTableStartY,
                                head: [statusTableData[0]],
                                body: statusTableData.slice(1),
                                theme: "grid",
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                styles: {
                                    fontSize: 8.5,
                                    lineColor: [0, 0, 0]
                                },
                                columnStyles: {
                                    0: { cellWidth: 40 },
                                    1: { cellWidth: 20 }
                                },
                                margin: { left: 14 },
                                tableWidth: 70,
                                pageBreak: 'avoid'
                            });
                            leftTableHeight = doc.autoTable.previous.finalY - summaryTableStartY;
                        }
                    }

                    // Agenda Summary Table (right)
                    if (data.agendaCounts && Array.isArray(data.agendaCounts)) {
                        let agendaTableData = [["Agenda", "Count"]];
                        data.agendaCounts.forEach(item => {
                            if (item && item.agenda && item.count) {
                                agendaTableData.push([item.agenda.substring(0, 25), item.count]);
                            }
                        });

                        if (agendaTableData.length > 1) {
                            doc.setFontSize(11);
                            doc.setFont("helvetica", "bold");
                            doc.text("Agenda Summary", 100, summaryTableStartY - 5);

                            doc.autoTable({
                                startY: summaryTableStartY,
                                head: [agendaTableData[0]],
                                body: agendaTableData.slice(1),
                                theme: "grid",
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.2,
                                    lineColor: [0, 0, 0]
                                },
                                styles: {
                                    fontSize: 8.5,
                                    lineColor: [0, 0, 0]
                                },
                                columnStyles: {
                                    0: { cellWidth: 60 },
                                    1: { cellWidth: 20 }
                                },
                                margin: { left: 100 },
                                tableWidth: 90,
                                pageBreak: 'avoid'
                            });
                            rightTableHeight = doc.autoTable.previous.finalY - summaryTableStartY;
                        }
                    }

                    // Update currentY to the bottom of whichever table is taller
                    currentY = summaryTableStartY + Math.max(leftTableHeight, rightTableHeight) + 10;

                    // Grand Total
                    doc.setFontSize(11);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Total Appointments: ${data.appointments.length}`, 14, currentY);
                    currentY += 10;

                    // Add footer
                    addReportFooter(doc, currentY);
                    addPageNumbers(doc, pageCount);

                    // Save PDF
                    const fileName = `Custom_Appointment_Report_${startDate}_to_${endDate}_${facultyName.replace(/\s+/g, '_')}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Custom Report Generated!</strong><br>
                Report from <i>${formattedStart}</i> to <i>${formattedEnd}</i> has been downloaded.<br>
                Total: <strong>${data.appointments.length}</strong> appointments<br>
                <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    showModal("Error generating custom report. Please try again.", false);
                });
        }

        // ------------------------ HELPER FUNCTIONS ------------------------
        function addReportHeaderApt(doc, title, subtitle = "") {
            const pageWidth = doc.internal.pageSize.getWidth();

            // Header
            doc.setFont("times", "bold");
            doc.setFontSize(18);
            doc.text("COLEGIO DE STA. TERESA DE AVILA", pageWidth / 2, 15, { align: "center" });

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text("6 Kingfisher St. cor. Skylark St., Zabarte Subd., Novaliches, Quezon City",
                pageWidth / 2, 20, { align: "center" });

            doc.setFont("helvetica", "bold");
            doc.setFontSize(16);
            doc.text("COLLEGE OF INFORMATION TECHNOLOGY", pageWidth / 2, 30, { align: "center" });

            // Black bar with title
            doc.setFillColor(0, 0, 0);
            doc.rect(15, 35, pageWidth - 30, 10, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("APPOINTMENT REPORT", pageWidth / 2, 41.5, { align: "center" });

            // Academic info
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text("AY: 2024-2025", pageWidth / 2, 50, { align: "center" });
            doc.text("TERM : SECOND SEMESTER", pageWidth / 2, 55, { align: "center" });

            // Subtitle and generated date
            doc.setFont("helvetica", "italic");
            doc.setFontSize(10);

            const generatedDate = new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const generatedText = `Generated on: ${generatedDate}`;
            const generatedTextWidth = doc.getTextWidth(generatedText);

            if (subtitle) {
                doc.text(subtitle, 15, 60);
            }

            doc.text(generatedText, pageWidth - 15 - generatedTextWidth, 60);
        }

        function addSummaryTables(doc, agendaCounts = {}, statusCounts = {}, startY) {
            const summaryTable = {
                startY: startY,
                head: [["Agenda Summary", " ", "Status Summary", " "]],
                body: [],
                theme: 'grid',
                headStyles: {
                    fillColor: [240, 240, 240],
                    textColor: [0, 0, 0],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                styles: { fontSize: 9 }
            };

            const maxRows = Math.max(
                Object.keys(agendaCounts).length,
                Object.keys(statusCounts).length
            );

            for (let i = 0; i < maxRows; i++) {
                const agendaKey = Object.keys(agendaCounts)[i] || "";
                const agendaVal = agendaKey ? agendaCounts[agendaKey] : "";

                const statusKey = Object.keys(statusCounts)[i] || "";
                const statusVal = statusKey ? statusCounts[statusKey] : "";

                summaryTable.body.push([
                    agendaKey, agendaVal?.toString() || "",
                    statusKey, statusVal?.toString() || ""
                ]);
            }

            doc.autoTable(summaryTable);
        }

        function addReportFooter(doc, currentY) {
            const pageHeight = doc.internal.pageSize.height;
            const footerHeight = 30;
            let footerY = currentY + 10;

            if (footerY + footerHeight > pageHeight) {
                doc.addPage();
                footerY = 20;
            }

            doc.text("Prepared by:", 15, footerY);
            doc.setFont("helvetica", "bold");
            doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
            doc.setFont("helvetica", "normal");
            doc.line(15, footerY + 11, 75, footerY + 11);
            doc.text("Dean", 15, footerY + 17);
        }

        function addPageNumbers(doc) {
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(9);
                doc.text(`Page ${i} of ${pageCount}`, 180, 285);
            }
        }

        function formatTime(time) {
            if (!time) return "N/A";

            if (typeof time === "object") {
                if (time.hours !== undefined && time.minutes !== undefined) {
                    const period = time.hours >= 12 ? 'PM' : 'AM';
                    const hours12 = time.hours % 12 || 12;
                    return `${hours12}:${time.minutes.toString().padStart(2, '0')} ${period}`;
                } else if (time.toISOString) {
                    return new Date(time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                }
            } else if (typeof time === "string") {
                let date = new Date(`1970-01-01T${time}`);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            return time;
        }

        function showModal(message, isSuccess) {
            const modal = document.getElementById("exportModal");
            const modalMessage = document.getElementById("exportModalMessage");

            // Clear any existing timeout
            if (modal.timeoutId) {
                clearTimeout(modal.timeoutId);
            }

            modalMessage.innerHTML = message;
            modal.style.display = "block";
            modalMessage.style.color = isSuccess ? "#333" : "#F44336";

            // Auto-hide after 5 seconds
            modal.timeoutId = setTimeout(() => {
                modal.style.display = "none";
            }, 5000);

            // Manual closing
            const closeBtn = document.querySelector(".close-modal-1");
            if (closeBtn) {
                closeBtn.onclick = () => {
                    clearTimeout(modal.timeoutId);
                    modal.style.display = "none";
                };
            }
        }
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const modal = document.getElementById("customModal1");
        const openModal = document.getElementById("openModal");
        const closeModal = document.querySelector(".close-modal1");
        const reportType = document.getElementById("reportType");
        const extraFields = document.getElementById("extraFields");
        const generatePdf = document.getElementById("generatePdf");

        // Open & Close Modal
        openModal.addEventListener("click", () => modal.style.display = "flex");
        closeModal.addEventListener("click", () => modal.style.display = "none");
        window.addEventListener("click", (event) => {
            if (event.target === modal) modal.style.display = "none";
        });

        // Handle report type selection
        reportType.addEventListener("change", function () {
            extraFields.innerHTML = "";
            let selectedType = this.value;

            if (selectedType === "weekly") {
                extraFields.innerHTML = `
                    <label>Select Week:</label>
                    <input type="week" id="reportWeek" class="form-control">
                `;
                generatePdf.setAttribute("data-type", "weekly");

            } else if (selectedType === "monthly") {
                extraFields.innerHTML = `
                    <label>Select Month:</label>
                    <input type="month" id="reportMonth" class="form-control">
                `;
                generatePdf.setAttribute("data-type", "monthly");

            } else if (selectedType === "yearly") {
                extraFields.innerHTML = `
                    <label>Select Year:</label>
                    <select id="reportYear" class="form-select">
                        ${generateYearOptions()}
                    </select>
                `;
                generatePdf.setAttribute("data-type", "yearly");

            } else if (selectedType === "custom") {
                extraFields.innerHTML = `
                    <label>Start Date:</label>
                    <input type="date" id="startDate" class="form-control">

                    <label>End Date:</label>
                    <input type="date" id="endDate" class="form-control">
                `;
                generatePdf.setAttribute("data-type", "custom");
            }
        });


        // Function to generate year options dynamically
        function generateYearOptions() {
            let currentYear = new Date().getFullYear();
            let options = '<option value="" disabled selected>Select a Year</option>';
            for (let year = currentYear; year >= currentYear - 10; year--) { // Adjust range as needed
                options += `<option value="${year}">${year}</option>`;
            }
            return options;
        }


        // Call the correct function based on report type
        generatePdf.addEventListener("click", function () {
            let reportType = this.getAttribute("data-type");
            let facultyRFID = document.getElementById("facultySelect")?.value;

            if (!facultyRFID) {
                alert("Please select a faculty member.");
                return;
            }

            console.log("Selected RFID:", facultyRFID);

            if (reportType === "weekly") {
                generateWeeklyReport(facultyRFID);
            } else if (reportType === "monthly") {
                generateMonthlyReport(facultyRFID);
            } else if (reportType === "yearly") {
                generateYearlyReport(facultyRFID);
            } else if (reportType === "custom") {
                generateCustomReport(facultyRFID);
            } else {
                alert("Please select a valid report type.");
            }
        });

        // ------------------------ ATTENDANCE MONTHLY REPORT FUNCTION ------------------------
        function generateMonthlyReport() {
            // Get modal elements
            const modal = document.getElementById("exportModal");
            const modalMessage = document.getElementById("exportModalMessage");
            const closeBtn = document.querySelector(".close-modal-1");

            // Modal control functions
            function showModal(message, isSuccess) {
                // Clear any existing timeout
                if (modal.timeoutId) clearTimeout(modal.timeoutId);

                modalMessage.innerHTML = message;
                modal.style.display = "block";
                modalMessage.style.color = isSuccess ? "#333" : "#F44336";

                // Auto-close after 5 seconds
                modal.timeoutId = setTimeout(() => {
                    modal.style.display = "none";
                }, 5000);
            }

            function closeModal() {
                if (modal.timeoutId) clearTimeout(modal.timeoutId);
                modal.style.display = "none";
            }

            // Set up event listeners
            closeBtn?.addEventListener("click", closeModal);
            modal?.addEventListener("click", (e) => e.target === modal && closeModal());

            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedMonth = document.getElementById("reportMonth")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            // Validate inputs
            if (!selectedMonth) {
                showModal("Please select a month.", false);
                return;
            }
            if (!facultyRFID) {
                showModal("Please select a faculty member.", false);
                return;
            }


            // Extract year from selectedMonth (format: YYYY-MM)
            const [selectedYear, selectedMonthOnly] = selectedMonth.split("-");

            if (!selectedYear || !selectedMonthOnly) {
                showModal("Invalid month format. Expected format: YYYY-MM", false);
                return;
            }

            showModal("Generating monthly report...", true);

            // Fetch attendance records for the selected month, year, and faculty RFID
            fetch(`../functions/fetch-records-by-month.php?month=${selectedMonth}&year=${selectedYear}&rfid_no=${facultyRFID}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const {
                        facultyInfo,
                        attendanceReport,
                        statusCounts,
                        totalScheduledDays,
                        totalScheduledHours,
                        actualWorkedHours,
                        totalRenderedHours
                    } = data;

                    if (!attendanceReport || attendanceReport.length === 0) {
                        showModal("No attendance records found for this month.", false);
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Attendance Report", `Monthly Report - ${selectedMonth}`);

                    let y = 68;

                    // --- Faculty Info ---
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");

                    doc.text("Faculty:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${facultyInfo.fname} ${facultyInfo.lname}`, 35, y);
                    y += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("RFID No:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.rfid_no, 35, y);

                    let rightY = 68;
                    doc.setFont("helvetica", "normal");
                    doc.text("Employment Type:", 148, rightY);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.employment_type, 180, rightY);
                    rightY += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("Email:", 134, rightY);
                    doc.setFont("helvetica", "italic");
                    doc.text(facultyInfo.email, 145, rightY);

                    y = Math.max(y, rightY) + 10;

                    const recordsByWeek = {};

                    function getWeekNumber(date) {
                        const d = new Date(date);
                        const dayNum = d.getUTCDay() || 7; // Make Sunday (0) into 7
                        d.setUTCDate(d.getUTCDate() + 4 - dayNum); // Set to nearest Thursday
                        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
                        const weekNo = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
                        return weekNo;
                    }

                    // Group records by week number
                    attendanceReport.forEach(record => {
                        const date = new Date(record.date).toLocaleDateString("en-US");
                        const weekNumber = getWeekNumber(record.date);

                        if (!recordsByWeek[weekNumber]) {
                            recordsByWeek[weekNumber] = {};
                        }

                        if (!recordsByWeek[weekNumber][date]) {
                            recordsByWeek[weekNumber][date] = {
                                records: [],
                                time_in: record.time_in,
                                time_out: record.time_out,
                                status: record.status
                            };
                        }

                        recordsByWeek[weekNumber][date].records.push({
                            type: record.type,
                            start: formatTime(record.start_time),
                            end: formatTime(record.end_time),
                            sched_hours: record.sched_hours,
                            worked_hours: record.total_worked_hours
                        });
                    });

                    let totalWorkingDays = 0;
                    let totalPresent = 0;
                    let totalAbsent = 0;
                    let totalLate = 0;
                    let totalSchedHours = 0;
                    let grandTotalWorkedHours = 0;

                    const dateStatusMap = new Map();

                    attendanceReport.forEach(record => {
                        const date = record.date;
                        const status = record.status;
                        const schedHours = parseFloat(record.sched_hours || 0);
                        const workedHours = parseFloat(record.worked_hours || 0);

                        totalSchedHours += schedHours;

                        if (!dateStatusMap.has(date)) {
                            dateStatusMap.set(date, status);
                        } else {
                            const existingStatus = dateStatusMap.get(date);
                            if (status === 'Absent' || (status === 'Late' && existingStatus === 'Present')) {
                                dateStatusMap.set(date, status); // prioritize worse status
                            }
                        }
                    });

                    totalWorkingDays = dateStatusMap.size;

                    dateStatusMap.forEach(status => {
                        switch (status) {
                            case 'Present':
                                totalPresent++;
                                break;
                            case 'Absent':
                                totalAbsent++;
                                break;
                            case 'Late':
                                totalLate++;
                                break;
                        }
                    });

                    const attendedDays = totalPresent + totalLate;
                    const attendancePercentage = totalWorkingDays ? (attendedDays / totalWorkingDays) * 100 : 0;

                    // Process each week
                    Object.entries(recordsByWeek).forEach(([weekNumber, dates]) => {

                        // Process each date in the week
                        Object.entries(dates).forEach(([date, info]) => {
                            const pageHeight = doc.internal.pageSize.height;
                            const estimatedHeight = 50; // Estimate for both tables + spacing

                            if (y + estimatedHeight > pageHeight - 20) {
                                doc.addPage();
                                y = 20;
                            }

                            // Week number and date label - now on same line
                            doc.setFontSize(10);
                            doc.setFont("helvetica", "bold");
                            doc.text(`Week ${weekNumber} - ${date}`, 14, y);
                            y += 7; // Slightly more space than before

                            // WORKED HOURS TABLE - LEFT SIDE
                            const body = info.records.map(r => [r.type, r.start, r.end, r.sched_hours, r.worked_hours]);

                            const totalWorkedHoursPerTable = body.reduce((sum, r) => {
                                const val = parseFloat(r[4]);
                                return !isNaN(val) ? sum + val : sum;
                            }, 0);

                            grandTotalWorkedHours += totalWorkedHoursPerTable;

                            doc.autoTable({
                                startY: y,
                                head: [["Type", "Start", "End", "Sched", "Worked"]],
                                body: body,
                                theme: "grid",
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.1,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.1,
                                    lineColor: [0, 0, 0],
                                    fontSize: 9
                                },
                                styles: { fontSize: 9 },
                                margin: { left: 14, right: 100 },
                                didDrawCell: (data) => {
                                    if (data.row.index === body.length - 1 && data.column.index === 0) {
                                        doc.setFont("helvetica", "bold");
                                        doc.text("Total Worked Hours:", 14, data.cell.y + data.cell.height + 6);
                                        doc.text(
                                            totalWorkedHoursPerTable.toFixed(2),
                                            60,
                                            data.cell.y + data.cell.height + 6
                                        );
                                    }
                                }
                            });

                            const tableEndY = doc.lastAutoTable.finalY;

                            // KIOSK ATTENDANCE TABLE - RIGHT SIDE
                            const kioskBody = [
                                ["Time In", formatTime(info.time_in)],
                                ["Time Out", formatTime(info.time_out)],
                                ["Status", info.status]
                            ];

                            doc.autoTable({
                                startY: y,
                                head: [["KIOSK Attendance", "Details"]],
                                body: kioskBody,
                                theme: "grid",
                                styles: { fontSize: 9 },
                                headStyles: {
                                    fillColor: [240, 240, 240],
                                    textColor: [0, 0, 0],
                                    fontStyle: 'bold',
                                    halign: 'left',
                                    lineWidth: 0.1,
                                    lineColor: [0, 0, 0]
                                },
                                bodyStyles: {
                                    fillColor: [255, 255, 255],
                                    textColor: [0, 0, 0],
                                    halign: 'left',
                                    lineWidth: 0.1,
                                    lineColor: [0, 0, 0]
                                },
                                margin: { left: 110 }
                            });

                            y = Math.max(doc.lastAutoTable.finalY, tableEndY) + 20;
                        });
                    });

                    // Summary Section
                    y = doc.lastAutoTable.finalY + 20;

                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Attendance Summary", 14, y);
                    y += 2;

                    const summaryBody = [
                        ["Total Working Days", totalWorkingDays],
                        ["Present", totalPresent],
                        ["Absent", totalAbsent],
                        ["Late", totalLate],
                        ["Total Scheduled Hours", `${totalSchedHours.toFixed(2)} hrs`],
                        ["Total Rendered Hours", `${grandTotalWorkedHours.toFixed(2)} hrs`],
                        ["Attendance Percentage", `${attendancePercentage.toFixed(2)}%`]
                    ];

                    doc.autoTable({
                        startY: y + 3,
                        head: [["Category", "Value"]],
                        body: summaryBody,
                        theme: "grid",
                        styles: { fontSize: 10 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14, right: 14 }
                    });

                    // Footer
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40;

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    const fileName = `Monthly_Attendance_Report_${facultyName.replace(/\s+/g, '_')}_${selectedMonth}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Monthly Report Generated!</strong><br>
                 Report for <i>${facultyName}</i> (${selectedMonth}) has been downloaded.<br>
                 <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(err => {
                    console.error("Error:", err);
                    showModal("Error generating monthly report. Please try again.", false);
                });

            function formatTime(timeString) {
                if (!timeString) return "N/A";
                const time = new Date(`1970-01-01T${timeString}`);
                return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        }

        // ------------------------ ATTENDANCE WEEKLY REPORT FUNCTION ------------------------
        function generateWeeklyReport() {
            // Get modal elements
            const modal = document.getElementById("exportModal");
            const modalMessage = document.getElementById("exportModalMessage");
            const closeBtn = document.querySelector(".close-modal-1");

            // Modal control functions
            function showModal(message, isSuccess) {
                // Clear any existing timeout
                if (modal.timeoutId) clearTimeout(modal.timeoutId);

                modalMessage.innerHTML = message;
                modal.style.display = "block";
                modalMessage.style.color = isSuccess ? "#333" : "#F44336";

                // Auto-close after 5 seconds
                modal.timeoutId = setTimeout(() => {
                    modal.style.display = "none";
                }, 5000);
            }

            function closeModal() {
                if (modal.timeoutId) clearTimeout(modal.timeoutId);
                modal.style.display = "none";
            }

            // Set up event listeners
            closeBtn?.addEventListener("click", closeModal);
            modal?.addEventListener("click", (e) => e.target === modal && closeModal());
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let weekInput = document.getElementById("reportWeek")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            // Validate inputs - using modal instead of alert
            if (!weekInput) {
                showModal("Please select a week.", false);
                return;
            }
            if (!facultyRFID) {
                showModal("Please select a faculty member.", false);
                return;
            }

            let [selectedYear, selectedWeek] = weekInput.split("-W");
            if (!selectedYear || !selectedWeek) {
                showModal("Invalid week format. Expected format: YYYY-W##", false);
                return;
            }

            showModal("Generating weekly report...", true);

            fetch(`../functions/fetch-records-by-week.php?week=${selectedWeek}&year=${selectedYear}&rfid_no=${facultyRFID}`)
                .then(response => response.json())
                .then(data => {
                    const {
                        facultyInfo,
                        attendanceReport,
                        statusCounts,
                        totalScheduledDays,
                        totalScheduledHours,
                        actualWorkedHours,
                        totalRenderedHours
                    } = data;

                    if (!attendanceReport || attendanceReport.length === 0) {
                        showModal("No attendance records found for this week.", false);
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Attendance Report", `Weekly Report - Week ${selectedWeek}, ${selectedYear}`);

                    let y = 68;

                    // --- Faculty Info ---
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");

                    doc.text("Faculty:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${facultyInfo.fname} ${facultyInfo.lname}`, 35, y);
                    y += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("RFID No:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.rfid_no, 35, y);

                    let rightY = 68;
                    doc.setFont("helvetica", "normal");
                    doc.text("Employment Type:", 148, rightY);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.employment_type, 180, rightY);
                    rightY += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("Email:", 134, rightY);
                    doc.setFont("helvetica", "italic");
                    doc.text(facultyInfo.email, 145, rightY);

                    y = Math.max(y, rightY) + 10;

                    const recordsByDate = {};
                    attendanceReport.forEach(record => {
                        const date = new Date(record.date).toLocaleDateString("en-US");
                        if (!recordsByDate[date]) {
                            recordsByDate[date] = {
                                records: [],
                                time_in: record.time_in,
                                time_out: record.time_out,
                                status: record.status
                            };
                        }

                        recordsByDate[date].records.push({
                            type: record.type,
                            start: formatTime(record.start_time),
                            end: formatTime(record.end_time),
                            sched_hours: record.sched_hours,
                            worked_hours: record.total_worked_hours
                        });
                    });

                    let totalWorkingDays = 0;
                    let totalPresent = 0;
                    let totalAbsent = 0;
                    let totalLate = 0;
                    let totalSchedHours = 0;
                    let grandTotalWorkedHours = 0;

                    const dateStatusMap = new Map();

                    attendanceReport.forEach(record => {
                        const date = record.date;
                        const status = record.status;
                        const schedHours = parseFloat(record.sched_hours || 0);
                        const workedHours = parseFloat(record.worked_hours || 0);

                        totalSchedHours += schedHours;

                        if (!dateStatusMap.has(date)) {
                            dateStatusMap.set(date, status);
                        } else {
                            const existingStatus = dateStatusMap.get(date);
                            if (status === 'Absent' || (status === 'Late' && existingStatus === 'Present')) {
                                dateStatusMap.set(date, status); // prioritize worse status
                            }
                        }
                    });

                    totalWorkingDays = dateStatusMap.size;

                    dateStatusMap.forEach(status => {
                        switch (status) {
                            case 'Present':
                                totalPresent++;
                                break;
                            case 'Absent':
                                totalAbsent++;
                                break;
                            case 'Late':
                                totalLate++;
                                break;
                        }
                    });

                    // ✅ Late is also considered present for attendancePercentage
                    const attendedDays = totalPresent + totalLate;
                    const attendancePercentage = totalWorkingDays ? (attendedDays / totalWorkingDays) * 100 : 0;

                    Object.entries(recordsByDate).forEach(([date, info]) => {

                        const pageHeight = doc.internal.pageSize.height;
                        const estimatedHeight = 50; // Estimate for both tables + spacing

                        if (y + estimatedHeight > pageHeight - 20) {
                            doc.addPage();
                            y = 20;
                        }
                        // Date label
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Date ${date}`, 14, y);
                        y += 5;

                        // ──────────────────────────────────────────────
                        // WORKED HOURS TABLE - LEFT SIDE
                        // ──────────────────────────────────────────────
                        const body = info.records.map(r => [r.type, r.start, r.end, r.sched_hours, r.worked_hours]);

                        const totalWorkedHoursPerTable = body.reduce((sum, r) => {
                            const val = parseFloat(r[4]);
                            return !isNaN(val) ? sum + val : sum;
                        }, 0);

                        grandTotalWorkedHours += totalWorkedHoursPerTable;


                        doc.autoTable({
                            startY: y,
                            head: [["Type", "Start", "End", "Sched", "Worked"]],
                            body: body,
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                fontSize: 9
                            },
                            styles: { fontSize: 9 },
                            margin: { left: 14, right: 100 },
                            didDrawCell: (data) => {
                                if (data.row.index === body.length - 1 && data.column.index === 0) {
                                    doc.setFont("helvetica", "bold");
                                    doc.text("Total Worked Hours:", 14, data.cell.y + data.cell.height + 6);
                                    doc.text(
                                        body.reduce((sum, r) => sum + parseFloat(r[4]), 0).toFixed(2),
                                        60,
                                        data.cell.y + data.cell.height + 6
                                    );
                                }
                            }
                        });

                        const tableEndY = doc.lastAutoTable.finalY;

                        // ──────────────────────────────────────────────
                        // KIOSK ATTENDANCE TABLE - RIGHT SIDE
                        // ──────────────────────────────────────────────
                        const kioskBody = [
                            ["Time In", formatTime(info.time_in)],
                            ["Time Out", formatTime(info.time_out)],
                            ["Status", info.status]
                        ];

                        doc.autoTable({
                            startY: y,
                            head: [["KIOSK Attendance", "Details"]],
                            body: kioskBody,
                            theme: "grid",
                            styles: { fontSize: 9 },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            margin: { left: 110 }
                        });

                        y = Math.max(doc.lastAutoTable.finalY, tableEndY) + 20;
                    });


                    // Summary Section as a Table
                    y = doc.lastAutoTable.finalY + 25;

                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Attendance Summary", 14, y);
                    y += 2;

                    const summaryBody = [
                        ["Total Working Days", totalWorkingDays],
                        ["Present", totalPresent],
                        ["Absent", totalAbsent],
                        ["Late", totalLate],
                        ["Total Scheduled Hours", `${totalSchedHours.toFixed(2)} hrs`],
                        ["Total Rendered Hours", `${grandTotalWorkedHours.toFixed(2)} hrs`],
                        ["Attendance Percentage", `${attendancePercentage.toFixed(2)}%`]
                    ];

                    doc.autoTable({
                        startY: y + 3,
                        head: [["Category", "Value"]],
                        body: summaryBody,
                        theme: "grid",
                        styles: { fontSize: 10 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14, right: 14 }
                    });
                    // ========== FOOTER (Prepared By) ==========
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40; // or any value near the bottom

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    const fileName = `Weekly_Attendance_Report_Week_${selectedWeek}_${selectedYear}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Weekly Report Generated!</strong><br>
                 Report for Week ${selectedWeek}, ${selectedYear} has been downloaded.<br>
                 <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(err => {
                    console.error("Error:", err);
                    showModal("Error generating weekly report. Please try again.", false);
                });

            function formatTime(timeString) {
                if (!timeString) return "N/A";
                const time = new Date(`1970-01-01T${timeString}`);
                return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        }
        // ------------------------ ATTENDANCE YEARLY REPORT FUNCTION ------------------------
        function generateYearlyReport() {
            // Get modal elements
            const modal = document.getElementById("exportModal");
            const modalMessage = document.getElementById("exportModalMessage");
            const closeBtn = document.querySelector(".close-modal-1");

            // Modal control functions
            function showModal(message, isSuccess) {
                // Clear any existing timeout
                if (modal.timeoutId) clearTimeout(modal.timeoutId);

                modalMessage.innerHTML = message;
                modal.style.display = "block";
                modalMessage.style.color = isSuccess ? "#333" : "#F44336";

                // Auto-close after 5 seconds
                modal.timeoutId = setTimeout(() => {
                    modal.style.display = "none";
                }, 5000);
            }

            function closeModal() {
                if (modal.timeoutId) clearTimeout(modal.timeoutId);
                modal.style.display = "none";
            }

            // Set up event listeners
            closeBtn?.addEventListener("click", closeModal);
            modal?.addEventListener("click", (e) => e.target === modal && closeModal());
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedYear = document.getElementById("reportYear")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            // Validate inputs - using modal instead of alert
            if (!selectedYear) {
                showModal("Please select a year.", false);
                return;
            }
            if (!facultyRFID) {
                showModal("Please select a faculty member.", false);
                return;
            }

            // Get current date to determine which months to show
            const currentDate = new Date();
            const currentYear = currentDate.getFullYear();
            const currentMonth = currentDate.getMonth(); // 0-11 (January=0)

            // Show loading indicator
            const originalButtonText = facultySelect.nextElementSibling.innerHTML;
            facultySelect.nextElementSibling.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating Report...';

            // Fetch attendance records
            fetch(`../functions/fetch-records-by-year.php?year=${selectedYear}&rfid_no=${facultyRFID}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`Server returned ${response.status} status`);
                    }
                    return response.json();
                })
                .then(data => {
                    // Reset button text
                    facultySelect.nextElementSibling.innerHTML = originalButtonText;

                    // Validate response structure
                    if (!data || typeof data !== 'object') {
                        throw new Error("Invalid response from server");
                    }

                    if (data.error) {
                        throw new Error(data.error);
                    }

                    // Destructure all data from API response
                    const {
                        facultyInfo = {},
                        monthlySummary = [],
                        yearlyTotals = {},
                        statusCounts = {},
                        grandTotalHours = "0.00"
                    } = data;

                    // Validate required data
                    if (!facultyInfo.fname || !facultyInfo.lname) {
                        throw new Error("Faculty information is incomplete");
                    }

                    // Convert all numeric values to numbers to ensure toFixed() works
                    const processedYearlyTotals = {
                        total_days: parseInt(yearlyTotals.total_days) || 0,
                        present: parseInt(yearlyTotals.present) || 0,
                        absent: parseInt(yearlyTotals.absent) || 0,
                        late: parseInt(yearlyTotals.late) || 0,
                        scheduled_hours: parseFloat(yearlyTotals.scheduled_hours) || 0,
                        worked_hours: parseFloat(yearlyTotals.worked_hours) || 0,
                        attendance_percentage: parseFloat(yearlyTotals.attendance_percentage) || 0
                    };

                    // --- Header ---
                    addReportHeader(doc, "Attendance Report", `Yearly Report - ${selectedYear}`);

                    let y = 68;

                    // --- Faculty Info ---
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");
                    doc.text("Faculty:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${facultyInfo.fname} ${facultyInfo.lname}`, 35, y);
                    y += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("RFID No:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.rfid_no, 35, y);

                    let rightY = 68;
                    doc.setFont("helvetica", "normal");
                    doc.text("Employment Type:", 148, rightY);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.employment_type, 180, rightY);
                    rightY += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("Email:", 134, rightY);
                    doc.setFont("helvetica", "italic");
                    doc.text(facultyInfo.email, 145, rightY);

                    y = Math.max(y, rightY) + 10;

                    // Status Overview (using statusCounts from API)
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Attendance Overview", 14, y);
                    y += 8;

                    const statusBody = [
                        ["Present", statusCounts.Present || 0],
                        ["Absent", statusCounts.Absent || 0],
                        ["Late", statusCounts.Late || 0],
                        ["Total Hours", grandTotalHours || "0.00"]
                    ];

                    doc.autoTable({
                        startY: y,
                        head: [["Status", "Count"]],
                        body: statusBody,
                        theme: "grid",
                        styles: { fontSize: 10 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14 }
                    });

                    y = doc.lastAutoTable.finalY + 8;

                    // Monthly Summary Table - Only show months up to current month if same year
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Monthly Summary", 14, y);
                    y += 5;

                    const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"];

                    // Determine how many months to show
                    const monthsToShow = (selectedYear == currentYear) ? currentMonth + 1 : 12;

                    const summaryBody = [];
                    for (let i = 0; i < monthsToShow; i++) {
                        const monthData = monthlySummary[i] || {
                            present: 0,
                            absent: 0,
                            late: 0,
                            scheduled_hours: 0,
                            worked_hours: 0,
                            attendance_percentage: 0
                        };

                        // Convert all numeric values to numbers
                        const present = parseInt(monthData.present) || 0;
                        const absent = parseInt(monthData.absent) || 0;
                        const late = parseInt(monthData.late) || 0;
                        const scheduled_hours = parseFloat(monthData.scheduled_hours) || 0;
                        const worked_hours = parseFloat(monthData.worked_hours) || 0;
                        const attendance_percentage = parseFloat(monthData.attendance_percentage) || 0;

                        summaryBody.push([
                            monthNames[i],
                            present + absent + late, // Total days
                            present,
                            absent,
                            late,
                            scheduled_hours.toFixed(2),
                            worked_hours.toFixed(2),
                            attendance_percentage.toFixed(2) + "%"
                        ]);
                    }

                    doc.autoTable({
                        startY: y,
                        head: [["Month", "Days", "Present", "Absent", "Late", "Sched Hrs", "Worked Hrs", "Attendance %"]],
                        body: summaryBody,
                        theme: "grid",
                        styles: { fontSize: 8 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'center',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'center',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14 }
                    });

                    y = doc.lastAutoTable.finalY + 8;

                    // Yearly Summary (using processedYearlyTotals)
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Yearly Summary", 14, y);
                    y += 5;

                    const yearlySummaryBody = [
                        ["Total Working Days", processedYearlyTotals.total_days],
                        ["Total Present", processedYearlyTotals.present],
                        ["Total Absent", processedYearlyTotals.absent],
                        ["Total Late", processedYearlyTotals.late],
                        ["Total Scheduled Hours", processedYearlyTotals.scheduled_hours.toFixed(2) + " hrs"],
                        ["Total Rendered Hours", processedYearlyTotals.worked_hours.toFixed(2) + " hrs"],
                        ["Yearly Attendance Percentage", processedYearlyTotals.attendance_percentage.toFixed(2) + "%"]
                    ];

                    doc.autoTable({
                        startY: y,
                        head: [["Category", "Value"]],
                        body: yearlySummaryBody,
                        theme: "grid",
                        styles: { fontSize: 10 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14, right: 14 }
                    });

                    // Footer
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40;

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    const fileName = `Yearly_Attendance_Report_${facultyName.replace(/\s+/g, '_')}_${selectedYear}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Yearly Report Generated!</strong><br>
                 Report for ${selectedYear} has been downloaded.<br>
                 <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(err => {
                    console.error("Error:", err);
                    showModal(`Error generating report: ${err.message}`, false);
                });
        }

        // ------------------------ ATTENDANCE CUSTOM REPORT FUNCTION ------------------------
        function generateCustomReport() {
            // Get modal elements
            const modal = document.getElementById("exportModal");
            const modalMessage = document.getElementById("exportModalMessage");
            const closeBtn = document.querySelector(".close-modal-1");

            // Modal control functions
            function showModal(message, isSuccess) {
                // Clear any existing timeout
                if (modal.timeoutId) clearTimeout(modal.timeoutId);

                modalMessage.innerHTML = message;
                modal.style.display = "block";
                modalMessage.style.color = isSuccess ? "#333" : "#F44336";

                // Auto-close after 5 seconds
                modal.timeoutId = setTimeout(() => {
                    modal.style.display = "none";
                }, 5000);
            }

            function closeModal() {
                if (modal.timeoutId) clearTimeout(modal.timeoutId);
                modal.style.display = "none";
            }

            // Set up event listeners
            closeBtn?.addEventListener("click", closeModal);
            modal?.addEventListener("click", (e) => e.target === modal && closeModal());
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let startDate = document.getElementById("startDate")?.value;
            let endDate = document.getElementById("endDate")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";


            // Validate inputs - using modal instead of alert
            if (!startDate || !endDate) {
                showModal("Please select both start and end dates.", false);
                return;
            }
            if (!facultyRFID) {
                showModal("Please select a faculty member.", false);
                return;
            }

            showModal("Generating custom report...", true);

            fetch(`../functions/fetch-records-by-range.php?start_date=${startDate}&end_date=${endDate}&rfid_no=${facultyRFID}`)
                .then(response => response.json())
                .then(data => {
                    const {
                        facultyInfo,
                        attendanceReport,
                        statusCounts,
                        totalScheduledDays,
                        totalScheduledHours,
                        actualWorkedHours,
                        totalRenderedHours
                    } = data;

                    if (!attendanceReport || attendanceReport.length === 0) {
                        showModal("No attendance records found for the selected date range.", false);
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Attendance Report", `Custom Report - ${startDate} to ${endDate}`);

                    let y = 68;

                    // --- Faculty Info ---
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");

                    doc.text("Faculty:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(`${facultyInfo.fname} ${facultyInfo.lname}`, 35, y);
                    y += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("RFID No:", 15, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.rfid_no, 35, y);

                    let rightY = 68;
                    doc.setFont("helvetica", "normal");
                    doc.text("Employment Type:", 148, rightY);
                    doc.setFont("helvetica", "bold");
                    doc.text(facultyInfo.employment_type, 180, rightY);
                    rightY += 6;

                    doc.setFont("helvetica", "normal");
                    doc.text("Email:", 134, rightY);
                    doc.setFont("helvetica", "italic");
                    doc.text(facultyInfo.email, 145, rightY);

                    y = Math.max(y, rightY) + 10;

                    const recordsByDate = {};
                    attendanceReport.forEach(record => {
                        const date = new Date(record.date).toLocaleDateString("en-US");
                        if (!recordsByDate[date]) {
                            recordsByDate[date] = {
                                records: [],
                                time_in: record.time_in,
                                time_out: record.time_out,
                                status: record.status
                            };
                        }

                        recordsByDate[date].records.push({
                            type: record.type,
                            start: formatTime(record.start_time),
                            end: formatTime(record.end_time),
                            sched_hours: record.sched_hours,
                            worked_hours: record.total_worked_hours
                        });
                    });

                    let totalWorkingDays = 0;
                    let totalPresent = 0;
                    let totalAbsent = 0;
                    let totalLate = 0;
                    let totalSchedHours = 0;
                    let grandTotalWorkedHours = 0;

                    const dateStatusMap = new Map();

                    attendanceReport.forEach(record => {
                        const date = record.date;
                        const status = record.status;
                        const schedHours = parseFloat(record.sched_hours || 0);
                        const workedHours = parseFloat(record.worked_hours || 0);

                        totalSchedHours += schedHours;

                        if (!dateStatusMap.has(date)) {
                            dateStatusMap.set(date, status);
                        } else {
                            const existingStatus = dateStatusMap.get(date);
                            if (status === 'Absent' || (status === 'Late' && existingStatus === 'Present')) {
                                dateStatusMap.set(date, status); // prioritize worse status
                            }
                        }
                    });

                    totalWorkingDays = dateStatusMap.size;

                    dateStatusMap.forEach(status => {
                        switch (status) {
                            case 'Present':
                                totalPresent++;
                                break;
                            case 'Absent':
                                totalAbsent++;
                                break;
                            case 'Late':
                                totalLate++;
                                break;
                        }
                    });

                    // ✅ Late is also considered present for attendancePercentage
                    const attendedDays = totalPresent + totalLate;
                    const attendancePercentage = totalWorkingDays ? (attendedDays / totalWorkingDays) * 100 : 0;

                    Object.entries(recordsByDate).forEach(([date, info]) => {
                        const pageHeight = doc.internal.pageSize.height;
                        const estimatedHeight = 50; // Estimate for both tables + spacing

                        if (y + estimatedHeight > pageHeight - 20) {
                            doc.addPage();
                            y = 20;
                        }

                        // Date label
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Date ${date}`, 14, y);
                        y += 5;

                        // ──────────────────────────────────────────────
                        // WORKED HOURS TABLE - LEFT SIDE
                        // ──────────────────────────────────────────────
                        const body = info.records.map(r => [r.type, r.start, r.end, r.sched_hours, r.worked_hours]);

                        const totalWorkedHoursPerTable = body.reduce((sum, r) => {
                            const val = parseFloat(r[4]);
                            return !isNaN(val) ? sum + val : sum;
                        }, 0);

                        grandTotalWorkedHours += totalWorkedHoursPerTable;

                        doc.autoTable({
                            startY: y,
                            head: [["Type", "Start", "End", "Sched", "Worked"]],
                            body: body,
                            theme: "grid",
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                fontSize: 9
                            },
                            styles: { fontSize: 9 },
                            margin: { left: 14, right: 100 },
                            didDrawCell: (data) => {
                                if (data.row.index === body.length - 1 && data.column.index === 0) {
                                    doc.setFont("helvetica", "bold");
                                    doc.text("Total Worked Hours:", 14, data.cell.y + data.cell.height + 6);
                                    doc.text(
                                        body.reduce((sum, r) => sum + parseFloat(r[4]), 0).toFixed(2),
                                        60,
                                        data.cell.y + data.cell.height + 6
                                    );
                                }
                            }
                        });

                        const tableEndY = doc.lastAutoTable.finalY;

                        // ──────────────────────────────────────────────
                        // KIOSK ATTENDANCE TABLE - RIGHT SIDE
                        // ──────────────────────────────────────────────
                        const kioskBody = [
                            ["Time In", formatTime(info.time_in)],
                            ["Time Out", formatTime(info.time_out)],
                            ["Status", info.status]
                        ];

                        doc.autoTable({
                            startY: y,
                            head: [["KIOSK Attendance", "Details"]],
                            body: kioskBody,
                            theme: "grid",
                            styles: { fontSize: 9 },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                halign: 'left',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            margin: { left: 110 }
                        });

                        y = Math.max(doc.lastAutoTable.finalY, tableEndY) + 20;
                    });

                    // Summary Section as a Table
                    y = doc.lastAutoTable.finalY + 25;

                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Attendance Summary", 14, y);
                    y += 2;

                    const summaryBody = [
                        ["Total Working Days", totalWorkingDays],
                        ["Present", totalPresent],
                        ["Absent", totalAbsent],
                        ["Late", totalLate],
                        ["Total Scheduled Hours", `${totalSchedHours.toFixed(2)} hrs`],
                        ["Total Rendered Hours", `${grandTotalWorkedHours.toFixed(2)} hrs`],
                        ["Attendance Percentage", `${attendancePercentage.toFixed(2)}%`]
                    ];

                    doc.autoTable({
                        startY: y + 3,
                        head: [["Category", "Value"]],
                        body: summaryBody,
                        theme: "grid",
                        styles: { fontSize: 10 },
                        headStyles: {
                            fillColor: [240, 240, 240],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            halign: 'left',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        margin: { left: 14, right: 14 }
                    });

                    // ========== FOOTER (Prepared By) ==========
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40; // or any value near the bottom

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);

                    const fileName = `Custom_Attendance_Report_${startDate}_to_${endDate}.pdf`;
                    doc.save(fileName);

                    showModal(
                        `<strong style="color:#333;font-weight:600">Custom Report Generated!</strong><br>
                 Report from ${startDate} to ${endDate} has been downloaded.<br>
                 <code>${fileName}</code>`,
                        true
                    );
                })
                .catch(err => {
                    console.error("Error:", err);
                    showModal("Error generating custom report. Please try again.", false);
                });

            function formatTime(timeString) {
                if (!timeString) return "N/A";
                const time = new Date(`1970-01-01T${timeString}`);
                return time.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
        }


        // ------------------------ ATTENDANCE HELPER FUNCTIONS ------------------------
        function addReportHeader(doc, title, subtitle = "") {
            const pageWidth = doc.internal.pageSize.getWidth();

            // === HEADER ===
            doc.setFont("times", "bold");
            doc.setFontSize(18);
            doc.text("COLEGIO DE STA. TERESA DE AVILA", pageWidth / 2, 15, null, null, "center");

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text("6 Kingfisher St. cor. Skylark St., Zabarte Subd., Novaliches, Quezon City", pageWidth / 2, 20, null, null, "center");

            doc.setFont("helvetica", "bold");
            doc.setFontSize(16);
            doc.text("COLLEGE OF INFORMATION TECHNOLOGY", pageWidth / 2, 30, null, null, "center");

            // === Black Bar for "APPOINTMENT REPORT" ===
            doc.setFillColor(0, 0, 0); // Black background
            doc.rect(15, 35, pageWidth - 30, 10, 'F'); // Black bar

            doc.setFontSize(14);
            doc.setTextColor(255, 255, 255); // White text
            doc.text("ATTENDANCE REPORT", pageWidth / 2, 41.5, null, null, "center");

            // === Academic Year and Term ===
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text("AY: 2024-2025", pageWidth / 2, 50, null, null, "center");
            doc.text("TERM : SECOND SEMESTER", pageWidth / 2, 55, null, null, "center");

            // === Subtitle on left, Generated on right ===
            doc.setFont("helvetica", "italic");
            doc.setFontSize(10);

            const generatedDate = new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const generatedText = `Generated on: ${generatedDate}`;
            const generatedTextWidth = doc.getTextWidth(generatedText);

            if (subtitle) {
                doc.text(subtitle, 15, 60); // Subtitle on left
            }

            doc.text(generatedText, pageWidth - 15 - generatedTextWidth, 60); // Generated on right

        }

        function addPageNumbers(doc) {
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(9);
                doc.text(`Page ${i} of ${pageCount}`, 180, 285);
            }
        }

        function formatTime(time) {
            if (!time) return "N/A";

            // Handle case where time is an object
            if (typeof time === "object" && time.date) {
                let dateObj = new Date(time.date);
                return dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            // Handle case where time is a string (e.g., "08:40:00")
            if (typeof time === "string") {
                let date = new Date(`1970-01-01T${time}`);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            return time; // Return as-is if already formatted
        }

    });

</script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const summaryModal = document.getElementById("summaryModal");
        const openSummaryModal = document.getElementById("openSummaryModal");
        const closeSummaryModal = document.querySelector(".close-summary-modal");
        const summaryReportType = document.getElementById("summaryReportType");
        const summaryExtraFields = document.getElementById("summaryExtraFields");
        const generateSummaryPdf = document.getElementById("generateSummaryPdf");

        // Open & Close Summary Modal
        openSummaryModal.addEventListener("click", () => summaryModal.style.display = "flex");
        closeSummaryModal.addEventListener("click", () => summaryModal.style.display = "none");
        window.addEventListener("click", (event) => {
            if (event.target === summaryModal) summaryModal.style.display = "none";
        });

        // Handle summary report type selection
        summaryReportType.addEventListener("change", function () {
            summaryExtraFields.innerHTML = "";
            let selectedType = this.value;

            if (selectedType === "weekly") {
                summaryExtraFields.innerHTML = `
                    <label>Select Week:</label>
                    <input type="week" id="summaryReportWeek" class="form-control">
                `;
                generateSummaryPdf.setAttribute("data-type", "weekly");

            } else if (selectedType === "monthly") {
                summaryExtraFields.innerHTML = `
                    <label>Select Month:</label>
                    <input type="month" id="summaryReportMonth" class="form-control">
                `;
                generateSummaryPdf.setAttribute("data-type", "monthly");

            } else if (selectedType === "yearly") {
                summaryExtraFields.innerHTML = `
                    <label>Select Year:</label>
                    <select id="summaryReportYear" class="form-select">
                        ${generateYearOptions()}
                    </select>
                `;
                generateSummaryPdf.setAttribute("data-type", "yearly");

            } else if (selectedType === "custom") {
                summaryExtraFields.innerHTML = `
                    <label>Start Date:</label>
                    <input type="date" id="customReportStartDate" class="form-control">
                    <label>End Date:</label>
                    <input type="date" id="customReportEndDate" class="form-control">
                `;
                generateSummaryPdf.setAttribute("data-type", "custom");
            }
        });

        // Function to generate year options dynamically
        function generateYearOptions() {
            let currentYear = new Date().getFullYear();
            let options = '<option value="" disabled selected>Select a Year</option>';
            for (let year = currentYear; year >= currentYear - 10; year--) { // Adjust range as needed
                options += `<option value="${year}">${year}</option>`;
            }
            return options;
        }

        // Call the correct function based on summary report type
        generateSummaryPdf.addEventListener("click", function () {
            let reportType = this.getAttribute("data-type");

            if (!reportType) {
                alert("Please select a valid summary report type.");
                return;
            }

            console.log("Generating Summary Report of type:", reportType);

            if (reportType === "weekly") {
                generateWeeklySummaryReport();
            } else if (reportType === "monthly") {
                generateMonthlySummaryReport();
            } else if (reportType === "yearly") {
                generateYearlySummaryReport();
            } else if (reportType === "custom") {
                generateCustomSummaryReport();
            } else {
                alert("Unknown summary report type.");
            }
        });

        // ------------------------ ATTENDANCE WEEKLY SUMMARY REPORT FUNCTION -------------------------
        function generateWeeklySummaryReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedWeek = document.getElementById("summaryReportWeek")?.value;

            // Validate input
            if (!selectedWeek) {
                alert("Please select a week.");
                return;
            }

            // Fetch attendance summary records for the selected week
            fetch(`../functions/fetch-summary-records-by-week.php?week=${selectedWeek}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const { facultySummaries, weekRange } = data;

                    if (!facultySummaries || facultySummaries.length === 0) {
                        alert("Attendance data for this week is not available. Please check again later.");
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Weekly Attendance Summary", `Week of ${weekRange}`);

                    let y = 70; // Initial Y position after header
                    let facultyCount = 0; // Counter for faculty per page

                    facultySummaries.forEach(faculty => {
                        // Check if we need a new page (leave space for name + both tables)
                        const nameHeight = 5;
                        const firstTableHeight = 3 * 10; // 4 rows × approx 10 units each
                        const secondTableHeight = 2 * 10; // 3 rows × approx 10 units each
                        const totalSectionHeight = nameHeight + firstTableHeight + secondTableHeight + 10;

                        if (y + totalSectionHeight > doc.internal.pageSize.height - 15) {
                            doc.addPage();
                            y = 15; // Reset Y position for new page
                        }

                        // Faculty name container with black border and light gray background
                        doc.setFillColor(240, 240, 240);  // Light gray background (unchanged)
                        doc.setDrawColor(0, 0, 0);        // BLACK border (changed from gray)
                        doc.setLineWidth(0.1);             // Border thickness (unchanged)

                        // Draw filled rectangle with black border ('FD' = Fill + Draw)
                        doc.rect(14, y - 2, 182, 8, 'FD');

                        // Faculty name text 
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);  // Black text
                        doc.text(`${faculty.fname} ${faculty.lname}`, 16, y + 3); // Left-aligned name
                        doc.text(faculty.employment_type, 178, y + 3, { align: "right" }); // Right-aligned role
                        y += 5;  // Vertical space after container

                        // Total Working Days and Status Counts with thicker lines
                        const workingDaysTable = [
                            ["Total Working Days", faculty.totalScheduledDays],
                            ["Present", faculty.statusCounts.Present],
                            ["Late", faculty.statusCounts.Late],
                            ["Absent", faculty.statusCounts.Absent]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: workingDaysTable,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,  // Consistent line width
                                lineColor: [0, 0, 0]  // Explicit black lines for all cells
                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]  // Black lines for header
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]  // Black lines for body
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,  // Grid lines
                            lineColor: [0, 0, 0],  // Black grid lines
                            tableLineWidth: 0.1,  // Outer border
                            tableLineColor: [0, 0, 0]  // Explicit black outer border
                        });

                        y = doc.lastAutoTable.finalY + 0

                        // Attendance Summary with thicker lines
                        const attendanceSummary = [
                            ["Total Scheduled Hours", `${faculty.totalScheduledHours} hrs`],
                            ["Total Rendered Hours", `${faculty.totalRenderedHours} hrs`],
                            ["Attendance Percentage", `${faculty.attendancePercentage}%`]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: attendanceSummary,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,       // Consistent line width
                                lineColor: [0, 0, 0], // Black lines for all cells

                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,       // Matches body line width
                                lineColor: [0, 0, 0], // Black header lines
                                fontStyle: 'bold'      // Make header text bold
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,       // Consistent with header
                                lineColor: [0, 0, 0]  // Black body lines
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,           // Grid lines
                            lineColor: [0, 0, 0],     // Black grid lines
                            tableLineWidth: 0.1,      // Thicker outer border (matches inner lines)
                            tableLineColor: [0, 0, 0],// Black outer border
                        });

                        y = doc.lastAutoTable.finalY + 10; // More space between faculty entries
                    });

                    // Footer
                    const footerY = doc.internal.pageSize.height - 30;
                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    doc.save(`Weekly_Summary_Report_${selectedWeek}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating weekly report. Please try again.");
                });
        }

        // ------------------------ ATTENDANCE MONTHLY SUMMARY REPORT FUNCTION ------------------------
        function generateMonthlySummaryReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedMonth = document.getElementById("summaryReportMonth")?.value;

            // Validate input
            if (!selectedMonth) {
                alert("Please select a month.");
                return;
            }

            // Extract year from selectedMonth (format: YYYY-MM)
            const [selectedYear, selectedMonthOnly] = selectedMonth.split("-");

            if (!selectedYear || !selectedMonthOnly) {
                alert("Invalid month format. Expected format: YYYY-MM");
                return;
            }

            // Fetch attendance summary records for the selected month and year
            fetch(`../functions/fetch-summary-records-by-month.php?month=${selectedMonth}&year=${selectedYear}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const { facultySummaries } = data;

                    if (!facultySummaries || facultySummaries.length === 0) {
                        alert("Attendance data for this month is not available. Please check again later.");
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Attendance Summary Report", `Monthly Summary - ${selectedMonth}`);

                    let y = 70; // Initial Y position after header

                    facultySummaries.forEach(faculty => {
                        // Check if we need a new page (leave space for name + both tables)
                        const nameHeight = 5;
                        const firstTableHeight = 3 * 10; // 4 rows × approx 10 units each
                        const secondTableHeight = 2 * 10; // 3 rows × approx 10 units each
                        const totalSectionHeight = nameHeight + firstTableHeight + secondTableHeight + 10;

                        if (y + totalSectionHeight > doc.internal.pageSize.height - 15) {
                            doc.addPage();
                            y = 15; // Reset Y position for new page
                        }

                        // Faculty name container with black border and light gray background
                        doc.setFillColor(240, 240, 240);  // Light gray background (unchanged)
                        doc.setDrawColor(0, 0, 0);        // BLACK border (changed from gray)
                        doc.setLineWidth(0.1);             // Border thickness (unchanged)

                        // Draw filled rectangle with black border ('FD' = Fill + Draw)
                        doc.rect(14, y - 2, 182, 8, 'FD');

                        // Faculty name text 
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);  // Black text
                        doc.text(`${faculty.fname} ${faculty.lname}`, 16, y + 3); // Left-aligned name
                        doc.text(faculty.employment_type, 178, y + 3, { align: "right" }); // Right-aligned role
                        y += 5;  // Vertical space after container

                        // Total Working Days and Status Counts with thicker lines
                        const workingDaysTable = [
                            ["Total Working Days", faculty.totalScheduledDays],
                            ["Present", faculty.statusCounts.Present],
                            ["Late", faculty.statusCounts.Late],
                            ["Absent", faculty.statusCounts.Absent]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: workingDaysTable,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,  // Consistent line width
                                lineColor: [0, 0, 0]  // Explicit black lines for all cells
                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]  // Black lines for header
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]  // Black lines for body
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,  // Grid lines
                            lineColor: [0, 0, 0],  // Black grid lines
                            tableLineWidth: 0.1,  // Outer border
                            tableLineColor: [0, 0, 0]  // Explicit black outer border
                        });

                        y = doc.lastAutoTable.finalY + 0

                        // Attendance Summary with thicker lines
                        const attendanceSummary = [
                            ["Total Scheduled Hours", `${faculty.totalScheduledHours} hrs`],
                            ["Total Rendered Hours", `${faculty.totalRenderedHours} hrs`],
                            ["Attendance Percentage", `${faculty.attendancePercentage}%`]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: attendanceSummary,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,       // Consistent line width
                                lineColor: [0, 0, 0], // Black lines for all cells

                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,       // Matches body line width
                                lineColor: [0, 0, 0], // Black header lines
                                fontStyle: 'bold'      // Make header text bold
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,       // Consistent with header
                                lineColor: [0, 0, 0]  // Black body lines
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,           // Grid lines
                            lineColor: [0, 0, 0],     // Black grid lines
                            tableLineWidth: 0.1,      // Thicker outer border (matches inner lines)
                            tableLineColor: [0, 0, 0],// Black outer border
                        });

                        y = doc.lastAutoTable.finalY + 10; // More space between faculty entries
                    });

                    // ========== FOOTER (Prepared By) ==========
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40; // or any value near the bottom

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    doc.save(`Monthly_Summary_Report_${selectedMonth}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating summary report. Please try again.");
                });
        }

        // ------------------------ ATTENDANCE YEARLY SUMMARY REPORT FUNCTION ------------------------
        function generateYearlySummaryReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedYear = document.getElementById("summaryReportYear")?.value;

            // Validate input
            if (!selectedYear) {
                alert("Please select a year.");
                return;
            }

            // Fetch yearly attendance summary
            fetch(`../functions/fetch-summary-records-by-year.php?year=${selectedYear}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const { facultySummaries, monthlyBreakdown } = data;

                    if (!facultySummaries || facultySummaries.length === 0) {
                        alert("Attendance data for this year is not available.");
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Annual Attendance Summary", `Year ${selectedYear}`);

                    let y = 70; // Initial Y position

                    // Yearly Summary Table
                    doc.autoTable({
                        startY: y,
                        head: [['Faculty Member', 'Present', 'Late', 'Absent', 'Total Days', 'Attendance %']],
                        body: facultySummaries.map(faculty => [
                            `${faculty.lname}, ${faculty.fname}`,
                            faculty.statusCounts.Present,
                            faculty.statusCounts.Late,
                            faculty.statusCounts.Absent,
                            faculty.totalScheduledDays,
                            `${faculty.attendancePercentage}%`
                        ]),
                        styles: {
                            fontSize: 10,
                            lineWidth: 0.2,
                            lineColor: [0, 0, 0],
                            textColor: [1, 1, 1]
                        },
                        headStyles: {
                            fillColor: [50, 50, 50],
                            textColor: [255, 255, 255],
                            fontStyle: 'bold'
                        },
                        columnStyles: {
                            0: { cellWidth: 50 },
                            1: { halign: 'center' },
                            2: { halign: 'center' },
                            3: { halign: 'center' },
                            4: { halign: 'center' },
                            5: { halign: 'center' }
                        },
                        margin: { left: 14 }
                    });

                    y = doc.lastAutoTable.finalY + 15;

                    // Monthly Breakdown Section
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Monthly Breakdown", 14, y);
                    y += 8;

                    // Group data by faculty
                    facultySummaries.forEach(faculty => {
                        // Check page space
                        if (y > doc.internal.pageSize.height - 80) {
                            doc.addPage();
                            y = 20;
                        }

                        // Faculty header
                        doc.setFontSize(10);
                        doc.setFillColor(240, 240, 240);
                        doc.rect(14, y - 2, 182, 8, 'FD');
                        doc.text(`${faculty.fname} ${faculty.lname}`, 16, y + 3);
                        y += 6;

                        // Monthly data table
                        doc.autoTable({
                            startY: y,
                            head: [['Month', 'Present', 'Late', 'Absent', 'Days', 'Attendance %']],
                            body: monthlyBreakdown
                                .filter(item => item.rfid_no === faculty.rfid_no)
                                .map(item => [
                                    item.month,
                                    item.present,
                                    item.late,
                                    item.absent,
                                    item.total_days,
                                    `${item.attendance_percentage}%`
                                ]),
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,
                                cellPadding: 2,
                                lineColor: [0, 0, 0],
                                textColor: [1, 1, 1]
                            },
                            headStyles: {

                                fillColor: [200, 200, 200],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold'
                            },
                            columnStyles: {
                                0: { cellWidth: 40 },
                                1: { halign: 'center', cellWidth: 26 },
                                2: { halign: 'center', cellWidth: 30 },
                                3: { halign: 'center', cellWidth: 26 },
                                4: { halign: 'center', cellWidth: 30 },
                                5: { halign: 'center', cellWidth: 30 }
                            },
                            margin: { left: 14 }
                        });

                        y = doc.lastAutoTable.finalY + 10;
                    });

                    // Footer
                    const footerY = doc.internal.pageSize.height - 30;
                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    doc.save(`Annual_Summary_Report_${selectedYear}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating annual report.");
                });
        }

        // ------------------------ ATTENDANCE CUSTOM SUMMARY REPORT FUNCTION ------------------------
        function generateCustomSummaryReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            // Get date range inputs instead of just month
            let startDate = document.getElementById("customReportStartDate")?.value;
            let endDate = document.getElementById("customReportEndDate")?.value;

            // Validate inputs
            if (!startDate || !endDate) {
                alert("Please select both start and end dates.");
                return;
            }

            // Convert dates to readable format for the report title
            const startDateObj = new Date(startDate);
            const endDateObj = new Date(endDate);
            const formattedStart = startDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
            const formattedEnd = endDateObj.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

            // Fetch attendance summary records for the custom date range
            fetch(`../functions/fetch-summary-records-by-range.php?start=${startDate}&end=${endDate}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    const { facultySummaries } = data;

                    if (!facultySummaries || facultySummaries.length === 0) {
                        alert("No attendance data available for the selected date range.");
                        return;
                    }

                    // --- Header ---
                    addReportHeader(doc, "Custom Attendance Summary Report", `${formattedStart} to ${formattedEnd}`);

                    let y = 70; // Initial Y position after header

                    facultySummaries.forEach(faculty => {
                        // Check if we need a new page (leave space for name + both tables)
                        const nameHeight = 5;
                        const firstTableHeight = 3 * 10; // 4 rows × approx 10 units each
                        const secondTableHeight = 2 * 10; // 3 rows × approx 10 units each
                        const totalSectionHeight = nameHeight + firstTableHeight + secondTableHeight + 10;

                        if (y + totalSectionHeight > doc.internal.pageSize.height - 15) {
                            doc.addPage();
                            y = 15; // Reset Y position for new page
                        }

                        // Faculty name container with black border and light gray background
                        doc.setFillColor(240, 240, 240);
                        doc.setDrawColor(0, 0, 0);
                        doc.setLineWidth(0.1);

                        // Draw filled rectangle with black border ('FD' = Fill + Draw)
                        doc.rect(14, y - 2, 182, 8, 'FD');

                        // Faculty name text 
                        doc.setFontSize(10);
                        doc.setFont("helvetica", "bold");
                        doc.setTextColor(0, 0, 0);
                        doc.text(`${faculty.fname} ${faculty.lname}`, 16, y + 3);
                        doc.text(faculty.employment_type, 178, y + 3, { align: "right" });
                        y += 5;

                        // Total Working Days and Status Counts
                        const workingDaysTable = [
                            ["Total Working Days", faculty.totalScheduledDays],
                            ["Present", faculty.statusCounts.Present],
                            ["Late", faculty.statusCounts.Late],
                            ["Absent", faculty.statusCounts.Absent]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: workingDaysTable,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            tableLineWidth: 0.1,
                            tableLineColor: [0, 0, 0]
                        });

                        y = doc.lastAutoTable.finalY + 0;

                        // Attendance Summary
                        const attendanceSummary = [
                            ["Total Scheduled Hours", `${faculty.totalScheduledHours} hrs`],
                            ["Total Rendered Hours", `${faculty.totalRenderedHours} hrs`],
                            ["Attendance Percentage", `${faculty.attendancePercentage}%`]
                        ];

                        doc.autoTable({
                            startY: y,
                            body: attendanceSummary,
                            theme: "grid",
                            styles: {
                                fontSize: 10,
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            headStyles: {
                                fillColor: [240, 240, 240],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                fontStyle: 'bold'
                            },
                            bodyStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            margin: { left: 14, right: 14 },
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            tableLineWidth: 0.1,
                            tableLineColor: [0, 0, 0]
                        });

                        y = doc.lastAutoTable.finalY + 10;
                    });

                    // Footer
                    const pageHeight = doc.internal.pageSize.height;
                    const footerY = pageHeight - 40;

                    doc.setFontSize(10);
                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    doc.save(`Custom_Summary_Report_${startDate}_to_${endDate}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating custom summary report. Please try again.");
                });
        }

        // ------------------------ ATTENDANCE HELPER FUNCTIONS ------------------------
        function addReportHeader(doc, title, subtitle = "") {
            const pageWidth = doc.internal.pageSize.getWidth();

            // === HEADER ===
            doc.setFont("times", "bold");
            doc.setFontSize(18);
            doc.text("COLEGIO DE STA. TERESA DE AVILA", pageWidth / 2, 15, null, null, "center");

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text("6 Kingfisher St. cor. Skylark St., Zabarte Subd., Novaliches, Quezon City", pageWidth / 2, 20, null, null, "center");

            doc.setFont("helvetica", "bold");
            doc.setFontSize(16);
            doc.text("COLLEGE OF INFORMATION TECHNOLOGY", pageWidth / 2, 30, null, null, "center");

            // === Black Bar for "APPOINTMENT REPORT" ===
            doc.setFillColor(0, 0, 0); // Black background
            doc.rect(15, 35, pageWidth - 30, 10, 'F'); // Black bar

            doc.setFontSize(14);
            doc.setTextColor(255, 255, 255); // White text
            doc.text("ATTENDANCE REPORT", pageWidth / 2, 41.5, null, null, "center");

            // === Academic Year and Term ===
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text("AY: 2024-2025", pageWidth / 2, 50, null, null, "center");
            doc.text("TERM : SECOND SEMESTER", pageWidth / 2, 55, null, null, "center");

            // === Subtitle on left, Generated on right ===
            doc.setFont("helvetica", "italic");
            doc.setFontSize(10);

            const generatedDate = new Date().toLocaleDateString('en-PH', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            const generatedText = `Generated on: ${generatedDate}`;
            const generatedTextWidth = doc.getTextWidth(generatedText);

            if (subtitle) {
                doc.text(subtitle, 15, 60); // Subtitle on left
            }

            doc.text(generatedText, pageWidth - 15 - generatedTextWidth, 60); // Generated on right

        }

        function addPageNumbers(doc) {
            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(9);
                doc.text(`Page ${i} of ${pageCount}`, 180, 285);
            }
        }

        function formatTime(time) {
            if (!time) return "N/A";

            // Handle case where time is an object
            if (typeof time === "object" && time.date) {
                let dateObj = new Date(time.date);
                return dateObj.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            // Handle case where time is a string (e.g., "08:40:00")
            if (typeof time === "string") {
                let date = new Date(`1970-01-01T${time}`);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            return time; // Return as-is if already formatted
        }

    });
</script>

</html>