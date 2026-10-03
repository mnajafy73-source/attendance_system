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

function roundTime($min, $block, $threshold) {
    if ($block <= 0) return $min;
    $rem = $min % $block;
    if ($rem <= $threshold) return $min - $rem;
    return $min + ($block - $rem);
}

function dateInRange($date, $from, $to) {
    return ($date >= $from && $date <= $to);
}

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

function getDow($date_str) {
    $p = explode('/', $date_str);
    if (count($p) != 3) return -1;
    list($gy, $gm, $gd) = jalali_to_gregorian(intval($p[0]), intval($p[1]), intval($p[2]));
    $w = date('w', mktime(0, 0, 0, $gm, $gd, $gy));
    return ($w + 1) % 7;
}

function getLeaveHoursForDay($rule, $dow_idx) {
    $field = 'LeaveHoursNormal';
    if ($dow_idx == 5) $field = 'LeaveHoursThursday';
    elseif ($dow_idx == 6) $field = 'LeaveHoursFriday';
    $val = $rule[$field] ?? '08:00';
    $p = explode(':', $val);
    if (count($p) == 2) return intval($p[0]) * 60 + intval($p[1]);
    return 480;
}

$pcode_filter = $_GET['pcode'] ?? '';
$search_filter = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? '1405/06';

if (!empty($pcode_filter)) $search_filter = $pcode_filter;

if (empty($pcode_filter) && !empty($search_filter)) {
    $s = $conn->real_escape_string($search_filter);
    if (ctype_digit($s)) {
        $r = $conn->query("SELECT PCode FROM personnel WHERE PCode = '$s' LIMIT 1");
    } else {
        $r = $conn->query("SELECT PCode FROM personnel WHERE Name LIKE '%$s%' ORDER BY CAST(PCode AS UNSIGNED) LIMIT 1");
    }
    if ($r && $row = $r->fetch_assoc()) $pcode_filter = $row['PCode'];
}

renderHeader('محاسبه کارکرد');
?>
<h1>🧮 محاسبه کارکرد</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div style="flex:2;">
            <label>🔍 جستجو یا انتخاب از لیست</label>
            <input type="text" name="search" list="personnel_datalist" value="<?php echo htmlspecialchars($search_filter); ?>" autocomplete="off">
            <datalist id="personnel_datalist">
                <?php
                $ps = $conn->query("SELECT PCode, Name FROM personnel ORDER BY CAST(PCode AS UNSIGNED)");
                while ($p = $ps->fetch_assoc()) echo "<option value='" . htmlspecialchars($p['Name']) . "'>کد: " . htmlspecialchars($p['PCode']) . "</option>";
                ?>
            </datalist>
        </div>
        <div>
            <label>📅 ماه</label>
            <select name="month">
                <?php
                $years = ['1405', '1406'];
                $month_names_dd = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                foreach ($years as $y) {
                    echo "<optgroup label='سال $y'>";
                    for ($m = 1; $m <= 12; $m++) {
                        $val = sprintf('%s/%02d', $y, $m);
                        $sel = ($month_filter == $val) ? 'selected' : '';
                        echo "<option value='$val' $sel>" . $month_names_dd[$m-1] . " $y</option>";
                    }
                    echo "</optgroup>";
                }
                ?>
            </select>
        </div>
        <div><button type="submit" class="btn btn-primary">🔍 محاسبه</button></div>
        <div><a href="calculation.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
</form>
</div>

<?php
if (empty($pcode_filter)) { renderFooter(); exit; }

$stmt = $conn->prepare("SELECT p.*, w.GroupID, w.GroupName FROM personnel p LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID WHERE p.PCode = ?");
$stmt->bind_param('s', $pcode_filter);
$stmt->execute();
$person = $stmt->get_result()->fetch_assoc();

if (!$person) { echo "<div class='flash err'>پرسنل یافت نشد.</div>"; renderFooter(); exit; }

$errors = [];
if (empty($person['RuleID'])) $errors[] = 'قانون اختصاصی';
if (empty($person['GeneralRuleID'])) $errors[] = 'قانون کلی';
if (empty($person['GroupID'])) $errors[] = 'گروه کاری';

