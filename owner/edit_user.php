<?php
session_start();
require_once "../config/db_connect.php";

// เช็คสิทธิ์ว่าต้องเป็นเจ้าของร้านเท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// รับค่า ID ของลูกค้าที่ต้องการแก้ไข
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลลูกค้าคนนั้นมาแสดง (เช็คด้วยว่า role ต้องเป็น customer)
$result = mysqli_query($conn, "SELECT * FROM users WHERE user_id = $user_id AND role = 'customer'");
if(mysqli_num_rows($result) == 0) {
    // ถ้าไม่พบข้อมูล หรือไม่ใช่ลูกค้า ให้เด้งกลับ
    header("location: manage_users.php");
    exit();
}
$row = mysqli_fetch_assoc($result);

// ประมวลผลเมื่อกดปุ่ม "บันทึกการแก้ไข"
if (isset($_POST['update_user'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $sql = "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' WHERE user_id=$user_id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "แก้ไขข้อมูลลูกค้าเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการแก้ไขข้อมูล: " . mysqli_error($conn);
    }
    header("location: manage_users.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลลูกค้า | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
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
    <div class="container mt-5">
        <div class="card shadow-sm mx-auto" style="max-width: 500px;">
            <div class="card-header bg-primary text-white fw-bold">
                ✏️ แก้ไขข้อมูลลูกค้า: <?php echo $row['username']; ?>
            </div>
            <div class="card-body">
                <form action="edit_user.php?id=<?php echo $user_id; ?>" method="POST">
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small">ชื่อ</label>
                            <input type="text" name="first_name" class="form-control" value="<?php echo $row['first_name']; ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label small">นามสกุล</label>
                            <input type="text" name="last_name" class="form-control" value="<?php echo $row['last_name']; ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>">
                    </div>

                    <div class="mb-4">
                        <label class="form-label small">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $row['phone']; ?>" required>
                    </div>

                    <button type="submit" name="update_user" class="btn btn-primary w-100 fw-bold">บันทึกข้อมูล</button>
                    <a href="manage_users.php" class="btn btn-secondary w-100 mt-2">ยกเลิก / กลับ</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>