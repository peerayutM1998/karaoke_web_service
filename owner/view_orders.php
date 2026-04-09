<?php
session_start();
require_once "../config/db_connect.php";

// เช็คสิทธิ์ว่าเป็นเจ้าของร้านหรือไม่ (พนักงานก็ใช้หน้านี้ได้ถ้าคุณเปลี่ยน role)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// รับค่าตัวกรองจากฟอร์ม (ถ้าไม่มีให้ใช้วันที่ปัจจุบัน)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // ค่าเริ่มต้นคือต้นเดือน
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // ค่าเริ่มต้นคือวันนี้
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// สร้างเงื่อนไข SQL สำหรับค้นหา
$status_condition = "";
if ($filter_status != 'all') {
    $status_condition = "AND o.order_status = '$filter_status'";
}

// ดึงข้อมูลบิลอาหารพร้อมชื่อลูกค้าและชื่อห้อง
$query = "SELECT o.*, r.room_name, u.first_name, u.phone 
          FROM orders o 
          JOIN bookings b ON o.booking_id = b.booking_id 
          JOIN rooms r ON b.room_id = r.room_id 
          JOIN users u ON b.customer_id = u.user_id 
          WHERE DATE(o.created_at) BETWEEN '$start_date' AND '$end_date' 
          $status_condition 
          ORDER BY o.created_at DESC";
$result = mysqli_query($conn, $query);

// คำนวณสรุปยอด (นับเฉพาะบิลที่เสิร์ฟแล้ว หรือ จ่ายเงินแล้ว)
$total_income = 0;
$total_orders = 0;
$total_cancelled = 0;

$orders_data = []; // เก็บข้อมูลไว้แสดงในตาราง
if(mysqli_num_rows($result) > 0){
    while($row = mysqli_fetch_assoc($result)){
        $orders_data[] = $row;
        
        if(in_array($row['order_status'], ['จ่ายแล้ว', 'served', 'preparing'])) {
            $total_income += $row['total_price'];
            $total_orders++;
        } elseif ($row['order_status'] == 'cancelled') {
            $total_cancelled++;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รวมยอดสั่งอาหาร | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } 
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { background-color: #fff; }
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link" href="index.php">🏠 แดชบอร์ด</a></li>
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
                            <li><a class="dropdown-item active" href="view_orders.php">🍔 รายการสั่งอาหาร</a></li>
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

    <div class="container mt-4 mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4 no-print">
            <h3 class="mb-0">🍔 สรุปรายการสั่งอาหารและเครื่องดื่ม</h3>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ พิมพ์รายงาน</button>
        </div>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <form action="view_orders.php" method="GET" class="row align-items-end g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">สถานะบิลอาหาร</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo ($filter_status=='all')?'selected':''; ?>>ทั้งหมด</option>
                            <option value="served" <?php echo ($filter_status=='served')?'selected':''; ?>>เสิร์ฟแล้ว</option>
                            <option value="จ่ายแล้ว" <?php echo ($filter_status=='จ่ายแล้ว')?'selected':''; ?>>ชำระเงินแล้ว (รอกำลังทำ)</option>
                            <option value="pending" <?php echo ($filter_status=='pending')?'selected':''; ?>>ยังไม่ชำระเงิน</option>
                            <option value="cancelled" <?php echo ($filter_status=='cancelled')?'selected':''; ?>>ถูกยกเลิก</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 fw-bold">🔍 ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success text-white shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <h5 class="mb-2 opacity-75">ยอดขายอาหารรวม</h5>
                        <h2 class="fw-bold mb-0">฿<?php echo number_format($total_income, 2); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-primary text-white shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <h5 class="mb-2 opacity-75">ออเดอร์ที่สำเร็จ</h5>
                        <h2 class="fw-bold mb-0"><?php echo $total_orders; ?> <span class="fs-5">บิล</span></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger text-white shadow-sm border-0 h-100">
                    <div class="card-body text-center py-4">
                        <h5 class="mb-2 opacity-75">ออเดอร์ที่ถูกยกเลิก</h5>
                        <h2 class="fw-bold mb-0"><?php echo $total_cancelled; ?> <span class="fs-5">บิล</span></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">วันที่-เวลา</th>
                            <th>รหัสบิล</th>
                            <th>ลูกค้า (ห้อง)</th>
                            <th>รายการอาหาร (จำนวน)</th>
                            <th class="text-end">ยอดรวม</th>
                            <th class="text-center">วิธีชำระเงิน</th>
                            <th class="text-center">สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($orders_data) > 0): ?>
                            <?php foreach($orders_data as $order): 
                                $order_id = $order['order_id'];
                                
                                // ดึงรายการอาหารย่อยมาโชว์ในตาราง
                                $items_q = mysqli_query($conn, "SELECT oi.quantity, m.menu_name FROM order_items oi JOIN menus m ON oi.menu_id = m.menu_id WHERE oi.order_id = $order_id");
                                $items_text = [];
                                while($i = mysqli_fetch_assoc($items_q)){
                                    $items_text[] = $i['menu_name'] . " (x" . $i['quantity'] . ")";
                                }
                            ?>
                            <tr>
                                <td class="p-3 text-muted"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                <td class="fw-bold">#ORD-<?php echo $order_id; ?></td>
                                <td>
                                    <?php echo $order['first_name']; ?><br>
                                    <small class="text-primary fw-bold">ห้อง: <?php echo $order['room_name']; ?></small>
                                </td>
                                <td><small><?php echo implode("<br>• ", $items_text); ?></small></td>
                                <td class="text-end fw-bold text-success">฿<?php echo number_format($order['total_price'], 2); ?></td>
                                <td class="text-center">
                                    <?php echo !empty($order['payment_method']) ? $order['payment_method'] : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <?php 
                                        if($order['order_status'] == 'served') echo '<span class="badge bg-success">เสิร์ฟแล้ว</span>';
                                        elseif($order['order_status'] == 'จ่ายแล้ว') echo '<span class="badge bg-primary">ชำระแล้ว</span>';
                                        elseif($order['order_status'] == 'preparing') echo '<span class="badge bg-info text-dark">กำลังทำ</span>';
                                        elseif($order['order_status'] == 'cancelled') echo '<span class="badge bg-danger">ยกเลิก</span>';
                                        else echo '<span class="badge bg-warning text-dark">รอชำระเงิน</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center p-5 text-muted">ไม่มีรายการสั่งอาหารในช่วงเวลาที่คุณเลือก</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>