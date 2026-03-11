<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

$today = date('Y-m-d');

// ดึงคิวจองเฉพาะของ "วันนี้" ที่สถานะเป็น confirmed (อนุมัติแล้ว)
$query = "SELECT b.*, u.first_name, u.phone, r.room_name 
          FROM bookings b 
          JOIN users u ON b.customer_id = u.user_id 
          JOIN rooms r ON b.room_id = r.room_id 
          WHERE b.booking_date = '$today' AND b.booking_status = 'confirmed'
          ORDER BY b.start_time ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>คิวจองวันนี้ | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <div class="container mt-4">
        <h3 class="mb-4">📋 คิวจองห้องคาราโอเกะ (ประจำวันที่ <?php echo date('d/m/Y'); ?>)</h3>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">เวลา</th>
                            <th>ห้อง</th>
                            <th>ชื่อลูกค้า</th>
                            <th>เบอร์โทร</th>
                            <th>ระยะเวลา (ชม.)</th>
                            <th class="text-center">สถานะการเตรียมห้อง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td class="p-3 fw-bold text-danger">
                                    <?php echo date('H:i', strtotime($row['start_time'])) . ' - ' . date('H:i', strtotime($row['end_time'])); ?>
                                </td>
                                <td class="fw-bold text-primary fs-5"><?php echo $row['room_name']; ?></td>
                                <td><?php echo $row['first_name']; ?></td>
                                <td><?php echo $row['phone']; ?></td>
                                <td><?php echo $row['total_hours']; ?> ชม.</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-success">เตรียมห้องพร้อมแล้ว</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center p-5 text-muted">ไม่มีคิวการจองสำหรับวันนี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>