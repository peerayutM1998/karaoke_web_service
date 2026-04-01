<?php
session_start();
require_once "../config/db_connect.php";

// เช็คว่าเป็นพนักงานหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

$emp_id = $_SESSION['user_id'];

// ตรวจสอบว่ากำลังเปิดแท็บไหนอยู่ (ค่าเริ่มต้นคือแท็บจองห้อง)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'bookings';

// ==========================================
// 1. ระบบจัดการ: สลิปค่าห้องพัก (Bookings)
// ==========================================
if (isset($_GET['approve_id'])) {
    $payment_id = $_GET['approve_id'];
    $get_b = mysqli_query($conn, "SELECT booking_id FROM payments WHERE payment_id = $payment_id");
    $b_row = mysqli_fetch_assoc($get_b);
    $b_id = $b_row['booking_id'];

    mysqli_query($conn, "UPDATE payments SET payment_status = 'verified', verified_by = $emp_id WHERE payment_id = $payment_id");
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = $b_id");
    
    $_SESSION['success'] = "ตรวจสอบสลิปค่าห้องผ่านแล้ว! ยืนยันการจองเรียบร้อย";
    header("location: verify_payments.php?tab=bookings");
    exit();
}

if (isset($_GET['reject_id'])) {
    $payment_id = $_GET['reject_id'];
    mysqli_query($conn, "UPDATE payments SET payment_status = 'rejected', verified_by = $emp_id WHERE payment_id = $payment_id");
    $_SESSION['error'] = "ปฏิเสธสลิปค่าห้องแล้ว ลูกค้าต้องอัปโหลดสลิปใหม่";
    header("location: verify_payments.php?tab=bookings");
    exit();
}

// ==========================================
// 2. ระบบจัดการ: สลิปค่าอาหาร (Orders)
// ==========================================
if (isset($_GET['approve_order_id'])) {
    $order_id = $_GET['approve_order_id'];
    // เปลี่ยนสถานะเป็น จ่ายแล้ว และอัปเดตเวลาจ่ายเงิน
    mysqli_query($conn, "UPDATE orders SET order_status = 'จ่ายแล้ว', payment_time = NOW() WHERE order_id = $order_id");
    
    $_SESSION['success'] = "ตรวจสอบสลิปค่าอาหารผ่านแล้ว! ออเดอร์ถูกส่งเข้าห้องครัวเรียบร้อย";
    header("location: verify_payments.php?tab=orders");
    exit();
}

if (isset($_GET['reject_order_id'])) {
    $order_id = $_GET['reject_order_id'];
    // ถ้าสลิปไม่ผ่าน ให้กลับไปเป็นสถานะรอชำระเงินใหม่
    mysqli_query($conn, "UPDATE orders SET order_status = 'pending' WHERE order_id = $order_id");
    
    $_SESSION['error'] = "ปฏิเสธสลิปค่าอาหารแล้ว ออเดอร์ถูกเปลี่ยนกลับเป็นยังไม่ชำระเงิน";
    header("location: verify_payments.php?tab=orders");
    exit();
}

// ==========================================
// ดึงข้อมูลมาแสดงผลในตาราง
// ==========================================

// คิวรี่ 1: ดึงสลิปค่าห้อง (จากตาราง payments)
$query_bookings = "SELECT p.*, b.net_price, u.first_name, u.last_name, u.phone, r.room_name 
                   FROM payments p 
                   JOIN bookings b ON p.booking_id = b.booking_id 
                   JOIN users u ON b.customer_id = u.user_id 
                   JOIN rooms r ON b.room_id = r.room_id 
                   WHERE p.payment_status = 'pending' 
                   ORDER BY p.payment_date ASC";
$result_bookings = mysqli_query($conn, $query_bookings);

// คิวรี่ 2: ดึงสลิปค่าอาหาร (จากตาราง orders ที่สถานะ 'รอตรวจสอบสลิป')
$query_orders = "SELECT o.*, r.room_name, u.first_name, u.last_name, u.phone 
                 FROM orders o 
                 JOIN bookings b ON o.booking_id = b.booking_id 
                 JOIN users u ON b.customer_id = u.user_id 
                 JOIN rooms r ON b.room_id = r.room_id 
                 WHERE o.order_status = 'รอตรวจสอบสลิป' 
                 ORDER BY o.created_at ASC";
