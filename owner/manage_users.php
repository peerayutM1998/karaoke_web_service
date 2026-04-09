<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ระบบลบข้อมูลลูกค้า
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id = $del_id AND role = 'customer'");
    $_SESSION['success'] = "ลบข้อมูลลูกค้าเรียบร้อยแล้ว";
    header("location: manage_users.php");
    exit();
}

$query = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการลูกค้า | เจ้าของร้าน</title>
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
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0">👥 จัดการข้อมูลลูกค้า</h3>
            </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="p-3">ID</th>
                            <th>Username</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>วันที่สมัคร</th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="p-3"><?php echo $row['user_id']; ?></td>
                            <td><?php echo $row['username']; ?></td>
                            <td><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['created_at'])); ?></td>
                            <td class="text-center">
                                <a href="edit_user.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                <a href="manage_users.php?delete_id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบลูกค้าคนนี้? (การจองที่ผูกไว้จะหายไปด้วย)');">ลบ</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>