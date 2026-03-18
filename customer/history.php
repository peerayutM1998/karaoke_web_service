<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลเฉพาะที่เสร็จสิ้นหรือยกเลิกแล้ว
$query = "SELECT b.*, r.room_name 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE b.customer_id = $user_id 
          AND b.booking_status IN ('completed', 'cancelled')
          ORDER BY b.booking_date DESC, b.start_time DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติย้อนหลัง | ลูกค้า</title>
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
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">🕒 ประวัติการใช้บริการที่ผ่านมา</h3>
            <a href="index.php" class="btn btn-outline-primary">กลับหน้าหลัก</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="p-3">รหัสบิล</th>
                                <th>ห้องคาราโอเกะ</th>
                                <th>วันที่เข้าใช้บริการ</th>
                                <th>ยอดรวมค่าห้อง</th>
                                <th>สถานะ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3">#<?php echo $row['booking_id']; ?></td>
                                    <td class="fw-bold"><?php echo $row['room_name']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?> 
                                        (<?php echo date('H:i', strtotime($row['start_time'])); ?>)
                                    </td>
                                    <td>฿<?php echo number_format($row['net_price'], 2); ?></td>
                                    <td>
                                        <?php if($row['booking_status'] == 'completed'): ?>
                                            <span class="badge bg-secondary">เสร็จสิ้นการบริการ</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">ถูกยกเลิก</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center p-5 text-muted">ยังไม่มีประวัติการใช้บริการ</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</body>
</html>