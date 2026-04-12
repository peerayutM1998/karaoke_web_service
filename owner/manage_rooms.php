<?php
session_start();
date_default_timezone_set('Asia/Bangkok');
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ระบบเพิ่มห้อง
if (isset($_POST['add_room'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $capacity = mysqli_real_escape_string($conn, $_POST['capacity']);
    $price = mysqli_real_escape_string($conn, $_POST['price_per_hour']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $sql = "INSERT INTO rooms (room_name, capacity, price_per_hour, status) VALUES ('$room_name', '$capacity', '$price', '$status')";
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "เพิ่มห้องคาราโอเกะสำเร็จ";
    header("location: manage_rooms.php");
    exit();
}

// ระบบลบห้อง
if (isset($_GET['delete_id'])) {
    $del_id = mysqli_real_escape_string($conn, $_GET['delete_id']);
    mysqli_query($conn, "DELETE FROM rooms WHERE room_id = $del_id");
    $_SESSION['success'] = "ลบข้อมูลห้องสำเร็จ";
    header("location: manage_rooms.php");
    exit();
}

// แก้ไข Query: ตัด b.status ออก เพื่อป้องกัน Error และเช็คจากเวลาแทน
$query = "SELECT r.*, 
          (SELECT COUNT(*) FROM bookings b 
           WHERE b.room_id = r.room_id 
           AND NOW() BETWEEN b.start_time AND b.end_time
           AND b.booking_status NOT IN ('cancelled', 'rejected')) as current_booking_count
          FROM rooms r";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการห้องคาราโอเกะ | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } 
        .badge { font-size: 0.9em; padding: 0.5em 0.8em; }
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
                <li class="nav-item">
                    <a class="nav-link" href="index.php">🏠 แดชบอร์ด</a>
                </li>

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
                        <li><a class="dropdown-item" href="view_orders.php">🍔 รายการสั่งอาหาร</a></li>
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
    <div class="container mt-4">
        <h3 class="mb-4">🎤 จัดการข้อมูลห้องคาราโอเกะ</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold">สร้างห้องใหม่</div>
                    <div class="card-body">
                        <form action="manage_rooms.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">ชื่อห้อง</label>
                                <input type="text" name="room_name" class="form-control" required placeholder="เช่น VIP-01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ความจุ (ท่าน)</label>
                                <input type="number" name="capacity" class="form-control" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ราคาต่อชั่วโมง (บาท)</label>
                                <input type="number" step="0.01" name="price_per_hour" class="form-control" required min="1">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">สถานะเริ่มต้น</label>
                                <select name="status" class="form-select">
                                    <option value="available">เปิดใช้งาน (Available)</option>
                                    <option value="maintenance">ซ่อมบำรุง (Maintenance)</option>
                                </select>
                            </div>
                            <button type="submit" name="add_room" class="btn btn-primary w-100">บันทึกห้อง</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="p-3">รหัสห้อง</th>
                                    <th>ชื่อห้อง</th>
                                    <th>ความจุ</th>
                                    <th>ราคา/ชม.</th>
                                    <th>สถานะจริง</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3 text-muted">#<?php echo $row['room_id']; ?></td>
                                    <td class="fw-bold"><?php echo $row['room_name']; ?></td>
                                    <td><?php echo $row['capacity']; ?> คน</td>
                                    <td class="text-success fw-bold">฿<?php echo number_format($row['price_per_hour'], 2); ?></td>
                                    <td>
                                        <?php 
                                        if ($row['status'] == 'maintenance') {
                                            echo '<span class="badge bg-secondary">🛠️ ปิดซ่อมบำรุง</span>';
                                        } elseif ($row['current_booking_count'] > 0) {
                                            echo '<span class="badge bg-danger">🔴 ไม่ว่าง (กำลังใช้งาน)</span>';
                                        } else {
                                            echo '<span class="badge bg-success">🟢 ว่าง/พร้อมใช้</span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_room.php?id=<?php echo $row['room_id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                        <a href="manage_rooms.php?delete_id=<?php echo $row['room_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบห้อง?');">ลบ</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>