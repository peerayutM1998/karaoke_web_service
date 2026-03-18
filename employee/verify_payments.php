<?php
session_start();
require_once "../config/db_connect.php";

// เช็คว่าเป็นพนักงานหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

$emp_id = $_SESSION['user_id'];

// พนักงานกดยืนยันสลิปโอนเงิน
if (isset($_GET['approve_id'])) {
    $payment_id = $_GET['approve_id'];
    
    $get_b = mysqli_query($conn, "SELECT booking_id FROM payments WHERE payment_id = $payment_id");
    $b_row = mysqli_fetch_assoc($get_b);
    $b_id = $b_row['booking_id'];

    // อัปเดตสถานะการจ่ายเงิน และบันทึกว่าพนักงานคนไหน (emp_id) เป็นคนกดยืนยัน
    mysqli_query($conn, "UPDATE payments SET payment_status = 'verified', verified_by = $emp_id WHERE payment_id = $payment_id");
    
    // เปลี่ยนสถานะห้องเป็น confirmed เพื่อให้พร้อมใช้งาน
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'confirmed' WHERE booking_id = $b_id");
    
    $_SESSION['success'] = "ตรวจสอบสลิปผ่านแล้ว! ยืนยันการจองให้ลูกค้าเรียบร้อย";
    header("location: verify_payments.php");
    exit();
}

// พนักงานกดปฏิเสธสลิป (กรณีสลิปปลอม/ยอดไม่ตรง)
if (isset($_GET['reject_id'])) {
    $payment_id = $_GET['reject_id'];
    mysqli_query($conn, "UPDATE payments SET payment_status = 'rejected', verified_by = $emp_id WHERE payment_id = $payment_id");
    $_SESSION['error'] = "ปฏิเสธสลิปรายการนี้แล้ว ลูกค้าต้องอัปโหลดสลิปใหม่";
    header("location: verify_payments.php");
    exit();
}

// ดึงรายการชำระเงินที่ "รอตรวจสอบ" (pending) มาแสดง
$query = "SELECT p.*, b.net_price, u.first_name, u.last_name, u.phone, r.room_name 
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
    <title>ตรวจสลิปโอนเงิน | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
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
                    <li class="nav-item"><a class="nav-link active text-dark fw-bold" href="manage_orders.php">ออเดอร์อาหาร</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="check_payments.php">เช็คบิล</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_customers.php">ลูกค้า Walk-in</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="verify_payments.php">ตรวจสลิปโอนเงิน</a></li>
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <a href="add_order.php" class="btn btn-success btn-sm me-3">➕ พนักงานสั่งอาหาร</a>
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="mb-4">🔍 ตรวจสอบสลิปโอนเงิน (รอยืนยัน)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

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
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
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
                                    <a href="../uploads/slips/<?php echo $row['slip_image']; ?>" target="_blank" class="btn btn-sm btn-outline-info">ดูสลิป</a>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a href="verify_payments.php?approve_id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันรับยอดเงินและอนุมัติคิวจองให้ลูกค้าเลยใช่ไหม?');">ยืนยันยอด</a>
                                        <a href="verify_payments.php?reject_id=<?php echo $row['payment_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ปฏิเสธสลิปใบนี้?');">ปฏิเสธ</a>
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