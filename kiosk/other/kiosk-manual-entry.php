<?php
declare(strict_types=1);
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="view-transition" content="same-origin" />
    <!-- Custom Links -->
    <link rel="stylesheet" href="../../assets/css/kiosk-design.css" />
    <link rel="shortcut icon" href="../../assets/images/F-Connect.ico" type="image/x-icon" />
    <title>F - Connect</title>
    <style>
        /* Updated Keyboard Styles */
        .keyboard-row {
            display: flex;
            justify-content: center;
            margin-bottom: 12px;
            gap: 8px;
        }

        .key,
        .keyboard-special,
        .keyboard-mode {
            padding: 20px;
            min-width: 70px;
            height: 70px;
            font-size: 1.8rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            border: none;
            border-radius: 7px;
            background: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            user-select: none;
            touch-action: manipulation;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.1s ease;
            color: #2c3e50;
        }

        .keyboard-special {
            background: #ecf0f1;
            font-size: 1.6rem;
        }

        .keyboard-mode {
            background: #bdc3c7;
            font-weight: 600;
            font-size: 1.3rem;
            padding: 15px 20px;
            height: auto;
        }

        .keyboard-mode.active {
            background: #3498db;
            color: white;
        }

        .key:active,
        .keyboard-special:active,
        .keyboard-mode:active {
            transform: scale(0.95);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .key:active {
            background: #3498db;
            color: white;
        }

        .keyboard-special:active {
            background: #95a5a6;
            color: white;
        }

        .keyboard-layout {
            display: none;
        }

        #keyboard-shift {
            font-weight: bold;
            background: #bdc3c7;
            font-size: 1.6rem;
            border: none;
            border-radius: 7px;
        }

        #keyboard-shift.active {
            background: #3498db;
            color: white;
            border: none;
            border-radius: 7px;
        }

        #keyboard-shift.caps-active {
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 7px;
        }

        #keyboard-close {
            background: #e74c3c;
            color: white;
            font-size: 1.3rem;
            border: none;
            border-radius: 7px;
        }

        /* Add to your head section */
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    </style>
</head>

