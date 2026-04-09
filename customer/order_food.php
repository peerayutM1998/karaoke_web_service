<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// ดึงข้อมูลการจองที่ "กำลังใช้งานอยู่" หรือ "ยืนยันแล้ว" เพื่อให้สั่งอาหารเข้าห้องนั้นได้
$query_booking = "SELECT booking_id, room_name FROM bookings b JOIN rooms r ON b.room_id = r.room_id WHERE b.customer_id = $user_id AND b.booking_status = 'confirmed'";
$result_booking = mysqli_query($conn, $query_booking);

// ดึงเมนูอาหารทั้งหมดที่สถานะพร้อมขาย
$query_menu = "SELECT * FROM menus WHERE status = 'available'";
$result_menu = mysqli_query($conn, $query_menu);

// ประมวลผลการสั่งอาหาร
if (isset($_POST['submit_order'])) {
    $booking_id = $_POST['booking_id'];
    $items = $_POST['quantities']; // เป็น Array ของจำนวนที่สั่ง (key = menu_id, value = จำนวน)
    
    $total_order_price = 0;
    $has_items = false;

    // 1. สร้างหัวบิลในตาราง orders ก่อน
    $sql_order = "INSERT INTO orders (booking_id, total_price, order_status) VALUES ($booking_id, 0, 'pending')";
    mysqli_query($conn, $sql_order);
    $new_order_id = mysqli_insert_id($conn);

    // 2. วนลูปบันทึกรายการอาหารย่อยลงตาราง order_items
    foreach ($items as $menu_id => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            
            // ดึงข้อมูลเมนูปัจจุบันมาเช็คราคาและสต็อก
            $menu_res = mysqli_query($conn, "SELECT price, category, stock_qty FROM menus WHERE menu_id = $menu_id");
            $menu_data = mysqli_fetch_assoc($menu_res);
            $price_per_unit = $menu_data['price'];
            $category = $menu_data['category'];
            $stock_qty = $menu_data['stock_qty'];

            // 🌟 ป้องกันกรณีลูกค้าแฮ็กแก้ HTML สั่งเกินสต็อก
            if ($category == 'drink' && $qty > $stock_qty) {
                $qty = $stock_qty; // บังคับให้สั่งได้มากสุดเท่าที่มี
            }

            // ถ้ายอดสั่งยังมากกว่า 0 ค่อยบันทึกลงบิล
            if ($qty > 0) {
                $has_items = true;
                $subtotal = $price_per_unit * $qty;
                $total_order_price += $subtotal;

                $sql_item = "INSERT INTO order_items (order_id, menu_id, quantity, price_per_unit, subtotal) 
                             VALUES ($new_order_id, $menu_id, $qty, '$price_per_unit', '$subtotal')";
                mysqli_query($conn, $sql_item);

                // 🌟 ตัดสต็อกเครื่องดื่มในฐานข้อมูล
                if ($category == 'drink') {
                    mysqli_query($conn, "UPDATE menus SET stock_qty = stock_qty - $qty WHERE menu_id = $menu_id");
                }
            }
        }
    }

    // 3. อัปเดตยอดรวมในหัวบิล
    if ($has_items) {
        mysqli_query($conn, "UPDATE orders SET total_price = '$total_order_price' WHERE order_id = $new_order_id");
        
        // ส่ง order_id ที่เพิ่งสร้างไปยังหน้าชำระเงิน
        header("location: pay_food_order.php?order_id=" . $new_order_id);
        exit();
    } else {
        // ถ้าไม่ได้เลือกอาหารเลย ให้ลบบิลทิ้ง
        mysqli_query($conn, "DELETE FROM orders WHERE order_id = $new_order_id");
        $_SESSION['error'] = "กรุณาเลือกรายการอาหารอย่างน้อย 1 อย่าง หรือสินค้าที่คุณเลือกหมดสต็อก";
        header("location: order_food.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สั่งอาหารและเครื่องดื่ม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; } </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">Karaoke Customer</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">หน้าหลักโปรไฟล์</a></li>
                    <li class="nav-item"><a class="nav-link" href="booking.php">จองห้องพัก</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_bookings.php">ประวัติการจอง</a></li>
                    <li class="nav-item"><a class="nav-link active" href="order_food.php">สั่งอาหาร</a></li>
                    <li class="nav-item"><a class="nav-link" href="my_orders.php">ประวัติสั่งอาหาร</a></li>
                </ul>
                <div class="d-flex text-white align-items-center">
                    <span class="me-3">👤 สวัสดี, คุณ <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-danger btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4 mb-5">
        <h3 class="mb-4">🍔 สั่งอาหารและเครื่องดื่มเข้าห้อง</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if(mysqli_num_rows($result_booking) > 0): ?>
            <form action="order_food.php" method="POST">
                
                <div class="card shadow-sm mb-4">
                    <div class="card-body bg-light">
                        <label class="form-label fw-bold">เลือกห้องที่ต้องการสั่งอาหารเข้า:</label>
                        <select name="booking_id" class="form-select border-primary" required>
                            <?php while($b = mysqli_fetch_assoc($result_booking)): ?>
                                <option value="<?php echo $b['booking_id']; ?>">บิลการจอง #<?php echo $b['booking_id']; ?> - ห้อง: <?php echo $b['room_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <?php while($menu = mysqli_fetch_assoc($result_menu)): 
                        // เช็คสต็อกสำหรับเครื่องดื่ม
                        $is_drink = ($menu['category'] == 'drink');
                        $out_of_stock = ($is_drink && $menu['stock_qty'] <= 0);
                        
                        // กำหนดค่า max ให้ input ถ้าเป็นเครื่องดื่ม
                        $max_attr = $is_drink ? 'max="'.$menu['stock_qty'].'"' : '';
                    ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm <?php echo $out_of_stock ? 'opacity-50' : ''; ?>">
                            
                            <img src="../uploads/menus/<?php echo $menu['menu_image']; ?>" class="card-img-top" style="height: 150px; object-fit: cover;" onerror="this.src='https://via.placeholder.com/150?text=No+Image'">
                            
                            <div class="card-body text-center d-flex flex-column">
                                <h6 class="card-title fw-bold text-truncate"><?php echo $menu['menu_name']; ?></h6>
                                
                                <?php if($is_drink): ?>
                                    <?php if($out_of_stock): ?>
                                        <div class="mb-2"><span class="badge bg-danger">สินค้าหมด</span></div>
                                    <?php else: ?>
                                        <div class="mb-2"><span class="badge bg-info text-dark">เหลือ <?php echo $menu['stock_qty']; ?> ชิ้น</span></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="mb-2"><span class="badge bg-success">ทำสดใหม่</span></div>
                                <?php endif; ?>

                                <p class="text-danger fw-bold mb-3 fs-5 mt-auto">฿<?php echo number_format($menu['price'], 2); ?></p>
                                
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">จำนวน</span>
                                    <input type="number" name="quantities[<?php echo $menu['menu_id']; ?>]" class="form-control text-center fw-bold" value="0" min="0" <?php echo $max_attr; ?> <?php echo $out_of_stock ? 'disabled' : ''; ?>>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="sticky-bottom bg-white p-3 border-top shadow text-end mt-4 rounded">
                    <a href="index.php" class="btn btn-secondary me-2">กลับหน้าหลัก</a>
                    <button type="submit" name="submit_order" class="btn btn-success fw-bold px-5 btn-lg">สั่งอาหารและดำเนินการชำระเงิน ➔</button>
                </div>

            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center p-5 shadow-sm">
                <h5 class="fw-bold">คุณยังไม่มีห้องที่ได้รับการยืนยันให้เข้าใช้บริการในขณะนี้</h5>
                <p>กรุณาจองห้องและรอให้พนักงานยืนยันสถานะก่อนทำการสั่งอาหาร</p>
                <a href="booking.php" class="btn btn-primary mt-3 fw-bold px-4 py-2">🎤 ไปหน้าจองห้อง</a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>