if (count($errors) > 0) {
    ?>
    <div class="card" style="background:#fff3cd; border:2px solid #ffc107;">
        <h2 style="color:#856404; margin-top:0;">⚠️ محاسبه انجام نشد</h2>
        <ul><?php foreach ($errors as $e) echo "<li style='color:#e74c3c; font-weight:bold;'>❌ $e</li>"; ?></ul>
        <a href="personnel_form.php?pcode=<?php echo $person['PCode']; ?>" class="btn btn-warning">✏️ ویرایش پرسنل</a>
    </div>
    <?php
    renderFooter(); exit;
}

$stmt = $conn->prepare("SELECT * FROM rules WHERE RuleID = ?");
$stmt->bind_param('i', $person['RuleID']); $stmt->execute();
$rule = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT * FROM rules WHERE RuleID = ?");
$stmt->bind_param('i', $person['GeneralRuleID']); $stmt->execute();
$general = $stmt->get_result()->fetch_assoc();

$calendar = [];
$like = $month_filter . '%';
$stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
$stmt->bind_param('is', $person['GroupID'], $like); $stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $calendar[$r['JalaliDate']] = $r;

$shifts = [];
$res = $conn->query("SELECT * FROM shifts");
while ($r = $res->fetch_assoc()) $shifts[$r['ShiftID']] = $r;

$stmt = $conn->prepare("SELECT * FROM attendance WHERE LPAD(Prc_PCode, 8, '0') = LPAD(?, 8, '0') AND Prc_Date LIKE ? AND RIGHT(Prc_Date, 2) != '00' ORDER BY Prc_Date");
$stmt->bind_param('ss', $pcode_filter, $like); $stmt->execute();
$attendance = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $attendance[$r['Prc_Date']] = $r;

$exceptions = [];
$res = $conn->query("SELECT * FROM rule_exceptions ORDER BY DateFrom");
while ($r = $res->fetch_assoc()) $exceptions[] = $r;

$all_rules = [];
$res = $conn->query("SELECT * FROM rules");
while ($r = $res->fetch_assoc()) $all_rules[$r['RuleID']] = $r;

$colors = ['1'=>'#e74c3c','2'=>'#3498db','3'=>'#27ae60','4'=>'#f39c12','5'=>'#9b59b6','6'=>'#1abc9c','7'=>'#e67e22','8'=>'#34495e','9'=>'#c0392b'];

$ym = explode('/', $month_filter);
$year = intval($ym[0]); $month = intval($ym[1]);
$days_in_month = ($month <= 6) ? 31 : (($month <= 11) ? 30 : 29);

$total_work = 0; $total_ot = 0; $total_leave = 0;

$monthly_leave_min = 0;
if (!empty($rule['MonthlyLeaveHours'])) {
    $parts = explode(':', $rule['MonthlyLeaveHours']);
    if (count($parts) == 2) $monthly_leave_min = intval($parts[0]) * 60 + intval($parts[1]);
}

$dow_names_full = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
?>

