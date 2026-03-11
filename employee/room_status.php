<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// กดเช็คเอาท์ให้ลูกค้า (เปลี่ยนสถานะเป็น completed)
if (isset($_GET['checkout_id'])) {
    $checkout_id = $_GET['checkout_id'];
    mysqli_query($conn, "UPDATE bookings SET booking_status = 'completed' WHERE booking_id = $checkout_id");
    $_SESSION['success'] = "ทำการเช็คเอาท์ลูกค้าเรียบร้อยแล้ว ห้องพร้อมให้บริการต่อ";
    header("location: room_status.php");
    exit();
}

$today = date('Y-m-d');
$curr_time = date('H:i:s');

// ดึงคิวที่ "กำลังใช้งานอยู่" หรือ "ถึงเวลาเข้าใช้แล้ว" ในวันนี้
$query = "SELECT b.*, u.first_name, r.room_name 
          FROM bookings b 
          JOIN users u ON b.customer_id = u.user_id 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE b.booking_date = '$today' AND b.booking_status = 'confirmed' AND b.start_time <= '$curr_time'
          ORDER BY b.end_time ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการห้อง/เช็คเอาท์ | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <div class="container mt-4">
        <h3 class="mb-4">🔑 จัดการสถานะห้องพัก (Check-out)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white fw-bold">
                            ห้อง: <?php echo $row['room_name']; ?> (กำลังใช้งาน)
                        </div>
                        <div class="card-body">
                            <p class="mb-1"><strong>ลูกค้า:</strong> <?php echo $row['first_name']; ?></p>
                            <p class="mb-1"><strong>หมดเวลา:</strong> <span class="text-danger fw-bold"><?php echo date('H:i', strtotime($row['end_time'])); ?></span></p>
                            <hr>
                            <a href="room_status.php?checkout_id=<?php echo $row['booking_id']; ?>" class="btn btn-warning w-100 fw-bold" onclick="return confirm('ลูกค้าชำระเงินครบถ้วนและต้องการเช็คเอาท์ออกจากห้องใช่หรือไม่?');">
                                🚪 เช็คเอาท์ / เคลียร์ห้อง
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center p-5 bg-white shadow-sm rounded">
                    <h5 class="text-muted">ขณะนี้ไม่มีห้องที่กำลังถูกใช้งาน</h5>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>