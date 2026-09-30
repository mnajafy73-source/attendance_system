<?php
include 'config.php';
include 'layout.php';

// ==================== توابع تبدیل تاریخ شمسی ====================
function jalali_to_gregorian($jy, $jm, $jd) {
    $jy += 1595;
    $days = -355668 + (365 * $jy) + (((int)($jy / 33)) * 8) + ((int)((($jy % 33) + 3) / 4)) + $jd + (($jm < 7) ? ($jm - 1) * 31 : (($jm - 7) * 30) + 186);
    $gy = 400 * ((int)($days / 146097));
    $days %= 146097;
    if ($days > 36524) {
        $gy += 100 * ((int)(--$days / 36524));
        $days %= 36524;
        if ($days >= 365) $days++;
    }
    $gy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $gy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    $gd = $days + 1;
    $sal_a = [0, 31, (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    for ($gm = 1; $gm <= 12 && $gd > $sal_a[$gm]; $gm++) $gd -= $sal_a[$gm];
    return [$gy, $gm, $gd];
}

function jalali_dow($jy, $jm, $jd) {
    list($gy, $gm, $gd) = jalali_to_gregorian($jy, $jm, $jd);
    $w = date('w', mktime(0, 0, 0, $gm, $gd, $gy));
    return ($w + 1) % 7;
}

function gregorian_to_jalali2($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + ((int)(($gy2 + 3) / 4)) - ((int)(($gy2 + 99) / 100)) + ((int)(($gy2 + 399) / 400)) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * ((int)($days / 12053)));
    $days %= 12053;
    $jy += 4 * ((int)($days / 1461));
    $days %= 1461;
    if ($days > 365) {
        $jy += (int)(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + (int)($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + (int)(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return [$jy, $jm, $jd];
}

function jalali_month_days($jy, $jm) {
    if ($jm <= 6) return 31;
    if ($jm <= 11) return 30;
    $g = jalali_to_gregorian($jy, 12, 30);
    $back = gregorian_to_jalali2($g[0], $g[1], $g[2]);
    return ($back[1] == 12 && $back[2] == 30) ? 30 : 29;
}

// ==================== اطلاعات ====================
$group_id = intval($_GET['group_id'] ?? 0);
if (!$group_id) {
    renderHeader('خطا');
    echo "<div class='flash err'>گروه کاری انتخاب نشده. <a href='work_groups.php'>بازگشت</a></div>";
    renderFooter(); exit;
}

$stmt = $conn->prepare("SELECT * FROM work_groups WHERE GroupID = ?");
$stmt->bind_param('i', $group_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
if (!$group) { renderHeader('خطا'); echo "<div class='flash err'>گروه یافت نشد.</div>"; renderFooter(); exit; }

$year = intval($group['Year']);
$month_names = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
$dow_names = ['ش','ی','د','س','چ','پ','ج'];

$shifts = [];
$res = $conn->query("SELECT * FROM shifts ORDER BY ShiftID");
while ($r = $res->fetch_assoc()) $shifts[] = $r;

$colors = [
    '1' => '#e74c3c', '2' => '#3498db', '3' => '#27ae60', '4' => '#f39c12',
    '5' => '#9b59b6', '6' => '#1abc9c', '7' => '#e67e22', '8' => '#34495e', '9' => '#c0392b'
];

$calendar_data = [];
$stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ?");
$stmt->bind_param('i', $group_id);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $calendar_data[$r['JalaliDate']] = $r;

renderHeader('تقویم شیفت - ' . $group['GroupName']);
?>
<style>
.cal-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
.cal-month { background: white; border-radius: 6px; padding: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.cal-month h4 { margin: 0 0 6px; text-align: center; color: #2c3e50; font-size: 14px; }
.cal-table { width: 100%; border-collapse: collapse; }
.cal-table th { background: #ecf0f1; color: #555; font-size: 11px; padding: 3px 0; text-align: center; border: none; }
.cal-table td { text-align: center; padding: 0; border: 1px solid #eee; font-size: 11px; }
.cal-day { display: block; padding: 5px 2px; cursor: pointer; transition: 0.1s; color: #333; }
.cal-day:hover { background: #f0f8ff; }
.cal-day.empty { background: #fafafa; cursor: default; }
.cal-day.holiday-official { background: #ffcccc !important; color: #c0392b !important; font-weight: bold; }
.cal-day.holiday-unofficial { background: #ffe0b3 !important; color: #d35400 !important; font-weight: bold; }
.cal-day.has-shift { color: white !important; font-weight: bold; }
.cal-day.selected { outline: 3px solid #2c3e50; outline-offset: -3px; background: #fffacd; }
.legend { background: white; padding: 12px; border-radius: 6px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.legend span { display: inline-block; padding: 4px 10px; margin: 3px; border-radius: 3px; font-size: 12px; color: white; }
.help-box { background: #fffbea; border: 1px solid #f0e68c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
.help-box code { background: #333; color: #fff; padding: 2px 6px; border-radius: 3px; }
</style>

<div class="card">
    <h2 style="margin-top:0;">📅 تقویم شیفت گروه: <?php echo htmlspecialchars($group['GroupName']); ?> (سال <?php echo $year; ?>)</h2>
    <a href="work_groups.php" class="btn btn-primary">⬅️ بازگشت</a>
</div>

<div class="help-box">
    <strong>🎮 راهنمای کیبورد:</strong>
    روی یک روز کلیک کن، سپس:
    <code>1</code> تا <code>9</code> = اختصاص شیفت با اون رنگ &nbsp;|&nbsp;
    <code>0</code> یا <code>Delete</code> = پاک کردن &nbsp;|&nbsp;
    <code>H</code> = تعطیل رسمی &nbsp;|&nbsp;
    <code>U</code> = تعطیل غیررسمی
</div>

<div class="legend">
    <strong>راهنمای شیفت‌ها:</strong>
    <?php foreach ($shifts as $s): 
        $c = $colors[$s['ColorCode']] ?? '#95a5a6';
    ?>
        <span style="background: <?php echo $c; ?>">کلید <?php echo $s['ColorCode']; ?>: <?php echo htmlspecialchars($s['ShiftName']); ?></span>
    <?php endforeach; ?>
    <span style="background: #ffcccc; color: #c0392b;">H - تعطیل رسمی</span>
    <span style="background: #ffe0b3; color: #d35400;">U - تعطیل غیررسمی</span>
</div>

<div class="cal-grid">
<?php for ($m = 1; $m <= 12; $m++):
    $days_in_month = jalali_month_days($year, $m);
    $first_dow = jalali_dow($year, $m, 1);
?>
    <div class="cal-month">
        <h4><?php echo $month_names[$m-1]; ?></h4>
        <table class="cal-table">
            <tr>
                <?php foreach ($dow_names as $d): ?><th><?php echo $d; ?></th><?php endforeach; ?>
            </tr>
            <tr>
            <?php
            for ($i = 0; $i < $first_dow; $i++) echo "<td><span class='cal-day empty'></span></td>";
            $col = $first_dow;
            for ($d = 1; $d <= $days_in_month; $d++) {
                if ($col == 7) { echo "</tr><tr>"; $col = 0; }
                $date_str = sprintf('%04d/%02d/%02d', $year, $m, $d);
                $entry = $calendar_data[$date_str] ?? null;
                
                $classes = 'cal-day';
                $style = '';
                $shift_color_code = '';
                if ($entry) {
                    if ($entry['IsHoliday'] == 1) {
                        $classes .= ($entry['HolidayType'] == 'official') ? ' holiday-official' : ' holiday-unofficial';
                    } elseif ($entry['ShiftID']) {
                        foreach ($shifts as $s) {
                            if ($s['ShiftID'] == $entry['ShiftID']) {
                                $style = "background:" . ($colors[$s['ColorCode']] ?? '#95a5a6') . ";";
                                $classes .= ' has-shift';
                                $shift_color_code = $s['ColorCode'];
                                break;
                            }
                        }
                    }
                }
                echo "<td><span class='$classes' style='$style' data-date='$date_str' onclick='selectDay(this, event)'>$d</span></td>";
                $col++;
            }
            while ($col < 7 && $col != 0) { echo "<td><span class='cal-day empty'></span></td>"; $col++; }
            ?>
            </tr>
        </table>
    </div>
<?php endfor; ?>
</div>

<script>
const shifts = <?php echo json_encode($shifts); ?>;
const colors = <?php echo json_encode($colors); ?>;
const groupId = <?php echo $group_id; ?>;
let selectedEl = null;

function selectDay(el, ev) {
    ev.stopPropagation();
    if (el.classList.contains('empty')) return;
    
    if (selectedEl) selectedEl.classList.remove('selected');
    el.classList.add('selected');
    selectedEl = el;
}

document.addEventListener('click', function(e) {
    if (!e.target.classList.contains('cal-day')) {
        if (selectedEl) { selectedEl.classList.remove('selected'); selectedEl = null; }
    }
});

document.addEventListener('keydown', function(e) {
    if (!selectedEl) return;
    
    const key = e.key;
    const date = selectedEl.dataset.date;
    
    // پاک کردن
    if (key === '0' || key === 'Delete' || key === 'Backspace') {
        e.preventDefault();
        saveDay(date, null, 0, '');
        selectedEl.classList.remove('has-shift', 'holiday-official', 'holiday-unofficial', 'selected');
        selectedEl.style.background = '';
        selectedEl.style.color = '';
        selectedEl = null;
        return;
    }
    
    // تعطیل رسمی
    if (key === 'h' || key === 'H') {
        e.preventDefault();
        saveDay(date, null, 1, 'official');
        selectedEl.classList.remove('has-shift', 'holiday-unofficial', 'selected');
        selectedEl.classList.add('holiday-official');
        selectedEl.style.background = '';
        selectedEl = null;
        return;
    }
    
    // تعطیل غیررسمی
    if (key === 'u' || key === 'U') {
        e.preventDefault();
        saveDay(date, null, 1, 'unofficial');
        selectedEl.classList.remove('has-shift', 'holiday-official', 'selected');
        selectedEl.classList.add('holiday-unofficial');
        selectedEl.style.background = '';
        selectedEl = null;
        return;
    }
    
    // کلیدهای 1 تا 9
    if (key >= '1' && key <= '9') {
        e.preventDefault();
        // پیدا کردن شیفتی که ColorCode اش برابر key هست
        const shift = shifts.find(s => s.ColorCode == key);
        if (!shift) {
            alert('شیفتی با رنگ ' + key + ' تعریف نشده. اول توی صفحه شیفت‌ها یکی با این رنگ بساز.');
            return;
        }
        saveDay(date, shift.ShiftID, 0, '');
        selectedEl.classList.remove('has-shift', 'holiday-official', 'holiday-unofficial', 'selected');
        selectedEl.classList.add('has-shift');
        selectedEl.style.background = colors[key] || '#95a5a6';
        selectedEl.style.color = 'white';
        selectedEl = null;
    }
});

function saveDay(date, shiftId, holiday, holidayType) {
    const formData = new FormData();
    formData.append('group_id', groupId);
    formData.append('date', date);
    formData.append('shift_id', shiftId || '');
    formData.append('holiday', holiday);
    formData.append('holiday_type', holidayType);
    
    fetch('save_calendar.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => { if (!data.ok) alert('خطا در ذخیره'); })
        .catch(err => alert('خطای شبکه'));
}
</script>
<?php renderFooter(); ?>