<div class="card">
    <h2>👤 <?php echo htmlspecialchars($person['Name']); ?> (کد: <?php echo $person['PCode']; ?>)</h2>
    <p>
        <strong>گروه کاری:</strong> <?php echo $person['GroupName']; ?> &nbsp;|&nbsp;
        <strong>قانون اختصاصی:</strong> <?php echo htmlspecialchars($rule['RuleName']); ?> &nbsp;|&nbsp;
        <strong>قانون کلی:</strong> <?php echo htmlspecialchars($general['RuleName']); ?>
    </p>
    <p>
        <strong>مرخصی ماهانه:</strong> <?php echo htmlspecialchars($rule['MonthlyLeaveHours']); ?> &nbsp;|&nbsp;
        <strong>روش محاسبه مرخصی:</strong> 
        <?php echo ($rule['LeaveCalcMethod'] == 'entry_exit_diff') ? 'اختلاف ورود/خروج با شیفت' : 'تفاضل کل'; ?>
    </p>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <tr>
        <th>تاریخ</th><th>روز</th><th>شیفت</th><th>ورود</th><th>خروج</th>
        <th>کارکرد</th><th>اضافه‌کاری</th><th>مرخصی</th><th>وضعیت</th>
    </tr>
    <?php for ($d = 1; $d <= $days_in_month; $d++):
        $date_str = sprintf('%04d/%02d/%02d', $year, $month, $d);
        $dow_idx = getDow($date_str);
        $is_thursday = ($dow_idx == 5);
        $is_friday = ($dow_idx == 6);
        $day_name = $dow_names_full[$dow_idx] ?? '-';
        
        $cur_rule = $rule; $cur_general = $general; $exception_applied = false;
        foreach ($exceptions as $ex) {
            if (dateInRange($date_str, $ex['DateFrom'], $ex['DateTo'])) {
                if (!empty($ex['SpecificRuleID']) && isset($all_rules[$ex['SpecificRuleID']])) {
                    $cur_rule = $all_rules[$ex['SpecificRuleID']]; $exception_applied = true;
                }
                if (!empty($ex['GeneralRuleID']) && isset($all_rules[$ex['GeneralRuleID']])) {
                    $cur_general = $all_rules[$ex['GeneralRuleID']]; $exception_applied = true;
                }
                break;
            }
        }
        
        $cal = $calendar[$date_str] ?? null;
        $att = $attendance[$date_str] ?? null;
        
        $shift_name = '-'; $shift_color = '';
        $expected = 480;
        $is_holiday = false; $has_shift = false; $no_shift = false;
        $is_night_shift = false;
        $sh_start_min = 0; $sh_end_min = 0;
        
        if ($cal) {
            if ($cal['IsHoliday'] == 1) {
                $is_holiday = true;
                $shift_name = ($cal['HolidayType'] == 'official') ? 'تعطیل رسمی' : 'تعطیل غیررسمی';
            } elseif ($cal['ShiftID'] && isset($shifts[$cal['ShiftID']])) {
                $has_shift = true;
                $s = $shifts[$cal['ShiftID']];
                $shift_name = $s['ShiftName'];
                $shift_color = $colors[$s['ColorCode']] ?? '#95a5a6';
                $sh_start_min = timeToMin($s['Start1']);
                $sh_end_min = timeToMin($s['End1']);
                if ($sh_start_min > 0 && $sh_end_min > 0) {
                    if ($sh_end_min <= $sh_start_min) $sh_end_min += 1440;
                    $expected = $sh_end_min - $sh_start_min;
                }
                if ($sh_start_min >= 1080) $is_night_shift = true;
                if (!empty($s['End1']) && substr($s['End1'], -1) === '+') $is_night_shift = true;
            } else {
                $no_shift = true;
                if (!empty($cur_general['NoShiftAsHoliday'])) { $is_holiday = true; $shift_name = 'بدون شیفت (تعطیل)'; }
                else { $shift_name = 'بدون شیفت'; }
            }
        } else {
            $no_shift = true;
            if (!empty($cur_general['NoShiftAsHoliday'])) { $is_holiday = true; $shift_name = 'بدون شیفت (تعطیل)'; }
            else { $shift_name = 'بدون شیفت'; }
        }
        
        $first_in = $att['Prc_FirstIn'] ?? -1000;
        $last_out = $att['Prc_LastOut'] ?? -1000;
        $first_in_orig = $first_in; $last_out_orig = $last_out;
        
        $has_arrived = ($first_in > 0 && $last_out > 0);
        
        // رند کردن ورود
        if ($first_in > 0 && !empty($cur_general['RoundEntryEnabled'])) {
            $eb = intval($cur_general['RoundEntryBlockMinutes'] ?? 15);
            $et = intval($cur_general['RoundEntryThresholdMinutes'] ?? 6);
            $first_in = roundTime($first_in, $eb, $et);
        }
        // رند کردن خروج
        if ($last_out > 0 && !empty($cur_general['RoundExitEnabled'])) {
            $xb = intval($cur_general['RoundExitBlockMinutes'] ?? 15);
            $xt = intval($cur_general['RoundExitThresholdMinutes'] ?? 6);
            $last_out = roundTime($last_out, $xb, $xt);
        }
        if ($first_in > 0 && $last_out > 0 && $last_out < $first_in) $last_out += 1440;
        
        $worked = ($first_in > 0 && $last_out > 0) ? ($last_out - $first_in) : 0;
        
        if (!empty($cur_general['MorningTeaEnabled']) && $worked > 0) {
            $ts = timeToMin($cur_general['MorningTeaStart']); $te = timeToMin($cur_general['MorningTeaEnd']);
            if ($ts >= $first_in && $te <= $last_out && $te > $ts) $worked -= ($te - $ts);
        }
        if (!empty($cur_general['EveningTeaEnabled']) && $worked > 0) {
            $ts = timeToMin($cur_general['EveningTeaStart']); $te = timeToMin($cur_general['EveningTeaEnd']);
            if ($ts >= $first_in && $te <= $last_out && $te > $ts) $worked -= ($te - $ts);
        }
        
        $ot = 0; $leave_min = 0;
        
        // مرخصی ساعتی از Paradox (فقط اگه پرسنل اومده باشه)
        if ($att && $has_arrived && !empty($cur_general['HourlyLeaveAsLeave'])) {
            $h1 = intval($att['Prc_HourEleaveSalary'] ?? 0);
            $h2 = intval($att['Prc_HourSleaveSalary'] ?? 0);
            $h3 = intval($att['Prc_HourleaveNoSalary'] ?? 0);
            $leave_min += $h1 + $h2 + $h3;
        }
        
        if ($is_holiday && $worked > 0) {
            $ot = $worked;
        } elseif (!$is_holiday && $has_shift && $worked > 0) {
            // === محاسبه OT بر اساس ورود قبل از شیفت و خروج بعد از شیفت ===
            $sh_start_abs = $sh_start_min;
            $sh_end_abs = $sh_end_min;
            
            // خروج بعد از پایان شیفت → OT
            if ($last_out > $sh_end_abs) {
                $ot += $last_out - $sh_end_abs;
            }
            // ورود قبل از شروع شیفت → OT
            if ($first_in < $sh_start_abs) {
                $ot += $sh_start_abs - $first_in;
            }
            
            // کسر ناهار/شام از OT
            $s = $shifts[$cal['ShiftID']];
            if ($is_night_shift) {
                $break_enabled = !empty($cur_general['NightDinnerEnabled']);
                $bs = timeToMin($cur_general['NightDinnerStart']);
                $be = timeToMin($cur_general['NightDinnerEnd']);
                if ($bs < $sh_start_abs) $bs += 1440;
                if ($be < $sh_start_abs) $be += 1440;
            } else {
                $break_enabled = !empty($cur_general['LunchEnabled']);
                $bs = timeToMin($cur_general['LunchStart']);
                $be = timeToMin($cur_general['LunchEnd']);
                if ($bs < $first_in && $bs + 720 < $first_in) $bs += 1440;
                if ($be < $bs) $be += 1440;
            }
            
            $ot_start = $sh_end_abs;
            $ot_end = $last_out;
            if ($break_enabled && $bs > 0 && $be > $bs && $bs >= $ot_start && $be <= $ot_end) {
                $deduct = $be - $bs;
                if ($ot > $deduct) $ot -= $deduct;
                else $ot = 0;
            }
            
            if ($ot < 0) $ot = 0;
            
            // === محاسبه مرخصی برای ورود دیرتر یا خروج زودتر ===
            // این مستقل از OT محاسبه می‌شه
            $late_min = ($first_in > $sh_start_abs) ? ($first_in - $sh_start_abs) : 0;
            $early_min = ($last_out < $sh_end_abs) ? ($sh_end_abs - $last_out) : 0;
            
            if (!empty($cur_rule['LeaveCalcMethod']) && $cur_rule['LeaveCalcMethod'] == 'entry_exit_diff') {
                // روش دوم: اختلاف ورود/خروج با شیفت
                $leave_min += $late_min + $early_min;
            } else {
                // روش اول (تفاضل کل): فقط اگه کارکرد کمتر از شیفت باشه
                // ولی OT حفظ می‌شه
                if ($worked < $expected) {
                    // محاسبه تفاضل کل (بدون کسر کردن از OT)
                    $leave_min += ($expected - $worked);
                }
            }
        } elseif ($has_shift && $worked == 0) {
            // تردد نداشته → مرخصی روزانه
            if (!empty($cur_rule['ShiftNoAttendanceAsLeave'])) {
                $leave_min += getLeaveHoursForDay($cur_rule, $dow_idx);
            } else {
                $default_leave_hours = [0=>480, 1=>480, 2=>480, 3=>480, 4=>480, 5=>240, 6=>0];
                $leave_min += $default_leave_hours[$dow_idx] ?? 480;
            }
        }
        
        $total_work += $worked; $total_ot += $ot; $total_leave += $leave_min;
        
        // وضعیت
        $status = '';
        if ($exception_applied) $status .= '🔷 ';
        if ($is_holiday) {
            if ($no_shift) $status .= ($worked > 0) ? '🟢 اضافه‌کار (تعطیل)' : '⚪ تعطیل (بدون شیفت)';
            else $status .= ($cal['HolidayType'] == 'official') ? '🔴 تعطیل رسمی' : '🟠 تعطیل غیررسمی';
            if ($worked > 0 && !$no_shift) $status .= ' - اضافه‌کار';
        } elseif ($worked == 0) {
            if ($leave_min > 0) $status .= '🟣 مرخصی';
            elseif ($is_friday) $status .= '⚪ تعطیل (جمعه)';
            else $status .= '⛔ غیبت';
        } elseif ($leave_min > 0 && $ot == 0) {
            $status .= '🟣 مرخصی';
        } elseif ($leave_min > 0 && $ot > 0) {
            $status .= '🟢 اضافه‌کار + 🟣 مرخصی';
        } elseif ($ot > 0) {
            $status .= '🟢 اضافه‌کار';
        } else {
            $status .= '✅ نرمال';
        }
        
        echo "<tr>";
        echo "<td>$date_str</td>";
        echo "<td style='" . ($is_thursday ? 'color:#e67e22;font-weight:bold;' : ($is_friday ? 'color:#e74c3c;font-weight:bold;' : '')) . "'>$day_name</td>";
        echo "<td>" . ($shift_color ? "<span style='background:$shift_color;color:white;padding:2px 8px;border-radius:3px;font-size:12px;'>$shift_name</span>" : $shift_name) . ($is_night_shift ? ' 🌙' : '') . "</td>";
        echo "<td>" . minToStr($first_in) . ($first_in_orig != $first_in && $first_in > 0 ? " <small style='color:#e67e22;'>(از " . minToStr($first_in_orig) . ")</small>" : "") . "</td>";
        echo "<td>" . minToStr($last_out % 1440) . ($last_out_orig != $last_out && $last_out > 0 ? " <small style='color:#e67e22;'>(از " . minToStr($last_out_orig) . ")</small>" : "") . "</td>";
        echo "<td>" . ($worked > 0 ? minToDur($worked) : '-') . "</td>";
        echo "<td style='color:#27ae60;'>" . ($ot > 0 ? minToDur($ot) : '-') . "</td>";
        echo "<td style='color:#9b59b6;'>" . ($leave_min > 0 ? minToDur($leave_min) : '-') . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    endfor; ?>
