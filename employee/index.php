<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// ดึงข้อมูลห้องพักทั้งหมด และเช็คว่ามีการจองที่ "กำลังใช้งานอยู่" (สมมติว่าเป็นช่วงเวลาปัจจุบันและสถานะ confirmed) หรือไม่
$query_rooms = "SELECT * FROM rooms";
$result_rooms = mysqli_query($conn, $query_rooms);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หน้าหลัก | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
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
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4 fw-bold">📺 สถานะห้องคาราโอเกะ (Live Status)</h3>
        
        <div class="row g-4">
            <?php while($room = mysqli_fetch_assoc($result_rooms)): 
                // เช็คว่าห้องนี้มีลูกค้ากำลังใช้งานอยู่หรือไม่ (มีคิว confirmed ในเวลานี้)
                $r_id = $room['room_id'];
                $curr_time = date('H:i:s');
                $curr_date = date('Y-m-d');
                $check_use = mysqli_query($conn, "SELECT * FROM bookings WHERE room_id = $r_id AND booking_date = '$curr_date' AND start_time <= '$curr_time' AND end_time >= '$curr_time' AND booking_status = 'confirmed'");
                
                $is_in_use = (mysqli_num_rows($check_use) > 0);
                
                // กำหนดสีการ์ดตามสถานะ
                if ($room['status'] == 'maintenance') {
                    $card_bg = 'bg-secondary'; $status_text = 'ปิดซ่อมบำรุง'; $icon = '🛠️';
                } elseif ($is_in_use) {
                    $card_bg = 'bg-danger'; $status_text = 'มีลูกค้าใช้งาน'; $icon = '🎤';
                } else {
                    $card_bg = 'bg-success'; $status_text = 'ว่าง'; $icon = '✅';
                }
            ?>
            <div class="col-md-3">
                <div class="card <?php echo $card_bg; ?> text-white shadow h-100 border-0 text-center">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <h1 class="display-4 mb-0"><?php echo $icon; ?></h1>
                        <h4 class="fw-bold mt-2"><?php echo $room['room_name']; ?></h4>
                        <p class="mb-0 opacity-75">สถานะ: <?php echo $status_text; ?></p>
                        <small class="mt-2">(จุได้ <?php echo $room['capacity']; ?> คน)</small>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>