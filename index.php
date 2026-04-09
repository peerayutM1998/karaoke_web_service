<?php
session_start();
require_once "config/db_connect.php";

// ค้นหาข้อมูลห้องคาราโอเกะ
$search_query = "";
if (isset($_GET['search'])) {
    // โค้ดส่วนนี้เตรียมไว้สำหรับการค้นหาขั้นสูง
    // 🌟 แก้ไข: ดึงห้อง "ทั้งหมด" มาแสดง ไม่ใช่แค่ห้องที่ว่าง
    $query = "SELECT * FROM rooms";
} else {
    // 🌟 แก้ไข: ดึงข้อมูลห้อง "ทั้งหมด" มาแสดงหน้าแรก
    $query = "SELECT * FROM rooms";
}
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านคาราโอเกะออนไลน์ | หน้าแรก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f8f9fa; }
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1516280440502-65f58c3ca276?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .room-card { transition: transform 0.3s; position: relative; }
        .room-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        
        /* สไตล์สำหรับป้ายสถานะมุมขวาบนของรูป */
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 15px;
            font-size: 0.9rem;
            font-weight: bold;
            border-radius: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">🎤 KaraokeOnline</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">หน้าแรก</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="<?php echo $_SESSION['role']; ?>/index.php">ไปที่หน้าจัดการของฉัน</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-danger text-white ms-2 px-3" href="logout.php">ออกจากระบบ</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="register.php">สมัครสมาชิก</a></li>
                        <li class="nav-item"><a class="nav-link btn btn-primary text-white ms-2 px-3" href="login.php">เข้าสู่ระบบ</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <h1 class="display-4 fw-bold mb-4">ปลดปล่อยเสียงเพลงในตัวคุณ</h1>
            <p class="lead mb-5">จองห้องคาราโอเกะล่วงหน้า ง่าย สะดวก พร้อมโปรโมชั่นมากมาย</p>
            
            <div class="card p-4 mx-auto shadow-lg" style="max-width: 800px; background: rgba(255,255,255,0.95); color: #333;">
                <form action="index.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">วันที่ต้องการจอง</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">เวลาเริ่ม</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">เวลาสิ้นสุด</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" name="search" class="btn btn-danger w-100">ค้นหา</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <div class="container my-5">
        <h2 class="text-center mb-4 fw-bold">ห้องคาราโอเกะของเรา</h2>
        <div class="row g-4">
            
            <?php if(mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <div class="col-md-4">
                    <div class="card h-100 room-card border-0 shadow-sm">
                        
                        <?php if($row['status'] == 'available'): ?>
                            <span class="status-badge bg-success text-white">✅ ว่างพร้อมให้บริการ</span>
                        <?php else: ?>
                            <span class="status-badge bg-danger text-white">❌ ไม่ว่าง</span>
                        <?php endif; ?>

                        <img src="https://images.unsplash.com/photo-1514525253161-7a46d19cd819?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                             class="card-img-top" alt="Room Image" 
                             style="height: 200px; object-fit: cover; <?php echo ($row['status'] != 'available') ? 'filter: grayscale(80%); opacity: 0.8;' : ''; ?>">
                        
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?php echo $row['room_name']; ?></h5>
                            <p class="card-text text-muted mb-1">
                                👥 ความจุ: ไม่เกิน <?php echo $row['capacity']; ?> ท่าน<br>
                                💰 ราคา: <?php echo number_format($row['price_per_hour'], 2); ?> บาท / ชั่วโมง
                            </p>
                        </div>
                        <div class="card-footer bg-white border-top-0 pb-3 text-center">
                            
                            <?php if($row['status'] == 'available'): ?>
                                <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                                    <a href="customer/booking.php?room_id=<?php echo $row['room_id']; ?>" class="btn btn-outline-primary w-100">จองห้องนี้</a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-outline-secondary w-100">เข้าสู่ระบบเพื่อจอง</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn btn-secondary w-100 disabled" disabled>ขณะนี้ห้องไม่ว่าง</button>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-danger fs-5">ขออภัย ไม่พบข้อมูลห้องในระบบ</p>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>