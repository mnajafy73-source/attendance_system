<?php
include 'config.php';
include 'layout.php';

function timeToMin($t) {
    if (empty($t) || $t == '-') return -1000;
    $plus = (substr($t, -1) === '+') ? 1440 : 0;
    $t = rtrim($t, '+');
    $p = explode(':', $t);
    if (count($p) != 2) return -1000;
    return intval($p[0]) * 60 + intval($p[1]) + $plus;
}

function minToStr($m) {
    if ($m === null || $m < 0) return '-';
    $h = floor($m / 60);
    $mm = $m % 60;
    return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($mm, 2, '0', STR_PAD_LEFT);
}

function minToDur($m) {
    if ($m <= 0) return '-';
    return floor($m / 60) . ':' . str_pad($m % 60, 2, '0', STR_PAD_LEFT);
}

$pcode_filter = $_GET['pcode'] ?? '';
$search_filter = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? '1405/06';

// اگه pcode نداد، از search استفاده کن
if (empty($pcode_filter) && !empty($search_filter)) {
    $s = $conn->real_escape_string($search_filter);
    $r = $conn->query("SELECT PCode FROM personnel WHERE Name LIKE '%$s%' OR PCode LIKE '%$s%' ORDER BY CAST(PCode AS UNSIGNED) LIMIT 1");
    if ($r && $row = $r->fetch_assoc()) {
        $pcode_filter = $row['PCode'];
    }
}

// لیست پرسنل برای dropdown
$personnel_list = [];
$res = $conn->query("SELECT PCode, Name FROM personnel ORDER BY CAST(PCode AS UNSIGNED)");
if ($res) { while ($r = $res->fetch_assoc()) $personnel_list[] = $r; }

