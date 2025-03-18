<?php
session_start();
require_once('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

// Image path (local path on your PHP server)
$imagePath = 'C:\xampp\htdocs\F-Connect\uploads\announcement_images\67d369f12088f_class_suspension.png';

// Read image as binary
$imageData = file_get_contents($imagePath);
if ($imageData === false) {
    die("❌ Failed to read the image file.");
}

// Prepare the SQL query
$sql = "UPDATE Announcement SET image = CONVERT(VARBINARY(MAX), ?) WHERE announcement_id = '4'"; 
$params = array($imageData);

// Execute the query
$stmt = sqlsrv_query($conn, $sql, $params);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
} else {
    echo "✅ Image uploaded to Azure SQL successfully! 4";
}

// Optional: Close the statement and connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
