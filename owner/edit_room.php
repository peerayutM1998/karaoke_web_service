<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$room_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = mysqli_query($conn, "SELECT * FROM rooms WHERE room_id = $room_id");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update_room'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $capacity = $_POST['capacity'];
    $price = $_POST['price_per_hour'];
    $status = $_POST['status'];

    $sql = "UPDATE rooms SET room_name='$room_name', capacity='$capacity', price_per_hour='$price', status='$status' WHERE room_id=$room_id";
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "แก้ไขข้อมูลห้องสำเร็จ";
    header("location: manage_rooms.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขห้อง | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
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
    <div class="container mt-5" style="max-width: 500px;">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white fw-bold">แก้ไขข้อมูลห้อง: <?php echo $row['room_name']; ?></div>
            <div class="card-body">
                <form action="edit_room.php?id=<?php echo $room_id; ?>" method="POST">
                    <div class="mb-3"><label>ชื่อห้อง</label><input type="text" name="room_name" class="form-control" value="<?php echo $row['room_name']; ?>" required></div>
                    <div class="mb-3"><label>ความจุ (ท่าน)</label><input type="number" name="capacity" class="form-control" value="<?php echo $row['capacity']; ?>" required></div>
                    <div class="mb-3"><label>ราคา/ชม.</label><input type="number" name="price_per_hour" class="form-control" value="<?php echo $row['price_per_hour']; ?>" required></div>
                    <div class="mb-4">
                        <label>สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="available" <?php echo ($row['status']=='available')?'selected':''; ?>>ว่าง/พร้อมใช้</option>
                            <option value="maintenance" <?php echo ($row['status']=='maintenance')?'selected':''; ?>>ซ่อมบำรุง</option>
                        </select>
                    </div>
                    <button type="submit" name="update_room" class="btn btn-primary w-100">บันทึกการแก้ไข</button>
                    <a href="manage_rooms.php" class="btn btn-secondary w-100 mt-2">ยกเลิก</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>