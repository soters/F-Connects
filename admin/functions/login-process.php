<?php
session_start();
include('../../connection/connection.php');
date_default_timezone_set('Asia/Manila');

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $sql = "
            SELECT 
                a.rfid_no, a.acc_type, a.fname, a.picture_path,
                acc.password AS stored_password, acc.email AS account_email
            FROM Admin a
            INNER JOIN AdminAccount acc ON a.email = acc.email
            WHERE a.email = ?
        ";

        $params = array($email);
        $stmt = sqlsrv_query($conn, $sql, $params);

        if ($stmt === false) {
            die(print_r(sqlsrv_errors(), true));
        }

        if (sqlsrv_has_rows($stmt)) {
            $fetch = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

            if (password_verify($password, $fetch['stored_password'])) {
                // Fallback picture if none is set
                $default_pic = '../../assets/images/Prof.png';
                $picture_path = !empty($fetch['picture_path']) ? $fetch['picture_path'] : $default_pic;

                // Set session values
                $_SESSION['account_email'] = $fetch['account_email'];
                $_SESSION['rfid_no'] = $fetch['rfid_no'];
                $_SESSION['acc_type'] = ($fetch['acc_type'] === "Super Admin") ? "Super Admin" : $fetch['acc_type'];
                $_SESSION['admin_fname'] = $fetch['fname'];
                $_SESSION['picture_path'] = $picture_path;

                header("Location: ../pages/admin-index.php");
                exit();
            } else {
                header('Location: ../authentication/admin-login.php?error=incorrect_password');
                exit();
            }
        } else {
            header('Location: ../authentication/admin-login.php?error=account_not_found');
            exit();
        }
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        die("An error occurred. Please contact the administrator.");
    }
}
?>