renderHeader('محاسبه کارکرد');
?>
<h1>🧮 محاسبه کارکرد</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div>
            <label>👤 انتخاب از لیست پرسنل</label>
            <select name="pcode">
                <option value="">-- انتخاب کنید --</option>
                <?php foreach ($personnel_list as $p): ?>
                    <option value="<?php echo $p['PCode']; ?>" <?php echo ($pcode_filter == $p['PCode']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['Name']); ?> (<?php echo $p['PCode']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>🔍 یا جستجو با نام/کد</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="نام یا کد پرسنلی...">
        </div>
        <div>
            <label>📅 ماه</label>
            <input type="text" name="month" value="<?php echo htmlspecialchars($month_filter); ?>" placeholder="1405/06">
        </div>
        <div><button type="submit" class="btn btn-primary">🔍 محاسبه</button></div>
        <div><a href="calculation.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
    <small style="color:#666; display:block; margin-top:8px;">💡 اگه از لیست انتخاب کنی، همون اولویت داره. اگه تایپ کنی، اولین مورد منطبق پیدا می‌شه.</small>
</form>
</div>

<?php
if (empty($pcode_filter)) { renderFooter(); exit; }

// اطلاعات پرسنل
$stmt = $conn->prepare("SELECT p.*, w.GroupID, w.GroupName, r.* FROM personnel p 
    LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID 
    LEFT JOIN rules r ON p.RuleID = r.RuleID 
    WHERE p.PCode = ?");
$stmt->bind_param('s', $pcode_filter);
$stmt->execute();
$person = $stmt->get_result()->fetch_assoc();

if (!$person) { echo "<div class='flash err'>پرسنل یافت نشد.</div>"; renderFooter(); exit; }

// تقویم شیفت این گروه
$calendar = [];
if ($person['GroupID']) {
    $like = $month_filter . '%';
    $stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
    $stmt->bind_param('is', $person['GroupID'], $like);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $calendar[$r['JalaliDate']] = $r;
}

// شیفت‌ها
$shifts = [];
$res = $conn->query("SELECT * FROM shifts");
while ($r = $res->fetch_assoc()) $shifts[$r['ShiftID']] = $r;

// ترددهای این ماه (فقط روزهای 01 به بعد - حذف روز 00)
$like = $month_filter . '%';
$stmt = $conn->prepare("SELECT * FROM attendance WHERE LPAD(Prc_PCode, 8, '0') = LPAD(?, 8, '0') AND Prc_Date LIKE ? AND RIGHT(Prc_Date, 2) != '00' ORDER BY Prc_Date");
$stmt->bind_param('ss', $pcode_filter, $like);
$stmt->execute();
$attendance = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $attendance[$r['Prc_Date']] = $r;

$colors = ['1'=>'#e74c3c','2'=>'#3498db','3'=>'#27ae60','4'=>'#f39c12','5'=>'#9b59b6','6'=>'#1abc9c','7'=>'#e67e22','8'=>'#34495e','9'=>'#c0392b'];

$ym = explode('/', $month_filter);
$year = intval($ym[0]); $month = intval($ym[1]);
$days_in_month = ($month <= 6) ? 31 : (($month <= 11) ? 30 : 29);

$daily_work = $person['DailyWorkMinutes'] ?? 480;
$ot_factor = $person['OvertimeFactor'] ?? 1.40;

$total_work = 0; $total_ot = 0; $total_under = 0;
?>

<div class="card">
    <h2>👤 <?php echo htmlspecialchars($person['Name']); ?> (کد: <?php echo $person['PCode']; ?>)</h2>
    <p>
        <strong>گروه کاری:</strong> <?php echo $person['GroupName'] ?: 'تعریف نشده'; ?> &nbsp;|&nbsp;
        <strong>قانون:</strong> <?php echo $person['RuleName'] ?: 'تعریف نشده'; ?> &nbsp;|&nbsp;
        <strong>کار روزانه:</strong> <?php echo minToDur($daily_work); ?> &nbsp;|&nbsp;
        <strong>ضریب اضافه‌کاری:</strong> <?php echo $ot_factor; ?>
    </p>
</div>

<div class="card">
<table>
    <tr>
        <th>تاریخ</th>
        <th>شیفت</th>
        <th>شروع</th>
        <th>پایان</th>
        <th>ورود</th>
        <th>خروج</th>
        <th>کارکرد</th>
        <th>اضافه‌کاری</th>
        <th>کم‌کاری</th>
        <th>وضعیت</th>
    </tr>
    <?php for ($d = 1; $d <= $days_in_month; $d++):
        $date_str = sprintf('%04d/%02d/%02d', $year, $month, $d);
        $cal = $calendar[$date_str] ?? null;
        $att = $attendance[$date_str] ?? null;
        
        $shift_name = '-'; $shift_start = '-'; $shift_end = '-'; $shift_color = '';
        $expected = $daily_work;
        $is_holiday = false;
        
        if ($cal) {
            if ($cal['IsHoliday'] == 1) {
                $is_holiday = true;
                $shift_name = ($cal['HolidayType'] == 'official') ? 'تعطیل رسمی' : 'تعطیل غیررسمی';
            } elseif ($cal['ShiftID'] && isset($shifts[$cal['ShiftID']])) {
                $s = $shifts[$cal['ShiftID']];
                $shift_name = $s['ShiftName'];
                $shift_color = $colors[$s['ColorCode']] ?? '#95a5a6';
                if ($s['Start1']) $shift_start = $s['Start1'];
                if ($s['End3']) $shift_end = $s['End3'];
                elseif ($s['End2']) $shift_end = $s['End2'];
                elseif ($s['End1']) $shift_end = $s['End1'];
            }
        }
        
        $first_in = $att['Prc_FirstIn'] ?? -1000;
        $last_out = $att['Prc_LastOut'] ?? -1000;
        $worked = ($first_in > 0 && $last_out > 0) ? ($last_out - $first_in) : 0;
        
        $ot = 0; $under = 0;
        if (!$is_holiday && $worked > 0) {
            if ($worked > $expected) $ot = $worked - $expected;
            else $under = $expected - $worked;
        } elseif ($is_holiday && $worked > 0) {
            $ot = $worked;
        }
        
        $total_work += $worked;
        $total_ot += $ot;
        $total_under += $under;
        
        $status = '';
        if ($is_holiday) $status = $cal['HolidayType'] == 'official' ? '🔴 تعطیل' : '🟠 تعطیل';
        elseif (!$cal) $status = '⚪ بدون شیفت';
        elseif ($worked == 0) $status = '⛔ غیبت';
        elseif ($under > 0) $status = '🟡 کم‌کار';
        elseif ($ot > 0) $status = '🟢 اضافه‌کار';
        else $status = '✅ نرمال';
        
        echo "<tr>";
        echo "<td>$date_str</td>";
        echo "<td>" . ($shift_color ? "<span style='background:$shift_color;color:white;padding:2px 8px;border-radius:3px;font-size:12px;'>$shift_name</span>" : $shift_name) . "</td>";
        echo "<td>$shift_start</td>";
        echo "<td>$shift_end</td>";
        echo "<td>" . minToStr($first_in) . "</td>";
        echo "<td>" . minToStr($last_out) . "</td>";
        echo "<td>" . ($worked > 0 ? minToDur($worked) : '-') . "</td>";
        echo "<td style='color:#27ae60;'>" . ($ot > 0 ? minToDur($ot) : '-') . "</td>";
        echo "<td style='color:#e74c3c;'>" . ($under > 0 ? minToDur($under) : '-') . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    endfor; ?>
</table>
</div>

<div class="card" style="background:#2c3e50; color:white;">
    <h2 style="margin-top:0; color:white;">📊 جمع کل ماه</h2>
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px;">
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل کارکرد</div>
            <div style="font-size:24px;"><?php echo minToDur($total_work); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل اضافه‌کاری</div>
            <div style="font-size:24px; color:#2ecc71;"><?php echo minToDur($total_ot); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل کم‌کاری</div>
            <div style="font-size:24px; color:#e74c3c;"><?php echo minToDur($total_under); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">اضافه‌کاری با ضریب (<?php echo $ot_factor; ?>)</div>
            <div style="font-size:24px; color:#f39c12;"><?php echo minToDur(round($total_ot * $ot_factor)); ?></div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>