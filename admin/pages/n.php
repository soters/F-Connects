<button class="gabtn-red" id="openModal2">Generate Report</button>

<div id="customModal2" class="modal-overlays">
    <div class="modal-container1">
        <div class="modal-header1">
            <h3>Generate Appointment Report</h3>
            <span class="close-modal2">&times;</span>
        </div>
        <div class="modal-body1">
            <label for="reportType2">Select Report Type:</label>
            <select class="form-select" id="reportType2">
                <option value="daily">Daily</option>
                <option value="monthly2">Monthly</option>
                <option value="yearly2">Yearly</option>
                <option value="faculty">By Faculty</option>
            </select>

            <div id="extraFields" class="mt-3"></div> <!-- Dynamic Fields -->

            <button class="generate-btn" id="generatePdf2">Generate Report</button>
        </div>
    </div>
</div>

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
            <select name="appointmentProfRfidNo" id="appointmentProfRfidNo" class="form-select">
                <option value="" disabled selected>Loading...</option>
            </select>`;
                generateAppointmentPdf.setAttribute("data-type", "faculty");

                // Fetch faculty data
                fetch('../functions/fetch-faculty.php')
                    .then(response => response.text())
                    .then(options => {
                        document.getElementById("appointmentProfRfidNo").innerHTML = '<option value="" disabled selected>Select a Faculty Member</option>' + options;
                    })
                    .catch(error => {
                        console.error("Error fetching faculty data:", error);
                        document.getElementById("appointmentProfRfidNo").innerHTML = '<option value="" disabled selected>Error loading data</option>';
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

        // ------------------------ DAILY REPORT FUNCTION ------------------------
        function generateAppointmentDailyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let dateInput = document.getElementById("reportDate")?.value;

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

                    addReportHeader(doc, `Appointment Report - ${dateInput}`);

                    let tableData = [
                        ["Code", "Faculty", "Student", "Start", "End", "Agenda", "Status"]
                    ];

                    data.appointments.forEach(appointment => {
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

                    doc.autoTable({
                        startY: 60,
                        head: [tableData[0]],
                        body: tableData.slice(1),
                        theme: "grid",
                        styles: { fontSize: 9 },
                        margin: { bottom: 20 }, // Ensure space for footer
                        didParseCell: function (data) {
                            if ([0, 3, 4, 5, 6].includes(data.column.index)) {
                                data.cell.styles.fontSize = 7;
                            }
                        }
                    });

                    // Status Summary
                    let statusY = doc.autoTable.previous.finalY + 10;
                    doc.setFont("helvetica", "bold");
                    doc.text("Status Summary", 15, statusY);
                    doc.setFont("helvetica", "normal");
                    let statusCounts = data.statusCounts;
                    let y = statusY + 5;
                    Object.keys(statusCounts).forEach(status => {
                        doc.text(`${status} - ${statusCounts[status]}`, 20, y);
                        y += 5;
                    });

                    // Agenda Summary
                    let agendaY = y + 10;
                    doc.setFont("helvetica", "bold");
                    doc.text("Agenda Summary", 15, agendaY);
                    doc.setFont("helvetica", "normal");
                    let agendaCounts = data.agendaCounts;
                    y = agendaY + 5;
                    Object.keys(agendaCounts).forEach(agenda => {
                        doc.text(`${agenda} - ${agendaCounts[agenda]}`, 20, y);
                        y += 5;
                    });

                    addPageNumbers(doc);
                    doc.save(`Appointment_Report_${dateInput}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating report.");
                });
        }

        // ------------------------ MONTHLY REPORT FUNCTION ------------------------
        function generateAppointmentMonthlyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let selectedMonth = document.getElementById("reportMonth")?.value;

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

        // ------------------------ YEARLY REPORT FUNCTION ------------------------

        function generateAppointmentYearlyReport() {
            const { jsPDF } = window.jspdf;
            let doc = new jsPDF();
            let selectedYear = document.getElementById("reportYear")?.value;

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

        // ------------------------ FACULTY REPORT FUNCTION ------------------------
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
                    addReportHeader(doc, `Faculty Appointment Report - ${facultyName}`);

                    let tableData = [
                        ["Date Logged", "Code", "Student", "Start", "End", "Agenda", "Status"]
                    ];

                    data.appointments.forEach(appointment => {
                        tableData.push([
                            appointment.date_logged || "N/A",
                            appointment.appointment_code || "N/A",
                            `${appointment.stud_fname || ""} ${appointment.stud_lname || ""}`.trim(),
                            formatTime(appointment.start_time),
                            formatTime(appointment.end_time),
                            appointment.agenda || "N/A",
                            appointment.status || "N/A"
                        ]);
                    });

                    doc.autoTable({
                        startY: 60,
                        head: [tableData[0]],
                        body: tableData.slice(1),
                        theme: "grid",
                        styles: { fontSize: 9 },
                        margin: { bottom: 20 }, // Ensure space for footer
                        didParseCell: function (data) {
                            if ([1, 3, 4, 6].includes(data.column.index)) {
                                data.cell.styles.fontSize = 7;
                            }
                        }
                    });

                    // Status Summary
                    let statusY = doc.autoTable.previous.finalY + 10;
                    doc.setFont("helvetica", "bold");
                    doc.text("Status Summary", 15, statusY);
                    doc.setFont("helvetica", "normal");
                    let statusCounts = data.statusCounts;
                    let y = statusY + 5;
                    Object.keys(statusCounts).forEach(status => {
                        doc.text(`${status} - ${statusCounts[status]}`, 20, y);
                        y += 5;
                    });

                    // Agenda Summary
                    let agendaY = y + 10;
                    doc.setFont("helvetica", "bold");
                    doc.text("Agenda Summary", 15, agendaY);
                    doc.setFont("helvetica", "normal");
                    let agendaCounts = data.agendaCounts;
                    y = agendaY + 5;
                    Object.keys(agendaCounts).forEach(agenda => {
                        doc.text(`${agenda} - ${agendaCounts[agenda]}`, 20, y);
                        y += 5;
                    });

                    addPageNumbers(doc);
                    doc.save(`Faculty_Appointment_Report_${facultyName}.pdf`);
                })
                .catch(error => {
                    console.error("Error fetching data:", error);
                    alert("Error generating report.");
                });
        }

        // ------------------------ HELPER FUNCTIONS ------------------------
        function addReportHeader(doc, title) {
            let logoImage = "../../assets/images/csa_logo.png";
            doc.addImage(logoImage, "PNG", 15, 10, 26, 26);

            doc.setFont("times", "bold");
            doc.setFontSize(18);
            doc.text("Colegio de Sta. Teresa de Avila", 50, 18);

            doc.setFont("helvetica", "normal");
            doc.setFontSize(10);
            doc.text("Address: 6 Kingfisher corner Skylark Street, Zabarte Subdivision,", 50, 25);
            doc.text("Brgy. Kaligayahan, Novaliches, Quezon City, Philippines", 50, 30);
            doc.text("Contact: 282753916 | Email: officialregistrarcsta@gmail.com", 50, 35);

            doc.setLineWidth(0.5);
            doc.line(15, 40, 195, 40);

            doc.setFont("helvetica", "bold");
            doc.setFontSize(14);
            doc.text(title, 15, 50);

            let timestamp = new Date().toLocaleString();
            doc.setFont("helvetica", "italic");
            doc.setFontSize(9);
            doc.text(`Generated on: ${timestamp}`, 15, 55);
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