<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ระบบเพิ่มพนักงาน
if (isset($_POST['add_employee'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // เช็ค username ซ้ำ
    $check = mysqli_query($conn, "SELECT * FROM users WHERE username = '$username'");
    if(mysqli_num_rows($check) > 0) {
        $_SESSION['error'] = "Username นี้มีในระบบแล้ว";
    } else {
        $sql = "INSERT INTO users (username, password, first_name, last_name, email, phone, role) 
                VALUES ('$username', '$password', '$first_name', '$last_name', '$email', '$phone', 'employee')";
        if(mysqli_query($conn, $sql)) $_SESSION['success'] = "เพิ่มพนักงานใหม่สำเร็จ";
    }
    header("location: manage_employees.php");
    exit();
}

// ระบบลบพนักงาน
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM users WHERE user_id = $del_id AND role = 'employee'");
    $_SESSION['success'] = "ลบข้อมูลพนักงานเรียบร้อยแล้ว";
    header("location: manage_employees.php");
    exit();
}

$query = "SELECT * FROM users WHERE role = 'employee' ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการพนักงาน | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <div class="container mt-4">
        <h3 class="mb-4">💼 จัดการข้อมูลพนักงาน</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-bold">➕ เพิ่มพนักงานใหม่</div>
                    <div class="card-body">
                        <form action="manage_employees.php" method="POST">
                            <div class="mb-2">
                                <label class="form-label small">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">รหัสผ่าน</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="row mb-2">
                                <div class="col"><input type="text" name="first_name" class="form-control" placeholder="ชื่อ" required></div>
                                <div class="col"><input type="text" name="last_name" class="form-control" placeholder="นามสกุล" required></div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">อีเมล</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">เบอร์โทร</label>
                                <input type="text" name="phone" class="form-control" required>
                            </div>
                            <button type="submit" name="add_employee" class="btn btn-primary w-100">บันทึกข้อมูล</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-0 table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="p-3">ID</th>
                                    <th>ชื่อ-นามสกุล</th>
                                    <th>Username</th>
                                    <th>เบอร์โทรศัพท์</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3"><?php echo $row['user_id']; ?></td>
                                    <td class="fw-bold"><?php echo $row['first_name'] . " " . $row['last_name']; ?></td>
                                    <td><?php echo $row['username']; ?></td>
                                    <td><?php echo $row['phone']; ?></td>
                                    <td class="text-center">
                                        <a href="manage_employees.php?delete_id=<?php echo $row['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบพนักงาน?');">ลบ</a>
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
</body>
</html>