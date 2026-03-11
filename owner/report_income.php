<?php
session_start();
require_once "../config/db_connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header("location: ../login.php");
    exit();
}

// กำหนดค่าเริ่มต้นของวันที่ (ตั้งแต่วันแรกของเดือน ถึง วันนี้)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// ดึงข้อมูลรายได้รายวัน จากตาราง payments ที่ผ่านการตรวจสอบแล้ว
$query = "SELECT DATE(payment_date) as pay_date, COUNT(payment_id) as total_bills, SUM(amount_paid) as daily_income 
          FROM payments 
          WHERE payment_status = 'verified' AND DATE(payment_date) BETWEEN '$start_date' AND '$end_date'
          GROUP BY DATE(payment_date) 
          ORDER BY DATE(payment_date) ASC";
$result = mysqli_query($conn, $query);

$total_period_income = 0;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>รายงานรายได้ | เจ้าของร้าน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500&display=swap" rel="stylesheet">
    <style> 
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; } 
        /* ซ่อนปุ่มต่างๆ ตอนกดสั่งพิมพ์ (PDF) */
        @media print {
            .no-print { display: none !important; }
            .card { border: none !important; box-shadow: none !important; }
            body { background-color: #fff; }
        }
    </style>
</head>
<body>
    
    <div class="no-print">
        </div>

    <div class="container mt-4">
        <h3 class="mb-4">📈 รายงานสรุปรายได้</h3>

        <div class="card shadow-sm border-0 mb-4 no-print">
            <div class="card-body">
                <form action="report_income.php" method="GET" class="row align-items-end g-3">
                    <div class="col-md-4">
                        <label class="form-label">ตั้งแต่วันที่</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ถึงวันที่</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">ค้นหาข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-danger me-2">📄 พิมพ์ / PDF</button>
            <button onclick="exportTableToCSV('income_report.csv')" class="btn btn-success">📊 ส่งออก Excel (CSV)</button>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="text-center fw-bold mb-3">สรุปรายได้ร้านคาราโอเกะ</h5>
                <p class="text-center text-muted">ระหว่างวันที่ <?php echo date('d/m/Y', strtotime($start_date)); ?> ถึง <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
                
                <table class="table table-bordered mb-0" id="reportTable">
                    <thead class="table-light">
                        <tr>
                            <th class="p-3 text-center">วันที่</th>
                            <th class="text-center">จำนวนบิลที่ชำระสำเร็จ</th>
                            <th class="text-end pe-3">รายได้รวม (บาท)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result)): 
                                $total_period_income += $row['daily_income'];
                            ?>
                            <tr>
                                <td class="text-center p-3"><?php echo date('d/m/Y', strtotime($row['pay_date'])); ?></td>
                                <td class="text-center"><?php echo $row['total_bills']; ?> รายการ</td>
                                <td class="text-end pe-3 fw-bold text-success"><?php echo number_format($row['daily_income'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center p-4">ไม่มีข้อมูลรายได้ในช่วงเวลานี้</td></tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-dark">
                        <tr>
                            <th colspan="2" class="text-end p-3">รวมรายได้สุทธิทั้งหมด:</th>
                            <th class="text-end pe-3 fs-5">฿<?php echo number_format($total_period_income, 2); ?></th>
                        </tr>
                    </tfoot>
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
                for (var j = 0; j < cols.length; j++) 
                    row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
                csv.push(row.join(","));
            }

            // เพิ่ม BOM ให้ Excel อ่านภาษาไทยได้
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