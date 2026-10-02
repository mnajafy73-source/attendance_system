<?php
include 'config.php';
header('Content-Type: text/html; charset=utf-8');

$group_id = intval($_POST['group_id'] ?? 0);
$src_year = intval($_POST['src_year'] ?? 0);
$dst_year = intval($_POST['dst_year'] ?? 0);

if (!$group_id || !$src_year || !$dst_year) {
    die('پارامترها ناقص هستن');
}

// ================== توابع تاریخ شمسی ==================
function jalali_to_gregorian($jy, $jm, $jd) {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * ((int)($days / 146097));
    $days %= 146097;
    if ($days > 36524) { $gy += 100 * ((int)(--$days / 36524)); $days %= 36524; if ($days >= 365) $days++; }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) { $gy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    $gd = $days + 1;
    $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
    return [$gy, $gm, $gd];
}

function gregorian_to_jalali2($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jm = 1 + (int)($days / 31); $jd = 1 + ($days % 31); }
    else { $jm = 7 + (int)(($days - 186) / 30); $jd = 1 + (($days - 186) % 30); }
    return [$jy, $jm, $jd];
}

function get_dow($jy, $jm, $jd) {
    list($gy, $gm, $gd) = jalali_to_gregorian($jy, $jm, $jd);
    $w = date('w', mktime(0, 0, 0, $gm, $gd, $gy));
    return ($w + 1) % 7; // شنبه = 0 ... جمعه = 6
}

function month_days($jy, $jm) {
    if ($jm <= 6) return 31;
    if ($jm <= 11) return 30;
    $g = jalali_to_gregorian($jy, 12, 30);
    $back = gregorian_to_jalali2($g[0], $g[1], $g[2]);
    return ($back[1] == 12 && $back[2] == 30) ? 30 : 29;
}

// ================== خواندن داده‌های مبدأ ==================
$src_data = [];
$like = $src_year . '/%';
$stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
$stmt->bind_param('is', $group_id, $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $src_data[$r['JalaliDate']] = $r;
}
$stmt->close();

echo "<h2>نتیجه کپی سال</h2>";
echo "<p>داده‌های مبدأ (سال $src_year): " . count($src_data) . " رکورد</p>";

if (count($src_data) == 0) {
    echo "<p style='color:red;'>هیچ داده‌ای برای سال مبدأ پیدا نشد!</p>";
    echo "<a href='shift_calendar.php?group_id=$group_id&year=$dst_year'>بازگشت</a>";
    exit;
}

// ================== ساخت الگوی هفتگی ==================
// برای هر روز هفته (0=شنبه تا 6=جمعه)، یک نماینده از سال مبدأ پیدا می‌کنیم
$week_pattern = []; // dow => {ShiftID, IsHoliday, HolidayType}

// ابتدا از اول سال مبدأ شروع می‌کنیم
for ($sm = 1; $sm <= 12; $sm++) {
    $days_in_month = month_days($src_year, $sm);
    for ($sd = 1; $sd <= $days_in_month; $sd++) {
        $src_date = sprintf('%04d/%02d/%02d', $src_year, $sm, $sd);
        $src_dow = get_dow($src_year, $sm, $sd);
        
        // فقط اولین باری که این روز هفته دیده شد رو ثبت کن
        if (!array_key_exists($src_dow, $week_pattern)) {
            $entry = $src_data[$src_date] ?? null;
            $week_pattern[$src_dow] = $entry;
        }
    }
}

echo "<h3>الگوی هفتگی استخراج‌شده:</h3>";
$dow_names = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
echo "<ul>";
for ($d = 0; $d <= 6; $d++) {
    $entry = $week_pattern[$d] ?? null;
    if ($entry) {
        $info = "ShiftID=" . ($entry['ShiftID'] ?? 'NULL');
        if ($entry['IsHoliday']) $info .= " | تعطیل (" . $entry['HolidayType'] . ")";
        echo "<li>" . $dow_names[$d] . ": " . $info . "</li>";
    } else {
        echo "<li>" . $dow_names[$d] . ": خالی</li>";
    }
}
echo "</ul>";

// ================== پاک کردن داده‌های قبلی سال مقصد ==================
$conn->query("DELETE FROM shift_calendar WHERE GroupID = $group_id AND JalaliDate LIKE '$dst_year/%'");
echo "<p style='color:#e67e22;'>داده‌های قبلی سال $dst_year پاک شد.</p>";

// ================== آماده‌سازی statement ==================
$insert_stmt = $conn->prepare("INSERT INTO shift_calendar (GroupID, JalaliDate, ShiftID, IsHoliday, HolidayType) VALUES (?, ?, ?, ?, ?)");

if (!$insert_stmt) {
    die("خطا در آماده‌سازی: " . $conn->error);
}

$saved = 0;

// ================== حلقه کپی بر اساس روز هفته ==================
for ($dm = 1; $dm <= 12; $dm++) {
    $days_in_month = month_days($dst_year, $dm);
    for ($dd = 1; $dd <= $days_in_month; $dd++) {
        $dst_date = sprintf('%04d/%02d/%02d', $dst_year, $dm, $dd);
        $dst_dow = get_dow($dst_year, $dm, $dd);
        
        $entry = $week_pattern[$dst_dow] ?? null;
        
        if ($entry) {
            $shift_id = $entry['ShiftID'];  // می‌تونه NULL باشه
            $is_holiday = intval($entry['IsHoliday']);
            $holiday_type = $entry['HolidayType'] ?: null;
        } else {
            $shift_id = null;
            $is_holiday = 0;
            $holiday_type = null;
        }
        
        $insert_stmt->bind_param('isiss', $group_id, $dst_date, $shift_id, $is_holiday, $holiday_type);
        if ($insert_stmt->execute()) {
            $saved++;
        }
    }
}

$insert_stmt->close();

echo "<p style='color:green; font-size:18px;'>✅ $saved روز برای سال $dst_year ثبت شد.</p>";

// چک نهایی
$r = $conn->query("SELECT COUNT(*) as c FROM shift_calendar WHERE GroupID = $group_id AND JalaliDate LIKE '$dst_year/%' AND ShiftID IS NOT NULL");
$with_shift = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM shift_calendar WHERE GroupID = $group_id AND JalaliDate LIKE '$dst_year/%' AND IsHoliday = 1");
$with_holiday = $r->fetch_assoc()['c'];

echo "<p>روزهای دارای شیفت: <strong>$with_shift</strong></p>";
echo "<p>روزهای تعطیل: <strong>$with_holiday</strong></p>";

echo "<p style='margin-top:20px;'><a href='shift_calendar.php?group_id=$group_id&year=$dst_year' style='background:#3498db; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>مشاهده تقویم سال $dst_year</a></p>";
?>