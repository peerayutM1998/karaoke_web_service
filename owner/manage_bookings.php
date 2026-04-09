<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// เจ้าของร้านสามารถกดยกเลิกการจองได้
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = $cancel_id");
    $_SESSION['success'] = "อัปเดตสถานะเป็นยกเลิกแล้ว";
    header("location: manage_bookings.php");
    exit();
}

// ดึงข้อมูลการจองทั้งหมด พร้อมชื่อลูกค้าและชื่อห้อง
$query = "SELECT b.*, u.first_name, u.last_name, r.room_name 
          FROM bookings b 
          JOIN users u ON b.customer_id = u.user_id 
          JOIN rooms r ON b.room_id = r.room_id 
          ORDER BY b.booking_date DESC, b.start_time DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการการจอง | เจ้าของร้าน</title>
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
        <h3 class="mb-4">📅 ภาพรวมการจองห้องทั้งหมด</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">รหัสจอง</th>
                            <th>ลูกค้า</th>
                            <th>ห้องพัก</th>
                            <th>วัน-เวลาที่จอง</th>
                            <th>ยอดสุทธิ</th>
                            <th>สถานะ</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="p-3 text-muted">#<?php echo $row['booking_id']; ?></td>
                            <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                            <td class="fw-bold text-primary"><?php echo $row['room_name']; ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?><br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?></small>
                            </td>
                            <td class="text-success fw-bold">฿<?php echo number_format($row['net_price'], 2); ?></td>
                            <td>
                                <?php 
                                    if($row['booking_status'] == 'pending') echo '<span class="badge bg-warning text-dark">รออนุมัติ</span>';
                                    elseif($row['booking_status'] == 'confirmed') echo '<span class="badge bg-success">ยืนยันแล้ว</span>';
                                    elseif($row['booking_status'] == 'completed') echo '<span class="badge bg-secondary">เสร็จสิ้น</span>';
                                    else echo '<span class="badge bg-danger">ยกเลิกแล้ว</span>';
                                ?>
                            </td>
                            <td class="text-center">
                                <?php if($row['booking_status'] == 'pending' || $row['booking_status'] == 'confirmed'): ?>
                                    <a href="manage_bookings.php?cancel_id=<?php echo $row['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ต้องการยกเลิกการจองรายการนี้?');">บังคับยกเลิก</a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>