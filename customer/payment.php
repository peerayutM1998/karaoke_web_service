<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

// ดึงข้อมูลการจองเพื่อมาแสดงยอดเงินที่ต้องชำระ
$query_booking = "SELECT net_price FROM bookings WHERE booking_id = $booking_id AND customer_id = $user_id AND booking_status = 'pending'";
$result_booking = mysqli_query($conn, $query_booking);

if(mysqli_num_rows($result_booking) == 0) {
    $_SESSION['error'] = "ไม่พบข้อมูลการจองที่รอการชำระเงิน หรือการจองนี้ถูกยืนยันไปแล้ว";
    header("location: my_bookings.php");
    exit();
}

$booking_data = mysqli_fetch_assoc($result_booking);
$net_price = $booking_data['net_price'];

// ประมวลผลเมื่อกดปุ่มอัปโหลดสลิป
if (isset($_POST['submit_payment'])) {
    $amount_paid = mysqli_real_escape_string($conn, $_POST['amount_paid']);
    $payment_method = 'transfer'; // กำหนดเป็นโอนเงิน
    
    // จัดการอัปโหลดไฟล์รูปภาพ
    $target_dir = "../uploads/slips/";
    $file_extension = pathinfo($_FILES["slip_image"]["name"], PATHINFO_EXTENSION);
    $new_filename = "slip_" . $booking_id . "_" . time() . "." . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["slip_image"]["tmp_name"], $target_file)) {
        // บันทึกข้อมูลลงตาราง payments
        $sql = "INSERT INTO payments (booking_id, amount_paid, payment_method, slip_image, payment_status) 
                VALUES ($booking_id, '$amount_paid', '$payment_method', '$new_filename', 'pending')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "อัปโหลดสลิปสำเร็จ! กรุณารอพนักงานตรวจสอบเพื่อยืนยันการจอง";
            header("location: my_bookings.php");
            exit();
        } else {
            $error_msg = "เกิดข้อผิดพลาดในการบันทึกฐานข้อมูล: " . mysqli_error($conn);
        }
    } else {
        $error_msg = "ขออภัย เกิดข้อผิดพลาดในการอัปโหลดไฟล์รูปภาพของคุณ";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน | ลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>
    <div class="container mt-5">
        <div class="card shadow-sm border-0 mx-auto" style="max-width: 500px;">
            <div class="card-header bg-success text-white text-center py-3">
                <h5 class="mb-0 fw-bold">💳 ชำระเงินค่าจองห้องพัก</h5>
            </div>
            <div class="card-body p-4 text-center">
                
                <?php if(isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                <?php endif; ?>

                <p class="text-muted mb-1">รหัสการจอง: #<?php echo $booking_id; ?></p>
                <h2 class="fw-bold text-danger mb-4">ยอดชำระ: ฿<?php echo number_format($net_price, 2); ?></h2>

                <div class="p-3 border rounded mb-4 bg-light">
                    <p class="fw-bold mb-2">สแกน QR Code เพื่อโอนเงิน</p>
                    <img src="https://upload.wikimedia.org/wikipedia/commons/d/d0/QR_code_for_mobile_English_Wikipedia.svg" alt="PromptPay QR" width="150" class="mb-2">
                    <p class="mb-0 small text-muted">ชื่อบัญชี: ร้านคาราโอเกะออนไลน์ (ธนาคารทดสอบ)</p>
                    <p class="mb-0 small text-muted">เลขบัญชี: 123-4-56789-0</p>
                </div>

                <form action="payment.php?booking_id=<?php echo $booking_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3 text-start">
                        <label class="form-label fw-bold">ยอดเงินที่โอนจริง <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="amount_paid" class="form-control" value="<?php echo $net_price; ?>" required>
                    </div>
                    <div class="mb-4 text-start">
                        <label class="form-label fw-bold">อัปโหลดสลิปโอนเงิน <span class="text-danger">*</span></label>
                        <input class="form-control" type="file" name="slip_image" accept="image/*" required>
                    </div>
                    <button type="submit" name="submit_payment" class="btn btn-success w-100 py-2 fw-bold">แจ้งชำระเงิน</button>
                    <a href="my_bookings.php" class="btn btn-outline-secondary w-100 py-2 mt-2">ยกเลิก / กลับไปหน้าประวัติ</a>
                </form>

            </div>
        </div>
    </div>
</body>
</html>