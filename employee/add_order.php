<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// ดึงห้องที่กำลังใช้งานอยู่ เพื่อสั่งอาหารเข้าไป
$query_booking = "SELECT b.booking_id, r.room_name, u.first_name 
                  FROM bookings b 
                  JOIN rooms r ON b.room_id = r.room_id 
                  JOIN users u ON b.customer_id = u.user_id 
                  WHERE b.booking_status = 'confirmed'";
$result_booking = mysqli_query($conn, $query_booking);

// ดึงเมนูทั้งหมด
$result_menu = mysqli_query($conn, "SELECT * FROM menus WHERE status = 'available'");

if (isset($_POST['submit_employee_order'])) {
    $booking_id = $_POST['booking_id'];
    $items = $_POST['quantities'];
    
    $total_order_price = 0;
    $has_items = false;

    // สร้างบิลสถานะ preparing เลย (เพราะพนักงานสั่งให้ ถือว่ารับออเดอร์แล้ว)
    mysqli_query($conn, "INSERT INTO orders (booking_id, total_price, order_status) VALUES ($booking_id, 0, 'preparing')");
    $new_order_id = mysqli_insert_id($conn);

    foreach ($items as $menu_id => $qty) {
        if ($qty > 0) {
            $has_items = true;
            $menu_res = mysqli_query($conn, "SELECT price FROM menus WHERE menu_id = $menu_id");
            $price_per_unit = mysqli_fetch_assoc($menu_res)['price'];
            $subtotal = $price_per_unit * $qty;
            $total_order_price += $subtotal;

            mysqli_query($conn, "INSERT INTO order_items (order_id, menu_id, quantity, price_per_unit, subtotal) VALUES ($new_order_id, $menu_id, $qty, '$price_per_unit', '$subtotal')");
        }
    }

    if ($has_items) {
        mysqli_query($conn, "UPDATE orders SET total_price = '$total_order_price' WHERE order_id = $new_order_id");
        $_SESSION['success'] = "บันทึกออเดอร์และส่งเข้าครัวเรียบร้อยแล้ว";
    } else {
        mysqli_query($conn, "DELETE FROM orders WHERE order_id = $new_order_id");
        $_SESSION['error'] = "กรุณาใส่จำนวนอาหารอย่างน้อย 1 รายการ";
    }
    header("location: manage_orders.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รับออเดอร์ | พนักงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-info shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-dark" href="index.php">👨‍💼 Employee Desk</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active text-dark" href="index.php">สถานะห้อง (Dashboard)</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="view_bookings.php">คิวจองวันนี้</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="room_status.php">เช็คอิน/เช็คเอาท์</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_orders.php">ออเดอร์อาหาร</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="check_payments.php">เช็คบิล</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_customers.php">ลูกค้า Walk-in</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="verify_payments.php">ตรวจสลิปโอนเงิน</a></li>
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h3 class="mb-4">📝 พนักงานรับออเดอร์อาหาร</h3>

        <form action="add_order.php" method="POST">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <label class="fw-bold">เลือกห้องที่ลูกค้าสั่ง:</label>
                    <select name="booking_id" class="form-select" required>
                        <option value="">-- เลือกห้องที่กำลังใช้งาน --</option>
                        <?php while($b = mysqli_fetch_assoc($result_booking)): ?>
                            <option value="<?php echo $b['booking_id']; ?>">ห้อง: <?php echo $b['room_name']; ?> (ลูกค้า: <?php echo $b['first_name']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="row">
                <?php while($menu = mysqli_fetch_assoc($result_menu)): ?>
                <div class="col-md-3 mb-3">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body text-center">
                            <h6 class="fw-bold"><?php echo $menu['menu_name']; ?></h6>
                            <p class="text-danger mb-2">฿<?php echo number_format($menu['price'], 2); ?></p>
                            <input type="number" name="quantities[<?php echo $menu['menu_id']; ?>]" class="form-control text-center" value="0" min="0" placeholder="จำนวน">
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="sticky-bottom bg-white p-3 border-top shadow text-end mt-4">
                <a href="manage_orders.php" class="btn btn-secondary me-2">ยกเลิก</a>
                <button type="submit" name="submit_employee_order" class="btn btn-success fw-bold px-4">บันทึกออเดอร์</button>
            </div>
        </form>
    </div>
</body>
</html>