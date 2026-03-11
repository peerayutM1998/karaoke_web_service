<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ระบบเพิ่มโปรโมชั่น
if (isset($_POST['add_promo'])) {
    $promo_name = mysqli_real_escape_string($conn, $_POST['promo_name']);
    $discount_percent = !empty($_POST['discount_percent']) ? $_POST['discount_percent'] : 0;
    $discount_amount = !empty($_POST['discount_amount']) ? $_POST['discount_amount'] : 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql = "INSERT INTO promotions (promo_name, discount_percent, discount_amount, start_date, end_date, status) 
            VALUES ('$promo_name', $discount_percent, $discount_amount, '$start_date', '$end_date', 'active')";
    
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "เพิ่มโปรโมชั่นใหม่สำเร็จ";
    header("location: manage_promotions.php");
    exit();
}

// ระบบลบ/ยกเลิกโปรโมชั่น
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM promotions WHERE promo_id = $del_id");
    $_SESSION['success'] = "ลบโปรโมชั่นเรียบร้อยแล้ว";
    header("location: manage_promotions.php");
    exit();
}

// ดึงข้อมูลโปรโมชั่น
$query = "SELECT * FROM promotions ORDER BY start_date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการโปรโมชั่น | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <div class="container mt-4">
        <h3 class="mb-4">🎁 จัดการโปรโมชั่นและส่วนลด</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-warning text-dark fw-bold">สร้างโปรโมชั่นใหม่</div>
                    <div class="card-body">
                        <form action="manage_promotions.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label small">ชื่อโปรโมชั่น</label>
                                <input type="text" name="promo_name" class="form-control" required placeholder="เช่น ลดรับสงกรานต์">
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label small">ส่วนลด (%)</label>
                                    <input type="number" name="discount_percent" class="form-control" placeholder="0" min="0" max="100">
                                </div>
                                <div class="col">
                                    <label class="form-label small">ส่วนลด (บาท)</label>
                                    <input type="number" step="0.01" name="discount_amount" class="form-control" placeholder="0" min="0">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">วันที่เริ่ม</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label small">วันสิ้นสุด</label>
                                <input type="date" name="end_date" class="form-control" required>
                            </div>
                            <button type="submit" name="add_promo" class="btn btn-warning w-100 fw-bold">บันทึกโปรโมชั่น</button>
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
                                    <th class="p-3">ชื่อโปรโมชั่น</th>
                                    <th>รายละเอียดส่วนลด</th>
                                    <th>ระยะเวลา</th>
                                    <th>สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3 fw-bold"><?php echo $row['promo_name']; ?></td>
                                    <td class="text-danger fw-bold">
                                        <?php 
                                            if($row['discount_percent'] > 0) echo "-" . $row['discount_percent'] . "%";
                                            if($row['discount_amount'] > 0) echo "-" . number_format($row['discount_amount']) . " ฿";
                                        ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('d/m/Y', strtotime($row['start_date'])); ?> ถึง <br> <?php echo date('d/m/Y', strtotime($row['end_date'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if(strtotime($row['end_date']) >= strtotime(date('Y-m-d'))): ?>
                                            <span class="badge bg-success">ใช้งานอยู่</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">หมดอายุ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="manage_promotions.php?delete_id=<?php echo $row['promo_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบโปรโมชั่น?');">ลบ</a>
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