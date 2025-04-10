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
    <link rel="stylesheet" href="../assets/css/kiosk-design-landing.css" />
    <link rel="shortcut icon" href="../assets/images/F-Connect.ico" type="image/x-icon" />
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

        .logo-wrapper {
            margin-right: 10px;
            display: flex;
            align-items: flex-end;
            /* aligns text to bottom of image */
            gap: 0px;
        }

        .logo-wrapper img {
            width: 180px;
            height: 180px;
            opacity: 0;
            animation: fadeIn 1s ease forwards;
        }

        .logo-text {
            font-size: 5rem;
            font-weight: 800;
            background: linear-gradient(90deg, #2B5876, #043657);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-family: 'Unbounded', sans-serif;
            white-space: nowrap;
            overflow: hidden;
            letter-spacing: 3px;
            border-right: 4px solid #2B5876;
            width: 0;
            opacity: 0;
            animation: typing 1.5s ease forwards, blink 0.8s steps(1) infinite;
            animation-delay: 1.2s;
            animation-fill-mode: forwards;
            transform: translateY(9px);
            margin-left: -40px;
        }


        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        @keyframes typing {
            0% {
                width: 0;
                opacity: 1;
            }

            100% {
                width: 7.1ch;
                /* adjust as needed for final length */
                opacity: 1;
            }
        }

        @keyframes blink {

            0%,
            100% {
                border-color: transparent;
            }

            50% {
                transform: translateY(9px);
                border-color: #2B5876;
            }
        }

        /* Button Container */
        #top-right-button {
            position: absolute;
            bottom: 20px;
            right: 20px;
        }

        /* Button Styling */
        .help-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: linear-gradient(135deg, #FF790D, #FF9E2D);
            color: white;
            border: none;
            border-radius: 30px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(255, 121, 13, 0.3);
            position: relative;
            overflow: hidden;
        }

        .help-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 121, 13, 0.4);
            background: linear-gradient(135deg, #FF9E2D, #FF790D);
        }

        .help-btn:active {
            transform: translateY(0);
        }

        .help-btn i {
            font-size: 1.2rem;
        }

        /* Pulsing Icon Animation */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.1);
            }

            100% {
                transform: scale(1);
            }
        }

        .help-btn:hover i {
            animation: pulse 1.5s infinite;
        }

        .tooltip-container {
            position: relative;
            display: inline-block;
        }

        .tooltip-container:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-right: 10px;
            padding: 8px 12px;
            background: #333;
            color: white;
            border-radius: 6px;
            font-size: 0.85rem;
            font-family: 'Poppins', sans-serif;
            white-space: nowrap;
            opacity: 1;
            pointer-events: none;
            z-index: 1000;
        }

        .tooltip-container:hover::before {
            content: '';
            position: absolute;
            right: 100%;
            /* Changed from right to left */
            top: 50%;
            transform: translateY(-50%);
            margin-left: -6px;
            /* Changed from margin-right to margin-left */
            border-width: 6px;
            border-style: solid;
            border-color: transparent transparent transparent #333;
            /* Reordered border colors */
            z-index: 1001;
        }

        /* Glow Effect */
        .help-btn::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
            transition: all 0.3s ease;
        }

        .help-btn:hover::after {
            left: 100%;
        }

        /* Updated Tap to Proceed Animation */
        #rfid-message {
            font-size: 1.2rem;
            color: #333;
            background-color: rgba(255, 255, 255, 0.85);
            padding: 1.2rem 2.5rem;
            border-radius: 50px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            /* Changed from flex to inline-flex */
            align-items: center;
            justify-content: center;
            /* Center content horizontally */
            gap: 0.8rem;
            margin-top: 2rem;
            position: relative;
            overflow: hidden;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            border: 2px solid rgba(4, 54, 87, 0.2);
            animation: float 3s ease-in-out infinite, glow 2s ease-in-out infinite alternate;
            width: auto;
            /* Explicitly set to auto */
            white-space: nowrap;
            /* Prevent text wrapping */
            text-align: center;
            /* Center text */
        }

        /* Keep all your existing animations and hover effects */

        #rfid-message i {
            font-size: 1.5rem;
            transition: transform 0.3s ease;
        }

        #rfid-message:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background-color: rgba(255, 255, 255, 0.95);
            border-color: rgba(4, 54, 87, 0.3);
        }

        #rfid-message:hover i {
            transform: scale(1.2);
            animation: scanPulse 0.8s ease infinite;
        }

        /* Animation Keyframes */
        @keyframes float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-8px);
            }
        }

        @keyframes glow {
            from {
                box-shadow: 0 0 10px rgba(255, 255, 255, 0.8);
            }

            to {
                box-shadow: 0 0 20px rgba(255, 255, 255, 1);
            }
        }

        @keyframes scanPulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        /* Ripple Effect */
        #rfid-message::after {
            content: '';
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            width: 5px;
            height: 5px;
            background: rgba(255, 255, 255, 0.5);
            opacity: 0;
            border-radius: 100%;
            transform: scale(1, 1) translate(-50%, -50%);
            transform-origin: 50% 50%;
        }

        #rfid-message:hover::after {
            animation: ripple 1.5s ease-out infinite;
        }

        @keyframes ripple {
            0% {
                transform: scale(0, 0);
                opacity: 0.5;
            }

            100% {
                transform: scale(20, 20);
                opacity: 0;
            }
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
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
            background: linear-gradient(135deg, #ffffff 0%, #f5f7fa 100%);
            border-radius: 20px;
            padding: 2.5rem;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal-content p {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .modal-overlay.active .modal-content {
            transform: scale(1);
        }

        .modal-title {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-bottom: 2rem;
            font-family: 'Poppins', sans-serif;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .modal-options {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .modal-option {
            color: #333;
            font-size: 16px;
            font-weight: 500;
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            text-align: left;
            border: 4px solid transparent;
            /* base border so the thickness doesn't shift layout on hover */
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .modal-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.12);
            border: 4px solid #3498db;
            /* thicker and colored on hover */
        }

        .modal-option:active {
            transform: translateY(0);
        }

        .modal-option i {
            font-size: 2.5rem;
            margin-right: 1.5rem;
            color: #3498db;
            min-width: 50px;
            text-align: center;
        }

        .modal-option div {
            font-size: 1.3rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .hows {
            background: linear-gradient(145deg, #2196F3, #0b7dda);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(52, 152, 219, 0.3);
            margin-top: 1rem;
        }

        .hows:hover {
            background: #2980b9;
            transform: translateY(-2px);
            background: linear-gradient(145deg, #0b7dda, #2196F3);
        }

        .hows:active {
            transform: translateY(0);
        }

        #modalClose {
            width: 100%;
        }


        /* Animation for options when they appear */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-option {
            animation: fadeInUp 0.4s ease forwards;
        }

        .modal-option:nth-child(1) {
            animation-delay: 0.1s;
        }

        .modal-option:nth-child(2) {
            animation-delay: 0.2s;
        }

        /* Responsive adjustments */
        @media (max-width: 600px) {
            .modal-content {
                padding: 1.5rem;
            }

            .modal-title {
                font-size: 1.8rem;
            }

            .modal-option i {
                font-size: 2rem;
                margin-right: 1rem;
            }

            .modal-option div {
                font-size: 1.1rem;
            }
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
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        /* Error Modal Styles */
        .error-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .error-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .error-modal-content {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 10px 30px rgba(255, 59, 48, 0.2);
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-top: 6px solid #FF3B30;
            overflow: hidden;
        }

        .error-modal-overlay.active .error-modal-content {
            transform: translateY(0);
        }

        .error-modal-header {
            padding: 1.5rem;
            text-align: center;
            background: #fff;
        }

        .error-icon-circle {
            font-size: 50px;
            margin-bottom: 20px;
            animation: bounce 0.8s;
        }

        .error-modal-header h3 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 00px;
        }

        .error-modal-body {
            text-align: center;
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .error-modal-footer {
            padding: 12px 40px;
            display: flex;
            justify-content: center;
            background: #fff;
        }

        .error-modal-button {
            padding: 20px 30px;
            border: none;
            border-radius: 10px;
            font-size: 25px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #FF416C, #FF4B2B);
            color: white;
            width: 100%;
            margin-bottom: 20px;
        }

        .error-modal-button:hover {
            background: linear-gradient(135deg, #FF4B2B, #FF416C);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(255, 59, 48, 0.4);
        }

        .error-modal-button:active {
            transform: translateY(0);
        }

        /* Shake animation for attention */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .error-modal-content {
            animation: shake 0.5s cubic-bezier(.36, .07, .19, .97) both;
        }

        /* Info Message */
        .info-message {
            position: fixed;
            top: 1rem;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--info-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Info Modal Styles */
        .info-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .info-modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .info-modal-content {
            background: white;
            border-radius: 15px;
            width: 100%;
            max-width: 550px;
            box-shadow: 0 10px 30px rgba(74, 144, 226, 0.2);
            transform: translateY(20px);
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-top: 6px solid #4A90E2;
            overflow: hidden;
        }

        .info-modal-overlay.active .info-modal-content {
            transform: translateY(0);
        }

        .info-modal-header {
            padding: 1.5rem;
            text-align: center;
            background: #fff;
        }

        .info-icon-circle {
            font-size: 50px;
            margin-bottom: 20px;
            animation: bounce 0.8s;
        }

        .info-modal-header h3 {
            color: #333;
            font-size: 24px;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            margin-bottom: 00px;
        }

        .info-modal-body {
            text-align: center;
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .info-modal-footer {
            padding: 12px 40px;
            display: flex;
            justify-content: center;
            background: #fff;
        }

        .info-modal-button {
            padding: 20px 30px;
            border: none;
            border-radius: 10px;
            font-size: 25px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            background: linear-gradient(135deg, #4A90E2, #5B86E5);
            color: white;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-modal-button:hover {
            background: linear-gradient(135deg, #5B86E5, #4A90E2);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(74, 144, 226, 0.4);
        }

        .info-modal-button:active {
            transform: translateY(0);
        }

        /* Bounce animation for icon */
        @keyframes bounce {

            0%,
            20%,
            50%,
            80%,
            100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-20px);
            }

            60% {
                transform: translateY(-10px);
            }
        }
    </style>
</head>

<body>

    <?php
    if (!empty($_SESSION['error_message'])) {
        echo '
    <div class="error-modal-overlay" id="errorModal">
        <div class="error-modal-content">
            <div class="error-modal-header">
                <div class="error-icon-circle" style="color: #FF416C;">
                    <i class="bi bi-exclamation-circle"></i>
                </div>
                <h3>Oops! Something went wrong</h3>
            </div>
            <div class="error-modal-body">
                <p>' . htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <div class="error-modal-footer">
                <button class="error-modal-button" onclick="closeErrorModal()">
                    <i class="bi bi-check-circle"></i> Understood
                </button>
            </div>
        </div>
    </div>
    ';
        unset($_SESSION['error_message']);
    }
    ?>

    <?php
    if (!empty($_SESSION['info_message'])) {
        echo '
    <div class="info-modal-overlay" id="infoModal">
        <div class="info-modal-content">
            <div class="info-modal-header">
                <div class="info-icon-circle" style="color: #4A90E2;">
                    <i class="bi bi-info-circle"></i>
                </div>
                <h3>Information</h3>
            </div>
            <div class="info-modal-body">
                <p>' . htmlspecialchars($_SESSION['info_message'], ENT_QUOTES, 'UTF-8') . '</p>
            </div>
            <div class="info-modal-footer">
                <button class="info-modal-button" onclick="closeInfoModal()">
                    <i class="bi bi-check-circle"></i> Understood
                </button>
            </div>
        </div>
    </div>
    ';
        unset($_SESSION['info_message']);
    }
    ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>

    </nav>

    <div id="title-container">
        <div class="logo-wrapper">
            <img src="../../assets/images/F-Connect-removebg-preview.png" alt="F Logo">
            <div class="logo-text">CONNECT</div>
        </div>
        <p id="rfid-message">Tap anywwhere to proceed <i class="bi bi-hand-index"></i></p>
    </div>

    <div id="top-right-button">
        <div class="tooltip-container" data-tooltip="Click here if you forgot your RFID card">
            <a href="./other/kiosk-manual-entry.php" class="no-underline help-btn">
                <i class="bi bi-question-circle"></i>
                <span class="btn-text">Need Alternative Access?</span>
            </a>
        </div>
    </div>

    <div class="overlay" id="overlay" onclick="toggleModal()"></div>

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
            <!-- Added Close Button (matches your existing style) -->
            <button class="hows" id="modalClose" onclick="hideModal('mainModal')">
                <i class="bi bi-x-lg"></i> Close
            </button>
        </div>
    </div>

    <!-- Student Modal -->
    <div class="modal-overlay" id="studentModal">
        <div class="modal-content">
            <p>Student Verification</p>
            <div class="modal-options">
                <div class="modal-option">
                    <i class="bi bi-upc-scan"></i> Tap your RFID card to proceed
                </div>
            </div>
            <!-- Hidden Input Field -->
            <form id="rfid-form" method="POST" action="functions/check-student.php">
                <input type="hidden" id="rfid-id" name="rfid_id" value="">
            </form>
            <button class="how" id="modalClose" onclick="backToMainModal()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>

    <!-- Faculty Modal -->
    <div class="modal-overlay" id="facultyModal">
        <div class="modal-content">
            <p>Choose your verification method</p>
            <div class="modal-options">
                <div class="modal-option" id="facultyRfidOption">
                    <i class="bi bi-upc-scan"></i> RFID Card
                </div>
                <div class="modal-option" onclick="window.location.href='./faculty/kiosk-detect-face.php'">
                    <i class="bi bi-camera"></i> Open Facial Recognition
                </div>
            </div>
            <!-- Hidden Input Field -->
            <form id="faculty-rfid-form" method="POST" action="functions/check-faculty.php">
                <input type="hidden" id="faculty-rfid-id" name="rfid_id" value="">
            </form>
            <button class="how" id="modalClose" onclick="backToMainModal()">
                <i class="bi bi-arrow-left"></i> Back
            </button>
        </div>
    </div>


    <footer>
        <button class="footer-button" onclick="toggleModal()"><i
                class="custom-i bi bi-cloud-arrow-down-fill"></i></button>
    </footer>

    <div class="custom-modal" id="customModal">
        <p class="scan-me">SCAN ME</p>
        <img class="qr-image" src="../assets/images/bit.ly_4bq5MUR.png" alt="QR Code">
    </div>

    <script>
        function toggleModal() {
            var modal = document.getElementById("customModal");
            var overlay = document.getElementById("overlay");

            if (modal.style.display === "block") {
                modal.style.display = "none";
                overlay.style.display = "none";
            } else {
                modal.style.display = "block";
                overlay.style.display = "block";
            }
        }
    </script>

    <!-- Scripts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="../assets/js/custom-javascript.js"></script>

    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>

    <script>
        // Show the modal immediately
        document.addEventListener('DOMContentLoaded', function () {
            const errorModal = document.getElementById('errorModal');
            if (errorModal) {
                errorModal.classList.add('active');

                // Auto-close after 5 seconds
                setTimeout(() => {
                    closeErrorModal();
                }, 10000);
            }
        });

        function closeErrorModal() {
            const errorModal = document.getElementById('errorModal');
            if (errorModal) {
                errorModal.classList.remove('active');
                setTimeout(() => {
                    errorModal.remove();
                }, 300);
            }
        }
    </script>

    <script>
        /** RFID Input Auto-Submit */
        const rfidInput = document.getElementById('rfid-id');
        const rfidForm = document.getElementById('rfid-form');
        const studentModal = document.getElementById('studentModal');
        let isStudentModalOpen = false;

        // Track modal state
        studentModal.addEventListener('click', function (e) {
            if (e.target === studentModal) {
                isStudentModalOpen = false;
            }
        });

        function handleModalState(open) {
            isStudentModalOpen = open;
            if (open) {
                rfidInput.value = ''; // Clear input when modal opens
                rfidInput.focus(); // Set focus for RFID input
            }
        }

        // Only process input when modal is open
        if (rfidInput && rfidForm) {
            document.addEventListener('keydown', (event) => {
                if (isStudentModalOpen && event.target === document.body) {
                    const key = event.key;

                    if (key === 'Enter') {
                        // Submit the form when Enter is pressed
                        if (rfidInput.value.trim() !== '') {
                            rfidForm.submit();
                        }
                        event.preventDefault();
                    } else {
                        // Append keystrokes to the hidden input field
                        rfidInput.value += key;
                        event.preventDefault();
                    }
                }
            });
        }
    </script>
    <script>
        /** Faculty RFID Input Handling */
        const facultyRfidInput = document.getElementById('faculty-rfid-id');
        const facultyRfidForm = document.getElementById('faculty-rfid-form');
        const facultyModal = document.getElementById('facultyModal');
        const facultyRfidOption = document.getElementById('facultyRfidOption');
        let isFacultyModalOpen = false;
        let isRfidModeActive = false;

        // Track modal state
        facultyModal.addEventListener('click', function (e) {
            if (e.target === facultyModal) {
                isFacultyModalOpen = false;
                isRfidModeActive = false;
                updateRfidOptionDisplay();
            }
        });

        function handleFacultyModalState(open) {
            isFacultyModalOpen = open;
            if (!open) {
                isRfidModeActive = false;
                updateRfidOptionDisplay();
            }
            facultyRfidInput.value = ''; // Clear input when modal state changes
        }

        function updateRfidOptionDisplay() {
            if (isRfidModeActive) {
                facultyRfidOption.innerHTML = '<i class="bi bi-upc-scan"></i> Tap your RFID card now';
                facultyRfidOption.style.backgroundColor = '#f0f8ff';
                facultyRfidOption.style.border = '4px solid #3498db';
            } else {
                facultyRfidOption.innerHTML = '<i class="bi bi-upc-scan"></i> RFID Card';
                facultyRfidOption.style.backgroundColor = '';
                facultyRfidOption.style.border = '2px solid transparent';
                facultyRfidOption.style.boxShadow = 'none';
            }
        }

        // Handle RFID option click
        facultyRfidOption.addEventListener('click', function () {
            if (!isRfidModeActive) {
                isRfidModeActive = true;
                updateRfidOptionDisplay();
                facultyRfidInput.focus(); // Set focus for RFID input
            }
        });

        // Only process input when modal is open and in RFID mode
        if (facultyRfidInput && facultyRfidForm) {
            document.addEventListener('keydown', (event) => {
                if (isFacultyModalOpen && isRfidModeActive && event.target === document.body) {
                    const key = event.key;

                    if (key === 'Enter') {
                        // Submit the form when Enter is pressed
                        if (facultyRfidInput.value.trim() !== '') {
                            facultyRfidForm.submit();
                        }
                        event.preventDefault();
                    } else {
                        // Append keystrokes to the hidden input field
                        facultyRfidInput.value += key;
                        event.preventDefault();
                    }
                }
            });
        }
    </script>
    <script>
        window.addEventListener('load', () => {
            document.getElementById('logoContainer').classList.add('loaded');
        });
    </script>
    <script>
        // Array of possible messages that will cycle
        const messages = [
            "Tap anywhere to begin",
            "Touch the screen to start",
            "Tap anywhere to proceed",
            "Welcome, tap anywhere to enter",
            "Connect with a touch",
            "Tap anywhere to authenticate",
            "Tap anywhere to get started",
        ];

        // Current message index
        let currentMessage = 0;
        const messageElement = document.getElementById('rfid-message');

        // Change message every 5 seconds
        function cycleMessages() {
            currentMessage = (currentMessage + 1) % messages.length;
            messageElement.innerHTML = `${messages[currentMessage]} <i class="bi bi-hand-index"></i>`;

            // Add animation class
            messageElement.classList.add('message-change');

            // Remove after animation completes
            setTimeout(() => {
                messageElement.classList.remove('message-change');
            }, 500);
        }

        // Start cycling messages
        setInterval(cycleMessages, 5000);

        // Also change message on click/tap
        messageElement.addEventListener('click', cycleMessages);
    </script>
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
            handleModalState(true);
        }

        function showFacultyModal() {
            hideModal('mainModal');
            showModal('facultyModal');
            handleFacultyModalState(true);
        }

        function backToMainModal() {
            hideModal('studentModal');
            hideModal('facultyModal');
            showModal('mainModal');
            handleModalState(false);
            handleFacultyModalState(false);
        }

        function submitRfidForm() {
            document.getElementById('rfid-form').submit();
        }

        // Show main modal when clicking anywhere
        document.addEventListener('click', function (e) {
            // Don't trigger if clicking on modal or buttons that have their own handlers
            if (!e.target.closest('.modal-content') &&
                !e.target.closest('#top-right-button') &&
                !e.target.closest('footer')) {
                showModal('mainModal');
            }
        });
    </script>
    <script>
        // Show the modal immediately
        document.addEventListener('DOMContentLoaded', function () {
            const infoModal = document.getElementById('infoModal');
            if (infoModal) {
                infoModal.classList.add('active');

                // Auto-close after 5 seconds
                setTimeout(() => {
                    closeInfoModal();
                }, 10000);
            }
        });

        function closeInfoModal() {
            const infoModal = document.getElementById('infoModal');
            if (infoModal) {
                infoModal.classList.remove('active');
                setTimeout(() => {
                    infoModal.remove();
                }, 300);
            }
        }
    </script>
</body>

</html>