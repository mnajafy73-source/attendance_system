<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "hozozmohsen";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("خطا در اتصال: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// مسیر پایتون (این رو برای سیستم خودت تغییر بده)
$PYTHON_EXE  = 'C:\path\to\python.exe';
$SYNC_SCRIPT = 'F:\attendance_project\sync.py';
?>