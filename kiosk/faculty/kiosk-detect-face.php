<?php
session_start();
require_once('../../connection/connection.php');

// Fetch all image paths and corresponding RFID numbers from the database
$query = "SELECT rfid_no, image_path FROM FaceData";
$stmt = sqlsrv_query($conn, $query);

// Store all face data in an array
$faceData = array();
if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $faceData[] = array(
            'rfid_no' => $row['rfid_no'],
            'image_path' => $row['image_path']
        );
    }
}

// Close the connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

if (empty($faceData)) {
    // Handle case if no face data is found
    $_SESSION['error_message'] = "No face data found in the database.";
    header("Location: kiosk-faculty.php");
    exit();
}
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
        /* Add scanning animation styles */
        .scanning-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 80%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-radius: 7px;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 10;
            display: none;
            /* Hidden by default */
        }

        .scanning-animation {
            width: 280px;
            height: 280px;
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        .scanning-text {
            color: white;
            font-size: 1.2em;
            text-align: center;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .camera-holder {
            position: relative;
        }

        #video,
        #canvas {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Modern Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease-out;
            overflow: hidden;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            position: relative;
            background: white;
            margin: 10% auto;
            padding: 40px;
            width: 80%;
            max-width: 500px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
            animation: slideUp 0.4s cubic-bezier(0.22, 1, 0.36, 1);
            overflow: hidden;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, #4CAF50, #2196F3);
        }

        .modal-icon {
            font-size: 60px;
            margin-bottom: 20px;
            animation: bounce 0.8s;
        }

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

        .modal h3 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .modal p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .modal-button {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .modal-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .modal-button i {
            font-size: 20px;
        }

        #modalOkay {
            background: linear-gradient(135deg, #FF416C, #FF4B2B);
            color: white;
            width: 100%;
        }

        #modalOkay:hover {
            background: linear-gradient(135deg, #FF4B2B, #FF416C);
        }

        #successOkay {
            background: linear-gradient(135deg, #4CAF50, #2E8B57);
            color: white;
            width: 100%;
        }

        #successOkay:hover {
            background: linear-gradient(135deg, #2E8B57, #4CAF50);
        }

        /* Confetti for success */
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: #f00;
            opacity: 0;
        }

        /* Button Container Styles */
        #top-right-button {
            top: 80px;
            right: 20px;
        }

        #top-left-button {
            top: 80px;
            left: 20px;
        }

        /* Button Base Styles */
        .small-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(145deg, #ffffff, #e6e6e6);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: #333;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        /* Specific Button Styles */
        .refresh-btn {
            background: linear-gradient(145deg, #4CAF50, #2E8B57);
            color: white;
        }

        .back-btn {
            background: linear-gradient(145deg, #2196F3, #0b7dda);
            color: white;
        }

        /* Hover Effects */
        .small-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .refresh-btn:hover {
            background: linear-gradient(145deg, #2E8B57, #4CAF50);
        }

        .back-btn:hover {
            background: linear-gradient(145deg, #0b7dda, #2196F3);
        }

        /* Active/Pressed Effect */
        .small-button:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        /* Tooltip Styles */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 200px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.9rem;
            font-family: 'Poppins', sans-serif;
        }

        /* Refresh Button Tooltip (Left) */
        #top-right-button .tooltiptext {
            bottom: 50%;
            right: 125%;
            transform: translateY(50%);
        }

        #top-right-button .tooltiptext::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 100%;
            margin-top: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent transparent transparent #333;
        }

        /* Back Button Tooltip (Right) */
        #top-left-button .tooltiptext {
            bottom: 50%;
            left: 125%;
            transform: translateY(50%);
        }

        #top-left-button .tooltiptext::after {
            content: "";
            position: absolute;
            top: 50%;
            right: 100%;
            margin-top: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: transparent #333 transparent transparent;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        /* Pulse Animation for Refresh Button */
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

        .refresh-btn:hover i {
            animation: pulse 1s infinite;
        }
    </style>
</head>

<body class="fade-out">

    <?php
    if (!empty($_SESSION['error_message'])) {
        echo '<div id="error-message" class="error-message alert alert-danger" role="alert">';
        echo '<i class="bi bi-exclamation-triangle"></i> ';
        echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
        echo '</div>';
        echo '<br>';
        unset($_SESSION['error_message']);
    }
    ?>

    <nav class="navi-bar" role="banner">
        <a id="current-time"></a>
        <a id="live-date"></a>
    </nav>

    <div id="body-container">
        <div class="camera-holder">
            <!-- Video element for live camera feed -->
            <video id="video" width="600" height="450" autoplay muted></video>
            <canvas id="canvas" width="600" height="450"></canvas>
            <br>
            <!-- Scanning overlay -->
            <div class="scanning-overlay" id="scanningOverlay">
                <div class="scanning-animation"></div>
                <div class="scanning-text">Scanning your face...</div>
            </div>
            <br>
            <p id="action-message-rfid">Position your face on the camera</p>
            <p id="action-message-small">Remain still to ensure accurate recognition</p>
        </div>
    </div>

    <!-- Navigation Buttons with Enhanced Styling -->
    <div id="top-right-button">
        <div class="tooltip">
            <button type="button" class="small-button refresh-btn" onclick="refreshPage()">
                <i class="bi bi-arrow-clockwise"></i>
            </button>
            <span class="tooltiptext">Refresh Scanner</span>
        </div>
    </div>

    <div id="top-left-button">
        <div class="tooltip">
            <a href="../kiosk-index.php" class="no-underline">
                <button type="button" class="small-button back-btn" title="Back">
                    <i class="bi bi-arrow-left-short"></i>
                </button>
            </a>
            <span class="tooltiptext">Return to Main Menu</span>
        </div>
    </div>

    <!-- No Face Match Modal -->
    <div id="noFaceModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #FF416C;">
                <i class="bi bi-emoji-frown"></i>
            </div>
            <h3>Oops! Face Not Recognized</h3>
            <p>We couldn't find a match in our system. Please make sure you're facing the camera directly with good
                lighting.</p>
            <button id="modalOkay" class="modal-button">
                <i class="bi bi-arrow-repeat"></i> Try Again
            </button>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <div class="modal-icon" style="color: #4CAF50;">
                <i class="bi bi-emoji-smile"></i>
            </div>
            <h3>Welcome Back!</h3>
            <p>Face recognition successful! We're now recording your attendance.</p>
            <button id="successOkay" class="modal-button">
                <i class="bi bi-check-circle"></i> Continue
            </button>
        </div>
    </div>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script type="text/javascript" src="../../assets/js/custom-javascript.js"></script>
    <script src="../../assets/js/face-api.min.js"></script>

    <script>
        // Pass all face data from PHP to JavaScript
        const faceData = <?php echo json_encode($faceData); ?>;

        // Load face-api.js models
        Promise.all([
            faceapi.nets.ssdMobilenetv1.loadFromUri('../../assets/models'),
            faceapi.nets.faceLandmark68Net.loadFromUri('../../assets/models'),
            faceapi.nets.faceRecognitionNet.loadFromUri('../../assets/models'),
            faceapi.nets.ageGenderNet.loadFromUri('../../assets/models'),
            faceapi.nets.faceExpressionNet.loadFromUri('../../assets/models')
        ]).then(startVideo);

        // Start video stream from the camera
        async function startVideo() {
            const video = document.getElementById('video');
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
                video.srcObject = stream;

                video.onplay = () => {
                    detectFaceFromVideo(video);
                };
            } catch (error) {
                console.error("Error accessing camera:", error);
                document.getElementById('action-message-rfid').textContent = "Camera access required";
                document.getElementById('action-message-small').textContent = "Please allow camera permissions and refresh";
            }
        }

        // Show scanning overlay
        function showScanning() {
            document.getElementById('scanningOverlay').style.display = 'flex';
            document.getElementById('action-message-rfid').textContent = "Scanning in progress...";
            document.getElementById('action-message-small').textContent = "Please remain still";
        }

        // Hide scanning overlay
        function hideScanning() {
            document.getElementById('scanningOverlay').style.display = 'none';
            document.getElementById('action-message-rfid').textContent = "Position your face on the camera";
            document.getElementById('action-message-small').textContent = "Remain still to ensure accurate recognition";
        }

        // Detect faces from the video stream
        async function detectFaceFromVideo(video) {
            const canvas = document.getElementById('canvas');
            const displaySize = { width: video.width, height: video.height };

            faceapi.matchDimensions(canvas, displaySize);

            // Show scanning overlay while processing
            showScanning();

            try {
                // Load all labeled images
                const labeledFaceDescriptors = await loadLabeledImages();

                // Get face descriptors from the video
                const videoFaceDescriptors = await faceapi
                    .detectAllFaces(video)
                    .withFaceLandmarks()
                    .withFaceDescriptors();

                if (videoFaceDescriptors.length === 0) {
                    hideScanning();
                    showNoFaceModal();
                    console.log("No face detected in the camera.");
                    return;
                }

                // Create face matcher with all labeled descriptors
                const faceMatcher = new faceapi.FaceMatcher(labeledFaceDescriptors, 0.4);

                let matchedRfid = null;

                // Check each face in the video against all labeled faces
                for (const videoDescriptor of videoFaceDescriptors) {
                    const bestMatch = faceMatcher.findBestMatch(videoDescriptor.descriptor);

                    if (bestMatch.distance <= 0.4) {
                        // Find the RFID number for the matched label
                        const matchedFace = faceData.find(face => face.image_path.includes(bestMatch.label));
                        if (matchedFace) {
                            matchedRfid = matchedFace.rfid_no;
                            break;
                        }
                    }
                }

                if (matchedRfid) {
                    hideScanning();
                    showSuccessModal(matchedRfid);
                    console.log("Matching face found for RFID:", matchedRfid);
                } else {
                    hideScanning();
                    showNoFaceModal();
                    console.log("No matching face found in database.");
                }

                const resizedResults = faceapi.resizeResults(videoFaceDescriptors, displaySize);
                faceapi.draw.drawDetections(canvas, resizedResults);
            } catch (error) {
                hideScanning();
                console.error("Error during face detection:", error);
                showNoFaceModal();
            }
        }

        function showNoFaceModal() {
            const noFaceModal = document.getElementById('noFaceModal');
            noFaceModal.style.display = 'block';

            document.getElementById('modalOkay').addEventListener('click', () => {
                location.reload();
            });
        }

        async function loadLabeledImages() {
            const labeledFaceDescriptors = [];

            for (const face of faceData) {
                try {
                    const img = await faceapi.fetchImage(face.image_path);
                    const faceDescriptor = await faceapi
                        .detectSingleFace(img)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (faceDescriptor) {
                        labeledFaceDescriptors.push(
                            new faceapi.LabeledFaceDescriptors(
                                face.image_path,
                                [faceDescriptor.descriptor]
                            )
                        );
                    }
                } catch (error) {
                    console.error("Error loading image:", face.image_path, error);
                }
            }

            return labeledFaceDescriptors;
        }

        function refreshPage() {
            window.location.reload();
        }
    </script>

    <script>
        function createConfetti() {
            const colors = ['#4CAF50', '#2196F3', '#FFC107', '#FF416C', '#9C27B0'];
            const modalContent = document.querySelector('#successModal .modal-content');

            // Clear any existing confetti
            const existingConfetti = document.querySelectorAll('.confetti');
            existingConfetti.forEach(confetti => confetti.remove());

            for (let i = 0; i < 50; i++) {
                const confetti = document.createElement('div');
                confetti.className = 'confetti';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = -10 + 'px';
                confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
                confetti.style.width = Math.random() * 8 + 5 + 'px';
                confetti.style.height = Math.random() * 8 + 5 + 'px';
                confetti.style.position = 'absolute';
                confetti.style.zIndex = '1001';

                modalContent.appendChild(confetti);

                setTimeout(() => {
                    confetti.style.opacity = '1';
                    confetti.style.top = Math.random() * 100 + '%';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.transition = `all ${Math.random() * 1 + 0.5}s ease-out`;

                    setTimeout(() => {
                        confetti.style.opacity = '0';
                        setTimeout(() => {
                            confetti.remove();
                        }, 500);
                    }, 1000);
                }, i * 20);
            }
        }

        // Update your showSuccessModal function to include the RFID data
        function showSuccessModal(rfid_no) {
            const successModal = document.getElementById('successModal');
            successModal.setAttribute('data-rfid', rfid_no);
            successModal.style.display = 'block';

            document.getElementById('successOkay').addEventListener('click', () => {
                createConfetti();
                setTimeout(() => {
                    window.location.href = `../functions/insert-attendance-v2.php?rfid_no=${encodeURIComponent(rfid_no)}`;
                }, 800);
            });
        }
    </script>

</body>

</html>