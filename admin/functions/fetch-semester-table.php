<?php
include('../../connection/connection.php');

$sql = "SELECT semester_id, semester_name, start_date, end_date FROM Semester ORDER BY semester_id DESC";
$stmt = sqlsrv_query($conn, $sql);

if ($stmt) {
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>
                <td>{$row['semester_name']}</td>
                <td>{$row['start_date']->format('Y-m-d')}</td>
                <td>{$row['end_date']->format('Y-m-d')}</td>
                
              </tr>";
    }
} else {
    echo "<tr><td colspan='3'>No semesters found.</td></tr>";
}
?>
