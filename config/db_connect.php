<?php
$servername = "localhost";
$username = "root"; // ค่าเริ่มต้นของ XAMPP
$password = "";     // ค่าเริ่มต้นของ XAMPP มักจะไม่มีรหัสผ่าน
$dbname = "karaoke_db"; // ชื่อฐานข้อมูลที่คุณจะสร้างใน phpMyAdmin

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตั้งค่าภาษาไทยให้ MySQL
$conn->set_charset("utf8");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>