<body class="fade-out">

    <!-- Display Error Message -->
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="error-message alert alert-danger" role="alert">
            <i class="bi bi-exclamation-triangle"></i>
            <?= htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>
    </nav>

    <!-- Modal Overlay -->
    <div id="modal-overlay"
        style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7); display: flex; justify-content: center; align-items: center; z-index: 1000;">

        <!-- Modal Container -->
        <div id="modal-container"
            style="background: white; border-radius: 20px; width: 90%; max-width: 700px; box-shadow: 0 15px 40px rgba(0,0,0,0.3); overflow: hidden; animation: modalFadeIn 0.4s ease-out;">

            <!-- Modal Header -->
            <div
                style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); padding: 30px; color: white; text-align: center;">
                <h2 style="margin: 0; font-weight: 600; font-size: 2em;">ACCOUNT INFORMATION</h2>
                <p style="margin: 10px 0 0; opacity: 0.9; font-size: 1.2em;">Please provide your account information</p>
            </div>

            <!-- Modal Body -->
            <div style="padding: 35px;">
                <form id="student-form" method="POST" action="../functions/check-email.php">
                    <div style="margin-bottom: 35px;">
                        <label
                            style="display: block; margin-bottom: 15px; color: #34495e; font-weight: 500; font-size: 1.3em;  font-family: 'Poppins';">EMAIL</label>
                        <div style="position: relative; margin-bottom: 10px;">
                            <input id="student-info" name="email" type="text" required
                                style="width: 100%; padding: 20px; border: 3px solid #e0e0e0; font-weight: 500; border-radius: 12px; font-size: 1.4em;  font-family: 'Poppins'; transition: all 0.3s ease;"
                                onfocus="this.style.borderColor='#3498db'; this.style.boxShadow='0 0 0 4px rgba(52, 152, 219, 0.2)'"
                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                                placeholder="Enter your email">
                        </div>
                        <label
                            style="display: block; margin-bottom: 15px; color: #34495e; font-weight: 500; font-size: 1.3em; font-family: 'Poppins';">PASSWORD</label>
                        <div style="position: relative; margin-bottom: 10px;">
                            <input id="student-info-password" name="password" type="password" required
                                style="width: 100%; padding: 20px; border: 3px solid #e0e0e0; font-weight: 500; border-radius: 12px; font-size: 1.4em; transition: all 0.3s ease;"
                                onfocus="this.style.borderColor='#3498db'; this.style.boxShadow='0 0 0 4px rgba(52, 152, 219, 0.2)'"
                                onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none'"
                                placeholder="Enter your password">
                            <!-- Custom SVG Eye Icons -->
                            <div id="togglePassword"
                                style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); cursor: pointer; width: 36px; height: 36px; padding: 8px;">
                                <!-- Visible Eye Icon (shown by default) -->
                                <svg id="eye-show" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#7f8c8d"
                                    style="width: 100%; height: 100%;">
                                    <path
                                        d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z" />
                                </svg>
                                <!-- Slashed Eye Icon (hidden by default) -->
                                <svg id="eye-hide" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#7f8c8d"
                                    style="width: 100%; height: 100%; display: none;">
                                    <path
                                        d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46C3.08 8.3 1.78 10.02 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78l3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z" />
                                </svg>
                            </div>
                        </div>
                        <div id="password-strength"
                            style="height: 8px; background: #f0f0f0; border-radius: 4px; margin-top: 15px; overflow: hidden;">
                            <div id="strength-bar"
                                style="height: 100%; width: 0%; background: #e74c3c; transition: width 0.3s ease, background 0.3s ease;">
                            </div>
                        </div>
                        <p id="strength-text"
                            style="font-size: 1.1em; color: #7f8c8d; margin-top: 10px; text-align: right;">Password
                            strength</p>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 15px;">
                        <!-- Updated button -->
                        <button type="button" id="cancel-btn" style="flex: 1; background: #f5f5f5; color: #555; border: none; padding: 20px; border-radius: 12px;
    font-size: 1.3em; font-weight: 500; cursor: pointer; transition: all 0.3s ease;
    font-family: 'Poppins', sans-serif;" onmouseover="this.style.background='#e0e0e0'"
                            onmouseout="this.style.background='#f5f5f5'">
                            CANCEL
                        </button>
                        <button type="submit"
                            style="flex: 2; background: linear-gradient(to right, #2ecc71, #27ae60); color: white; border: none; padding: 20px; border-radius: 12px; font-size: 1.3em;  font-family: 'Poppins'; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 6px 10px rgba(0,0,0,0.1);"
                            onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 12px rgba(0,0,0,0.15)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 10px rgba(0,0,0,0.1)'">
                            VERIFY & CONTINUE
                        </button>
                    </div>
                </form>

                <!-- Footer Links 
                <div style="text-align: center; margin-top: 30px;">
                    <a href="#"
                        style="color: #3498db; text-decoration: none; font-size: 1.2em; transition: color 0.2s ease; display: block; margin-bottom: 12px; padding: 10px;"
                        onmouseover="this.style.color='#2980b9'; this.style.textDecoration='underline'"
                        onmouseout="this.style.color='#3498db'; this.style.textDecoration='none'">
                        Forgot your password?
                    </a>
                    <a href="#"
                        style="color: #7f8c8d; text-decoration: none; font-size: 1em; transition: color 0.2s ease; padding: 10px;"
                        onmouseover="this.style.color='#555'; this.style.textDecoration='underline'"
                        onmouseout="this.style.color='#7f8c8d'; this.style.textDecoration='none'">
                        Need help? Contact support
                    </a>
                </div>-->
            </div>
        </div>
    </div>

    <style>
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-30px) scale(0.95);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Make everything more touch-friendly */
        input,
        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        button,
        a {
            user-select: none;
        }

        /* Eye icon hover effects */
        #togglePassword:hover svg {
            fill: #3498db;
            transform: scale(1.1);
        }

        #togglePassword svg {
            transition: all 0.2s ease;
        }
    </style>

    <!--div id="top-right-button">
            <button type="button" class="small-button" data-bs-toggle="tooltip" title="Need help?"
                data-bs-placement="left">
                <i class="bi bi-question-lg"></i>
        </div>-->

    <!--<div id="top-left-button">
        <a href="../kiosk-index.php" class="no-underline">
            <button type="button" class="small-button" data-bs-toggle="tooltip" title="Back" data-bs-placement="right">
                <i class="bi bi-arrow-left-short"></i>
            </button>
        </a>
    </div>-->

    <!--<footer>
        <p id="collaboration-text">In collaboration with Colegio de Sta. Teresa de Avila</p>
    </footer>-->

    <!-- Add this before </body> -->
    <div id="custom-keyboard" style="
    position: fixed;
    bottom: 0;
    left: 0;
    width: 100%;
    background: #f5f5f5;
    padding: 10px;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    display: none;
    z-index: 10000;
