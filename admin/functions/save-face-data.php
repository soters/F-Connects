<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['rfid_no']) || !isset($data['images'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit;
    }

    $rfid_no = $data['rfid_no'];
    $images = $data['images'];
    $directory = "../../labeled_images/{$rfid_no}";

    // Include your database connection file
    require_once('../../connection/connection.php');

    try {
        // Check if RFID exists in FaceData
        $checkSql = "SELECT image_path FROM FaceData WHERE rfid_no = ?";
        $checkStmt = sqlsrv_query($conn, $checkSql, array($rfid_no));

        if ($checkStmt === false) {
            echo json_encode(['success' => false, 'message' => 'Database error.']);
            die(print_r(sqlsrv_errors(), true));
        }

        // Delete existing images
        while ($row = sqlsrv_fetch_array($checkStmt, SQLSRV_FETCH_ASSOC)) {
            $filePath = $row['image_path'];
            if (file_exists($filePath)) {
                unlink($filePath); // Delete file from directory
            }
        }

        // Delete records from FaceData
        $deleteSql = "DELETE FROM FaceData WHERE rfid_no = ?";
        $deleteStmt = sqlsrv_query($conn, $deleteSql, array($rfid_no));

        if ($deleteStmt === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete existing records.']);
            die(print_r(sqlsrv_errors(), true));
        }

        // Create directory if it doesn't exist
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        // Insert new images
        foreach ($images as $imageData) {
            $imageData = str_replace('data:image/jpeg;base64,', '', $imageData);
            $imageData = base64_decode($imageData);
            $fileName = "image_" . time() . "_" . bin2hex(random_bytes(4)) . ".jpg";
            $filePath = "{$directory}/{$fileName}";

            if (!file_put_contents($filePath, $imageData)) {
                echo json_encode(['success' => false, 'message' => 'Failed to save image.']);
                exit;
            }

            // Insert new image record into FaceData
            $insertSql = "INSERT INTO FaceData (rfid_no, image_path, upload_date, upload_time) 
                          VALUES (?, ?, GETDATE(), CONVERT(TIME, GETDATE()))";
            $params = array($rfid_no, $filePath);
            $insertStmt = sqlsrv_query($conn, $insertSql, $params);

            if ($insertStmt === false) {
                echo json_encode(['success' => false, 'message' => 'Database error.']);
                die(print_r(sqlsrv_errors(), true));
            }
        }

        echo json_encode(['success' => true, 'message' => 'Images updated successfully.']);
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
}
