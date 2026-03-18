<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

// ประมวลผลเพิ่มลูกค้าใหม่
if (isset($_POST['add_customer'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    // กำหนดรหัสผ่านเริ่มต้นเป็นเบอร์โทรศัพท์ เพื่อความง่ายสำหรับ Walk-in
    $password = mysqli_real_escape_string($conn, $_POST['phone']); 
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username' OR phone = '$phone'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Username หรือ เบอร์โทรศัพท์ นี้มีในระบบแล้ว";
    } else {
        $sql = "INSERT INTO users (username, password, first_name, last_name, email, phone, role) 
                VALUES ('$username', '$password', '$first_name', '$last_name', '$email', '$phone', 'customer')";
        if(mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "สมัครสมาชิกให้ลูกค้าสำเร็จ! (รหัสผ่านเริ่มต้นคือเบอร์โทรศัพท์)";
        }
    }
    header("location: manage_customers.php");
    exit();
}

// ดึงข้อมูลลูกค้าทั้งหมดมาแสดง
$query = "SELECT * FROM users WHERE role = 'customer' ORDER BY created_at DESC LIMIT 50";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการลูกค้า Walk-in | พนักงาน</title>
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
                    <li class="nav-item"><a class="nav-link text-dark" href="index.php">สถานะห้อง (Dashboard)</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="view_bookings.php">คิวจองวันนี้</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="room_status.php">เช็คอิน/เช็คเอาท์</a></li>
                    <li class="nav-item"><a class="nav-link active text-dark fw-bold" href="manage_orders.php">ออเดอร์อาหาร</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="check_payments.php">เช็คบิล</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="manage_customers.php">ลูกค้า Walk-in</a></li>
                    <li class="nav-item"><a class="nav-link text-dark" href="verify_payments.php">ตรวจสลิปโอนเงิน</a></li>
                </ul>
                <div class="d-flex text-dark align-items-center fw-bold">
                    <a href="add_order.php" class="btn btn-success btn-sm me-3">➕ พนักงานสั่งอาหาร</a>
                    <span class="me-3">พนักงาน: <?php echo $_SESSION['first_name']; ?></span>
                    <a href="../logout.php" class="btn btn-dark btn-sm">ออกจากระบบ</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h3 class="mb-4">👥 สมัครสมาชิกให้ลูกค้าหน้าร้าน (Walk-in)</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white fw-bold">➕ ลงทะเบียนลูกค้าใหม่</div>
                    <div class="card-body">
                        <form action="manage_customers.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small">เบอร์โทรศัพท์ (ใช้เป็น Username และรหัสผ่าน)</label>
                                <input type="text" name="phone" class="form-control" required maxlength="10">
                                <input type="hidden" name="username" id="hidden_username">
                            </div>
                            <div class="row mb-3">
                                <div class="col"><input type="text" name="first_name" class="form-control" placeholder="ชื่อ" required></div>
                                <div class="col"><input type="text" name="last_name" class="form-control" placeholder="นามสกุล" required></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">อีเมล (ถ้ามี)</label>
                                <input type="email" name="email" class="form-control" value="no-email@karaoke.com">
                            </div>
                            <button type="submit" name="add_customer" class="btn btn-primary w-100 fw-bold" onclick="document.getElementById('hidden_username').value = document.getElementsByName('phone')[0].value;">บันทึกข้อมูลลูกค้า</button>
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
                                    <th class="p-3">ชื่อ-นามสกุล</th>
                                    <th>Username (เข้าสู่ระบบ)</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th>วันที่สมัคร</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3 fw-bold"><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                                    <td class="text-primary"><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td class="text-muted small"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                   <td><a href="edit_customer.php?id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-warning">แก้ไข</a></td> 
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>