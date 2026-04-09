<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// 🌟 ระบบประมวลผลเมื่อพนักงานกดยกเลิกคิว (ลูกค้าไม่มา)
if (isset($_GET['action']) && isset($_GET['id']) && $_GET['action'] == 'cancel_noshow') {
    $cancel_id = intval($_GET['id']);
    
    // เปลี่ยนสถานะการจองเป็น cancelled ระบบ Overlap Check จะถือว่าเวลานี้ว่างทันที
    $sql_cancel = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = $cancel_id";
    
    if (mysqli_query($conn, $sql_cancel)) {
        $_SESSION['success'] = "ยกเลิกคิวจอง #$cancel_id (ลูกค้าไม่มา) เรียบร้อยแล้ว! เวลานี้ถูกปลดล็อคให้คนอื่นจองได้แล้วครับ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการยกเลิกคิว: " . mysqli_error($conn);
    }
    header("location: view_bookings.php");
    exit();
}

$today = date('Y-m-d');

// ดึงคิวจองเฉพาะของ "วันนี้" ที่สถานะเป็น confirmed (อนุมัติแล้ว)
$query = "SELECT b.*, u.first_name, u.phone, r.room_name 
          FROM bookings b 
          JOIN users u ON b.customer_id = u.user_id 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE b.booking_date = '$today' AND b.booking_status = 'confirmed'
          ORDER BY b.start_time ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>คิวจองวันนี้ | พนักงาน</title>
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
                    <li class="nav-item"><a class="nav-link active text-dark fw-bold" href="view_bookings.php">คิวจองวันนี้</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="room_status.php">เช็คอิน/เช็คเอาท์</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_orders.php">ออเดอร์อาหาร</a></li>
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

    <div class="container mt-4 mb-5">
        <h3 class="mb-4">📋 คิวจองห้องคาราโอเกะ (ประจำวันที่ <?php echo date('d/m/Y'); ?>)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">เวลา</th>
                            <th>ห้อง</th>
                            <th>ชื่อลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>ระยะเวลา (ชม.)</th>
                            <th class="text-center">จัดการคิว</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="p-3 fw-bold text-danger">
                                    <?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?>
                                </td>
                                <td class="fw-bold text-primary fs-5"><?php echo $row['room_name']; ?></td>
                                <td><?php echo $row['first_name']; ?></td>
                                <td>
                                    <a href="tel:<?php echo $row['phone']; ?>" class="text-decoration-none text-dark">
                                        📞 <?php echo $row['phone']; ?>
                                    </a>
                                </td>
                                <td><?php echo $row['total_hours']; ?> ชม.</td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-success">✅ เตรียมห้องแล้ว</button>
                                        <a href="view_bookings.php?action=cancel_noshow&id=<?php echo $row['booking_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('ลูกค้ารายนี้ไม่มาตามนัดใช่หรือไม่?\n\nหากกดยืนยัน คิวนี้จะถูกยกเลิก และเวลาจองนี้จะเปิดว่างให้ลูกค้ารายอื่นกดจองได้ทันที');">
                                           ❌ ยกเลิก (ลูกค้าไม่มา)
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center p-5 text-muted">ไม่มีคิวการจองสำหรับวันนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>