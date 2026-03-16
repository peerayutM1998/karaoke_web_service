<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลการจองเดิมมาแสดง (ต้องเป็นของลูกค้านี้ และสถานะ pending เท่านั้น)
$query = "SELECT * FROM bookings WHERE booking_id = $booking_id AND customer_id = $user_id AND booking_status = 'pending'";
$result = mysqli_query($conn, $query);

if(mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "ไม่พบข้อมูลการจอง หรือรายการนี้ไม่สามารถแก้ไขได้แล้ว (อาจได้รับการยืนยันไปแล้ว)";
    header("location: my_bookings.php");
    exit();
}

$booking_data = mysqli_fetch_assoc($result);

// ดึงข้อมูลห้องและโปรโมชั่นสำหรับ Dropdown
$rooms = mysqli_query($conn, "SELECT * FROM rooms WHERE status = 'available'");
$promos = mysqli_query($conn, "SELECT * FROM promotions WHERE status = 'active' AND end_date >= CURDATE()");

// ประมวลผลเมื่อกดบันทึกการแก้ไข
if(isset($_POST['update_booking'])) {
    $new_room_id = $_POST['room_id'];
    $new_promo_id = !empty($_POST['promo_id']) ? $_POST['promo_id'] : 'NULL';
    $new_date = $_POST['booking_date'];
    $new_start = $_POST['start_time'];
    $new_end = $_POST['end_time'];

    // คำนวณชั่วโมงและราคาใหม่
    $start_dt = strtotime("$new_date $new_start");
    $end_dt = strtotime("$new_date $new_end");
    $total_hours = ceil(($end_dt - $start_dt) / 3600);

    if($total_hours <= 0) {
        $_SESSION['error'] = "เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น";
    } else {
        $room_query = mysqli_query($conn, "SELECT price_per_hour FROM rooms WHERE room_id = $new_room_id");
        $room_data = mysqli_fetch_assoc($room_query);
        $room_price = $room_data['price_per_hour'] * $total_hours;
        
        $net_price = $room_price;
        if($new_promo_id != 'NULL') {
            $promo_query = mysqli_query($conn, "SELECT discount_percent, discount_amount FROM promotions WHERE promo_id = $new_promo_id");
            $promo_data = mysqli_fetch_assoc($promo_query);
            if($promo_data['discount_percent'] > 0) {
                $net_price = $room_price - ($room_price * ($promo_data['discount_percent']/100));
            } else {
                $net_price = $room_price - $promo_data['discount_amount'];
            }
        }

        // อัปเดตข้อมูลลงฐานข้อมูล
        $sql_update = "UPDATE bookings SET 
                        room_id = '$new_room_id', 
                        promo_id = $new_promo_id, 
                        booking_date = '$new_date', 
                        start_time = '$new_start', 
                        end_time = '$new_end', 
                        total_hours = '$total_hours', 
                        room_price = '$room_price', 
                        net_price = '$net_price' 
                       WHERE booking_id = $booking_id";
        
        if(mysqli_query($conn, $sql_update)) {
            $_SESSION['success'] = "แก้ไขข้อมูลการจองเรียบร้อยแล้ว ยอดชำระใหม่คือ ฿" . number_format($net_price, 2);
            header("location: my_bookings.php");
            exit();
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขการจอง | ลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>
    
    <div class="container mt-5">
        <div class="card shadow-sm border-0 mx-auto" style="max-width: 700px;">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0 fw-bold">✏️ แก้ไขการจองห้องคาราโอเกะ (รหัส: #<?php echo $booking_id; ?>)</h5>
            </div>
            <div class="card-body p-4">

                <?php if(isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                <?php endif; ?>

                <form action="edit_booking.php?id=<?php echo $booking_id; ?>" method="POST">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold">เลือกห้องคาราโอเกะ <span class="text-danger">*</span></label>
                        <select name="room_id" class="form-select" required>
                            <?php while($r = mysqli_fetch_assoc($rooms)): ?>
                                <option value="<?php echo $r['room_id']; ?>" <?php echo ($booking_data['room_id'] == $r['room_id']) ? 'selected' : ''; ?>>
                                    <?php echo $r['room_name']; ?> - <?php echo $r['price_per_hour']; ?> บ./ชม.
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">วันที่จอง <span class="text-danger">*</span></label>
                            <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo $booking_data['booking_date']; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">เวลาเริ่ม <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control" required value="<?php echo date('H:i', strtotime($booking_data['start_time'])); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control" required value="<?php echo date('H:i', strtotime($booking_data['end_time'])); ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">เลือกโปรโมชั่น (ถ้ามี)</label>
                        <select name="promo_id" class="form-select">
                            <option value="">-- ไม่ใช้โปรโมชั่น --</option>
                            <?php while($p = mysqli_fetch_assoc($promos)): ?>
                                <option value="<?php echo $p['promo_id']; ?>" <?php echo ($booking_data['promo_id'] == $p['promo_id']) ? 'selected' : ''; ?>>
                                    <?php echo $p['promo_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="my_bookings.php" class="btn btn-secondary px-4">ยกเลิก/กลับ</a>
                        <button type="submit" name="update_booking" class="btn btn-success px-4 fw-bold">บันทึกการแก้ไข</button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</body>
</html>