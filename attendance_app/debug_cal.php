<?php
include 'config.php';
header('Content-Type: text/plain; charset=utf-8');

echo "=== وضعیت جدول shift_calendar ===\n\n";

// ۱. تعداد کل رکوردها
$r = $conn->query("SELECT COUNT(*) as c FROM shift_calendar");
echo "کل رکوردها: " . $r->fetch_assoc()['c'] . "\n\n";

// ۲. تعداد رکورد به تفکیک گروه و سال
echo "=== به تفکیک گروه و سال ===\n";
$r = $conn->query("SELECT GroupID, SUBSTRING(JalaliDate,1,4) as y, COUNT(*) as cnt FROM shift_calendar GROUP BY GroupID, y ORDER BY GroupID, y");
while ($row = $r->fetch_assoc()) {
    echo "گروه {$row['GroupID']} - سال {$row['y']} : {$row['cnt']} روز\n";
}

// ۳. ۱۰ رکورد آخر
echo "\n=== ۱۰ رکورد آخر ===\n";
$r = $conn->query("SELECT * FROM shift_calendar ORDER BY ID DESC LIMIT 10");
while ($row = $r->fetch_assoc()) {
    echo "ID={$row['ID']} | GroupID={$row['GroupID']} | Date={$row['JalaliDate']} | ShiftID=" . ($row['ShiftID'] ?? 'NULL') . " | Holiday={$row['IsHoliday']} | Type=" . ($row['HolidayType'] ?? '-') . "\n";
}

// ۴. ۵ رکورد اول سال ۱۴۰۵
echo "\n=== ۵ رکورد اول سال ۱۴۰۵ ===\n";
$r = $conn->query("SELECT * FROM shift_calendar WHERE JalaliDate LIKE '1405/%' ORDER BY JalaliDate LIMIT 5");
$cnt = 0;
while ($row = $r->fetch_assoc()) {
    echo "Date={$row['JalaliDate']} | ShiftID=" . ($row['ShiftID'] ?? 'NULL') . " | Holiday={$row['IsHoliday']}\n";
    $cnt++;
}
if ($cnt == 0) echo "(خالی)\n";

// ۵. ۵ رکورد اول سال ۱۴۰۶
echo "\n=== ۵ رکورد اول سال ۱۴۰۶ ===\n";
$r = $conn->query("SELECT * FROM shift_calendar WHERE JalaliDate LIKE '1406/%' ORDER BY JalaliDate LIMIT 5");
$cnt = 0;
while ($row = $r->fetch_assoc()) {
    echo "Date={$row['JalaliDate']} | ShiftID=" . ($row['ShiftID'] ?? 'NULL') . " | Holiday={$row['IsHoliday']}\n";
    $cnt++;
}
if ($cnt == 0) echo "(خالی)\n";

// ۶. اطلاعات گروه ۱
echo "\n=== اطلاعات گروه ۱ ===\n";
$r = $conn->query("SELECT * FROM work_groups WHERE GroupID = 1");
while ($row = $r->fetch_assoc()) {
    print_r($row);
}
?>