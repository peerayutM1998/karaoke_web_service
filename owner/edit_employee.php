<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$emp_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $emp_id AND role = 'employee'");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update_employee'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $sql = "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' WHERE user_id=$emp_id";
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "แก้ไขข้อมูลพนักงานสำเร็จ";
    header("location: manage_employees.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขพนักงาน | เจ้าของร้าน</title>
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
            <div class="card-header bg-dark text-white fw-bold">แก้ไขข้อมูลพนักงาน: <?php echo $row['username']; ?></div>
            <div class="card-body">
                <form action="edit_employee.php?id=<?php echo $emp_id; ?>" method="POST">
                    <div class="row mb-3">
                        <div class="col"><label>ชื่อ</label><input type="text" name="first_name" class="form-control" value="<?php echo $row['first_name']; ?>" required></div>
                        <div class="col"><label>นามสกุล</label><input type="text" name="last_name" class="form-control" value="<?php echo $row['last_name']; ?>" required></div>
                    </div>
                    <div class="mb-3"><label>อีเมล</label><input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>"></div>
                    <div class="mb-4"><label>เบอร์โทรศัพท์</label><input type="text" name="phone" class="form-control" value="<?php echo $row['phone']; ?>" required></div>
                    <button type="submit" name="update_employee" class="btn btn-primary w-100">บันทึกข้อมูล</button>
                    <a href="manage_employees.php" class="btn btn-secondary w-100 mt-2">ยกเลิก</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>