<?php
session_start();
// ทำลาย Session ทั้งหมด
session_unset();
session_destroy();

// เด้งกลับไปหน้าแรกสุด
header("location: index.php");
exit();
?>