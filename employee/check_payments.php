<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// กดปุ่มชำระเงินเรียบร้อย (Clear Bill)
if (isset($_GET['clear_bill_id'])) {
    $b_id = $_GET['clear_bill_id'];
    
    // อัปเดตสถานะห้องเป็น completed (เสร็จสิ้น) เพื่อให้หายไปจากหน้าจอ
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'completed' WHERE booking_id = $b_id");
    
    $_SESSION['success'] = "บันทึกการรับชำระเงินและเคลียร์บิลเรียบร้อยแล้ว";
    header("location: check_payments.php");
    exit();
}

// ดึงข้อมูลห้องที่กำลังใช้งาน (confirmed) พร้อมคำนวณยอดอาหารรวมทั้งหมดของห้องนั้น
$query = "SELECT b.booking_id, b.net_price, u.first_name, r.room_name,
                 COALESCE(SUM(o.total_price), 0) as total_food
          FROM bookings b
          JOIN users u ON b.customer_id = u.user_id
          JOIN rooms r ON b.room_id = r.room_id
          LEFT JOIN orders o ON b.booking_id = o.booking_id AND o.order_status != 'cancelled'
          WHERE b.booking_status = 'confirmed'
          GROUP BY b.booking_id
          ORDER BY b.start_time ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>เช็คบิลรวม | พนักงาน</title>
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
                    <li class="nav-item"><a class="nav-link active text-dark" href="index.php">สถานะห้อง (Dashboard)</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="view_bookings.php">คิวจองวันนี้</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="room_status.php">เช็คอิน/เช็คเอาท์</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_orders.php">ออเดอร์อาหาร</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="check_payments.php">เช็คบิล</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_customers.php">ลูกค้า Walk-in</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="verify_payments.php">ตรวจสลิปโอนเงิน</a></li>
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h3 class="mb-4">🧾 เช็คบิลและรับชำระเงิน (Clear Bill)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $room_fee = $row['net_price'];
                    $food_fee = $row['total_food'];
                    $grand_total = $room_fee + $food_fee;
                ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between">
                            <span>ห้อง: <?php echo $row['room_name']; ?></span>
                            <span>#<?php echo $row['booking_id']; ?></span>
                        </div>
                        <div class="card-body bg-light">
                            <p class="mb-2 text-muted">ลูกค้า: <strong><?php echo $row['first_name']; ?></strong></p>
                            
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                    ค่าห้องคาราโอเกะ
                                    <span class="fw-bold text-dark">฿<?php echo number_format($room_fee, 2); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                    ค่าอาหารและเครื่องดื่ม
                                    <span class="fw-bold text-dark">฿<?php echo number_format($food_fee, 2); ?></span>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 border-dark">
                                    <span class="fs-5 fw-bold">ยอดรวมสุทธิ</span>
                                    <span class="fs-4 fw-bold text-danger">฿<?php echo number_format($grand_total, 2); ?></span>
                                </li>
                            </ul>

                            <a href="check_payments.php?clear_bill_id=<?php echo $row['booking_id']; ?>" class="btn btn-success w-100 py-2 fw-bold fs-5" onclick="return confirm('ลูกค้าชำระเงินยอด ฿<?php echo number_format($grand_total, 2); ?> ครบถ้วนแล้วใช่หรือไม่?');">
                                💰 รับชำระเงิน & ปิดบิล
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center p-5 bg-white shadow-sm rounded">
                    <h5 class="text-muted">ไม่มีห้องที่กำลังรอเช็คบิลในขณะนี้</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>