$result_orders = mysqli_query($conn, $query_orders);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตรวจสลิปโอนเงิน | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-info shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-dark" href="index.php">👨‍💼 Employee Desk</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link text-dark" href="index.php">สถานะห้อง (Dashboard)</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="view_bookings.php">คิวจองวันนี้</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="room_status.php">เช็คอิน/เช็คเอาท์</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_orders.php">ออเดอร์อาหาร</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="check_payments.php">เช็คบิล</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_customers.php">ลูกค้า Walk-in</a></li>
                    <li class="nav-item"><a class="nav-link active text-dark fw-bold" href="verify_payments.php">ตรวจสลิปโอนเงิน</a></li>
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <h3 class="mb-4">🔍 ตรวจสอบสลิปโอนเงิน (รอยืนยัน)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <ul class="nav nav-tabs fs-5 fw-bold mb-3" id="paymentTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'bookings') ? 'active text-primary' : 'text-muted'; ?>" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button" role="tab">
                    🛏️ สลิปค่าจองห้องพัก
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?php echo ($active_tab == 'orders') ? 'active text-primary' : 'text-muted'; ?>" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button" role="tab">
                    🍔 สลิปค่าอาหาร
                </button>
            </li>
        </ul>

        <div class="tab-content" id="paymentTabsContent">
            
            <div class="tab-pane fade <?php echo ($active_tab == 'bookings') ? 'show active' : ''; ?>" id="bookings" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="p-3">รหัสจอง</th>
                                    <th>ลูกค้า (เบอร์โทร)</th>
                                    <th>ห้องพัก</th>
                                    <th>ยอดแจ้งโอน</th>
                                    <th>เวลาที่แจ้งโอน</th>
                                    <th class="text-center">หลักฐาน (สลิป)</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result_bookings) > 0): ?>
                                    <?php while($row = mysqli_fetch_assoc($result_bookings)): ?>
                                    <tr>
                                        <td class="p-3 fw-bold text-muted">#<?php echo $row['booking_id']; ?></td>
                                        <td>
                                            <?php echo $row['first_name'] . " " . $row['last_name']; ?><br>
                                            <small class="text-muted">📞 <?php echo $row['phone']; ?></small>
                                        </td>
                                        <td class="text-primary fw-bold"><?php echo $row['room_name']; ?></td>
                                        <td class="text-success fw-bold">฿<?php echo number_format($row['amount_paid'], 2); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($row['payment_date'])); ?></td>
                                        <td class="text-center">
                                            <a href="../uploads/slips/<?php echo $row['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">🖼️ ดูสลิป</a>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="verify_payments.php?approve_id=<?php echo $row['payment_id']; ?>&tab=bookings" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันรับยอดเงินและอนุมัติคิวจองให้ลูกค้าเลยใช่ไหม?');">✅ ยืนยันยอด</a>
                                                <a href="verify_payments.php?reject_id=<?php echo $row['payment_id']; ?>&tab=bookings" class="btn btn-sm btn-danger" onclick="return confirm('ปฏิเสธสลิปใบนี้?');">❌ ปฏิเสธ</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center p-5 text-muted">ไม่มีรายการสลิปค่าห้องที่รอตรวจสอบในขณะนี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade <?php echo ($active_tab == 'orders') ? 'show active' : ''; ?>" id="orders" role="tabpanel">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="p-3">รหัสบิล</th>
                                    <th>ลูกค้า (เบอร์โทร)</th>
                                    <th>ห้องที่สั่ง</th>
                                    <th>ยอดรวมอาหาร</th>
                                    <th>เวลาที่สั่ง</th>
                                    <th class="text-center">หลักฐาน (สลิป)</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(mysqli_num_rows($result_orders) > 0): ?>
                                    <?php while($ord = mysqli_fetch_assoc($result_orders)): ?>
                                    <tr>
                                        <td class="p-3 fw-bold text-muted">#ORD-<?php echo $ord['order_id']; ?></td>
                                        <td>
                                            <?php echo $ord['first_name'] . " " . $ord['last_name']; ?><br>
                                            <small class="text-muted">📞 <?php echo $ord['phone']; ?></small>
                                        </td>
                                        <td class="text-primary fw-bold"><?php echo $ord['room_name']; ?></td>
                                        <td class="text-success fw-bold">฿<?php echo number_format($ord['total_price'], 2); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($ord['created_at'])); ?></td>
                                        <td class="text-center">
                                            <?php if(!empty($ord['slip_image'])): ?>
                                                <a href="../uploads/slips/<?php echo $ord['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">🖼️ ดูสลิป</a>
                                            <?php else: ?>
                                                <span class="text-muted small">ไม่พบรูปสลิป</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group" role="group">
                                                <a href="verify_payments.php?approve_order_id=<?php echo $ord['order_id']; ?>&tab=orders" class="btn btn-sm btn-success" onclick="return confirm('สลิปถูกต้อง? ยืนยันยอดและส่งออเดอร์เข้าครัวเลยไหม?');">✅ ผ่าน (ส่งครัว)</a>
                                                <a href="verify_payments.php?reject_order_id=<?php echo $ord['order_id']; ?>&tab=orders" class="btn btn-sm btn-danger" onclick="return confirm('สลิปมีปัญหา? ปฏิเสธยอดนี้ไหม?');">❌ ไม่ผ่าน</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="7" class="text-center p-5 text-muted">ไม่มีรายการสลิปค่าอาหารที่รอตรวจสอบในขณะนี้</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>