<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ดึงข้อมูลบิลอาหาร พร้อมเชื่อมกับข้อมูลห้องและลูกค้า
$query = "SELECT o.*, b.booking_date, r.room_name, u.first_name, u.last_name 
          FROM orders o 
          JOIN bookings b ON o.booking_id = b.booking_id 
          JOIN rooms r ON b.room_id = r.room_id 
          JOIN users u ON b.customer_id = u.user_id 
          ORDER BY o.created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ภาพรวมออเดอร์อาหาร | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-danger shadow-sm mb-4 no-print">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">👑 Owner Panel</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ownerNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="ownerNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">🏠 แดชบอร์ด</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">⚙️ จัดการระบบ</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_rooms.php">จัดการห้องคาราโอเกะ</a></li>
                        <li><a class="dropdown-item" href="manage_promotions.php">จัดการโปรโมชั่น</a></li>
    <li><a class="dropdown-item" href="manage_menus.php">จัดการเมนูอาหาร</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="manage_users.php">จัดการลูกค้า</a></li>
                        <li><a class="dropdown-item" href="manage_employees.php">จัดการพนักงาน</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">🛎️ ตรวจสอบบริการ</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="manage_bookings.php">📅 คิวการจองทั้งหมด</a></li>
                        <li><a class="dropdown-item" href="verify_payments.php">💳 ตรวจสลิปโอนเงิน</a></li>
                        <li><a class="dropdown-item" href="view_orders.php">🍔 รายการสั่งอาหาร</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">📊 รายงาน</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="report_income.php">💰 สรุปรายได้</a></li>
                        <li><a class="dropdown-item" href="report_usage.php">📈 สรุปการใช้บริการ</a></li>
                    </ul>
                </li>
            </ul>
            
            <div class="d-flex text-white align-items-center">
                <a href="../logout.php" class="btn btn-outline-light btn-sm fw-bold">🚪 ออกจากระบบ</a>
            </div>
        </div>
    </div>
</nav>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">🍔 ภาพรวมการสั่งอาหารและเครื่องดื่ม</h3>
            <a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">รหัสบิลอาหาร</th>
                            <th>รหัสการจองห้อง</th>
                            <th>ห้อง (ลูกค้า)</th>
                            <th>ยอดรวมอาหาร</th>
                            <th>เวลาที่สั่ง</th>
                            <th>สถานะออเดอร์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="p-3 fw-bold text-muted">#ORD-<?php echo $row['order_id']; ?></td>
                                <td>#<?php echo $row['booking_id']; ?></td>
                                <td>
                                    <span class="text-primary fw-bold"><?php echo $row['room_name']; ?></span><br>
                                    <small class="text-muted">(<?php echo $row['first_name'] . " " . $row['last_name']; ?>)</small>
                                </td>
                                <td class="text-danger fw-bold">฿<?php echo number_format($row['total_price'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <?php 
                                        if($row['order_status'] == 'pending') echo '<span class="badge bg-warning text-dark">รอรับออเดอร์</span>';
                                        elseif($row['order_status'] == 'preparing') echo '<span class="badge bg-info text-dark">กำลังปรุง/เตรียม</span>';
                                        elseif($row['order_status'] == 'served') echo '<span class="badge bg-success">เสิร์ฟแล้ว</span>';
                                        else echo '<span class="badge bg-danger">ยกเลิก</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center p-5 text-muted">ยังไม่มีรายการสั่งอาหาร</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>