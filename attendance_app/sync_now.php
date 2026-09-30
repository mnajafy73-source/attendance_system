<?php
session_start();

$PYTHON_EXE  = 'C:\Users\Robat\AppData\Local\Python\pythoncore-3.14-64\python.exe';
$SYNC_SCRIPT = 'F:\attendance_project\sync.py';

$output = [];
$start = microtime(true);

$cmd = '"' . $PYTHON_EXE . '" "' . $SYNC_SCRIPT . '" 2>&1';
exec($cmd, $output, $return_var);

$elapsed = round(microtime(true) - $start, 2);

unset($_SESSION['auto_sync_done']);
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<title>همگام‌سازی دستی</title>
<style>
body { font-family: Tahoma; background: #f0f2f5; padding: 40px; text-align: center; }
.card { background: white; padding: 30px; border-radius: 8px; max-width: 800px; margin: 0 auto; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
pre { background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; text-align: left; direction: ltr; max-height: 400px; overflow: auto; font-size: 13px; white-space: pre-wrap; }
.success { color: #27ae60; font-size: 20px; font-weight: bold; }
.error { color: #e74c3c; font-size: 20px; font-weight: bold; }
</style>
</head>
<body>
<div class="card">
    <h1>🔄 نتیجه همگام‌سازی</h1>
    <?php if ($return_var === 0): ?>
        <p class="success">✅ همگام‌سازی با موفقیت انجام شد.</p>
    <?php else: ?>
        <p class="error">❌ خطا (کد: <?php echo $return_var; ?>)</p>
    <?php endif; ?>
    <p><strong>زمان:</strong> <?php echo $elapsed; ?> ثانیه</p>
    <pre><?php echo htmlspecialchars(implode("\n", $output)); ?></pre>
    <p style="margin-top:20px;">
        <a href="index.php" style="background:#3498db; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;">🏠 بازگشت به داشبورد</a>
    </p>
</div>
</body>
</html>