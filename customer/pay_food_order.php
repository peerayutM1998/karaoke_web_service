<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

// ตรวจสอบว่ามีการส่ง order_id มาหรือไม่
if (!isset($_GET['order_id'])) {
    header("location: order_food.php");
    exit();
}

$order_id = $_GET['order_id'];

// ดึงข้อมูลบิลเพื่อเอายอดรวมมาแสดง
$query_order = "SELECT * FROM orders WHERE order_id = $order_id";
$result_order = mysqli_query($conn, $query_order);
$order_data = mysqli_fetch_assoc($result_order);

// ประมวลผลเมื่อลูกค้ากดยืนยันการชำระเงิน
if (isset($_POST['submit_payment'])) {
    $payment_method = $_POST['payment_method'];
    
    // ตั้งสถานะเริ่มต้น
    $status = ($payment_method == 'เงินสด') ? 'รอชำระเงินสด' : 'รอตรวจสอบสลิป';

// ถ้าลูกค้าเลือกโอนเงิน จัดการอัปโหลดสลิป
    $slip_name_db = NULL; // ตั้งค่าเริ่มต้นให้ไม่มีรูป

    if ($payment_method == 'โอนเงิน' && isset($_FILES['slip_image']['name']) && $_FILES['slip_image']['name'] != '') {
        $target_dir = "../uploads/slips/"; 
        $slip_name_db = time() . "_" . basename($_FILES["slip_image"]["name"]); // ชื่อไฟล์ที่จะบันทึกลง DB
        $target_file = $target_dir . $slip_name_db;
        
        move_uploaded_file($_FILES["slip_image"]["tmp_name"], $target_file);
    }

    // อัปเดตข้อมูลการชำระเงินและชื่อรูปสลิปลงฐานข้อมูล
    $sql_update = "UPDATE orders SET 
                   payment_method = '$payment_method', 
                   order_status = '$status',
                   payment_time = NOW(),
                   slip_image = '$slip_name_db' 
                   WHERE order_id = $order_id";
    
    if (mysqli_query($conn, $sql_update)) {
        $_SESSION['success'] = "ชำระเงินสำเร็จ! ออเดอร์ของคุณถูกส่งไปยังห้องครัวแล้ว";
        // จ่ายเสร็จให้เด้งไปหน้าประวัติการสั่งอาหาร
        header("location: my_orders.php"); 
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงินค่าอาหาร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-success text-white text-center">
                        <h4 class="mb-0">💳 ชำระเงินค่าอาหาร</h4>
                    </div>
                    <div class="card-body p-4">
                        
                        <div class="text-center mb-4">
                            <p class="text-muted mb-1">หมายเลขออเดอร์: #<?php echo $order_id; ?></p>
                            <h2 class="text-danger fw-bold">ยอดชำระ: ฿<?php echo number_format($order_data['total_price'], 2); ?></h2>
                        </div>

                        <form action="" method="POST" enctype="multipart/form-data">
                            <h5 class="fw-bold mb-3">เลือกวิธีการชำระเงิน:</h5>
                            
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payCash" value="เงินสด" checked onclick="toggleSlip(false)">
                                <label class="form-check-label fs-5" for="payCash">
                                    💵 ชำระด้วยเงินสด (พนักงานจะไปเก็บเงินที่ห้อง)
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="payTransfer" value="โอนเงิน" onclick="toggleSlip(true)">
                                <label class="form-check-label fs-5" for="payTransfer">
                                    📱 โอนเงินผ่าน QR Code
                                </label>
                            </div>

                            <div id="slipUploadDiv" class="border rounded p-3 bg-light text-center" style="display: none;">
                                <p class="mb-2 fw-bold text-primary">สแกนเพื่อชำระเงิน</p>
                                <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="QR Code" width="150" class="mb-3">
                                <p>ธนาคารกสิกรไทย: 123-4-56789-0<br>ชื่อบัญชี: ร้านคาราโอเกะออนไลน์</p>
                                
                                <div class="mt-3 text-start">
                                    <label class="form-label fw-bold text-danger">* กรุณาแนบหลักฐานการโอนเงิน (สลิป)</label>
                                    <input type="file" name="slip_image" id="slip_image" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <hr class="my-4">
                            <div class="d-grid gap-2">
                                <button type="submit" name="submit_payment" class="btn btn-primary btn-lg fw-bold">ยืนยันการชำระเงิน</button>
                                <a href="order_food.php" class="btn btn-outline-secondary">กลับไปแก้ไขรายการอาหาร</a>
                            </div>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSlip(show) {
            var slipDiv = document.getElementById('slipUploadDiv');
            var slipInput = document.getElementById('slip_image');
            if (show) {
                slipDiv.style.display = 'block';
                slipInput.required = true; // บังคับให้อัปโหลดสลิปถ้าเลือกโอนเงิน
            } else {
                slipDiv.style.display = 'none';
                slipInput.required = false;
            }
        }
    </script>
</body>
</html>