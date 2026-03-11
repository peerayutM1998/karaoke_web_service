<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ประมวลผลการยกเลิกการจอง
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    // เช็คก่อนว่าสถานะเป็น pending จริงๆ ถึงจะให้ยกเลิกได้ เพื่อความปลอดภัย
    $check_sql = "SELECT booking_status FROM bookings WHERE booking_id = $cancel_id AND customer_id = $user_id";
    $check_res = mysqli_query($conn, $check_sql);
    $row = mysqli_fetch_assoc($check_res);

    if ($row && $row['booking_status'] == 'pending') {
        $cancel_sql = "UPDATE bookings SET booking_status = 'cancelled' WHERE booking_id = $cancel_id";
        mysqli_query($conn, $cancel_sql);
        $_SESSION['success'] = "ยกเลิกการจองสำเร็จ";
    } else {
        $_SESSION['error'] = "ไม่สามารถยกเลิกได้ เนื่องจากรายการนี้ได้รับการยืนยันแล้ว";
    }
    header("location: my_bookings.php");
    exit();
}

// ดึงข้อมูลประวัติการจองทั้งหมด
$query = "SELECT b.*, r.room_name 
          FROM bookings b 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE b.customer_id = $user_id 
          ORDER BY b.booking_date DESC, b.start_time DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ประวัติการจอง | ลูกค้า</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Karaoke Customer</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">หน้าหลักโปรไฟล์</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">จองห้องพัก</a></li>
                    <li class="nav-item"><a class="nav-link active" href="my_bookings.php">ประวัติการจอง</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <h3 class="mb-4">ประวัติการจองห้องคาราโอเกะของคุณ</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="p-3">รหัสจอง</th>
                                <th>ห้อง</th>
                                <th>วันที่-เวลา</th>
                                <th>ชั่วโมงรวม</th>
                                <th>ยอดสุทธิ</th>
                                <th>สถานะ</th>
                                <th class="text-center p-3">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3">#<?php echo $row['booking_id']; ?></td>
                                    <td class="fw-bold text-primary"><?php echo $row['room_name']; ?></td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($row['booking_date'])); ?><br>
                                        <small class="text-muted"><?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?></small>
                                    </td>
                                    <td><?php echo $row['total_hours']; ?> ชม.</td>
                                    <td class="fw-bold text-success">฿<?php echo number_format($row['net_price'], 2); ?></td>
                                    <td>
                                        <?php 
                                            if($row['booking_status'] == 'pending') echo '<span class="badge bg-warning text-dark">รอตรวจสอบ</span>';
                                            elseif($row['booking_status'] == 'confirmed') echo '<span class="badge bg-success">ยืนยันแล้ว</span>';
                                            elseif($row['booking_status'] == 'completed') echo '<span class="badge bg-secondary">ใช้บริการแล้ว</span>';
                                            else echo '<span class="badge bg-danger">ยกเลิกแล้ว</span>';
                                        ?>
                                    </td>
                                    <td class="text-center p-3">
                                        <?php if($row['booking_status'] == 'pending'): ?>
                                            <a href="my_bookings.php?cancel_id=<?php echo $row['booking_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจองนี้?');">ยกเลิก</a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center p-4 text-muted">ยังไม่มีประวัติการจอง</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>