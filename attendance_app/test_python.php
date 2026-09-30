<?php
$PYTHON_EXE = 'C:\Users\Robat\AppData\Local\Microsoft\WindowsApps\PythonSoftwareFoundation.Python.3.13_qbz5n2kfra8p0\python.exe';

echo "<h2>تست اجرای پایتون از PHP</h2>";

// تست ۱: بررسی وجود فایل پایتون
echo "<h3>۱. آیا فایل پایتون وجود داره؟</h3>";
if (file_exists($PYTHON_EXE)) {
    echo "<p style='color:green;'>✅ بله، فایل پایتون وجود داره.</p>";
} else {
    echo "<p style='color:red;'>❌ نه، فایل پایتون پیدا نشد.</p>";
}

// تست ۲: بررسی وجود sync.py
echo "<h3>۲. آیا فایل sync.py وجود داره؟</h3>";
$script = 'F:\attendance_project\sync.py';
if (file_exists($script)) {
    echo "<p style='color:green;'>✅ بله، sync.py وجود داره.</p>";
} else {
    echo "<p style='color:red;'>❌ نه، sync.py پیدا نشد.</p>";
}

// تست ۳: اجرای ساده پایتون
echo "<h3>۳. اجرای دستور ساده پایتون</h3>";
$output = [];
$cmd = '"' . $PYTHON_EXE . '" --version 2>&1';
echo "<p><strong>دستور:</strong> <code>" . htmlspecialchars($cmd) . "</code></p>";
exec($cmd, $output, $return);
echo "<pre>خروجی: " . htmlspecialchars(implode("\n", $output)) . "\nکد بازگشت: $return</pre>";

// تست ۴: اجرای sync.py
echo "<h3>۴. اجرای کامل sync.py</h3>";
$output2 = [];
$cmd2 = '"' . $PYTHON_EXE . '" "' . $script . '" 2>&1';
echo "<p><strong>دستور:</strong> <code>" . htmlspecialchars($cmd2) . "</code></p>";
exec($cmd2, $output2, $return2);
echo "<pre>خروجی:\n" . htmlspecialchars(implode("\n", $output2)) . "\n\nکد بازگشت: $return2</pre>";
?>