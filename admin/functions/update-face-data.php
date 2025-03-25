<?php
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["rfid_no"])) {
    $rfid = $_POST["rfid_no"];
    
    // Directories
    $uploadDir = "../../uploads/face_data/";
    $facultyDir = "../../labeled_images/{$rfid}";

    // Ensure the upload directories exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (!is_dir($facultyDir)) {
        mkdir($facultyDir, 0777, true);
    }

    $imagePaths = [];

    // Loop through the uploaded files
    for ($i = 0; $i < 3; $i++) {
        if (isset($_FILES['images']['name'][$i]) && $_FILES['images']['error'][$i] == 0) {
            $fileTmpPath = $_FILES['images']['tmp_name'][$i];
            $fileName = $rfid . "_img" . ($i + 1) . "_" . time() . ".jpg"; // Unique file name
            $destPath = $facultyDir . "/" . $fileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                $imagePaths[] = "../../labeled_images/{$rfid}/" . $fileName;
            } else {
                $message = "Error uploading Image " . ($i + 1);
                header("Location: ../pages/admin-upload-face.php?message=" . urlencode($message) . "&type=error&rfid_no=" . urlencode($rfid));
                exit();
            }
        }
    }

    if (count($imagePaths) == 3) {
        // Start a transaction
        sqlsrv_begin_transaction($conn);

        // Check if the RFID already exists in FaceData
        $checkQuery = "SELECT COUNT(*) AS count FROM FaceData WHERE rfid_no = ?";
        $checkStmt = sqlsrv_query($conn, $checkQuery, array($rfid));
        $row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC);
        
        if ($row && $row['count'] > 0) {
            // Delete existing records for this RFID
            $deleteQuery = "DELETE FROM FaceData WHERE rfid_no = ?";
            $deleteStmt = sqlsrv_query($conn, $deleteQuery, array($rfid));

            if (!$deleteStmt) {
                sqlsrv_rollback($conn);
                $message = "Error deleting old face data!";
                header("Location: ../pages/admin-upload-face.php?message=" . urlencode($message) . "&type=error&rfid_no=" . urlencode($rfid));
                exit();
            }
        }

        // Insert new image records
        $insertQuery = "INSERT INTO FaceData (rfid_no, image_path, upload_date, upload_time) VALUES (?, ?, GETDATE(), CONVERT(TIME, GETDATE()))";
        
        foreach ($imagePaths as $imagePath) {
            $insertStmt = sqlsrv_query($conn, $insertQuery, array($rfid, $imagePath));
            if (!$insertStmt) {
                sqlsrv_rollback($conn);
                $message = "Database insert failed!";
                header("Location: ../pages/admin-upload-face.php?message=" . urlencode($message) . "&type=error&rfid_no=" . urlencode($rfid));
                exit();
            }
        }

        // Commit the transaction
        sqlsrv_commit($conn);
        $message = "Face data updated successfully!";
        $type = "success";
    } else {
        $message = "All 3 images are required!";
        $type = "error";
    }
} else {
    $message = "Invalid request!";
    $type = "error";
}

// Redirect with message
header("Location: ../pages/admin-upload-face.php?message=" . urlencode($message) . "&type=" . urlencode($type) . "&rfid_no=" . urlencode($rfid));
exit();
?>
