<?php
declare(strict_types=1);
session_start();
require_once('../connection/connection.php');
date_default_timezone_set('Asia/Manila');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin" />
    <!-- Custom Links -->
    <link rel="stylesheet" href="../assets/css/kiosk-design.css" />
    <link rel="shortcut icon" href="../assets/images/F-Connect.ico" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <title>F - Connect</title>
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --success-color: #4bb543;
            --warning-color: #ffcc00;
            --danger-color: #f44336;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }
        
        .navi-bar {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        #title-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-grow: 1;
            padding: 2rem;
            text-align: center;
        }
        
        .title-logo {
            max-width: 300px;
            margin-bottom: 2rem;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
        
        #rfid-message {
            font-size: 1.5rem;
            color: var(--dark-color);
            background-color: rgba(255, 255, 255, 0.8);
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        #rfid-message:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }
        
        #top-right-button {
            position: absolute;
            top: 1rem;
            right: 1rem;
        }
        
        .how {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .how:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .no-underline {
            text-decoration: none;
            color: white;
        }
        
        footer {
            background-color: var(--dark-color);
            color: white;
            text-align: center;
            padding: 1rem;
            position: fixed;
            bottom: 0;
            width: 100%;
        }
        
        .footer-button {
            background-color: var(--accent-color);
            color: var(--dark-color);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0 auto;
        }
        
        .footer-button:hover {
            background-color: #3aa8d8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .modal-content {
            background-color: white;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            transform: translateY(20px);
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }
        
        .modal-title {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }
        
        .modal-options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .modal-option {
            background-color: var(--light-color);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 1rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .modal-option:hover {
            background-color: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }
        
        .modal-option i {
            font-size: 1.5rem;
        }
        
        .qr-modal .modal-content {
            max-width: 300px;
        }
        
        .scan-me {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .qr-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
        }
        
        /* Error Message */
        .error-message {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--danger-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Animation */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>

<body>

    <?php
    if (!empty($_SESSION['error_message'])) {
        echo '<div id="error-message" class="error-message alert alert-danger" role="alert">';
        echo '<i class="bi bi-exclamation-triangle"></i> ';
        echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
        echo '</div>';
        unset($_SESSION['error_message']); // Clear the error message after displaying it
    }
    ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>
    </nav>

    <div id="title-container">
        <img src="../assets/images/F-Connect_L4.png" alt="F-Connect Logo" class="title-logo">
        <p id="rfid-message" class="pulse">Tap anywhere to proceed <i class="bi bi-upc-scan"></i></p>

        <!-- Hidden Input Field -->
        <form id="rfid-form" method="POST" action="functions/check-type.php">
            <input type="hidden" id="rfid-id" name="rfid_id" value="">
        </form>
    </div>

    <div id="top-right-button">
        <button type="button" class="how">
            <a href="./other/kiosk-manual-entry.php" class="no-underline" id="how">Forgot your RFID Card?</a>
        </button>
    </div>

    <!-- Main Modal (User Type Selection) -->
    <div class="modal-overlay" id="mainModal">
        <div class="modal-content">
            <h2 class="modal-title">Select Your User Type</h2>
            <div class="modal-options">
                <div class="modal-option" onclick="showStudentModal()">
                    <i class="bi bi-mortarboard"></i> Student
                </div>
                <div class="modal-option" onclick="showFacultyModal()">
                    <i class="bi bi-person-badge"></i> Faculty Member
                </div>
            </div>
        </div>
    </div>

    <!-- Student Modal -->
    <div class="modal-overlay" id="studentModal">
        <div class="modal-content">
            <h2 class="modal-title">Student Login</h2>
            <p>Please tap your RFID card to proceed</p>
            <div class="modal-options">
                <div class="modal-option" onclick="submitRfidForm()">
                    <i class="bi bi-upc-scan"></i> Tap RFID Card
                </div>
            </div>
            <button class="how" style="margin-top: 1rem;" onclick="backToMainModal()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <!-- Faculty Modal -->
    <div class="modal-overlay" id="facultyModal">
        <div class="modal-content">
            <h2 class="modal-title">Faculty Login</h2>
            <p>Choose your login method</p>
            <div class="modal-options">
                <div class="modal-option" onclick="window.location.href='faculty-rfid.php'">
                    <i class="bi bi-upc-scan"></i> RFID Card
                </div>
                <div class="modal-option" onclick="window.location.href='faculty-facial.php'">
                    <i class="bi bi-camera"></i> Facial Recognition
                </div>
            </div>
            <button class="how" style="margin-top: 1rem;" onclick="backToMainModal()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <!-- QR Modal -->
    <div class="modal-overlay qr-modal" id="qrModal">
        <div class="modal-content">
            <p class="scan-me">Scan Me</p>
            <img class="qr-image" src="../assets/images/bit.ly_4bq5MUR.png" alt="QR Code">
            <button class="how" style="margin-top: 1rem;" onclick="hideModal('qrModal')">
                <i class="bi bi-x"></i> Close
            </button>
        </div>
    </div>

    <footer>
        <button class="footer-button" onclick="showModal('qrModal')">
            Get the app <i class="custom-i bi bi-cloud-arrow-down-fill"></i>
        </button>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="../assets/js/custom-javascript.js"></script>

    <script>
        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }
        
        function hideModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        function showStudentModal() {
            hideModal('mainModal');
            showModal('studentModal');
        }
        
        function showFacultyModal() {
            hideModal('mainModal');
            showModal('facultyModal');
        }
        
        function backToMainModal() {
            hideModal('studentModal');
            hideModal('facultyModal');
            showModal('mainModal');
        }
        
        function submitRfidForm() {
            document.getElementById('rfid-form').submit();
        }
        
        // Show main modal when clicking anywhere
        document.addEventListener('click', function(e) {
            // Don't trigger if clicking on modal or buttons that have their own handlers
            if (!e.target.closest('.modal-content') && 
                !e.target.closest('#top-right-button') && 
                !e.target.closest('footer')) {
                showModal('mainModal');
            }
        });
        
        // Automatically hide the error message after 3 seconds
        setTimeout(() => {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.transition = 'opacity 0.5s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.remove();
                }, 500);
            }
        }, 3000);
        
        /** RFID Input Auto-Submit */
        const rfidInput = document.getElementById('rfid-id');
        const rfidForm = document.getElementById('rfid-form');
        
        if (rfidInput && rfidForm) {
            document.addEventListener('keydown', (event) => {
                if (event.target === document.body) {
                    const key = event.key;
        
                    if (key === 'Enter') {
                        if (rfidInput.value.trim() !== '') {
                            rfidForm.submit();
                        }
                    } else {
                        rfidInput.value += key;
                    }
                }
            });
        }
        
        // Date and time display
        function updateDateTime() {
            const now = new Date();
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            
            document.getElementById('current-time').textContent = now.toLocaleTimeString('en-US', timeOptions);
            document.getElementById('live-date').textContent = now.toLocaleDateString('en-US', dateOptions);
        }
        
        setInterval(updateDateTime, 1000);
        updateDateTime(); // Initialize immediately
    </script>
</body>

</html>