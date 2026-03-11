<?php
session_start();
require_once "../config/db_connect.php";

// เช็คสิทธิ์การเข้าถึง (ต้องเป็น owner เท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// 1. สรุปรายได้วันนี้ (นับเฉพาะบิลที่พนักงานกดยืนยันแล้ว)
$today = date('Y-m-d');
$query_income = "SELECT SUM(amount_paid) AS total_income FROM payments WHERE DATE(payment_date) = '$today' AND payment_status = 'verified'";
$res_income = mysqli_query($conn, $query_income);
$row_income = mysqli_fetch_assoc($res_income);
$today_income = $row_income['total_income'] ? $row_income['total_income'] : 0;

// 2. จำนวนห้องที่ถูกจองวันนี้ (นับ pending และ confirmed)
$query_booked = "SELECT COUNT(booking_id) AS total_booked FROM bookings WHERE booking_date = '$today' AND booking_status IN ('pending', 'confirmed')";
$res_booked = mysqli_query($conn, $query_booked);
$row_booked = mysqli_fetch_assoc($res_booked);
$today_booked = $row_booked['total_booked'];

// 3. รายการรออนุมัติการจ่ายเงิน (สลิปที่ลูกค้าเพิ่งอัปโหลด)
$query_pending_pay = "SELECT COUNT(payment_id) AS pending_pay FROM payments WHERE payment_status = 'pending'";
$res_pending_pay = mysqli_query($conn, $query_pending_pay);
$row_pending_pay = mysqli_fetch_assoc($res_pending_pay);
$pending_payments = $row_pending_pay['pending_pay'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">👑 Owner Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">แดชบอร์ด</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_rooms.php">จัดการห้อง</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_users.php">ลูกค้า</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_employees.php">พนักงาน</a></li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <span class="me-3">สวัสดี, คุณ <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-outline-light btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4 fw-bold">📊 ภาพรวมธุรกิจ (วันนี้)</h3>
        
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">รายได้วันนี้</h6>
                        <h2 class="fw-bold mb-0">฿<?php echo number_format($today_income, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm h-100 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-uppercase opacity-75">คิวจองห้องวันนี้</h6>
                        <h2 class="fw-bold mb-0"><?php echo $today_booked; ?> <span class="fs-6">รายการ</span></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning text-dark shadow-sm h-100 border-0">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title text-uppercase opacity-75">รอตรวจสอบสลิป</h6>
                            <h2 class="fw-bold mb-0"><?php echo $pending_payments; ?> <span class="fs-6">รายการ</span></h2>
                        </div>
                        <?php if($pending_payments > 0): ?>
                            <a href="verify_payments.php" class="btn btn-dark btn-sm">ตรวจสอบเลย</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>