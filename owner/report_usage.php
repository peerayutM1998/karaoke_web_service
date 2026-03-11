<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// นับจำนวนครั้งที่จอง และรวมชั่วโมงการใช้งาน โดยจัดกลุ่มตามห้อง (นับเฉพาะบิลที่จ่ายเงิน/ใช้บริการจริง)
$query = "SELECT r.room_name, r.capacity, COUNT(b.booking_id) as total_bookings, SUM(b.total_hours) as total_hours_used 
          FROM rooms r
          LEFT JOIN bookings b ON r.room_id = b.room_id AND b.booking_status IN ('confirmed', 'completed') AND b.booking_date BETWEEN '$start_date' AND '$end_date'
          GROUP BY r.room_id
          ORDER BY total_hours_used DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานการใช้บริการ | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } 
        @media print { .no-print { display: none !important; } .card { border: none !important; } }
    </style>
</head>
<body>
    
    <div class="no-print">
        </div>

    <div class="container mt-4">
        <h3 class="mb-4">📊 รายงานสรุปความนิยมของห้อง (การใช้บริการ)</h3>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <form action="report_usage.php" method="GET" class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">วิเคราะห์ข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-danger me-2">📄 พิมพ์ / PDF</button>
            <button onclick="exportTableToCSV('room_usage_report.csv')" class="btn btn-success">📊 ส่งออก Excel (CSV)</button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="text-center fw-bold mb-3">สรุปสถิติการใช้ห้องคาราโอเกะ</h5>
                <p class="text-center text-muted">ระหว่างวันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                
                <table class="table table-bordered mb-0 table-hover" id="reportTable">
                    <thead class="table-light">
                        <tr>
                            <th class="p-3">อันดับ</th>
                            <th>ชื่อห้องพัก</th>
                            <th class="text-center">ความจุ (คน)</th>
                            <th class="text-center">จำนวนครั้งที่ถูกจอง (รอบ)</th>
                            <th class="text-center">ระยะเวลารวม (ชั่วโมง)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                            $rank = 1;
                            while($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr>
                            <td class="p-3 text-center fw-bold text-muted"><?php echo $rank++; ?></td>
                            <td class="fw-bold text-primary"><?php echo $row['room_name']; ?></td>
                            <td class="text-center"><?php echo $row['capacity']; ?></td>
                            <td class="text-center fw-bold"><?php echo $row['total_bookings'] ? $row['total_bookings'] : 0; ?></td>
                            <td class="text-center text-danger fw-bold"><?php echo $row['total_hours_used'] ? $row['total_hours_used'] : 0; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function exportTableToCSV(filename) {
            var csv = [];
            var rows = document.querySelectorAll("#reportTable tr");
            for (var i = 0; i < rows.length; i++) {
                var row = [], cols = rows[i].querySelectorAll("td, th");
                for (var j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(","));
            }
            var csvFile = new Blob(["\uFEFF"+csv.join("\n")], {type: "text/csv;charset=utf-8;"});
            var downloadLink = document.createElement("a");
            downloadLink.download = filename;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = "none";
            document.body.appendChild(downloadLink);
            downloadLink.click();
        }
    </script>
</body>
</html>