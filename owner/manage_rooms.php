<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// ระบบเพิ่มห้อง
if (isset($_POST['add_room'])) {
    $room_name = mysqli_real_escape_string($conn, $_POST['room_name']);
    $capacity = $_POST['capacity'];
    $price = $_POST['price_per_hour'];
    $status = $_POST['status'];

    $sql = "INSERT INTO rooms (room_name, capacity, price_per_hour, status) VALUES ('$room_name', '$capacity', '$price', '$status')";
    if(mysqli_query($conn, $sql)) $_SESSION['success'] = "เพิ่มห้องคาราโอเกะสำเร็จ";
    header("location: manage_rooms.php");
    exit();
}

// ระบบลบห้อง
if (isset($_GET['delete_id'])) {
    $del_id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM rooms WHERE room_id = $del_id");
    $_SESSION['success'] = "ลบข้อมูลห้องสำเร็จ";
    header("location: manage_rooms.php");
    exit();
}

$query = "SELECT * FROM rooms";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>จัดการห้องคาราโอเกะ | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } </style>
</head>
<body>
    
    <div class="container mt-4">
        <h3 class="mb-4">🎤 จัดการข้อมูลห้องคาราโอเกะ</h3>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white fw-bold">สร้างห้องใหม่</div>
                    <div class="card-body">
                        <form action="manage_rooms.php" method="POST">
                            <div class="mb-3">
                                <label class="form-label">ชื่อห้อง</label>
                                <input type="text" name="room_name" class="form-control" required placeholder="เช่น VIP-01">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ความจุ (ท่าน)</label>
                                <input type="number" name="capacity" class="form-control" required min="1">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">ราคาต่อชั่วโมง (บาท)</label>
                                <input type="number" step="0.01" name="price_per_hour" class="form-control" required min="1">
                            </div>
                            <div class="mb-4">
                                <label class="form-label">สถานะเริ่มต้น</label>
                                <select name="status" class="form-select">
                                    <option value="available">เปิดใช้งาน (Available)</option>
                                    <option value="maintenance">ซ่อมบำรุง (Maintenance)</option>
                                </select>
                            </div>
                            <button type="submit" name="add_room" class="btn btn-primary w-100">บันทึกห้อง</button>
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
                                    <th class="p-3">รหัสห้อง</th>
                                    <th>ชื่อห้อง</th>
                                    <th>ความจุ</th>
                                    <th>ราคา/ชม.</th>
                                    <th>สถานะ</th>
                                    <th class="text-center">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                <tr>
                                    <td class="p-3 text-muted">#<?php echo $row['room_id']; ?></td>
                                    <td class="fw-bold"><?php echo $row['room_name']; ?></td>
                                    <td><?php echo $row['capacity']; ?> คน</td>
                                    <td class="text-success fw-bold">฿<?php echo number_format($row['price_per_hour'], 2); ?></td>
                                    <td>
                                        <?php if($row['status'] == 'available'): ?>
                                            <span class="badge bg-success">ว่าง/พร้อมใช้</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">ปิดซ่อมบำรุง</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="manage_rooms.php?delete_id=<?php echo $row['room_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('ยืนยันการลบห้อง?');">ลบ</a>
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