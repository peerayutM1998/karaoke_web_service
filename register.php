<?php
session_start();
require_once "config/db_connect.php";

// ประมวลผลเมื่อมีการกดปุ่มสมัครสมาชิก
if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);

    // 1. ตรวจสอบว่ามีชื่อผู้ใช้งานหรืออีเมลนี้ในระบบแล้วหรือไม่
    $check_query = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error'] = "ชื่อผู้ใช้งานหรืออีเมลนี้มีในระบบแล้ว กรุณาใช้ชื่ออื่น";
    } else {
        // 2. บันทึกข้อมูลลงฐานข้อมูล (กำหนด role เป็น customer ทันที)
        // หมายเหตุ: สำหรับปริญญานิพนธ์ แนะนำให้ใช้รหัสผ่านแบบ Hash เพิ่มเติมในอนาคตเพื่อความปลอดภัย
        $sql = "INSERT INTO users (username, password, first_name, last_name, email, phone, role) 
                VALUES ('$username', '$password', '$first_name', '$last_name', '$email', '$phone', 'customer')";
        
        if (mysqli_query($conn, $sql)) {
            $_SESSION['success'] = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
            header("location: login.php"); // เด้งไปหน้าล็อกอินเมื่อสำเร็จ
            exit();
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก | ระบบจัดการร้านคาราโอเกะ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px 0;
        }
        .register-card {
            width: 100%;
            max-width: 600px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: none;
        }
        .register-header {
            background: linear-gradient(135deg, #ff758c 0%, #ff7eb3 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="card register-card">
        <div class="register-header">
            <h4 class="mb-0">📝 สมัครสมาชิกใหม่</h4>
            <small>เข้าร่วมเป็นส่วนหนึ่งกับร้านคาราโอเกะของเรา</small>
        </div>
        <div class="card-body p-4 p-md-5">
            
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                
                <h6 class="text-muted mb-3 border-bottom pb-2">ข้อมูลบัญชีผู้ใช้</h6>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">รหัสผ่าน (Password) <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="4">
                    </div>
                </div>

                <h6 class="text-muted mt-4 mb-3 border-bottom pb-2">ข้อมูลส่วนตัว</h6>
                <div class="row mb-3">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">ชื่อจริง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6 mb-3 mb-md-0">
                        <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" required maxlength="10">
                    </div>
                </div>

                <button type="submit" name="register" class="btn btn-danger w-100 py-2 mb-3 fw-bold">ยืนยันการสมัครสมาชิก</button>
            </form>

            <div class="text-center mt-3">
                <span class="text-muted">มีบัญชีอยู่แล้ว?</span> <a href="login.php" class="text-decoration-none fw-bold">เข้าสู่ระบบที่นี่</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>