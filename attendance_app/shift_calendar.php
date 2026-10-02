<?php
include 'config.php';
include 'layout.php';

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
    if ($days > 365) { $jy += (int)(($days - 1) / 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jm = 1 + (int)($days / 31); $jd = 1 + ($days % 31); }
    else { $jm = 7 + (int)(($days - 186) / 30); $jd = 1 + (($days - 186) % 30); }
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
    echo "<div class='flash err'>گروه کاری انتخاب نشده.</div>";
    renderFooter(); exit;
}

$stmt = $conn->prepare("SELECT * FROM work_groups WHERE GroupID = ?");
$stmt->bind_param('i', $group_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();
if (!$group) { renderHeader('خطا'); echo "<div class='flash err'>گروه یافت نشد.</div>"; renderFooter(); exit; }

list($jy_now, $jm_now, $jd_now) = gregorian_to_jalali2(date('Y'), date('n'), date('j'));
$default_year = $jy_now;
$year = intval($_GET['year'] ?? $default_year);

$existing_years = [];
$res = $conn->query("SELECT DISTINCT SUBSTRING(JalaliDate, 1, 4) AS y FROM shift_calendar WHERE GroupID = $group_id ORDER BY y");
while ($r = $res->fetch_assoc()) $existing_years[] = intval($r['y']);

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
$stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
$like = $year . '/%';
$stmt->bind_param('is', $group_id, $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $calendar_data[$r['JalaliDate']] = $r;

renderHeader('تقویم شیفت - ' . $group['GroupName'] . ' - سال ' . $year);
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
.cal-day.selected { outline: 3px solid #2c3e50; outline-offset: -3px; }
.legend { background: white; padding: 12px; border-radius: 6px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.legend span { display: inline-block; padding: 4px 10px; margin: 3px; border-radius: 3px; font-size: 12px; color: white; }
.help-box { background: #fffbea; border: 1px solid #f0e68c; padding: 10px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; }
.help-box code { background: #333; color: #fff; padding: 2px 6px; border-radius: 3px; }
.modal-bg { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 100; justify-content: center; align-items: center; }
.modal-bg.active { display: flex; }
.modal { background: white; padding: 25px; border-radius: 8px; min-width: 450px; max-width: 90%; }
.modal h3 { margin-top: 0; color: #2c3e50; }
.modal label { display: block; margin: 10px 0 5px; font-weight: bold; color: #34495e; }
.modal input, .modal select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
.modal-buttons { margin-top: 20px; text-align: left; }
.year-nav { display: flex; gap: 5px; align-items: center; }
.year-nav a { padding: 8px 15px; background: #ecf0f1; color: #2c3e50; text-decoration: none; border-radius: 4px; font-weight: bold; }
.year-nav a.active { background: #3498db; color: white; }
.year-nav a:hover { background: #3498db; color: white; }
</style>

<div class="card">
    <h2 style="margin-top:0;">📅 تقویم شیفت: <?php echo htmlspecialchars($group['GroupName']); ?></h2>
    
    <div style="display:flex; align-items:center; gap:15px; flex-wrap:wrap; margin-top:15px;">
        <strong>سال:</strong>
        <div class="year-nav">
            <?php
            $min_year = $default_year - 1;
            $max_year = $default_year + 3;
            if ($year < $min_year) $min_year = $year;
            if ($year > $max_year) $max_year = $year;
            
            for ($y = $min_year; $y <= $max_year; $y++):
                $active = ($y == $year) ? 'active' : '';
            ?>
                <a href="shift_calendar.php?group_id=<?php echo $group_id; ?>&year=<?php echo $y; ?>" class="<?php echo $active; ?>"><?php echo $y; ?></a>
            <?php endfor; ?>
        </div>
        
        <a href="work_groups.php" class="btn btn-primary" style="margin-right:auto;">⬅️ بازگشت</a>
        <button class="btn btn-warning" onclick="openRepeatModal()">🔁 تکرار الگو</button>
        <button class="btn" style="background:#9b59b6; color:white;" onclick="openCopyModal()">📋 کپی از سال قبل</button>
    </div>
</div>

<div class="help-box">
    <strong>🎮 راهنمای کیبورد:</strong>
    <code>1</code> تا <code>9</code> = شیفت &nbsp;|&nbsp;
    <code>0</code> = پاک کردن &nbsp;|&nbsp;
    <code>H</code> = تعطیل رسمی &nbsp;|&nbsp;
    <code>U</code> = تعطیل غیررسمی
    <br>✨ بعد از هر کلید، خودکار می‌ره روی روز بعد.
    <br>📌 سال‌های ذخیره‌شده: 
    <?php if (empty($existing_years)): ?>
        <em>هیچ تقویمی ذخیره نشده</em>
    <?php else: ?>
        <?php echo implode(', ', $existing_years); ?>
    <?php endif; ?>
</div>

<div class="legend">
    <strong>شیفت‌ها:</strong>
    <?php foreach ($shifts as $s): $c = $colors[$s['ColorCode']] ?? '#95a5a6'; ?>
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
            <tr><?php foreach ($dow_names as $d): ?><th><?php echo $d; ?></th><?php endforeach; ?></tr>
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
                if ($entry) {
                    if ($entry['IsHoliday'] == 1) {
                        $classes .= ($entry['HolidayType'] == 'official') ? ' holiday-official' : ' holiday-unofficial';
                    } elseif ($entry['ShiftID']) {
                        foreach ($shifts as $s) {
                            if ($s['ShiftID'] == $entry['ShiftID']) {
                                $style = "background:" . ($colors[$s['ColorCode']] ?? '#95a5a6') . ";";
                                $classes .= ' has-shift';
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

<!-- مودال تکرار الگو -->
<div class="modal-bg" id="repeatModal">
    <div class="modal">
        <h3>🔁 تکرار الگو</h3>
        <p style="color:#666; font-size:13px;">یک الگو رو از یک بازه انتخاب کن و بگو توی چه بازه‌ای تکرار بشه.</p>
        <label>ماه مبدأ</label>
        <select id="srcMonth"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>"><?php echo $month_names[$m-1]; ?></option><?php endfor; ?></select>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
            <div><label>از روز</label><input type="number" id="srcFrom" value="1" min="1" max="31"></div>
            <div><label>تا روز</label><input type="number" id="srcTo" value="7" min="1" max="31"></div>
        </div>
        <hr style="margin:20px 0;">
        <label>کجا تکرار بشه؟</label>
        <select id="dstMode">
            <option value="same_month_rest">ادامه همین ماه</option>
            <option value="rest_of_year">تا آخر همین سال</option>
            <option value="all_year">کل همین سال</option>
            <option value="specific_month">فقط یک ماه خاص</option>
        </select>
        <div id="specificMonthBox" style="display:none; margin-top:10px;">
            <label>ماه مقصد</label>
            <select id="dstMonth"><?php for ($m = 1; $m <= 12; $m++): ?><option value="<?php echo $m; ?>"><?php echo $month_names[$m-1]; ?></option><?php endfor; ?></select>
        </div>
        <div class="modal-buttons">
            <button onclick="applyRepeat()" class="btn btn-success" id="applyBtn">🔁 اعمال تکرار</button>
            <button onclick="closeRepeatModal()" class="btn btn-danger">انصراف</button>
        </div>
    </div>
</div>

<!-- مودال کپی از سال قبل -->
<div class="modal-bg" id="copyModal">
    <div class="modal">
        <h3>📋 کپی از سال قبل</h3>
        <p style="color:#666; font-size:13px;">
            کل تقویم یک سال رو به سال دیگه کپی می‌کنه.
            <br><strong style="color:#9b59b6;">✨ کپی دقیقاً بر اساس روز هفته انجام می‌شه</strong>
            <br>(شنبه به شنبه، یکشنبه به یکشنبه، ...)
        </p>
        
        <form method="post" action="copy_year.php">
            <input type="hidden" name="group_id" value="<?php echo $group_id; ?>">
            <input type="hidden" name="dst_year" value="<?php echo $year; ?>">
            
            <label>از سال (مبدأ)</label>
            <select name="src_year" required>
                <?php
                $all_years = $existing_years;
                if (!in_array($year, $all_years)) $all_years[] = $year;
                sort($all_years);
                $has_options = false;
                foreach ($all_years as $y):
                    if ($y == $year) continue;
                    $has_options = true;
                ?>
                    <option value="<?php echo $y; ?>"><?php echo $y; ?></option>
                <?php endforeach; ?>
                <?php if (!$has_options): ?>
                    <option value="" disabled>هیچ سال دیگه‌ای توی دیتابیس نیست</option>
                <?php endif; ?>
            </select>
            
            <label>به سال (مقصد)</label>
            <input type="text" value="<?php echo $year; ?>" readonly style="background:#f0f0f0;">
            
            <div style="background:#fff3cd; padding:10px; border-radius:5px; margin-top:15px; font-size:12px; color:#856404;">
                ⚠️ <strong>توجه:</strong> داده‌های قبلی سال مقصد پاک می‌شن و از نو ساخته می‌شن.
            </div>
            
            <div class="modal-buttons">
                <button type="submit" class="btn btn-success" <?php echo !$has_options ? 'disabled' : ''; ?>>📋 کپی کن</button>
                <button type="button" onclick="closeCopyModal()" class="btn btn-danger">انصراف</button>
            </div>
        </form>
    </div>
</div>

<script>
const shifts = <?php echo json_encode($shifts); ?>;
const colors = <?php echo json_encode($colors); ?>;
const groupId = <?php echo $group_id; ?>;
const year = <?php echo $year; ?>;
let calendarData = <?php echo json_encode($calendar_data); ?>;
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

function getNextDay(currentEl) {
    const allDays = Array.from(document.querySelectorAll('.cal-day:not(.empty)'));
    const idx = allDays.indexOf(currentEl);
    if (idx >= 0 && idx < allDays.length - 1) return allDays[idx + 1];
    return null;
}

function moveToNextDay() {
    if (!selectedEl) return;
    const next = getNextDay(selectedEl);
    selectedEl.classList.remove('selected');
    if (next) {
        next.classList.add('selected');
        next.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        selectedEl = next;
    } else { selectedEl = null; }
}

document.addEventListener('keydown', function(e) {
    if (!selectedEl) return;
    if (document.getElementById('repeatModal').classList.contains('active')) return;
    if (document.getElementById('copyModal').classList.contains('active')) return;
    
    const key = e.key;
    const date = selectedEl.dataset.date;
    
    if (key === '0' || key === 'Delete' || key === 'Backspace') {
        e.preventDefault();
        saveDay(date, null, 0, '');
        calendarData[date] = null;
        selectedEl.classList.remove('has-shift', 'holiday-official', 'holiday-unofficial');
        selectedEl.style.background = ''; selectedEl.style.color = '';
        moveToNextDay(); return;
    }
    if (key === 'h' || key === 'H') {
        e.preventDefault();
        saveDay(date, null, 1, 'official');
        calendarData[date] = { ShiftID: null, IsHoliday: 1, HolidayType: 'official' };
        selectedEl.classList.remove('has-shift', 'holiday-unofficial');
        selectedEl.classList.add('holiday-official');
        selectedEl.style.background = ''; selectedEl.style.color = '';
        moveToNextDay(); return;
    }
    if (key === 'u' || key === 'U') {
        e.preventDefault();
        saveDay(date, null, 1, 'unofficial');
        calendarData[date] = { ShiftID: null, IsHoliday: 1, HolidayType: 'unofficial' };
        selectedEl.classList.remove('has-shift', 'holiday-official');
        selectedEl.classList.add('holiday-unofficial');
        selectedEl.style.background = ''; selectedEl.style.color = '';
        moveToNextDay(); return;
    }
    if (key >= '1' && key <= '9') {
        e.preventDefault();
        const shift = shifts.find(s => s.ColorCode == key);
        if (!shift) { alert('شیفتی با رنگ ' + key + ' تعریف نشده.'); return; }
        saveDay(date, shift.ShiftID, 0, '');
        calendarData[date] = { ShiftID: shift.ShiftID, IsHoliday: 0, HolidayType: '' };
        selectedEl.classList.remove('has-shift', 'holiday-official', 'holiday-unofficial');
        selectedEl.classList.add('has-shift');
        selectedEl.style.background = colors[key] || '#95a5a6';
        selectedEl.style.color = 'white';
        moveToNextDay();
    }
});

function saveDay(date, shiftId, holiday, holidayType) {
    const formData = new FormData();
    formData.append('group_id', groupId);
    formData.append('date', date);
    formData.append('shift_id', shiftId || '');
    formData.append('holiday', holiday);
    formData.append('holiday_type', holidayType);
    return fetch('save_calendar.php', { method: 'POST', body: formData }).then(r => r.json());
}

// ============ تکرار الگو ============
function openRepeatModal() { document.getElementById('repeatModal').classList.add('active'); }
function closeRepeatModal() { document.getElementById('repeatModal').classList.remove('active'); }

document.getElementById('dstMode').addEventListener('change', function() {
    document.getElementById('specificMonthBox').style.display = (this.value === 'specific_month') ? 'block' : 'none';
});

function getMonthDays(m) {
    if (m <= 6) return 31;
    if (m <= 11) return 30;
    return 29;
}

async function applyRepeat() {
    const srcMonth = parseInt(document.getElementById('srcMonth').value);
    const srcFrom = parseInt(document.getElementById('srcFrom').value);
    const srcTo = parseInt(document.getElementById('srcTo').value);
    const dstMode = document.getElementById('dstMode').value;
    const dstMonth = parseInt(document.getElementById('dstMonth').value);
    if (srcFrom > srcTo) { alert('بازه مبدأ اشتباهه'); return; }
    
    const pattern = [];
    for (let d = srcFrom; d <= srcTo; d++) {
        const date = year + '/' + String(srcMonth).padStart(2,'0') + '/' + String(d).padStart(2,'0');
        const entry = calendarData[date];
        if (entry) {
            pattern.push({ shiftId: entry.ShiftID ? parseInt(entry.ShiftID) : null, isHoliday: parseInt(entry.IsHoliday), holidayType: entry.HolidayType || '' });
        } else {
            pattern.push({ shiftId: null, isHoliday: 0, holidayType: '' });
        }
    }
    
    const dstRanges = [];
    if (dstMode === 'same_month_rest') {
        dstRanges.push({ month: srcMonth, from: srcTo + 1, to: getMonthDays(srcMonth) });
    } else if (dstMode === 'rest_of_year') {
        dstRanges.push({ month: srcMonth, from: srcTo + 1, to: getMonthDays(srcMonth) });
        for (let m = srcMonth + 1; m <= 12; m++) dstRanges.push({ month: m, from: 1, to: getMonthDays(m) });
    } else if (dstMode === 'all_year') {
        for (let m = 1; m <= 12; m++) dstRanges.push({ month: m, from: 1, to: getMonthDays(m) });
    } else if (dstMode === 'specific_month') {
        dstRanges.push({ month: dstMonth, from: 1, to: getMonthDays(dstMonth) });
    }
    
    let totalDays = 0;
    for (const r of dstRanges) totalDays += (r.to - r.from + 1);
    if (!confirm('آیا مطمئنی؟ ' + totalDays + ' روز آپدیت می‌شه.')) return;
    
    document.getElementById('applyBtn').disabled = true;
    document.getElementById('applyBtn').textContent = '⏳ در حال انجام...';
    
    let patternIdx = 0, saved = 0, failed = 0;
    for (const range of dstRanges) {
        for (let d = range.from; d <= range.to; d++) {
            const date = year + '/' + String(range.month).padStart(2,'0') + '/' + String(d).padStart(2,'0');
            const p = pattern[patternIdx % pattern.length];
            try {
                const result = await saveDay(date, p.shiftId, p.isHoliday, p.holidayType);
                if (result && result.ok) {
                    saved++;
                    calendarData[date] = { ShiftID: p.shiftId, IsHoliday: p.isHoliday, HolidayType: p.holidayType };
                } else failed++;
            } catch(e) { failed++; }
            patternIdx++;
        }
    }
    document.getElementById('applyBtn').disabled = false;
    document.getElementById('applyBtn').textContent = '🔁 اعمال تکرار';
    closeRepeatModal();
    alert('✅ ذخیره: ' + saved + '\nناموفق: ' + failed);
    location.reload();
}

// ============ کپی از سال قبل ============
function openCopyModal() { document.getElementById('copyModal').classList.add('active'); }
function closeCopyModal() { document.getElementById('copyModal').classList.remove('active'); }
</script>
<?php renderFooter(); ?>