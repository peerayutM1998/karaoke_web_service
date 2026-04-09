<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$menu_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result = mysqli_query($conn, "SELECT * FROM menus WHERE menu_id = $menu_id");
if(mysqli_num_rows($result) == 0) {
    header("location: manage_menus.php");
    exit();
}
$row = mysqli_fetch_assoc($result);

if (isset($_POST['update_menu'])) {
    $menu_name = mysqli_real_escape_string($conn, $_POST['menu_name']);
    $price = $_POST['price'];
    $category = $_POST['category'];
    $status = $_POST['status'];
    
    // โค้ดอัปเดตพื้นฐาน
    $sql_update = "UPDATE menus SET menu_name='$menu_name', price='$price', category='$category', status='$status' WHERE menu_id=$menu_id";
    
    // เช็คว่ามีการอัปโหลดรูปภาพใหม่มาด้วยหรือไม่
    if (isset($_FILES['menu_image']['name']) && $_FILES['menu_image']['name'] != '') {
        $target_dir = "../uploads/menus/";
        $file_extension = pathinfo($_FILES["menu_image"]["name"], PATHINFO_EXTENSION);
        $new_image = "menu_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_image;
        
        if(move_uploaded_file($_FILES["menu_image"]["tmp_name"], $target_file)) {
            // อัปเดตรูปด้วย
            $sql_update = "UPDATE menus SET menu_name='$menu_name', price='$price', category='$category', status='$status', menu_image='$new_image' WHERE menu_id=$menu_id";
        }
    }

    if(mysqli_query($conn, $sql_update)) {
        $_SESSION['success'] = "แก้ไขข้อมูลเมนูสำเร็จ";
    }
    header("location: manage_menus.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขเมนู | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
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
        <div class="card shadow-sm border-0">
            <div class="card-header bg-warning text-dark fw-bold">✏️ แก้ไขเมนู: <?php echo $row['menu_name']; ?></div>
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="../uploads/menus/<?php echo $row['menu_image']; ?>" width="120" height="120" class="rounded object-fit-cover shadow-sm" onerror="this.src='https://via.placeholder.com/120'">
                </div>

                <form action="edit_menu.php?id=<?php echo $menu_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label small">ชื่อเมนู</label>
                        <input type="text" name="menu_name" class="form-control" value="<?php echo $row['menu_name']; ?>" required>
                    </div>
                    <div class="row mb-3">
                        <div class="col">
                            <label class="form-label small">ราคา (บาท)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $row['price']; ?>" required>
                        </div>
                        <div class="col">
                            <label class="form-label small">หมวดหมู่</label>
                            <select name="category" class="form-select" required>
                                <option value="food" <?php echo ($row['category']=='food')?'selected':''; ?>>อาหารหลัก</option>
                                <option value="snack" <?php echo ($row['category']=='snack')?'selected':''; ?>>ของทานเล่น</option>
                                <option value="drink" <?php echo ($row['category']=='drink')?'selected':''; ?>>เครื่องดื่ม</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">สถานะ</label>
                        <select name="status" class="form-select" required>
                            <option value="available" <?php echo ($row['status']=='available')?'selected':''; ?>>พร้อมขาย</option>
                            <option value="out_of_stock" <?php echo ($row['status']=='out_of_stock')?'selected':''; ?>>สินค้าหมด</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small">เปลี่ยนรูปภาพใหม่ (เว้นว่างไว้ถ้าใช้รูปเดิม)</label>
                        <input class="form-control" type="file" name="menu_image" accept="image/*">
                    </div>
                    <button type="submit" name="update_menu" class="btn btn-warning w-100 fw-bold">บันทึกการแก้ไข</button>
                    <a href="manage_menus.php" class="btn btn-secondary w-100 mt-2">ยกเลิก / กลับ</a>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>