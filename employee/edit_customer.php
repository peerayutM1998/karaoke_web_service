<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'employee') {
    header("location: ../login.php");
    exit();
}

$customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// ดึงข้อมูลลูกค้าเดิมมาแสดง
$query = "SELECT * FROM users WHERE user_id = $customer_id AND role = 'customer'";
$result = mysqli_query($conn, $query);
if(mysqli_num_rows($result) == 0) {
    header("location: manage_customers.php");
    exit();
}
$row = mysqli_fetch_assoc($result);

// ประมวลผลการแก้ไข
if(isset($_POST['update_customer'])) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    $sql = "UPDATE users SET first_name='$first_name', last_name='$last_name', email='$email', phone='$phone' WHERE user_id=$customer_id";
    if(mysqli_query($conn, $sql)) {
        $_SESSION['success'] = "อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว";
        header("location: manage_customers.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แก้ไขข้อมูลลูกค้า | พนักงาน</title>
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
    <div class="container mt-5">
        <div class="card shadow-sm mx-auto" style="max-width: 500px;">
            <div class="card-header bg-warning text-dark fw-bold">✏️ แก้ไขข้อมูลลูกค้า</div>
            <div class="card-body">
                <form action="edit_customer.php?id=<?php echo $customer_id; ?>" method="POST">
                    <div class="mb-3">
                        <label>ชื่อจริง</label>
                        <input type="text" name="first_name" class="form-control" value="<?php echo $row['first_name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>นามสกุล</label>
                        <input type="text" name="last_name" class="form-control" value="<?php echo $row['last_name']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo $row['phone']; ?>" required>
                    </div>
                    <div class="mb-4">
                        <label>อีเมล</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $row['email']; ?>">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="manage_customers.php" class="btn btn-secondary">กลับ</a>
                        <button type="submit" name="update_customer" class="btn btn-success">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>