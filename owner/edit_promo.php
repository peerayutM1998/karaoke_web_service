<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$promo_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = mysqli_query($conn, "SELECT * FROM promotions WHERE promo_id = $promo_id");
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update_promo'])) {
    $promo_name = mysqli_real_escape_string($conn, $_POST['promo_name']);
    $discount_percent = !empty($_POST['discount_percent']) ? $_POST['discount_percent'] : 0;
    $discount_amount = !empty($_POST['discount_amount']) ? $_POST['discount_amount'] : 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    $sql = "UPDATE promotions SET promo_name='$promo_name', discount_percent='$discount_percent', discount_amount='$discount_amount', start_date='$start_date', end_date='$end_date', status='$status' WHERE promo_id=$promo_id";
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "แก้ไขโปรโมชั่นสำเร็จ";
    header("location: manage_promotions.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขโปรโมชั่น | เจ้าของร้าน</title>
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
            <div class="card-header bg-warning text-dark fw-bold">แก้ไขโปรโมชั่น: <?php echo $row['promo_name']; ?></div>
            <div class="card-body">
                <form action="edit_promo.php?id=<?php echo $promo_id; ?>" method="POST">
                    <div class="mb-3"><label>ชื่อโปรโมชั่น</label><input type="text" name="promo_name" class="form-control" value="<?php echo $row['promo_name']; ?>" required></div>
                    <div class="row mb-3">
                        <div class="col"><label>ส่วนลด (%)</label><input type="number" name="discount_percent" class="form-control" value="<?php echo $row['discount_percent']; ?>"></div>
                        <div class="col"><label>ส่วนลด (บาท)</label><input type="number" name="discount_amount" class="form-control" value="<?php echo $row['discount_amount']; ?>"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col"><label>วันที่เริ่ม</label><input type="date" name="start_date" class="form-control" value="<?php echo $row['start_date']; ?>" required></div>
                        <div class="col"><label>วันสิ้นสุด</label><input type="date" name="end_date" class="form-control" value="<?php echo $row['end_date']; ?>" required></div>
                    </div>
                    <div class="mb-4">
                        <label>สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="active" <?php echo ($row['status']=='active')?'selected':''; ?>>ใช้งานปกติ</option>
                            <option value="inactive" <?php echo ($row['status']=='inactive')?'selected':''; ?>>ระงับชั่วคราว</option>
                        </select>
                    </div>
                    <button type="submit" name="update_promo" class="btn btn-warning w-100 fw-bold">บันทึกการแก้ไข</button>
                    <a href="manage_promotions.php" class="btn btn-secondary w-100 mt-2">ยกเลิก</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>