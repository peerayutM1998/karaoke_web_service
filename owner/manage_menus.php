<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// 1. ระบบเพิ่มเมนูใหม่
if (isset($_POST['add_menu'])) {
    $menu_name = mysqli_real_escape_string($conn, $_POST['menu_name']);
    $price = $_POST['price'];
    $category = $_POST['category'];
    $status = $_POST['status'];
    
    // 🌟 รับค่าจำนวนคงเหลือ (ถ้าไม่ใช่เครื่องดื่ม หรือไม่ได้กรอก จะให้เป็น 0)
    $stock_qty = (isset($_POST['stock_qty']) && $_POST['stock_qty'] != '') ? intval($_POST['stock_qty']) : 0;
    
    // จัดการอัปโหลดรูปภาพ
    $menu_image = 'default_menu.jpg'; // ค่าเริ่มต้นถ้ารูปไม่มา
    if (isset($_FILES['menu_image']['name']) && $_FILES['menu_image']['name'] != '') {
        $target_dir = "../uploads/menus/";
        // ดึงนามสกุลไฟล์ และตั้งชื่อใหม่ป้องกันชื่อซ้ำ
        $file_extension = pathinfo($_FILES["menu_image"]["name"], PATHINFO_EXTENSION);
        $menu_image = "menu_" . time() . "." . $file_extension;
        $target_file = $target_dir . $menu_image;
        
        move_uploaded_file($_FILES["menu_image"]["tmp_name"], $target_file);
    }

    // 🌟 เพิ่ม stock_qty ลงในคำสั่ง INSERT
    $sql = "INSERT INTO menus (menu_name, price, category, menu_image, status, stock_qty) 
            VALUES ('$menu_name', '$price', '$category', '$menu_image', '$status', '$stock_qty')";
    
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "เพิ่มเมนูอาหารใหม่สำเร็จ";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
    header("location: manage_menus.php");
    exit();
}

// 2. ระบบลบเมนู
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM menus WHERE menu_id = $del_id");
    $_SESSION['success'] = "ลบเมนูเรียบร้อยแล้ว";
    header("location: manage_menus.php");
    exit();
}

// ดึงข้อมูลเมนูทั้งหมด
$query = "SELECT * FROM menus ORDER BY category ASC, menu_name ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการเมนูอาหาร | เจ้าของร้าน</title>
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
        <h3 class="mb-4">🍽️ จัดการเมนูอาหารและเครื่องดื่ม</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white fw-bold">➕ เพิ่มเมนูใหม่</div>
                    <div class="card-body">
                        <form action="manage_menus.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label small">ชื่อเมนู</label>
                                <input type="text" name="menu_name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">ราคา (บาท)</label>
                                <input type="number" step="0.01" name="price" class="form-control" required min="0">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">หมวดหมู่</label>
                                <select name="category" id="category_select" class="form-select" required>
                                    <option value="food">อาหารจานหลัก</option>
                                    <option value="snack">ของทานเล่น</option>
                                    <option value="drink">เครื่องดื่ม</option>
                                </select>
                            </div>
                            
                            <div class="mb-3" id="stock_div" style="display: none;">
                                <label class="form-label small text-primary fw-bold">จำนวนคงเหลือ (กระป๋อง/ขวด)</label>
                                <input type="number" name="stock_qty" id="stock_qty" class="form-control border-primary" min="0" value="0">
                            </div>

                            <div class="mb-3">
                                <label class="form-label small">สถานะ</label>
                                <select name="status" class="form-select" required>
                                    <option value="available">พร้อมขาย</option>
                                    <option value="out_of_stock">สินค้าหมด</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small">รูปภาพเมนู</label>
                                <input class="form-control" type="file" name="menu_image" accept="image/*">
                            </div>
                            <button type="submit" name="add_menu" class="btn btn-success w-100 fw-bold">บันทึกเมนู</button>
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
                                    <th class="p-3 text-center">รูปภาพ</th>
                                    <th>ชื่อเมนู</th>
                                    <th>หมวดหมู่</th>
                                    <th>ราคา</th>
                                    <th class="text-center">คงเหลือ</th>
                                    <th>สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3 text-center">
                                        <img src="../uploads/menus/<?php echo $row['menu_image']; ?>" alt="menu" width="50" height="50" class="rounded object-fit-cover" onerror="this.src='https://via.placeholder.com/50'">
                                    </td>
                                    <td class="fw-bold"><?php echo $row['menu_name']; ?></td>
                                    <td>
                                        <?php 
                                            if($row['category'] == 'food') echo 'อาหารหลัก';
                                            elseif($row['category'] == 'snack') echo 'ของทานเล่น';
                                            else echo 'เครื่องดื่ม';
                                        ?>
                                    </td>
                                    <td class="text-danger fw-bold">฿<?php echo number_format($row['price'], 2); ?></td>
                                    
                                    <td class="text-center">
                                        <?php if($row['category'] == 'drink'): ?>
                                            <span class="badge bg-info text-dark px-2 py-1 fs-6"><?php echo $row['stock_qty']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if($row['status'] == 'available'): ?>
                                            <span class="badge bg-success">พร้อมขาย</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">หมด</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="edit_menu.php?id=<?php echo $row['menu_id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                        <a href="manage_menus.php?delete_id=<?php echo $row['menu_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('ยืนยันการลบเมนูนี้?');">ลบ</a>
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
    
    <script>
        document.getElementById('category_select').addEventListener('change', function() {
            var stockDiv = document.getElementById('stock_div');
            var stockInput = document.getElementById('stock_qty');
            
            if(this.value === 'drink') {
                stockDiv.style.display = 'block';
            } else {
                stockDiv.style.display = 'none';
                stockInput.value = '0'; // รีเซ็ตเป็น 0 ถ้าไม่ใช่น้ำ
            }
        });
    </script>
</body>
</html>