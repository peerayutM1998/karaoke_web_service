<?php
session_start();
require_once "../config/db_connect.php";

// ตรวจสอบสิทธิ์ว่าเป็น customer หรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลการจอง "ล่าสุด" ที่ยังไม่เสร็จสิ้นหรือยังไม่ถูกยกเลิก
$query_active = "SELECT b.*, r.room_name 
                 FROM bookings b 
                 JOIN rooms r ON b.room_id = r.room_id 
                 WHERE b.customer_id = $user_id 
                 AND b.booking_status IN ('pending', 'confirmed')
                 ORDER BY b.booking_date ASC, b.start_time ASC LIMIT 3";
$result_active = mysqli_query($conn, $query_active);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลัก | ลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Karaoke Customer</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">หน้าหลักโปรไฟล์</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">จองห้องพัก</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="order_food.php">สั่งอาหาร</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_orders.php">ประวัติสั่งอาหาร</a></li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <span class="me-3">👤 สวัสดี, คุณ <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4">แผงควบคุมส่วนตัว (Dashboard)</h3>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name=<?php echo $_SESSION['first_name']; ?>&background=0D8ABC&color=fff&size=100" class="rounded-circle mb-3" alt="Profile">
                        <h5 class="card-title fw-bold"><?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name']; ?></h5>
                        <p class="text-muted">สถานะ: ลูกค้าสมาชิก</p>
                        <hr>
                        <a href="booking.php" class="btn btn-primary w-100 mb-2">🎤 เริ่มจองห้องคาราโอเกะ</a>
                    </div>
                </div>
            </div>

            <div class="col-md-8 mb-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white fw-bold">📅 การจองห้องคาราโอเกะของคุณ (ที่กำลังจะมาถึง)</div>
                    <div class="card-body">
                        <?php if(mysqli_num_rows($result_active) > 0): ?>
                            <div class="list-group">
                                <?php while($row = mysqli_fetch_assoc($result_active)): ?>
                                    <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1 fw-bold text-primary">ห้อง: <?php echo $row['room_name']; ?></h6>
                                            <small class="text-muted">วันที่: <?php echo $row['booking_date']; ?> | เวลา: <?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?></small>
                                        </div>
                                        <span class="badge <?php echo ($row['booking_status'] == 'confirmed') ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill">
                                            <?php echo ($row['booking_status'] == 'confirmed') ? 'ยืนยันแล้ว' : 'รอตรวจสอบ'; ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                            <div class="mt-3 text-end">
                                <a href="my_bookings.php" class="text-decoration-none">ดูประวัติทั้งหมด ></a>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted mt-4">คุณยังไม่มีการจองที่กำลังดำเนินการ</p>
                            <div class="text-center">
                                <a href="booking.php" class="btn btn-outline-primary">ทำการจองตอนนี้เลย</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>