">
        <!-- Keyboard Header (Mode Toggles) -->
        <div class="keyboard-row" style="margin-bottom: 15px;">
            <button class="keyboard-mode" data-mode="letters" style="flex: 1;">ABC</button>
            <button class="keyboard-mode" data-mode="numbers" style="flex: 1;">123</button>
            <button class="keyboard-mode" data-mode="symbols" style="flex: 1;">#+=</button>
            <button id="keyboard-shift" style="flex: 1;"><i class="bi bi-capslock"></i></button>
            <button id="keyboard-close" style="flex: 1;"><i class="bi bi-keyboard"></i></button>
        </div>

        <!-- Letters Layout (Default) -->
        <div id="letters-layout" class="keyboard-layout">
            <div class="keyboard-row">
                <button class="key" data-key="q">q</button>
                <button class="key" data-key="w">w</button>
                <button class="key" data-key="e">e</button>
                <button class="key" data-key="r">r</button>
                <button class="key" data-key="t">t</button>
                <button class="key" data-key="y">y</button>
                <button class="key" data-key="u">u</button>
                <button class="key" data-key="i">i</button>
                <button class="key" data-key="o">o</button>
                <button class="key" data-key="p">p</button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key="a">a</button>
                <button class="key" data-key="s">s</button>
                <button class="key" data-key="d">d</button>
                <button class="key" data-key="f">f</button>
                <button class="key" data-key="g">g</button>
                <button class="key" data-key="h">h</button>
                <button class="key" data-key="j">j</button>
                <button class="key" data-key="k">k</button>
                <button class="key" data-key="l">l</button>
                <button class="keyboard-special" data-action="backspace"><i class="bi bi-backspace"></i></button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key="z">z</button>
                <button class="key" data-key="x">x</button>
                <button class="key" data-key="c">c</button>
                <button class="key" data-key="v">v</button>
                <button class="key" data-key="b">b</button>
                <button class="key" data-key="n">n</button>
                <button class="key" data-key="m">m</button>
                <button class="key" data-key=",">,</button>
                <button class="key" data-key=".">.</button>
                <button class="keyboard-special" data-action="enter">↵</button>
            </div>
            <div class="keyboard-row">
                <button class="keyboard-special" data-action="space" style="flex: 5;">SPACE</button>
                <button class="keyboard-special" data-action="hide" style="flex: 2;">HIDE</button>
            </div>
        </div>

        <!-- Numbers Layout -->
        <div id="numbers-layout" class="keyboard-layout" style="display: none;">
            <div class="keyboard-row">
                <button class="key" data-key="1">1</button>
                <button class="key" data-key="2">2</button>
                <button class="key" data-key="3">3</button>
                <button class="key" data-key="4">4</button>
                <button class="key" data-key="5">5</button>
                <button class="key" data-key="6">6</button>
                <button class="key" data-key="7">7</button>
                <button class="key" data-key="8">8</button>
                <button class="key" data-key="9">9</button>
                <button class="key" data-key="0">0</button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key="-"">-</button>
            <button class=" key" data-key="/">/</button>
                <button class="key" data-key=":">:</button>
                <button class="key" data-key=";">;</button>
                <button class="key" data-key="(">(</button>
                <button class="key" data-key=")">)</button>
                <button class="key" data-key="$">$</button>
                <button class="key" data-key="&">&</button>
                <button class="key" data-key="@">@</button>
                <button class="keyboard-special" data-action="backspace"><i class="bi bi-backspace"></i></button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key=".">.</button>
                <button class="key" data-key=",">,</button>
                <button class="key" data-key="?">?</button>
                <button class="key" data-key="!">!</button>
                <button class="key" data-key="'">'</button>
                <button class="key" data-key=""">" </button>
                    <button class="key" data-key="=">=</button>
                    <button class="key" data-key="+">+</button>
                    <button class="key" data-key="%">%</button>
                    <button class="keyboard-special" data-action="enter">↵</button>
            </div>
            <div class="keyboard-row">
                <button class="keyboard-special" data-action="space" style="flex: 5;">SPACE</button>
                <button class="keyboard-special" data-action="hide" style="flex: 2;">HIDE</button>
            </div>
        </div>

        <!-- Symbols Layout -->
        <div id="symbols-layout" class="keyboard-layout" style="display: none;">
            <div class="keyboard-row">
                <button class="key" data-key="[">[</button>
                <button class="key" data-key="]">]</button>
                <button class="key" data-key="{">{</button>
                <button class="key" data-key="}">}</button>
                <button class="key" data-key="#">#</button>
                <button class="key" data-key="%">%</button>
                <button class="key" data-key="^">^</button>
                <button class="key" data-key="*">*</button>
                <button class="key" data-key="+">+</button>
                <button class="key" data-key="=">=</button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key="_">_</button>
                <button class="key" data-key="\">\</button>
                <button class="key" data-key="|">|</button>
                <button class="key" data-key="~">~</button>
                <button class="key" data-key="<">
                    << /button>
                        <button class="key" data-key=">">></button>
                        <button class="key" data-key="€">€</button>
                        <button class="key" data-key="£">£</button>
                        <button class="key" data-key="¥">¥</button>
                        <button class="keyboard-special" data-action="backspace"><i
                                class="bi bi-backspace"></i></button>
            </div>
            <div class="keyboard-row">
                <button class="key" data-key=".">.</button>
                <button class="key" data-key=",">,</button>
                <button class="key" data-key="?">?</button>
                <button class="key" data-key="!">!</button>
                <button class="key" data-key="'">'</button>
                <button class="key" data-key=""">" </button>
                    <button class="key" data-key="`">`</button>
                    <button class="key" data-key="§">§</button>
                    <button class="key" data-key="±">±</button>
                    <button class="keyboard-special" data-action="enter">↵</button>
            </div>
            <div class="keyboard-row">
                <button class="keyboard-special" data-action="space" style="flex: 5;">SPACE</button>
                <button class="keyboard-special" data-action="hide" style="flex: 2;">HIDE</button>
            </div>
        </div>
        <!-- Warning Modal -->
        <div id="email-warning-modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.5); z-index: 9999; justify-content: center; align-items: center;">
            <div style="background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 400px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3); text-align: center; font-family: 'Poppins';">
                <h2 style="color: #e74c3c; font-size: 1.8em; margin-bottom: 15px;">Invalid Email</h2>
                <p style="font-size: 1.2em; margin-bottom: 25px;">Please enter a valid email address (e.g.
                    you@example.com).</p>
                <button id="close-email-warning" style="padding: 10px 20px; font-size: 1em; background-color: #e74c3c;
            color: white; border: none; border-radius: 8px; cursor: pointer;">OK</button>
            </div>
        </div>

    </div>

    <!-- Scripts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="../../assets/js/custom-javascript.js"></script>

    <script>
        $(document).ready(function () {
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
    <script>
        // Automatically hide the error message after 2 seconds
        setTimeout(() => {
            const errorMessage = document.getElementById('error-message');
            if (errorMessage) {
                errorMessage.style.transition = 'opacity 0.5s ease';
                errorMessage.style.opacity = '0';
                setTimeout(() => {
                    errorMessage.remove(); // Remove the element completely after fade-out
                }, 500); // Delay to match the fade-out duration
            }
        }, 3000); // 2 seconds delay before hiding
    </script>
    <script>
        /** RFID Input Auto-Submit */
        const rfidInput = document.getElementById('rfid-id');
        const rfidForm = document.getElementById('rfid-form');

        if (rfidInput && rfidForm) {
            document.addEventListener('keydown', (event) => {
                if (event.target === document.body) {
                    const key = event.key;

                    if (key === 'Enter') {
                        // Submit the form when Enter is pressed
                        if (rfidInput.value.trim() !== '') {
                            rfidForm.submit();
                        }
                    } else {
                        // Append keystrokes to the hidden input field
                        rfidInput.value += key;
                    }
                }
            });
        }
    </script>
    <script>
        document.querySelectorAll('a.no-underline').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault(); // Prevent immediate navigation
                const targetUrl = this.href; // Store the URL

                // Add the 'hidden' class to start the fade-out effect
                document.body.classList.add('hidden');

                // Wait for the transition to complete before navigating
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 500); // Match the CSS transition duration
            });
        });
    </script>
    <!-- Enhanced JavaScript for Kiosk -->
    <script>
        // Password visibility toggle with custom icons
        document.getElementById("togglePassword").addEventListener("click", function () {
            const passwordInput = document.getElementById("student-info-password");
            const eyeShow = document.getElementById("eye-show");
            const eyeHide = document.getElementById("eye-hide");

            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                eyeShow.style.display = "none";
                eyeHide.style.display = "block";
            } else {
                passwordInput.type = "password";
                eyeShow.style.display = "block";
                eyeHide.style.display = "none";
            }

            // Haptic feedback if supported
            if (window.navigator.vibrate) {
                navigator.vibrate(10);
            }
        });

        // Password strength meter
        document.getElementById("student-info-password").addEventListener("input", function () {
            const password = this.value;
            const strengthBar = document.getElementById("strength-bar");
            const strengthText = document.getElementById("strength-text");

            // Calculate strength
            let strength = 0;
            if (password.length > 0) strength += 20;
            if (password.length >= 8) strength += 20;
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;

            // Update UI
            strengthBar.style.width = strength + "%";

            // Change color based on strength
            if (strength < 40) {
                strengthBar.style.background = "#e74c3c";
                strengthText.textContent = "Weak password - try adding more characters";
            } else if (strength < 80) {
                strengthBar.style.background = "#f39c12";
                strengthText.textContent = "Moderate password - could be stronger";
            } else {
                strengthBar.style.background = "#2ecc71";
                strengthText.textContent = "Strong password - good job!";
            }
        });

        // Redirect to kiosk-index.php when clicking cancel
        document.getElementById("cancel-btn").addEventListener("click", function () {
            if (window.navigator.vibrate) navigator.vibrate(15);
            window.location.href = "../kiosk-index.php"; // Adjust path if needed
        });

        // Auto-focus the password field
        window.onload = function () {
            document.getElementById("student-info-password").focus();
        };
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const keyboard = document.getElementById('custom-keyboard');
            const passwordInput = document.getElementById('student-info-password');
            const emailInput = document.getElementById('student-info');
            let currentInput = passwordInput;
            let isShiftOn = false;
            let isCapsOn = false;
            let currentLayout = 'letters';

            // Show keyboard when input is clicked and set current input
            function handleInputClick(input) {
                return function () {
                    keyboard.style.display = 'block';
                    currentInput = this;
                    // Ensure the input has focus to track cursor position
                    this.focus();
                };
            }

            passwordInput.addEventListener('click', handleInputClick(passwordInput));
            emailInput.addEventListener('click', handleInputClick(emailInput));

            // Keyboard mode switching
            document.querySelectorAll('.keyboard-mode').forEach(button => {
                button.addEventListener('click', function () {
                    const mode = this.getAttribute('data-mode');
                    currentLayout = mode;

                    // Hide all layouts
                    document.querySelectorAll('.keyboard-layout').forEach(layout => {
                        layout.style.display = 'none';
                    });

                    // Show selected layout
                    document.getElementById(`${mode}-layout`).style.display = 'block';

                    // Update active mode button
                    document.querySelectorAll('.keyboard-mode').forEach(btn => {
                        btn.classList.toggle('active', btn === this);
                    });
                });
            });

            // Shift/Caps functionality
            document.getElementById('keyboard-shift').addEventListener('click', function () {
                if (isShiftOn) {
                    // If shift was on, turn on caps
                    isShiftOn = false;
                    isCapsOn = true;
                    this.classList.remove('active');
                    this.classList.add('caps-active');
                } else if (isCapsOn) {
                    // If caps was on, turn both off
                    isShiftOn = false;
                    isCapsOn = false;
                    this.classList.remove('caps-active');
                } else {
                    // Turn on shift
                    isShiftOn = true;
                    this.classList.add('active');
                }
                updateKeyLabels();
            });

            function updateKeyLabels() {
                document.querySelectorAll('.key').forEach(key => {
                    const originalKey = key.getAttribute('data-key');
                    if (isShiftOn || isCapsOn) {
                        key.textContent = originalKey.toUpperCase();
                    } else {
                        key.textContent = originalKey.toLowerCase();
                    }
                });
            }

            // Close keyboard
            document.getElementById('keyboard-close').addEventListener('click', function () {
                keyboard.style.display = 'none';
                isShiftOn = false;
                isCapsOn = false;
                const shiftBtn = document.getElementById('keyboard-shift');
                shiftBtn.classList.remove('active', 'caps-active');
            });

            // Handle key presses
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('key')) {
                    let keyValue = e.target.getAttribute('data-key');

                    // Apply shift/caps effect
                    if ((isShiftOn || isCapsOn) && /^[a-z]$/.test(keyValue)) {
                        keyValue = keyValue.toUpperCase();
                    }

                    // Reset shift after one use (like real keyboard)
                    if (isShiftOn && !isCapsOn) {
                        isShiftOn = false;
                        document.getElementById('keyboard-shift').style.background = '#e0e0e0';
                        document.getElementById('keyboard-shift').style.color = 'black';
                        updateKeyLabels();
                    }

                    // Get current cursor position
                    const startPos = currentInput.selectionStart;
                    const endPos = currentInput.selectionEnd;
                    const currentValue = currentInput.value;

                    // Insert the character at cursor position
                    currentInput.value = currentValue.substring(0, startPos) + keyValue + currentValue.substring(endPos);

                    // Fix for email input cursor jumping
                    const originalType = currentInput.type;
                    if (originalType === 'email') currentInput.type = 'text';

                    // Move cursor position after the inserted character
                    const newPos = startPos + keyValue.length;
                    currentInput.setSelectionRange(newPos, newPos);

                    // Restore original type if needed
                    if (originalType === 'email') currentInput.type = originalType;

                    currentInput.focus();

                }
            });

            // Special keys
            document.querySelectorAll('.keyboard-special').forEach(button => {
                button.addEventListener('click', function () {
                    const action = this.getAttribute('data-action');
                    const startPos = currentInput.selectionStart;
                    const endPos = currentInput.selectionEnd;
                    const currentValue = currentInput.value;

                    switch (action) {
                        case 'backspace':
                            if (startPos > 0) {
                                currentInput.value = currentValue.substring(0, startPos - 1) + currentValue.substring(endPos);
                                currentInput.setSelectionRange(startPos - 1, startPos - 1);
                            }
                            break;
                        case 'space':
                            currentInput.value = currentValue.substring(0, startPos) + ' ' + currentValue.substring(endPos);
                            currentInput.setSelectionRange(startPos + 1, startPos + 1);
                            break;
                        case 'enter':
                            if (currentInput.form) currentInput.form.submit();
                            break;
                        case 'hide':
                            keyboard.style.display = 'none';
                            break;
                    }
                    currentInput.focus();
                });
            });

            // Auto-show keyboard when modal appears
            setTimeout(() => {
                keyboard.style.display = 'block';
                emailInput.focus();
            }, 300);
        });
    </script>
    <script>
        const form = document.querySelector('form'); // Change if needed
        const emailInput = document.getElementById('student-info');
        const modal = document.getElementById('email-warning-modal');
        const closeBtn = document.getElementById('close-email-warning');

        form.addEventListener('submit', function (e) {
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!emailRegex.test(email)) {
                e.preventDefault();
                modal.style.display = 'flex'; // Show modal
                emailInput.focus();
            }
        });

        // Close modal
        closeBtn.addEventListener('click', function () {
            modal.style.display = 'none';
        });

    </script>
</body>