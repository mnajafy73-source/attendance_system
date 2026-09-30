<?php
echo "<h2>تست exec در PHP</h2>";

$PYTHON_EXE = 'C:\Users\Robat\AppData\Local\Python\pythoncore-3.14-64\python.exe';

// تست ۱: چک کن فایل پایتون کجاست
echo "<h3>۱. آیا فایل پایتون وجود داره؟</h3>";
if (file_exists($PYTHON_EXE)) {
    echo "<p style='color:green;'>✅ بله، فایل هست.</p>";
} else {
    echo "<p style='color:red;'>❌ نه، پیدا نشد.</p>";
}

// تست ۲: اجرای پایتون --version
echo "<h3>۲. اجرای --version</h3>";
$out = [];
$ret = 0;
exec('"' . $PYTHON_EXE . '" --version 2>&1', $out, $ret);
echo "<pre>کد بازگشت: $ret\nخروجی:\n" . htmlspecialchars(implode("\n", $out)) . "</pre>";

// تست ۳: اجرای یک دستور ساده پایتون
echo "<h3>۳. اجرای -c print</h3>";
$out2 = [];
$ret2 = 0;
exec('"' . $PYTHON_EXE . '" -c "print(123)" 2>&1', $out2, $ret2);
echo "<pre>کد بازگشت: $ret2\nخروجی:\n" . htmlspecialchars(implode("\n", $out2)) . "</pre>";

// تست ۴: اجرای sync.py
echo "<h3>۴. اجرای sync.py</h3>";
$out3 = [];
$ret3 = 0;
$cmd = '"' . $PYTHON_EXE . '" "F:\attendance_project\sync.py" 2>&1';
echo "<p>دستور: <code>" . htmlspecialchars($cmd) . "</code></p>";
exec($cmd, $out3, $ret3);
echo "<pre>کد بازگشت: $ret3\nخروجی:\n" . htmlspecialchars(implode("\n", $out3)) . "</pre>";