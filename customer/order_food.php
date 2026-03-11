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

// ดึงเมนูอาหารทั้งหมด
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
    $new_order_id = mysqli_insert_id($conn); // ดึง ID ของบิลที่เพิ่งสร้าง

    // 2. วนลูปบันทึกรายการอาหารย่อยลงตาราง order_items
    foreach ($items as $menu_id => $qty) {
        if ($qty > 0) {
            $has_items = true;
            // ดึงราคาปัจจุบันของเมนูนั้นๆ
            $menu_price_res = mysqli_query($conn, "SELECT price FROM menus WHERE menu_id = $menu_id");
            $menu_data = mysqli_fetch_assoc($menu_price_res);
            $price_per_unit = $menu_data['price'];
            $subtotal = $price_per_unit * $qty;
            
            $total_order_price += $subtotal;

            $sql_item = "INSERT INTO order_items (order_id, menu_id, quantity, price_per_unit, subtotal) 
                         VALUES ($new_order_id, $menu_id, $qty, '$price_per_unit', '$subtotal')";
            mysqli_query($conn, $sql_item);
        }
    }

    // 3. อัปเดตยอดรวมในหัวบิล
    if ($has_items) {
        mysqli_query($conn, "UPDATE orders SET total_price = '$total_order_price' WHERE order_id = $new_order_id");
        $_SESSION['success'] = "สั่งอาหารสำเร็จ! พนักงานกำลังเตรียมอาหารให้คุณ";
    } else {
        // ถ้าไม่ได้เลือกอาหารเลย ให้ลบบิลทิ้ง
        mysqli_query($conn, "DELETE FROM orders WHERE order_id = $new_order_id");
        $_SESSION['error'] = "กรุณาเลือกรายการอาหารอย่างน้อย 1 อย่าง";
    }
    header("location: order_food.php");
    exit();
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

    <div class="container mt-4">
        <h3 class="mb-4">🍔 สั่งอาหารและเครื่องดื่มเข้าห้อง</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
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
                    <?php while($menu = mysqli_fetch_assoc($result_menu)): ?>
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 shadow-sm">
                            <img src="https://images.unsplash.com/photo-1565299624946-b28f40a0ae38?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80" class="card-img-top" style="height: 150px; object-fit: cover;">
                            <div class="card-body text-center">
                                <h6 class="card-title fw-bold"><?php echo $menu['menu_name']; ?></h6>
                                <p class="text-danger fw-bold mb-2">฿<?php echo number_format($menu['price'], 2); ?></p>
                                <div class="input-group input-group-sm mb-3">
                                    <span class="input-group-text">จำนวน</span>
                                    <input type="number" name="quantities[<?php echo $menu['menu_id']; ?>]" class="form-control text-center" value="0" min="0">
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div class="sticky-bottom bg-white p-3 border-top shadow text-end mt-4">
                    <a href="index.php" class="btn btn-secondary me-2">กลับหน้าหลัก</a>
                    <button type="submit" name="submit_order" class="btn btn-primary fw-bold px-5">ยืนยันการสั่งอาหาร</button>
                </div>

            </form>
        <?php else: ?>
            <div class="alert alert-warning text-center p-5">
                <h5>คุณยังไม่มีห้องที่ได้รับการยืนยันให้เข้าใช้บริการในขณะนี้</h5>
                <p>กรุณาจองห้องและรอให้พนักงานยืนยันสถานะก่อนทำการสั่งอาหาร</p>
                <a href="booking.php" class="btn btn-primary mt-2">ไปหน้าจองห้อง</a>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>