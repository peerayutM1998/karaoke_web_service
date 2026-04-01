<?php
session_start();
// ตั้งค่าเวลาให้ตรงกับประเทศไทย
date_default_timezone_set('Asia/Bangkok');

require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// อัปเดตสถานะออเดอร์
if (isset($_GET['action']) && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    $action = $_GET['action'];
    
    if ($action == 'collect_cash') {
        $new_status = 'จ่ายแล้ว';
        // อัปเดตเวลาจ่ายเงินด้วย
        mysqli_query($conn, "UPDATE orders SET order_status = '$new_status', payment_time = NOW() WHERE order_id = $order_id");
        $_SESSION['success'] = "รับเงินสดเรียบร้อย ออเดอร์ถูกส่งเข้าครัวแล้ว!";
        header("location: manage_orders.php");
        exit();
    } elseif ($action == 'prepare') {
        $new_status = 'preparing';
        $_SESSION['success'] = "เริ่มทำอาหารแล้ว";
    } elseif ($action == 'serve') {
        $new_status = 'served';
        $_SESSION['success'] = "เสิร์ฟอาหารเรียบร้อย";
    } elseif ($action == 'cancel') {
        $new_status = 'cancelled';
        $_SESSION['error'] = "ยกเลิกออเดอร์บิลนี้เรียบร้อยแล้ว"; 
    }
    
    mysqli_query($conn, "UPDATE orders SET order_status = '$new_status' WHERE order_id = $order_id");
    header("location: manage_orders.php");
    exit();
}

// 1. ดึงออเดอร์ที่ "กำลังรอดำเนินการ" (รวมสถานะการชำระเงินใหม่เข้าไปด้วย)
$query_active = "SELECT o.*, r.room_name 
                 FROM orders o 
                 JOIN bookings b ON o.booking_id = b.booking_id 
                 JOIN rooms r ON b.room_id = r.room_id 
                 WHERE o.order_status IN ('pending', 'รอชำระเงินสด', 'รอตรวจสอบสลิป', 'จ่ายแล้ว', 'preparing')
                 ORDER BY o.created_at ASC";
$result_active = mysqli_query($conn, $query_active);

// 2. ดึง "ประวัติออเดอร์" ของวันนี้ (ที่เสิร์ฟแล้ว หรือ ยกเลิกแล้ว)
$today = date('Y-m-d');
$query_history = "SELECT o.*, r.room_name 
                  FROM orders o 
                  JOIN bookings b ON o.booking_id = b.booking_id 
                  JOIN rooms r ON b.room_id = r.room_id 
                  WHERE o.order_status IN ('served', 'cancelled') 
                  AND DATE(o.created_at) = '$today'
                  ORDER BY o.created_at DESC";
