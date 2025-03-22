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
                    <i class="fas bi-arrow-left-short"></i>
                    <span>To Dashboard</span>
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
                    <!--<option value="yearly">Yearly</option>-->
                    <option value="custom">Custom Date Range</option> <!-- New Option -->
                </select>

                <div id="extraFields" class="mt-3"></div> <!-- Dynamic Fields -->

                <button class="generate-btn mt-3" id="generatePdf">Generate Report</button>
            </div>
        </div>
    </div>

    <div id="appointmentModal" class="modal-overlays">
        <div class="modal-container1">
            <div class="modal-header1">
                <h3>Generate Appointment Report</h3>
                <span class="close-appointment-modal">&times;</span>
            </div>
            <div class="modal-body1">
                <label for="appointmentReportType">Select Report Type:</label>
                <select class="form-select" id="appointmentReportType">
                    <option value="daily">Daily</option>
                    <!--<option value="monthly">Monthly</option>-->
                    <!--<option value="yearly">Yearly</option>-->
                    <option value="faculty">By Faculty</option>
                </select>

                <div id="appointmentExtraFields" class="mt-3"></div> <!-- Dynamic Fields -->

                <button class="generate-btn" id="generateAppointmentPdf">Generate Report</button>
            </div>
        </div>
    </div>
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

        // Open & Close Modal
        openAppointmentModal.addEventListener("click", () => appointmentModal.style.display = "flex");
        closeAppointmentModal.addEventListener("click", () => appointmentModal.style.display = "none");
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

            } else if (selectedType === "faculty") {
                appointmentExtraFields.innerHTML = `
            <label>Select Faculty:</label>
            <select name="prof_rfid_no" id="prof_rfid_no" class="form-select">
                <option value="" disabled selected>Loading...</option>
            </select>`;
                generateAppointmentPdf.setAttribute("data-type", "faculty");

                // Fetch faculty data
                fetch('../functions/fetch-faculty.php')
                    .then(response => response.text())
                    .then(options => {
                        document.getElementById("prof_rfid_no").innerHTML = '<option value="" disabled selected>Select a Faculty Member</option>' + options;
                    })
                    .catch(error => {
                        console.error("Error fetching faculty data:", error);
                        document.getElementById("prof_rfid_no").innerHTML = '<option value="" disabled selected>Error loading data</option>';
                    });
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
        generateAppointmentPdf.addEventListener("click", function () {
            let reportType = this.getAttribute("data-type");
            if (reportType === "daily") {
                generateAppointmentDailyReport();
            } else if (reportType === "faculty") {
                generateAppointmentFacultyReport();
            } else if (reportType === "monthly") {
                generateAppointmentMonthlyReport();
            } else if (reportType === "yearly") {
                generateAppointmentYearlyReport();
            }
            else {
                alert("Please select a valid report type.");
            }
        });

        // ------------------------ APPOINTMENT DAILY REPORT FUNCTION ------------------------
        function generateAppointmentDailyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let dateInput = document.getElementById("appointmentReportDate")?.value;

            if (!dateInput) {
                alert("Please select a valid date.");
                return;
            }

            fetch(`../functions/fetch-appointment-by-date.php?date=${dateInput}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        alert("No appointments found for this date.");
                        return;
                    }

                    // Format the date (e.g., March 17, 2025)
                    let formattedDate = new Date(dateInput).toLocaleDateString('en-PH', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    addReportHeaderApt(doc, "Appointment Report", `Daily Report - ${formattedDate}`);

                    // ========== APPOINTMENT TABLE ==========
                    let tableData = [
                        ["Faculty", "Student", "Time", "Agenda", "Status"]
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
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            halign: 'center',
                            valign: 'middle',
                            cellPadding: 4
                        },
                        bodyStyles: {
                            textColor: [0, 0, 0],
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0]
                        },
                        styles: {
                            fontSize: 8.5
                        }
                    });

                    let currentY = doc.autoTable.previous.finalY + 2;

                    // ========== AGENDA AND STATUS SUMMARY (Side-by-side) ==========
                    let agendaCounts = data.agendaCounts || {};
                    let statusCounts = data.statusCounts || {};

                    const summaryTable = {
                        startY: currentY,
                        head: [["Agenda Summary", " ", "Status Summary", " "]],
                        body: [],
                        theme: 'grid',
                        headStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            halign: 'right',
                            valign: 'middle'
                        },
                        styles: {
                            fontSize: 9
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            fontStyle: 'normal',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            halign: 'right'
                        }
                    };

                    const maxRows = Math.max(Object.keys(agendaCounts).length, Object.keys(statusCounts).length);

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

                    currentY = doc.autoTable.previous.finalY + 2;

                    // ========== TOP FACULTY WITH MOST COMPLETED APPOINTMENTS ==========
                    if (data.topCompletedFaculty && data.topCompletedFaculty.length > 0) {
                        const facultyTableData = data.topCompletedFaculty.map(name => [name]);

                        doc.autoTable({
                            startY: currentY,
                            head: [["Faculty with Most Completed Appointments"]],
                            body: facultyTableData,
                            theme: 'grid',
                            styles: { fontSize: 9 },
                            headStyles: {
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                fillColor: [255, 255, 255],
                                fontStyle: 'bold',
                                halign: 'right'
                            },
                            bodyStyles: {
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                fillColor: [255, 255, 255],
                                fontStyle: 'normal',
                                halign: 'right'
                            }
                        });

                        currentY = doc.autoTable.previous.finalY + 3;
                    }

                    // ========== FOOTER (Prepared By) ==========
                    const footerHeight = 30; // estimated height needed for the footer
                    const pageHeight = doc.internal.pageSize.height;
                    let footerY = currentY + 10;

                    if (footerY + footerHeight > pageHeight) {
                        doc.addPage();
                        footerY = 20; // top margin of new page
                    }

                    doc.text("Prepared by:", 15, footerY);
                    doc.setFont("helvetica", "bold");
                    doc.text("HAROLD R. LUCERO, MIT", 15, footerY + 10);
                    doc.setFont("helvetica", "normal");
                    doc.line(15, footerY + 11, 75, footerY + 11);
                    doc.text("Dean", 15, footerY + 17);

                    addPageNumbers(doc);
                    doc.save(`Appointment_Daily_Report_${dateInput}.pdf`);

                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating report.");
                });
        }

        // ------------------------ APPOINTMENT MONTHLY REPORT FUNCTION ------------------------
        function generateAppointmentMonthlyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let selectedMonth = document.getElementById("appointmentReportMonth")?.value;

            if (!selectedMonth) {
                alert("Please select a month.");
                return;
            }

            fetch(`../functions/fetch-appointment-by-month.php?month=${selectedMonth}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        alert("No appointments found for this month.");
                        return;
                    }

                    // Group appointments by date
                    let groupedAppointments = {};
                    data.appointments.forEach(appointment => {
                        let date = appointment.date_logged;
                        if (!groupedAppointments[date]) {
                            groupedAppointments[date] = [];
                        }
                        groupedAppointments[date].push(appointment);
                    });

                    let y = 65; // Initial Y position for content

                    Object.keys(groupedAppointments).forEach((date, index) => {
                        let y = 20; // Reset Y position at the top

                        // Only add a new page for subsequent dates, not the first one
                        if (index > 0) {
                            doc.addPage();
                        }

                        // Add report header only on the first page
                        if (index === 0) {
                            addReportHeader(doc, `Monthly Appointment Report - ${selectedMonth}`);
                            y = 65; // Adjust y to avoid overlapping the header
                        }

                        // Title for the day's appointments
                        doc.setFontSize(12);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Appointments for ${date}`, 14, y);
                        doc.setFontSize(10);
                        y += 10; // Space after title

                        let tableData = [
                            ["Code", "Faculty", "Student", "Start", "End", "Agenda", "Status"]
                        ];

                        groupedAppointments[date].forEach(appointment => {
                            tableData.push([
                                appointment.appointment_code || "N/A",
                                `${appointment.fname || ""} ${appointment.lname || ""}`.trim(),
                                `${appointment.stud_fname || ""} ${appointment.stud_lname || ""}`.trim(),
                                formatTime(appointment.start_time),
                                formatTime(appointment.end_time),
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        // Generate table
                        doc.autoTable({
                            startY: y,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            styles: { fontSize: 7 },
                            margin: { bottom: 10 }
                        });

                        y = doc.lastAutoTable.finalY + 10; // Ensure spacing after table
                    });

                    // Add Summary Section on a new page
                    doc.addPage();
                    y = 20;
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Appointment Status Count", 14, y);
                    doc.setFont("helvetica", "normal");
                    y += 8;
                    Object.entries(data.statusCounts).forEach(([status, count]) => {
                        doc.text(`${status}: ${count}`, 20, y);
                        y += 6;
                    });

                    y += 12; // Space before next section

                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Appointment Agenda Count", 14, y);
                    doc.setFont("helvetica", "normal");
                    y += 8;
                    Object.entries(data.agendaCounts).forEach(([agenda, count]) => {
                        doc.text(`${agenda}: ${count}`, 20, y);
                        y += 6;
                    });

                    addPageNumbers(doc);
                    doc.save(`Monthly_Appointment_Report_${selectedMonth}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating monthly report.");
                });
        }

        // ------------------------ YEARLY APPOINTMENT REPORT FUNCTION ------------------------

        function generateAppointmentYearlyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let selectedYear = document.getElementById("appointmentReportYear")?.value;

            if (!selectedYear) {
                alert("Please select a year.");
                return;
            }

            fetch(`../functions/fetch-appointment-by-year.php?year=${selectedYear}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        alert("No appointments found for this year.");
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

                    let y = 65;
                    addReportHeader(doc, `Yearly Appointment Report - ${selectedYear}`);

                    Object.keys(groupedAppointments).forEach((month, index) => {
                        let y = index === 0 ? 65 : 20; // ✅ First page: 65, Others: 20
                        if (index > 0) {
                            doc.addPage();
                        }

                        doc.setFontSize(12);
                        doc.setFont("helvetica", "bold");
                        doc.text(`Appointments for ${month}`, 14, y);
                        doc.setFontSize(10);
                        y += 10;

                        let tableData = [
                            ["Code", "Faculty", "Student", "Start", "End", "Agenda", "Status"]
                        ];

                        groupedAppointments[month].forEach(appointment => {
                            tableData.push([
                                appointment.appointment_code || "N/A",
                                `${appointment.prof_fname || ""} ${appointment.prof_lname || ""}`.trim(), // ✅ Updated faculty name
                                `${appointment.stud_fname || ""} ${appointment.stud_lname || ""}`.trim(),
                                formatTime(appointment.start_time),
                                formatTime(appointment.end_time),
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        doc.autoTable({
                            startY: y,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            styles: { fontSize: 7 },
                            margin: { bottom: 20 }, // Ensure space for footer
                            didParseCell: function (data) {
                                if ([0, 3, 4, 5, 6].includes(data.column.index)) {
                                    data.cell.styles.fontSize = 7;
                                }
                            }
                        });

                    });

                    // Add Summary Section
                    doc.addPage();
                    y = 20;
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Appointment Status Count", 14, y);
                    doc.setFont("helvetica", "normal");
                    y += 8;
                    Object.entries(data.statusCounts).forEach(([status, count]) => {
                        doc.text(`${status}: ${count}`, 20, y);
                        y += 6;
                    });

                    y += 12;
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Appointment Agenda Count", 14, y);
                    doc.setFont("helvetica", "normal");
                    y += 8;
                    Object.entries(data.agendaCounts).forEach(([agenda, count]) => {
                        doc.text(`${agenda}: ${count}`, 20, y);
                        y += 6;
                    });

                    addPageNumbers(doc);
                    doc.save(`Yearly_Appointment_Report_${selectedYear}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating yearly report.");
                });
        }

        // ------------------------ APPOINTMENT FACULTY REPORT FUNCTION ------------------------
        function generateAppointmentFacultyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let facultyId = document.getElementById("prof_rfid_no")?.value;

            if (!facultyId) {
                alert("Please select a faculty member.");
                return;
            }

            fetch(`../functions/fetch-appointment-by-faculty.php?faculty=${facultyId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.appointments || data.appointments.length === 0) {
                        alert("No appointments found for this faculty.");
                        return;
                    }

                    let facultyName = `${data.faculty.fname} ${data.faculty.lname}`;
                    addReportHeaderApt(doc, "Appointment Report", `Faculty - ${facultyName}`);

                    // Group appointments by month
                    const appointmentsByMonth = {};
                    data.appointments.forEach(appointment => {
                        const date = new Date(appointment.date_logged);
                        const monthYear = `${date.toLocaleString('default', { month: 'long', year: 'numeric' })} Appointments`;
                        if (!appointmentsByMonth[monthYear]) {
                            appointmentsByMonth[monthYear] = [];
                        }
                        appointmentsByMonth[monthYear].push(appointment);
                    });

                    let currentY = 68;

                    // Loop through each month and generate a table
                    for (const month in appointmentsByMonth) {
                        if (currentY > 250) {
                            doc.addPage();
                            currentY = 20;
                        }

                        doc.setFont("helvetica", "bold");
                        doc.setFontSize(10);
                        doc.text(month, 15, currentY);
                        currentY += 5;

                        let tableData = [
                            ["Date", "Student", "Time", "Agenda", "Status"]
                        ];

                        appointmentsByMonth[month].forEach(appointment => {
                            let timeRange = `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`;
                            tableData.push([
                                appointment.date_logged || "N/A",
                                `${appointment.stud_fname || ""} ${appointment.stud_lname || ""}`.trim(),
                                timeRange,
                                appointment.agenda || "N/A",
                                appointment.status || "N/A"
                            ]);
                        });

                        doc.autoTable({
                            startY: currentY,
                            head: [tableData[0]],
                            body: tableData.slice(1),
                            theme: "grid",
                            headStyles: {
                                fillColor: [255, 255, 255],
                                textColor: [0, 0, 0],
                                fontStyle: 'bold',
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0],
                                halign: 'center',
                                valign: 'middle',
                                cellPadding: 4
                            },
                            bodyStyles: {
                                textColor: [0, 0, 0],
                                lineWidth: 0.1,
                                lineColor: [0, 0, 0]
                            },
                            styles: {
                                fontSize: 8.5
                            }
                        });

                        currentY = doc.autoTable.previous.finalY + 10;
                    }

                    // ========== AGENDA AND STATUS SUMMARY ==========
                    if (currentY > 240) {
                        doc.addPage();
                        currentY = 20;
                    }

                    let agendaCounts = data.agendaCounts || {};
                    let statusCounts = data.statusCounts || {};

                    const summaryTable = {
                        startY: currentY,
                        head: [["Agenda Summary", " ", "Status Summary", " "]],
                        body: [],
                        theme: 'grid',
                        headStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            fontStyle: 'bold',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            halign: 'right',
                            valign: 'middle'
                        },
                        styles: {
                            fontSize: 9
                        },
                        bodyStyles: {
                            fillColor: [255, 255, 255],
                            textColor: [0, 0, 0],
                            fontStyle: 'normal',
                            lineWidth: 0.1,
                            lineColor: [0, 0, 0],
                            halign: 'right'
                        }
                    };

                    const maxRows = Math.max(Object.keys(agendaCounts).length, Object.keys(statusCounts).length);

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
                    currentY = doc.autoTable.previous.finalY + 5;

                    // ========== RATING SUMMARY ==========
                    const ratingAverage = data.ratings?.average;
                    const ratingCount = data.ratings?.count;

                    if (currentY > 240) {
                        doc.addPage();
                        currentY = 20;
                    }

                    doc.autoTable({
                        startY: currentY,
                        head: [["Rating Summary"]],
                        body: [
                            ["Total Ratings Given: " + (ratingCount ?? "0")],
                            ["Average Rating: " + (ratingAverage !== null && ratingAverage !== undefined ? ratingAverage.toFixed(2) : "No ratings yet")]
                        ],
                        theme: 'grid',
                        headStyles: {
                            fillColor: [255, 255, 255],
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
                            fontSize: 10
                        },
                        styles: {
                            fontSize: 10,
                        }
                    });

                    currentY = doc.autoTable.previous.finalY + 5;

                    // ========== FOOTER (Prepared By) ==========
                    const footerHeight = 30;
                    const pageHeight = doc.internal.pageSize.height;
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

                    addPageNumbers(doc);
                    doc.save(`Faculty_Appointment_Report_${facultyName}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating report.");
                });
        }

        // ------------------------ APPOINTMENT HELPER FUNCTIONS ------------------------
        function addReportHeaderApt(doc, title, subtitle = "") {
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
            doc.text("APPOINTMENT REPORT", pageWidth / 2, 41.5, null, null, "center");

            // === Academic Year and Term ===
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.setFontSize(10);
            doc.text("AY: 2024-2025", pageWidth / 2, 55, null, null, "center");
            doc.text("TERM : SECOND SEMESTER", pageWidth / 2, 60, null, null, "center");

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

            if (typeof time === "object") {
                if (time.hours !== undefined && time.minutes !== undefined) {
                    return `${time.hours}:${time.minutes.toString().padStart(2, '0')} ${time.hours >= 12 ? 'PM' : 'AM'}`;
                } else if (time.toISOString) {
                    return new Date(time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
                }
            } else if (typeof time === "string") {
                let date = new Date(`1970-01-01T${time}`);
                return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });
            }

            return time; // Return as-is if it's already in a correct format
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
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedMonth = document.getElementById("reportMonth")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            // Extract year from selectedMonth (format: YYYY-MM)
            const [selectedYear, selectedMonthOnly] = selectedMonth.split("-");

            if (!selectedYear || !selectedMonthOnly) {
                alert("Invalid month format. Expected format: YYYY-MM");
                return;
            }

            // Fetch attendance records for the selected month, year, and faculty RFID
            fetch(`../functions/fetch-records-by-month.php?month=${selectedMonth}&year=${selectedYear}&rfid_no=${facultyRFID}`)
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
                        alert("No attendance records found for this month.");
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
                    doc.save(`Monthly_Attendance_Report_${selectedMonth}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating report.");
                });
        }

        // ------------------------ ATTENDANCE WEEKLY REPORT FUNCTION ------------------------
        function generateWeeklyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let weekInput = document.getElementById("reportWeek")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            if (!weekInput) return alert("Please select a week.");
            if (!facultyRFID) return alert("Please select a faculty member.");

            let [selectedYear, selectedWeek] = weekInput.split("-W");
            if (!selectedYear || !selectedWeek) return alert("Invalid week format.");

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
                        alert("No attendance records found for this week.");
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
                    doc.save(`Weekly_Attendance_Report_Week_${selectedWeek}_${selectedYear}.pdf`);

                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating report.");
                });
        }


        // ------------------------ ATTENDANCE YEARLY REPORT FUNCTION ------------------------
        function generateYearlyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let selectedYear = document.getElementById("reportYear")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            if (!selectedYear) {
                alert("Please select a year.");
                return;
            }

            if (!facultyRFID) {
                alert("Please select a faculty member.");
                return;
            }

            // Fetch attendance records for the selected year
            fetch(`../functions/fetch-records-by-year.php?year=${selectedYear}&rfid_no=${facultyRFID}`)
                .then(response => response.json())
                .then(data => {
                    console.log("Fetched Data:", data);

                    if (!data.attendanceRecords || data.attendanceRecords.length === 0) {
                        alert("No attendance records found for this year.");
                        return;
                    }

                    // Add Report Header
                    addReportHeader(doc, `Yearly Attendance Report - ${selectedYear}`);
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Faculty: ${facultyName}`, 14, 62);

                    let y = 70;
                    let recordsByMonth = {};

                    // Group records by month
                    data.attendanceRecords.forEach(record => {
                        let month = new Date(record.date_logged).toLocaleString('en-us', { month: 'long' });
                        if (!recordsByMonth[month]) {
                            recordsByMonth[month] = [];
                        }
                        recordsByMonth[month].push([
                            record.date_logged || "N/A",
                            formatTime(record.time_in),
                            formatTime(record.time_out),
                            record.total_hours || "0",
                            record.status || "N/A"
                        ]);
                    });

                    // Generate tables for each month
                    Object.keys(recordsByMonth).forEach((month, index, monthsArray) => {
                        if (y + 40 > doc.internal.pageSize.height) { // Check if there's space for the month header
                            doc.addPage();
                            y = 20;
                        }

                        doc.setFontSize(12);
                        doc.setFont("helvetica", "bold");
                        doc.text(month, 14, y);
                        y += 5;

                        let tableStartY = y;
                        doc.autoTable({
                            startY: y,
                            head: [["Date", "Time In", "Time Out", "Total Hours", "Status"]],
                            body: recordsByMonth[month],
                            theme: "grid",
                            styles: { fontSize: 8 },
                            margin: { bottom: 10 },
                            didDrawPage: (data) => {
                                y = data.cursor.y + 10; // Update y position after the table
                            }
                        });

                        // If the next table won't fit on this page, move to a new one
                        if (index < monthsArray.length - 1 && y + 40 > doc.internal.pageSize.height) {
                            doc.addPage();
                            y = 20;
                        }
                    });

                    // Attendance Summary Section
                    if (y + 40 > doc.internal.pageSize.height) {
                        doc.addPage();
                        y = 20;
                    }

                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text("Attendance Status Count", 14, y);
                    doc.setFont("helvetica", "normal");
                    y += 8;

                    Object.entries(data.statusCounts).forEach(([status, count]) => {
                        doc.text(`${status}: ${count}`, 20, y);
                        y += 6;
                    });

                    y += 8;
                    doc.setFontSize(12);
                    doc.setFont("helvetica", "bold");
                    doc.text(`Grand Total of Hours: ${data.grandTotalHours || 0} hrs`, 14, y);

                    addPageNumbers(doc);
                    doc.save(`Yearly_Attendance_Report_${selectedYear}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating yearly report.");
                });
        }

        // ------------------------ ATTENDANCE CUSTOM REPORT FUNCTION ------------------------
        function generateCustomReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();

            let startDate = document.getElementById("startDate")?.value;
            let endDate = document.getElementById("endDate")?.value;
            let facultySelect = document.getElementById("facultySelect");
            let facultyRFID = facultySelect?.value;
            let facultyName = facultySelect?.options[facultySelect.selectedIndex]?.text || "Unknown Faculty";

            if (!startDate || !endDate) {
                alert("Please select both start and end dates.");
                return;
            }

            if (!facultyRFID) {
                alert("Please select a faculty member.");
                return;
            }

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
                        alert("No attendance records found for the selected date range.");
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
                    doc.save(`Custom_Attendance_Report_${startDate}_to_${endDate}.pdf`);
                })
                .catch(err => {
                    console.error("Error:", err);
                    alert("Error generating report.");
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
            doc.text("AY: 2024-2025", pageWidth / 2, 55, null, null, "center");
            doc.text("TERM : SECOND SEMESTER", pageWidth / 2, 60, null, null, "center");

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