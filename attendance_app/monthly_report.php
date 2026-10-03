<?php
include 'config.php';
include 'layout.php';

function minToDur($m) {
    if ($m <= 0) return '-';
    return floor($m / 60) . ':' . str_pad($m % 60, 2, '0', STR_PAD_LEFT);
}

function minToHours($m) {
    if ($m <= 0) return '0.0';
    return number_format($m / 60, 1);
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

function dateInRange($date, $from, $to) {
    return ($date >= $from && $date <= $to);
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

function timeToMin($t) {
    if (empty($t) || $t == '-') return -1000;
    $plus = (substr($t, -1) === '+') ? 1440 : 0;
    $t = rtrim($t, '+');
    $p = explode(':', $t);
    if (count($p) != 2) return -1000;
    return intval($p[0]) * 60 + intval($p[1]) + $plus;
}

$search_filter = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? '1405/06';

renderHeader('گزارش ماهانه');

$ym = explode('/', $month_filter);
$year = intval($ym[0]); $month = intval($ym[1]);
$days_in_month = ($month <= 6) ? 31 : (($month <= 11) ? 30 : 29);
$like = $month_filter . '%';
?>

<h1>📊 گزارش ماهانه همه پرسنل</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div style="flex:2;">
            <label>🔍 جستجو یا انتخاب از لیست</label>
            <input type="text" name="search" list="personnel_datalist" value="<?php echo htmlspecialchars($search_filter); ?>" 
                   placeholder="اسم رو تایپ کن یا از لیست انتخاب کن..." autocomplete="off">
            <datalist id="personnel_datalist">
                <?php
                $ps = $conn->query("SELECT PCode, Name FROM personnel ORDER BY CAST(PCode AS UNSIGNED)");
                while ($p = $ps->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($p['Name']) . "'>کد: " . htmlspecialchars($p['PCode']) . "</option>";
                }
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
        <div><button type="submit" class="btn btn-primary">🔍 نمایش گزارش</button></div>
        <div><a href="monthly_report.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
</form>
</div>

<?php
$where = '';
if (!empty($search_filter)) {
    $s = $conn->real_escape_string($search_filter);
    if (ctype_digit($s)) {
        $where = "WHERE p.PCode = '$s'";
    } else {
        $where = "WHERE p.Name LIKE '%$s%'";
    }
}

$sql = "SELECT p.*, w.GroupName 
        FROM personnel p 
        LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID 
        $where 
        ORDER BY CAST(p.PCode AS UNSIGNED)";
$personnel = $conn->query($sql);

$shifts = [];
$res = $conn->query("SELECT * FROM shifts");
while ($r = $res->fetch_assoc()) $shifts[$r['ShiftID']] = $r;

$exceptions = [];
$res = $conn->query("SELECT * FROM rule_exceptions ORDER BY DateFrom");
while ($r = $res->fetch_assoc()) $exceptions[] = $r;

$all_rules = [];
$res = $conn->query("SELECT * FROM rules");
while ($r = $res->fetch_assoc()) $all_rules[$r['RuleID']] = $r;

$grand = ['ot' => 0, 'leave' => 0, 'present_days' => 0];
$report_data = [];

while ($p = $personnel->fetch_assoc()) {
    $pcode = $p['PCode'];
    $rule = isset($all_rules[$p['RuleID']]) ? $all_rules[$p['RuleID']] : null;
    $general = isset($all_rules[$p['GeneralRuleID']]) ? $all_rules[$p['GeneralRuleID']] : null;
    
    if (!$rule || !$general || empty($p['WorkGroupID'])) {
        $report_data[] = [
            'PCode' => $pcode, 'Name' => $p['Name'], 'GroupName' => $p['GroupName'] ?? '-',
            'incomplete' => true, 'present_days' => 0, 'total_ot' => 0, 'total_leave' => 0,
            'remaining' => 0
        ];
        continue;
    }
    
    $calendar = [];
    $stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
    $stmt->bind_param('is', $p['WorkGroupID'], $like);
    $stmt->execute();
    $r2 = $stmt->get_result();
    while ($row = $r2->fetch_assoc()) $calendar[$row['JalaliDate']] = $row;
    
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE LPAD(Prc_PCode, 8, '0') = LPAD(?, 8, '0') AND Prc_Date LIKE ? ORDER BY Prc_Date");
    $stmt->bind_param('ss', $pcode, $like);
    $stmt->execute();
    $attendance = [];
    $r3 = $stmt->get_result();
    while ($row = $r3->fetch_assoc()) $attendance[$row['Prc_Date']] = $row;
    
    $monthly_leave_min = 0;
    if (!empty($rule['MonthlyLeaveHours'])) {
        $parts = explode(':', $rule['MonthlyLeaveHours']);
        if (count($parts) == 2) $monthly_leave_min = intval($parts[0]) * 60 + intval($parts[1]);
    }
    
    $total_ot = 0;
    $total_leave = 0;
    $present_days = 0;
    
    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str = sprintf('%04d/%02d/%02d', $year, $month, $d);
        $dow_idx = getDow($date_str);
        
        $cur_rule = $rule; $cur_general = $general;
        foreach ($exceptions as $ex) {
            if (dateInRange($date_str, $ex['DateFrom'], $ex['DateTo'])) {
                if (!empty($ex['SpecificRuleID']) && isset($all_rules[$ex['SpecificRuleID']])) $cur_rule = $all_rules[$ex['SpecificRuleID']];
                if (!empty($ex['GeneralRuleID']) && isset($all_rules[$ex['GeneralRuleID']])) $cur_general = $all_rules[$ex['GeneralRuleID']];
                break;
            }
        }
        
        $cal = $calendar[$date_str] ?? null;
        $att = $attendance[$date_str] ?? null;
        
        $expected = 480;
        $is_holiday = false; $has_shift = false; $no_shift = false;
        $is_night_shift = false;
        $sh_start_abs = 0; $sh_end_abs = 0;
        
        if ($cal) {
            if ($cal['IsHoliday'] == 1) {
                $is_holiday = true;
            } elseif ($cal['ShiftID'] && isset($shifts[$cal['ShiftID']])) {
                $has_shift = true;
                $s = $shifts[$cal['ShiftID']];
                $sh_start_abs = timeToMin($s['Start1']);
                $sh_end_abs = timeToMin($s['End1']);
                if ($sh_start_abs > 0 && $sh_end_abs > 0) {
                    if ($sh_end_abs <= $sh_start_abs) $sh_end_abs += 1440;
                    $expected = $sh_end_abs - $sh_start_abs;
                }
                if ($sh_start_abs >= 1080) $is_night_shift = true;
                if (!empty($s['End1']) && substr($s['End1'], -1) === '+') $is_night_shift = true;
            } else {
                $no_shift = true;
                if (!empty($cur_general['NoShiftAsHoliday'])) $is_holiday = true;
            }
        } else {
            $no_shift = true;
            if (!empty($cur_general['NoShiftAsHoliday'])) $is_holiday = true;
        }
        
        $first_in = $att['Prc_FirstIn'] ?? -1000;
        $last_out = $att['Prc_LastOut'] ?? -1000;
        
        if ($first_in > 0 && !empty($cur_general['RoundEntryEnabled'])) {
            $rem = $first_in % $cur_general['RoundBlockMinutes'];
            if ($rem <= $cur_general['RoundThresholdMinutes']) $first_in -= $rem;
            else $first_in += ($cur_general['RoundBlockMinutes'] - $rem);
        }
        if ($last_out > 0 && !empty($cur_general['RoundExitEnabled'])) {
            $rem = $last_out % $cur_general['RoundBlockMinutes'];
            if ($rem <= $cur_general['RoundThresholdMinutes']) $last_out -= $rem;
            else $last_out += ($cur_general['RoundBlockMinutes'] - $rem);
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
        
        if ($worked > 0) $present_days++;
        
        $ot = 0; $leave_min = 0;
        if ($att && !empty($cur_general['HourlyLeaveAsLeave'])) {
            $h1 = intval($att['Prc_HourEleaveSalary'] ?? 0);
            $h2 = intval($att['Prc_HourSleaveSalary'] ?? 0);
            $h3 = intval($att['Prc_HourleaveNoSalary'] ?? 0);
            $leave_min += $h1 + $h2 + $h3;
        }
        
        if ($is_holiday && $worked > 0) {
            $ot = $worked;
        } elseif (!$is_holiday && $has_shift && $worked > 0) {
            if ($worked > $expected) {
                $ot = $worked - $expected;
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
                    if ($ot > $deduct) $ot -= $deduct; else $ot = 0;
                }
            } else {
                // کم‌کاری → مرخصی
                $leave_min += $expected - $worked;
            }
        } elseif ($has_shift && $worked == 0) {
            if (!empty($cur_rule['ShiftNoAttendanceAsLeave'])) {
                $leave_min += getLeaveHoursForDay($cur_rule, $dow_idx);
            } else {
                $default_leave_hours = [0=>480, 1=>480, 2=>480, 3=>480, 4=>480, 5=>240, 6=>0];
                $leave_min += $default_leave_hours[$dow_idx] ?? 480;
            }
        }
        
        $total_ot += $ot;
        $total_leave += $leave_min;
    }
    
    $grand['ot'] += $total_ot;
    $grand['leave'] += $total_leave;
    $grand['present_days'] += $present_days;
    
    $report_data[] = [
        'PCode' => $pcode, 'Name' => $p['Name'], 'GroupName' => $p['GroupName'] ?? '-',
        'incomplete' => false, 'present_days' => $present_days, 'total_ot' => $total_ot,
        'total_leave' => $total_leave, 'remaining' => $monthly_leave_min - $total_leave
    ];
}
?>

<div class="card">
    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px; text-align:center;">
        <div>
            <div style="font-size:12px; color:#666;">تعداد پرسنل</div>
            <div style="font-size:28px; color:#3498db; font-weight:bold;"><?php echo count($report_data); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع کل اضافه‌کاری</div>
            <div style="font-size:28px; color:#27ae60; font-weight:bold;"><?php echo minToDur($grand['ot']); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع کل مرخصی</div>
            <div style="font-size:28px; color:#9b59b6; font-weight:bold;"><?php echo minToDur($grand['leave']); ?></div>
        </div>
    </div>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>کد</th>
            <th>نام</th>
            <th>گروه کاری</th>
            <th>روزهای حضور</th>
            <th>اضافه‌کاری (ساعت)</th>
            <th>مرخصی</th>
            <th>مانده مرخصی</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($report_data) == 0): ?>
            <tr><td colspan="7" style="text-align:center;">موردی یافت نشد.</td></tr>
        <?php else: ?>
            <?php foreach ($report_data as $r): ?>
            <tr>
                <td><?php echo htmlspecialchars($r['PCode']); ?></td>
                <td>
                    <a href="calculation.php?pcode=<?php echo $r['PCode']; ?>&month=<?php echo urlencode($month_filter); ?>" style="color:#3498db;">
                        <?php echo htmlspecialchars($r['Name']); ?>
                    </a>
                </td>
                <td><?php echo htmlspecialchars($r['GroupName']); ?></td>
                <?php if (!empty($r['incomplete'])): ?>
                    <td colspan="4" style="text-align:center; color:#e74c3c;">
                        ⚠️ قانون اختصاصی یا قانون کلی یا گروه کاری تعریف نشده
                    </td>
                <?php else: ?>
                    <td style="text-align:center;"><?php echo $r['present_days']; ?></td>
                    <td style="color:#27ae60; font-weight:bold;"><?php echo minToHours($r['total_ot']); ?></td>
                    <td style="color:#9b59b6;"><?php echo minToDur($r['total_leave']); ?></td>
                    <td style="text-align:center; color:<?php echo ($r['remaining'] >= 0) ? '#27ae60' : '#e74c3c'; ?>; font-weight:bold;">
                        <?php echo ($r['remaining'] >= 0 ? '+' : '') . minToDur(abs($r['remaining'])); ?>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
    <tfoot style="background:#2c3e50; color:white; font-weight:bold;">
        <tr>
            <td colspan="3" style="text-align:left;">جمع کل:</td>
            <td style="text-align:center;"><?php echo $grand['present_days']; ?></td>
            <td><?php echo minToHours($grand['ot']); ?></td>
            <td><?php echo minToDur($grand['leave']); ?></td>
            <td></td>
        </tr>
    </tfoot>
</table>
</div>

<?php renderFooter(); ?>