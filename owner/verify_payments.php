<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// อนุมัติสลิปโอนเงิน (Approve)
if (isset($_GET['approve_id'])) {
    $payment_id = $_GET['approve_id'];
    
    // 1. ดึงหมายเลขการจอง (booking_id) จาก payment นี้
    $get_b = mysqli_query($conn, "SELECT booking_id FROM payments WHERE payment_id = $payment_id");
    $b_row = mysqli_fetch_assoc($get_b);
    $b_id = $b_row['booking_id'];

    // 2. อัปเดตสถานะการจ่ายเงินเป็น verified และบันทึกว่าแอดมินคนไหนกด
    mysqli_query($conn, "UPDATE payments SET payment_status = 'verified', verified_by = $admin_id WHERE payment_id = $payment_id");
    
    // 3. อัปเดตสถานะการจองห้องพักให้เป็น confirmed
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = $b_id");
    
    $_SESSION['success'] = "ยืนยันการรับยอดเงินและอนุมัติการจองเรียบร้อยแล้ว";
    header("location: verify_payments.php");
    exit();
}

// ปฏิเสธสลิป (Reject) กรณีสลิปปลอมหรือยอดไม่ตรง
if (isset($_GET['reject_id'])) {
    $payment_id = $_GET['reject_id'];
    mysqli_query($conn, "UPDATE payments SET payment_status = 'rejected', verified_by = $admin_id WHERE payment_id = $payment_id");
    $_SESSION['error'] = "ปฏิเสธสลิปการโอนเงินเรียบร้อยแล้ว (สถานะการจองยังคงเป็น pending)";
    header("location: verify_payments.php");
    exit();
}

// ดึงรายการชำระเงินที่ "รอตรวจสอบ" (pending) พร้อมข้อมูลลูกค้า
$query = "SELECT p.*, b.net_price, u.first_name, u.last_name, r.room_name 
          FROM payments p 
          JOIN bookings b ON p.booking_id = b.booking_id 
          JOIN users u ON b.customer_id = u.user_id 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE p.payment_status = 'pending' 
          ORDER BY p.payment_date ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสอบชำระเงิน | เจ้าของร้าน</title>
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
        <h3 class="mb-4">💳 ตรวจสอบสลิปโอนเงิน (รออนุมัติ)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">รหัสจอง</th>
                            <th>ลูกค้า (ห้อง)</th>
                            <th>ยอดที่ต้องจ่าย</th>
                            <th>ยอดโอนจริง</th>
                            <th>เวลาที่แจ้งโอน</th>
                            <th class="text-center">หลักฐาน (สลิป)</th>
                            <th class="text-center">ตรวจสอบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="p-3 fw-bold text-muted">#<?php echo $row['booking_id']; ?></td>
                                <td>
                                    <?php echo $row['first_name'] . " " . $row['last_name']; ?><br>
                                    <small class="text-primary">ห้อง: <?php echo $row['room_name']; ?></small>
                                </td>
                                <td>฿<?php echo number_format($row['net_price'], 2); ?></td>
                                <td class="text-success fw-bold">฿<?php echo number_format($row['amount_paid'], 2); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['payment_date'])); ?></td>
                                <td class="text-center">
                                    <a href="../uploads/slips/<?php echo $row['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">ดูสลิปโอนเงิน</a>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="verify_payments.php?approve_id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันรับยอดเงินและอนุมัติห้อง?');">ยืนยันรับยอด</a>
                                        <a href="verify_payments.php?reject_id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ปฏิเสธสลิปรายการนี้?');">ปฏิเสธ</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center p-5 text-muted">ไม่มีรายการชำระเงินที่รอตรวจสอบในขณะนี้</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>