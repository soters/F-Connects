<?php
session_start();
$admin_fname = $_SESSION['admin_fname'] ?? 'Unknown';
$acc_type = $_SESSION['acc_type'] ?? 'Unknown';
$picture_path = $_SESSION['picture_path'] ?? '../../assets/images/Prof.png';

include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : null; // Ensure room_id is an integer
$floor = isset($_GET['floor']) ? intval($_GET['floor']) : null; // Ensure room_id is an integer

// Initialize variables
$room_name = "";
$floor = "";
$type = "";
$x_coord = "";
$y_coord = "";
$bldg_id = "";
$building_name = "";

// Fetch existing data if room_id is provided
if ($room_id) {
    $sql = "SELECT l.room_name, l.floor, l.type, l.x_coord, l.y_coord, l.bldg_id, b.building_name 
            FROM Locations l
            JOIN Buildings b ON l.bldg_id = b.bldg_id
            WHERE l.room_id = ?";
    $params = array($room_id);
    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt && $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $room_name = htmlspecialchars($row['room_name']);
        $floor = htmlspecialchars($row['floor']);
        $type = htmlspecialchars($row['type']);
        $x_coord = htmlspecialchars($row['x_coord']);
        $y_coord = htmlspecialchars($row['y_coord']);
        $bldg_id = htmlspecialchars($row['bldg_id']);
        $building_name = htmlspecialchars($row['building_name']); // Fetch building name
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
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
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
        <form id="uploadForm" action="../functions/update-locations.php" method="POST" enctype="multipart/form-data">
            <div class="action-widgets-2">
                <div class="widget-button">
                    <h1 class="sub-title">Location / Edit </h1>
                    <div class="buttons">
                        <button class="create-btn-3" type="submit">Update</button>
                        <a href="admin-locations.php" class="discard-btn">Discard</a>
                    </div>
                </div>
                <div class="widget-search"></div>
            </div>
            <div id="messageBox" class="message-box"></div>
            <div class="faculty-container-1">
                <div class="buttons">
                    <a href="javascript:void(0);" class="red-btn"
                        onclick="openDeleteModal('<?= htmlspecialchars($room_id) ?>')">
                        Delete
                    </a>
                    <a class="pass-btn" type="button" onclick="openQRModal()">Generate QR Code</a>
                </div>

                <div class="faculty-container-2">
                    <h1 class="info-title-2">Location Details</h1>
                    <hr>
                    <div class="faculty-name-container">
                        <div>
                            <label for="room_name">Location Name</label>
                            <input class="name-input" type="text" id="room_name" name="room_name"
                                value="<?= $room_name ?>" required>

                        </div>
                        <div>
                            <div class="faculty-name-box">
                                <label for="floor">For Year</label>
                                <select id="floor" name="floor" class="name-input">
                                    <option value="1" <?= ($floor == 1) ? 'selected' : '' ?>>1st Floor</option>
                                    <option value="2" <?= ($floor == 2) ? 'selected' : '' ?>>2nd Floor</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <div class="faculty-name-box">
                                <label for="type">Type</label>
                                <select id="type" name="type" class="name-input-2">
                                    <option value="Facility" <?= ($type == 'Facility') ? 'selected' : '' ?>>Facility
                                    </option>
                                    <option value="Room" <?= ($type == 'Room') ? 'selected' : '' ?>>Room</option>
                                    <option value="Office" <?= ($type == 'Office') ? 'selected' : '' ?>>Office</option>
                                    <option value="Restroom" <?= ($type == 'Restroom') ? 'selected' : '' ?>>Restroom
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <h1 class="info-title">Coordinates</h1>
                    <hr>
                    <div class="faculty-name-container">
                        <div>
                            <label for="x_coord">X Coordinate</label>
                            <input class="name-input" type="text" id="x_coord" name="x_coord" value="<?= $x_coord ?>"
                                required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div>
                            <label for="y_coord">Y Coordinate</label>
                            <input class="name-input" type="text" id="y_coord" name="y_coord" value="<?= $y_coord ?>"
                                required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <input type="hidden" name="room_id" value="<?= $room_id ?>">
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
            <p>Are you sure you want to delete this location?</p>
            <div class="modal-actions">
                <button id="confirmDelete" class="btn-confirm">Yes, Delete</button>
                <button onclick="closeDeleteModal()" class="btn-cancel">Cancel</button>
            </div>
        </div>
    </div>

    <div id="modalOverlay" class="modal-overlay" onclick="closeQRModal()"></div>

    <div id="qrModal" class="custom-modal">
        <div class="modal-content">
            <p>Scan this QR Code for location details:</p>
            <div id="qrCodeContainer"></div>
            <div class="modal-actions">
                <button class="red-btn" onclick="exportToPDF()">PDF</button>
                <button class="fabtn" onclick="printQRCode()">Print</button>
                <button class="pass-btn" onclick="closeQRModal()">Close</button>
            </div>
        </div>
    </div>

    <!-- Scroll to Top Button -->
    <button id="scrollToTopBtn" onclick="scrollToTop()">↑</button>
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
        window.location.href = `../functions/delete-locations.php?room_id=${rfidNo}`; // Redirect with RFID
    });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

<script>
    function openQRModal() {
        let buildingName = "<?php echo $building_name; ?>";
        let roomName = "<?php echo $room_name; ?>";
        let qrData = buildingName + " - " + roomName;

        document.getElementById("qrCodeContainer").innerHTML = ""; 
        new QRCode(document.getElementById("qrCodeContainer"), qrData);

        document.getElementById("qrModal").style.display = "block";
        document.getElementById("modalOverlay").style.display = "block";
    }

    function closeQRModal() {
        document.getElementById("qrModal").style.display = "none";
        document.getElementById("modalOverlay").style.display = "none";
    }

    function printQRCode() {
        let printWindow = window.open('', '', 'width=600,height=600');
        printWindow.document.write('<html><head><title>Print QR Code</title></head><body>');
        printWindow.document.write(document.getElementById("qrCodeContainer").innerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
    function exportToPDF() {
    let { jsPDF } = window.jspdf;
    let doc = new jsPDF();

    let qrCanvas = document.querySelector("#qrCodeContainer canvas");
    let qrImage = qrCanvas.toDataURL("image/png");

    let pageWidth = doc.internal.pageSize.getWidth();
    let qrX = (pageWidth - 50) / 2; // Center QR horizontally
    let qrY = 40; // QR Code Y position

    let text = "SCAN Me!";
    let textWidth = doc.getTextWidth(text);
    let textX = (pageWidth - textWidth) / 2; // Center text horizontally
    let textY = qrY - 10; // Position above QR code

    doc.text(text, textX, textY);
    doc.addImage(qrImage, "PNG", qrX, qrY, 50, 50);
    doc.save("QRCode.pdf");
}

</script>

</html>