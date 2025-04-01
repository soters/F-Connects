<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal2 = document.getElementById("customModal2");
    const openModal2 = document.getElementById("openModal2");
    const closeModal2 = document.querySelector(".close-modal2");
    const reportType2 = document.getElementById("reportType2");
    const extraFields2 = document.getElementById("extraFields2");
    const generatePdf2 = document.getElementById("generatePdf2");

    // Open & Close Modal
    openModal2.addEventListener("click", () => modal2.style.display = "flex");
    closeModal2.addEventListener("click", () => modal2.style.display = "none");
    window.addEventListener("click", (event) => {
        if (event.target === modal2) modal2.style.display = "none";
    });

    // Handle report type selection
    reportType2.addEventListener("change", function () {
        extraFields2.innerHTML = "";
        let selectedType = this.value;

        if (selectedType === "weekly") {
            extraFields2.innerHTML = `
                <label>Select Week:</label>
                <input type="week" id="reportWeek" class="form-control">
            `;
            generatePdf2.setAttribute("data-type", "weekly");

        } else if (selectedType === "monthly") {
            extraFields2.innerHTML = `
                <label>Select Month:</label>
                <input type="month" id="reportMonth" class="form-control">
            `;
            generatePdf2.setAttribute("data-type", "monthly");

        } else if (selectedType === "yearly") {
            extraFields2.innerHTML = `
                <label>Select Year:</label>
                <select id="reportYear" class="form-select">
                    ${generateYearOptions()}
                </select>
            `;
            generatePdf2.setAttribute("data-type", "yearly");

        } else if (selectedType === "custom") {
            extraFields2.innerHTML = `
                <label>Start Date:</label>
                <input type="date" id="startDate" class="form-control">

                <label>End Date:</label>
                <input type="date" id="endDate" class="form-control">
            `;
            generatePdf2.setAttribute("data-type", "custom");
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
    generatePdf2.addEventListener("click", function () {
        let reportType = this.getAttribute("data-type");
        let facultyOption = document.getElementById("facultySelect").value;
        let facultyRFID = facultyOption === "all" ? null : facultyOption;

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
    function generateMonthlyReport(facultyRFID = null) {
        const { jsPDF } = window.jspdf;
        let doc = new jsPDF();

        let selectedMonth = document.getElementById("reportMonth")?.value;
        
        // Validate inputs
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

        // Build API URL
        let apiUrl = `../functions/fetch-records-by-month.php?month=${selectedMonth}&year=${selectedYear}`;
        if (facultyRFID) {
            apiUrl += `&rfid_no=${facultyRFID}`;
        }

        // Fetch attendance records
        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (facultyRFID) {
                    // Single faculty report
                    generateSingleFacultyMonthlyReport(doc, data, selectedMonth, selectedYear);
                } else {
                    // All faculty report
                    generateAllFacultyMonthlyReport(doc, data, selectedMonth, selectedYear);
                }
            })
            .catch(err => {
                console.error("Error:", err);
                alert("Error generating report. Please try again.");
            });
    }

    function generateSingleFacultyMonthlyReport(doc, data, selectedMonth, selectedYear) {
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

        // ... rest of your existing single faculty report generation code ...

        doc.save(`Monthly_Attendance_Report_${facultyInfo.fname.replace(/\s+/g, '_')}_${facultyInfo.lname.replace(/\s+/g, '_')}_${selectedMonth}.pdf`);
    }

    function generateAllFacultyMonthlyReport(doc, data, selectedMonth, selectedYear) {
        // Check if data is an array (for all faculty)
        if (!Array.isArray(data)) {
            alert("Invalid data format for all faculty report.");
            return;
        }

        // --- Header ---
        addReportHeader(doc, "Faculty Attendance Summary", `Monthly Report - ${selectedMonth}`);

        let y = 60;

        // Summary table for all faculty
        const summaryBody = data.map(faculty => {
            const attendancePercentage = faculty.totalScheduledDays > 0 
                ? (faculty.totalPresent / faculty.totalScheduledDays) * 100 
                : 0;
            
            return [
                `${faculty.fname} ${faculty.lname}`,
                faculty.totalScheduledDays,
                faculty.totalPresent,
                faculty.totalAbsent,
                faculty.totalLate,
                faculty.totalScheduledHours.toFixed(2),
                faculty.totalRenderedHours.toFixed(2),
                attendancePercentage.toFixed(2) + "%"
            ];
        });

        doc.autoTable({
            startY: y,
            head: [["Faculty", "Days", "Present", "Absent", "Late", "Sched Hrs", "Worked Hrs", "Att %"]],
            body: summaryBody,
            theme: "grid",
            styles: { fontSize: 9 },
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
        doc.save(`Monthly_Attendance_Summary_${selectedMonth}.pdf`);
    }

    // ... similar pattern for weekly, yearly, and custom reports ...

    // ------------------------ ATTENDANCE HELPER FUNCTIONS ------------------------
    function addReportHeader(doc, title, subtitle = "") {
        // ... keep your existing header function ...
    }

    function addPageNumbers(doc) {
        // ... keep your existing page numbers function ...
    }

    function formatTime(time) {
        // ... keep your existing time formatting function ...
    }
});
</script>