</table>
</div>

<div class="card" style="background:#2c3e50; color:white;">
    <h2 style="margin-top:0; color:white;">📊 جمع کل ماه</h2>
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
        <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">کل کارکرد</div><div style="font-size:24px;"><?php echo minToDur($total_work); ?></div></div>
        <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">کل اضافه‌کاری</div><div style="font-size:24px; color:#2ecc71;"><?php echo minToDur($total_ot); ?></div></div>
        <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">کل مرخصی</div><div style="font-size:24px; color:#9b59b6;"><?php echo minToDur($total_leave); ?></div></div>
    </div>
    
    <?php $remaining = $monthly_leave_min - $total_leave; ?>
    <div style="margin-top:20px; padding-top:15px; border-top:1px solid #34495e;">
        <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
            <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">مرخصی مجاز ماهانه</div><div style="font-size:20px;"><?php echo minToDur($monthly_leave_min); ?></div></div>
            <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">مرخصی استفاده‌شده</div><div style="font-size:20px; color:#e74c3c;"><?php echo minToDur($total_leave); ?></div></div>
            <div style="text-align:center;"><div style="font-size:12px; color:#95a5a6;">مانده مرخصی</div><div style="font-size:20px; color:<?php echo ($remaining >= 0) ? '#2ecc71' : '#e74c3c'; ?>;"><?php echo ($remaining >= 0 ? '+' : '') . minToDur(abs($remaining)); ?></div></div>
        </div>
    </div>
</div>

<?php renderFooter(); ?>