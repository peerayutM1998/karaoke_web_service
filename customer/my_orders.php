<?php
session_start();
require_once "../config/db_connect.php";

// เช็คสิทธิ์ว่าเป็นลูกค้าระบบหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลบิลอาหารทั้งหมดของลูกค้าคนนี้ (เชื่อมกับตาราง bookings เพื่อให้แน่ใจว่าเป็นของลูกค้าคนนี้จริงๆ)
$query_orders = "SELECT o.*, r.room_name 
                 FROM orders o 
                 JOIN bookings b ON o.booking_id = b.booking_id 
                 JOIN rooms r ON b.room_id = r.room_id 
                 WHERE b.customer_id = $user_id 
                 ORDER BY o.created_at DESC";
$result_orders = mysqli_query($conn, $query_orders);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติสั่งอาหาร | ลูกค้า</title>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">หน้าหลักโปรไฟล์</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">จองห้องพัก</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="order_food.php">สั่งอาหาร</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_orders.php">ประวัติสั่งอาหาร</a></li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <span class="me-3">👤 คุณ <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="mb-4">🍟 ประวัติการสั่งอาหารและเครื่องดื่ม</h3>

        <div class="row g-4">
            <?php if(mysqli_num_rows($result_orders) > 0): ?>
                <?php while($order = mysqli_fetch_assoc($result_orders)): 
                    $order_id = $order['order_id'];
                    // ดึงรายการอาหารย่อยในบิลนี้
                    $query_items = "SELECT oi.quantity, oi.subtotal, m.menu_name 
                                    FROM order_items oi 
                                    JOIN menus m ON oi.menu_id = m.menu_id 
                                    WHERE oi.order_id = $order_id";
                    $result_items = mysqli_query($conn, $query_items);
                ?>
                <div class="col-md-6">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom-0 pt-3">
                            <div>
                                <h5 class="mb-0 fw-bold text-primary">บิล #ORD-<?php echo $order_id; ?></h5>
                                <small class="text-muted">ห้อง: <?php echo $order['room_name']; ?> | เวลาสั่ง: <?php echo date('H:i (d/m/Y)', strtotime($order['created_at'])); ?></small>
                            </div>
                            <div>
                                <?php 
                                    if($order['order_status'] == 'pending') echo '<span class="badge bg-warning text-dark px-3 py-2">รอรับออเดอร์</span>';
                                    elseif($order['order_status'] == 'preparing') echo '<span class="badge bg-info text-dark px-3 py-2">กำลังทำอาหาร</span>';
                                    elseif($order['order_status'] == 'served') echo '<span class="badge bg-success px-3 py-2">เสิร์ฟแล้ว</span>';
                                    else echo '<span class="badge bg-danger px-3 py-2">ยกเลิก</span>';
                                ?>
                            </div>
                        </div>
                        <div class="card-body bg-light rounded-bottom m-2">
                            <ul class="list-group list-group-flush mb-3">
                                <?php while($item = mysqli_fetch_assoc($result_items)): ?>
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0">
                                        <span><?php echo $item['menu_name']; ?> <small class="text-muted">x <?php echo $item['quantity']; ?></small></span>
                                        <span class="fw-bold">฿<?php echo number_format($item['subtotal'], 2); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                            <div class="d-flex justify-content-between align-items-center border-top border-dark pt-2 mt-2">
                                <span class="fw-bold">ยอดรวมบิลนี้:</span>
                                <span class="fs-5 fw-bold text-danger">฿<?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center p-5 bg-white shadow-sm rounded">
                    <h5 class="text-muted mb-3">คุณยังไม่มีประวัติการสั่งอาหาร</h5>
                    <a href="order_food.php" class="btn btn-primary">ไปหน้าสั่งอาหาร</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>