$result_history = mysqli_query($conn, $query_history);
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

    <div class="container mt-4 mb-5">
        
        <h3 class="mb-4">🛎️ รับออเดอร์และเตรียมเสิร์ฟ</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <?php if(mysqli_num_rows($result_active) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result_active)): 
                    $order_id = $row['order_id'];
                    $items_query = mysqli_query($conn, "SELECT oi.quantity, m.menu_name FROM order_items oi JOIN menus m ON oi.menu_id = m.menu_id WHERE oi.order_id = $order_id");
                    
                    // กำหนดสีหัวการ์ดตามสถานะ
                    $bg_color = "bg-info text-dark"; // สีตั้งต้น
                    if ($row['order_status'] == 'รอชำระเงินสด') $bg_color = "bg-warning text-dark";
                    elseif ($row['order_status'] == 'รอตรวจสอบสลิป') $bg_color = "bg-secondary text-white";
                    elseif ($row['order_status'] == 'จ่ายแล้ว') $bg_color = "bg-primary text-white";
                ?>
                <div class="col-md-4">
                    <div class="card shadow border-0 h-100">
                        <div class="card-header <?php echo $bg_color; ?> fw-bold d-flex justify-content-between align-items-center">
                            <span>ห้อง: <?php echo $row['room_name']; ?></span>
                            <span>#ORD-<?php echo $order_id; ?></span>
                        </div>
                        <div class="card-body d-flex flex-column">
                            
                            <div class="mb-3 text-center">
                                <?php if($row['order_status'] == 'รอชำระเงินสด'): ?>
                                    <span class="badge bg-warning text-dark w-100 py-2 fs-6">⚠️ รอพนักงานไปเก็บเงินสด</span>
                                <?php elseif($row['order_status'] == 'รอตรวจสอบสลิป'): ?>
                                    <span class="badge bg-secondary w-100 py-2 fs-6">⏳ ลูกค้าโอนแล้ว รอตรวจสลิป</span>
                                <?php elseif($row['order_status'] == 'จ่ายแล้ว'): ?>
                                    <span class="badge bg-success w-100 py-2 fs-6">✅ ชำระเงินแล้ว (รอทำอาหาร)</span>
                                <?php elseif($row['order_status'] == 'preparing'): ?>
                                    <span class="badge bg-info text-dark w-100 py-2 fs-6">🍳 กำลังทำอาหาร</span>
                                <?php endif; ?>
                            </div>

                            <ul class="list-unstyled mb-3 flex-grow-1">
                                <?php while($item = mysqli_fetch_assoc($items_query)): ?>
                                    <li class="border-bottom py-1">➔ <?php echo $item['menu_name']; ?> <span class="badge bg-dark float-end">x <?php echo $item['quantity']; ?></span></li>
                                <?php endwhile; ?>
                            </ul>
                            
                            <div class="d-grid gap-2 mt-3 pt-3 border-top">
                                <?php if($row['order_status'] == 'รอชำระเงินสด'): ?>
                                    <a href="manage_orders.php?action=collect_cash&id=<?php echo $order_id; ?>" class="btn btn-warning fw-bold border border-dark">💵 เก็บเงินแล้ว (ส่งเข้าครัว)</a>
                                
                                <?php elseif($row['order_status'] == 'รอตรวจสอบสลิป'): ?>
                                    <a href="verify_payments.php" class="btn btn-secondary fw-bold">🔍 ไปหน้าตรวจสลิปโอนเงิน</a>
                                
                                <?php elseif($row['order_status'] == 'จ่ายแล้ว' || $row['order_status'] == 'pending'): ?>
                                    <a href="manage_orders.php?action=prepare&id=<?php echo $order_id; ?>" class="btn btn-primary fw-bold">🍳 เริ่มทำอาหาร</a>
                                
                                <?php elseif($row['order_status'] == 'preparing'): ?>
                                    <a href="manage_orders.php?action=serve&id=<?php echo $order_id; ?>" class="btn btn-success fw-bold">🍽️ เสิร์ฟอาหารเรียบร้อย</a>
                                <?php endif; ?>
                                
                                <a href="manage_orders.php?action=cancel&id=<?php echo $order_id; ?>" class="btn btn-outline-danger btn-sm mt-2" onclick="return confirm('ยืนยันการยกเลิกออเดอร์นี้ใช่หรือไม่?');">
                                    ❌ ยกเลิกออเดอร์นี้
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center p-5 bg-white shadow-sm rounded text-muted">
                    <h5 class="mb-0">ไม่มีออเดอร์ใหม่ค้างในระบบ</h5>
                </div>
            <?php endif; ?>
        </div>

        <hr class="mb-4">
        <h4 class="mb-3 text-secondary">🕒 ประวัติการสั่งอาหาร (ประจำวันที่ <?php echo date('d/m/Y'); ?>)</h4>
        
        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">เวลาที่สั่ง</th>
                            <th>รหัสบิล</th>
                            <th>ห้องคาราโอเกะ</th>
                            <th>รายการอาหาร (จำนวน)</th>
                            <th>ยอดรวม (บาท)</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result_history) > 0): ?>
                            <?php while($history = mysqli_fetch_assoc($result_history)): 
                                $h_order_id = $history['order_id'];
                                $h_items = mysqli_query($conn, "SELECT oi.quantity, m.menu_name FROM order_items oi JOIN menus m ON oi.menu_id = m.menu_id WHERE oi.order_id = $h_order_id");
                                $item_list = [];
                                while($i = mysqli_fetch_assoc($h_items)){
                                    $item_list[] = $i['menu_name'] . " (x" . $i['quantity'] . ")";
                                }
                            ?>
                            <tr>
                                <td class="p-3"><?php echo date('H:i', strtotime($history['created_at'])); ?> น.</td>
                                <td class="text-muted fw-bold">#ORD-<?php echo $h_order_id; ?></td>
                                <td class="text-primary fw-bold"><?php echo $history['room_name']; ?></td>
                                <td><small><?php echo implode(", ", $item_list); ?></small></td>
                                <td class="text-danger fw-bold">฿<?php echo number_format($history['total_price'], 2); ?></td>
                                <td>
                                    <?php if($history['order_status'] == 'served'): ?>
                                        <span class="badge bg-success">เสิร์ฟแล้ว</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">ถูกยกเลิก</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center p-4 text-muted">ยังไม่มีประวัติออเดอร์ที่ทำเสร็จสิ้น หรือ ถูกยกเลิก ในวันนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>