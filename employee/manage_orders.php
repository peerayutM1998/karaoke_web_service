<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// อัปเดตสถานะออเดอร์
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action == 'prepare') $new_status = 'preparing';
    elseif ($action == 'serve') $new_status = 'served';
    elseif ($action == 'cancel') $new_status = 'cancelled';
    
    mysqli_query($conn, "UPDATE orders SET order_status = '$new_status' WHERE order_id = $order_id");
    header("location: manage_orders.php");
    exit();
}

// ดึงออเดอร์ที่ยังไม่เสิร์ฟ (pending และ preparing)
$query = "SELECT o.*, r.room_name 
          FROM orders o 
          JOIN bookings b ON o.booking_id = b.booking_id 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE o.order_status IN ('pending', 'preparing')
          ORDER BY o.created_at ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการออเดอร์ | พนักงาน</title>
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
        <h3 class="mb-4">🛎️ รับออเดอร์และเตรียมเสิร์ฟ</h3>
        <a href="add_order.php" class="btn btn-success">➕ พนักงานสั่งอาหารให้ลูกค้า</a>

        <div class="row g-4">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): 
                    $order_id = $row['order_id'];
                    // ดึงรายการอาหารย่อยในบิลนั้น
                    $items_query = mysqli_query($conn, "SELECT oi.quantity, m.menu_name FROM order_items oi JOIN menus m ON oi.menu_id = m.menu_id WHERE oi.order_id = $order_id");
                ?>
                <div class="col-md-4">
                    <div class="card shadow border-0">
                        <div class="card-header <?php echo ($row['order_status'] == 'pending') ? 'bg-warning text-dark' : 'bg-info text-dark'; ?> fw-bold d-flex justify-content-between">
                            <span>ห้อง: <?php echo $row['room_name']; ?></span>
                            <span>#ORD-<?php echo $order_id; ?></span>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-3">
                                <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                                    <li class="border-bottom py-1">➔ <?php echo $item['menu_name']; ?> <span class="badge bg-secondary float-end">x <?php echo $item['quantity']; ?></span></li>
                                <?php endwhile; ?>
                            </ul>
                            
                            <div class="d-grid gap-2 mt-3">
                                <?php if($row['order_status'] == 'pending'): ?>
                                    <a href="manage_orders.php?action=prepare&id=<?php echo $order_id; ?>" class="btn btn-primary">🍳 กดรับออเดอร์ (กำลังทำ)</a>
                                <?php else: ?>
                                    <a href="manage_orders.php?action=serve&id=<?php echo $order_id; ?>" class="btn btn-success">🍽️ เสิร์ฟอาหารเรียบร้อย</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center p-5 bg-white shadow-sm rounded text-muted">
                    <h5>ไม่มีออเดอร์ค้างในระบบขณะนี้</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>