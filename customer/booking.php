<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

// 🌟 ดึงข้อมูลห้อง "ทั้งหมด" เพื่อมาแสดงสถานะใน Dropdown
$rooms_all = mysqli_query($conn, "SELECT * FROM rooms");

// ดึงข้อมูลโปรโมชั่นที่ใช้งานได้มาแสดงใน Dropdown
$promos = mysqli_query($conn, "SELECT * FROM promotions WHERE status = 'active' AND end_date >= CURDATE()");

// รับค่าจาก URL กรณีคลิกจองมาจากหน้า index
$selected_room = isset($_GET['room_id']) ? $_GET['room_id'] : '';

// ประมวลผลการจอง
if(isset($_POST['submit_booking'])) {
    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_id'];
    $promo_id = !empty($_POST['promo_id']) ? $_POST['promo_id'] : 'NULL';
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // คำนวณจำนวนชั่วโมง (แบบง่ายปัดเศษขึ้น)
    $start_dt = strtotime("$booking_date $start_time");
    $end_dt = strtotime("$booking_date $end_time");
    $total_hours = ceil(($end_dt - $start_dt) / 3600);

    if($total_hours <= 0) {
        $_SESSION['error'] = "เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น";
        header("location: booking.php");
        exit();
    } else {
        // ==========================================
        // 🌟 ระบบเช็คการจองซ้ำ (Overlap Check)
        // ==========================================
        $check_sql = "SELECT * FROM bookings 
                      WHERE room_id = '$room_id' 
                      AND booking_date = '$booking_date' 
                      AND booking_status NOT IN ('cancelled', 'rejected') 
                      AND (start_time < '$end_time' AND end_time > '$start_time')";
        
        $check_query = mysqli_query($conn, $check_sql);

        // ถ้าเวลาทับกัน
        if (mysqli_num_rows($check_query) > 0) {
            $_SESSION['error'] = "ขออภัยครับ ห้องนี้มีคิวจองในช่วงเวลาดังกล่าวแล้ว กรุณาเลือกเวลาอื่น";
            header("location: booking.php");
            exit();
        } 
        else {
            // ดึงราคาห้อง
            $room_query = mysqli_query($conn, "SELECT price_per_hour FROM rooms WHERE room_id = $room_id");
            $room_data = mysqli_fetch_assoc($room_query);
            $room_price = $room_data['price_per_hour'] * $total_hours;
            
            // คำนวณส่วนลดเบื้องต้น
            $net_price = $room_price;
            if($promo_id != 'NULL') {
                $promo_query = mysqli_query($conn, "SELECT discount_percent, discount_amount FROM promotions WHERE promo_id = $promo_id");
                $promo_data = mysqli_fetch_assoc($promo_query);
                if($promo_data['discount_percent'] > 0) {
                    $net_price = $room_price - ($room_price * ($promo_data['discount_percent']/100));
                } else {
                    $net_price = $room_price - $promo_data['discount_amount'];
                }
            }

            // บันทึกลงฐานข้อมูล
            $sql = "INSERT INTO bookings (customer_id, room_id, promo_id, booking_date, start_time, end_time, total_hours, room_price, net_price, booking_status) 
                    VALUES ('$user_id', '$room_id', $promo_id, '$booking_date', '$start_time', '$end_time', '$total_hours', '$room_price', '$net_price', 'pending')";
            
            if(mysqli_query($conn, $sql)) {
                $_SESSION['success'] = "บันทึกการจองสำเร็จ! กรุณารอพนักงานตรวจสอบและอนุมัติคิว";
                header("location: my_bookings.php");
                exit();
            } else {
                $_SESSION['error'] = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
                header("location: booking.php");
                exit();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จองห้อง | ลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } 
        .status-dot { height: 10px; width: 10px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        .bg-available { background-color: #28a745; } /* เขียว */
        .bg-occupied { background-color: #dc3545; }  /* แดง */
        .bg-maintenance { background-color: #6c757d; } /* เทา */
    </style>
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
                    <li class="nav-item"><a class="nav-link active" href="booking.php">จองห้องพัก</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
                    <li class="nav-item"><a class="nav-link" href="order_food.php">สั่งอาหาร</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_orders.php">ประวัติสั่งอาหาร</a></li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <span class="me-3">👤 สวัสดี, คุณ <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row g-4">
            
            <div class="col-md-7">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white pb-0 border-0">
                        <h4 class="mb-0 fw-bold mt-2">📝 แบบฟอร์มระบุเวลาจองห้อง</h4>
                        <hr>
                    </div>
                    <div class="card-body p-4 pt-2">

                        <?php if(isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>

                        <form action="booking.php" method="POST">
                            
                            <div class="mb-4">
                                <label class="form-label fw-bold">เลือกห้องคาราโอเกะ <span class="text-danger">*</span></label>
                                <select name="room_id" class="form-select border-primary" required>
                                    <option value="">-- กรุณาเลือกห้อง --</option>
                                    <?php 
                                    mysqli_data_seek($rooms_all, 0); 
                                    while($r = mysqli_fetch_assoc($rooms_all)): 
                                        
                                        // 🌟 ล็อคแค่ห้องที่ซ่อมบำรุง ห้องอื่นให้กดจองล่วงหน้าได้หมด
                                        if ($r['status'] == 'maintenance') {
                                            $status_text = "🛠️ (ปิดซ่อมบำรุง)";
                                            $is_disabled = "disabled"; 
                                        } else {
                                            $current = ($r['status'] == 'available') ? "🟢 ว่างตอนนี้" : "🔴 มีคนใช้งานอยู่ตอนนี้";
                                            $status_text = "[$current]";
                                            $is_disabled = ""; 
                                        }
                                    ?>
                                        <option value="<?php echo $r['room_id']; ?>" 
                                            <?php echo ($selected_room == $r['room_id']) ? 'selected' : ''; ?>
                                            <?php echo $is_disabled; ?>>
                                            <?php echo $r['room_name']; ?> <?php echo $status_text; ?> 
                                            (จุได้ <?php echo $r['capacity']; ?> คน) - <?php echo $r['price_per_hour']; ?> บ./ชม.
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted mt-2 d-block">
                                    💡 <strong>คำแนะนำ:</strong> คุณสามารถจองห้องที่ขึ้นสถานะ "🔴 มีคนใช้งานอยู่ตอนนี้" ล่วงหน้าได้ หากเวลาที่คุณเลือกไม่ชนกับคิวของลูกค้าท่านอื่น
                                </small>
                            </div>

                            <div class="row mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">วันที่จอง <span class="text-danger">*</span></label>
                                    <input type="date" name="booking_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">เวลาเริ่ม <span class="text-danger">*</span></label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">เวลาสิ้นสุด <span class="text-danger">*</span></label>
                                    <input type="time" name="end_time" class="form-control" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">เลือกโปรโมชั่น (ถ้ามี)</label>
                                <select name="promo_id" class="form-select">
                                    <option value="">-- ไม่ใช้โปรโมชั่น --</option>
                                    <?php while($p = mysqli_fetch_assoc($promos)): ?>
                                        <option value="<?php echo $p['promo_id']; ?>">
                                            <?php echo $p['promo_name']; ?> 
                                            (ลด <?php echo ($p['discount_percent'] > 0) ? $p['discount_percent'].'%' : $p['discount_amount'].' บาท'; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <button type="submit" name="submit_booking" class="btn btn-primary w-100 py-3 fs-5 fw-bold shadow-sm">ยืนยันเวลาจอง</button>
                        </form>

                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-dark text-white fw-bold">
                        📊 สรุปสถานะห้อง ณ เวลานี้
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php 
                            mysqli_data_seek($rooms_all, 0);
                            while($r = mysqli_fetch_assoc($rooms_all)): 
                                if($r['status'] == 'available') {
                                    $status_class = 'bg-available';
                                    $status_label = 'ว่างพร้อมใช้';
                                } elseif($r['status'] == 'maintenance') {
                                    $status_class = 'bg-maintenance';
                                    $status_label = 'ปิดซ่อมบำรุง';
                                } else {
                                    $status_class = 'bg-occupied';
                                    $status_label = 'กำลังใช้งาน';
                                }
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h6 class="mb-0 fw-bold"><?php echo $r['room_name']; ?></h6>
                                    <small class="text-muted">ความจุ <?php echo $r['capacity']; ?> คน</small>
                                </div>
                                <span>
                                    <span class="status-dot <?php echo $status_class; ?>"></span>
                                    <span class="fw-bold <?php echo ($r['status']=='available') ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $status_label; ?>
                                    </span>
                                </span>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>