<?php
session_start();
// เรียกใช้ไฟล์เชื่อมต่อฐานข้อมูล (db_connect.php ที่ให้ไปก่อนหน้านี้)
require_once "../config/db_connect.php"; 

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("location: login.php");
        exit();
    }

    // ค้นหาผู้ใช้ในระบบ
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {
        $row = mysqli_fetch_array($result);
        
        // เก็บข้อมูลลง Session
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['role'] = $row['role'];

        // ตรวจสอบ Role และส่งไปยังโฟลเดอร์ที่ถูกต้อง
        if ($row['role'] == 'owner') {
            header("location: ../owner/index.php");
        } else if ($row['role'] == 'employee') {
            header("location: ../employee/index.php");
        } else if ($row['role'] == 'customer') {
            header("location: ../customer/index.php");
        }
    } else {
        $_SESSION['error'] = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
        header("location: ../login.php");
    }
}
?>