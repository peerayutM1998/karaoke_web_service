<?php
session_start();
require_once "../config/db_connect.php";

// เช็คสิทธิ์ว่าเป็นลูกค้าระบบหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

// เช็คว่ามีการส่งรหัสบิล (order_id) มาหรือไม่
if (!isset($_GET['order_id'])) {
    header("location: my_orders.php");
    exit();
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลหัวบิล (เช็คด้วยว่าเป็นบิลของลูกค้าคนนี้จริงๆ เพื่อความปลอดภัย)
$query_order = "SELECT o.*, r.room_name 
                FROM orders o 
                JOIN bookings b ON o.booking_id = b.booking_id 
                JOIN rooms r ON b.room_id = r.room_id 
                WHERE o.order_id = $order_id AND b.customer_id = $user_id";
$result_order = mysqli_query($conn, $query_order);

// ถ้าไม่พบบิลนี้ (อาจจะกรอก URL มั่ว หรือไม่ใช่บิลของตัวเอง) ให้เด้งกลับ
if (mysqli_num_rows($result_order) == 0) {
    echo "<script>alert('ไม่พบข้อมูลใบเสร็จ หรือคุณไม่มีสิทธิ์เข้าถึง'); window.location.href='my_orders.php';</script>";
    exit();
}

$order = mysqli_fetch_assoc($result_order);

// ดึงรายการอาหารย่อยในบิลนี้
$query_items = "SELECT oi.quantity, oi.price_per_unit, oi.subtotal, m.menu_name 
                FROM order_items oi 
                JOIN menus m ON oi.menu_id = m.menu_id 
                WHERE oi.order_id = $order_id";
$result_items = mysqli_query($conn, $query_items);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ใบเสร็จรับเงิน #<?php echo $order_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Prompt', sans-serif; 
            background-color: #e9ecef; 
        }
        .receipt-container {
            max-width: 400px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .dotted-line {
            border-top: 2px dashed #ccc;
            margin: 15px 0;
        }
        /* คำสั่งสำหรับตอนกดปริ้นท์ ให้ซ่อนปุ่ม และลบเงากล่องทิ้ง */
        @media print {
            body { background-color: #fff; }
            .receipt-container { box-shadow: none; margin: 0 auto; padding: 15px; max-width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="receipt-container">
        <div class="text-center mb-3">
            <h4 class="fw-bold mb-1">🎤 ร้านคาราโอเกะออนไลน์</h4>
            <p class="text-muted mb-0 small">ใบเสร็จรับเงิน / ย่อ</p>
        </div>

        <div class="small mb-3">
            <div class="d-flex justify-content-between">
                <span><strong>เลขที่บิล:</strong> <?php echo !empty($order['receipt_no']) ? $order['receipt_no'] : "ORD-".str_pad($order_id, 6, "0", STR_PAD_LEFT); ?></span>
            </div>
            <div class="d-flex justify-content-between">
                <span><strong>วันที่:</strong> <?php echo date('d/m/Y', strtotime($order['created_at'])); ?></span>
                <span><strong>เวลา:</strong> <?php echo date('H:i', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <span><strong>ห้อง:</strong> <?php echo $order['room_name']; ?></span>
                <span><strong>ลูกค้า:</strong> คุณ <?php echo $_SESSION['first_name']; ?></span>
            </div>
        </div>

        <div class="dotted-line"></div>

        <div class="d-flex justify-content-between small fw-bold mb-2">
            <span style="width: 50%;">รายการ</span>
            <span style="width: 25%; text-align: right;">จำนวน</span>
            <span style="width: 25%; text-align: right;">ราคา</span>
        </div>

        <div class="small mb-3">
            <?php while($item = mysqli_fetch_assoc($result_items)): ?>
                <div class="d-flex justify-content-between mb-1">
                    <span style="width: 50%;" class="text-truncate"><?php echo $item['menu_name']; ?></span>
                    <span style="width: 25%; text-align: right;"><?php echo $item['quantity']; ?></span>
                    <span style="width: 25%; text-align: right;"><?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
            <?php endwhile; ?>
        </div>

        <div class="dotted-line"></div>

        <div class="d-flex justify-content-between fw-bold fs-5 mt-2">
            <span>ยอดรวมทั้งสิ้น</span>
            <span>฿<?php echo number_format($order['total_price'], 2); ?></span>
        </div>

        <div class="dotted-line"></div>

        <div class="text-center small mt-3">
            <p class="mb-1">
                <strong>สถานะ:</strong> 
                <?php 
                    if($order['order_status'] == 'จ่ายแล้ว' || $order['order_status'] == 'served') {
                        echo "<span class='text-success'>ชำระเงินแล้ว</span>";
                    } else {
                        echo "<span class='text-danger'>ยังไม่ชำระ / รอตรวจสอบ</span>";
                    }
                ?>
            </p>
            <p class="mb-2"><strong>ช่องทาง:</strong> <?php echo !empty($order['payment_method']) ? $order['payment_method'] : '-'; ?></p>
            <p class="fw-bold">*** ขอบคุณที่ใช้บริการครับ ***</p>
        </div>

        <div class="mt-4 text-center no-print">
            <button onclick="window.print()" class="btn btn-primary fw-bold w-100 mb-2">
                🖨️ สั่งพิมพ์ใบเสร็จ
            </button>
            <a href="my_orders.php" class="btn btn-outline-secondary w-100">
                ย้อนกลับ
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>