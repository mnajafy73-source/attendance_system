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

$month_filter = $_GET['month'] ?? '1405/06';
$group_filter = $_GET['group_id'] ?? '';

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
        <div>
            <label>📅 ماه</label>
            <input type="text" name="month" value="<?php echo htmlspecialchars($month_filter); ?>" placeholder="1405/06">
        </div>
        <div>
            <label>👷 گروه کاری</label>
            <select name="group_id">
                <option value="">همه گروه‌ها</option>
                <?php
                $gs = $conn->query("SELECT * FROM work_groups ORDER BY GroupName");
                while ($g = $gs->fetch_assoc()) {
                    $sel = ($group_filter == $g['GroupID']) ? 'selected' : '';
                    echo "<option value='{$g['GroupID']}' $sel>" . htmlspecialchars($g['GroupName']) . "</option>";
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
if (!empty($group_filter)) {
    $gid = intval($group_filter);
    $where = "WHERE p.WorkGroupID = $gid";
}

$sql = "SELECT p.*, w.GroupName, r.DailyWorkMinutes, r.OvertimeFactor, r.OvertimeHolidayFactor 
        FROM personnel p 
        LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID 
        LEFT JOIN rules r ON p.RuleID = r.RuleID 
        $where 
        ORDER BY CAST(p.PCode AS UNSIGNED)";
$personnel = $conn->query($sql);

$shifts = [];
$res = $conn->query("SELECT * FROM shifts");
while ($r = $res->fetch_assoc()) $shifts[$r['ShiftID']] = $r;

$grand = [
    'ot' => 0, 'ot_weighted' => 0, 'under' => 0, 
    'takhir' => 0, 'tajil' => 0, 'hour_leave' => 0,
    'present_days' => 0, 'leave_days' => 0
];

$report_data = [];

while ($p = $personnel->fetch_assoc()) {
    $pcode = $p['PCode'];
    
    $calendar = [];
    if ($p['WorkGroupID']) {
        $stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
        $stmt->bind_param('is', $p['WorkGroupID'], $like);
        $stmt->execute();
        $r2 = $stmt->get_result();
        while ($row = $r2->fetch_assoc()) $calendar[$row['JalaliDate']] = $row;
    }
    
    $stmt = $conn->prepare("SELECT * FROM attendance WHERE LPAD(Prc_PCode, 8, '0') = LPAD(?, 8, '0') AND Prc_Date LIKE ? ORDER BY Prc_Date");
    $stmt->bind_param('ss', $pcode, $like);
    $stmt->execute();
    $attendance = [];
    $r3 = $stmt->get_result();
    while ($row = $r3->fetch_assoc()) $attendance[$row['Prc_Date']] = $row;
    
    $daily_work = $p['DailyWorkMinutes'] ?? 480;
    $ot_factor = $p['OvertimeFactor'] ?? 1.40;
    $ot_holiday_factor = $p['OvertimeHolidayFactor'] ?? 1.96;
    
    $total_work = 0;
    $total_ot = 0;
    $total_ot_weighted = 0;
    $total_under = 0;
    $total_takhir = 0;
    $total_tajil = 0;
    $total_hour_leave = 0;
    $leave_days = 0;
    $present_days = 0;
    
    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str = sprintf('%04d/%02d/%02d', $year, $month, $d);
        $cal = $calendar[$date_str] ?? null;
        $att = $attendance[$date_str] ?? null;
        
        $is_holiday = false;
        $has_shift = false;
        
        if ($cal) {
            if ($cal['IsHoliday'] == 1) {
                $is_holiday = true;
            } elseif ($cal['ShiftID']) {
                $has_shift = true;
            }
        }
        
        // مقادیر مستقیم از دیتابیس
        if ($att) {
            $takhir = intval($att['Prc_TakhirLessWork'] ?? 0);
            $tajil = intval($att['Prc_TajilLessWork'] ?? 0);
            $h_eleave = intval($att['Prc_HourEleaveSalary'] ?? 0);
            $h_sleave = intval($att['Prc_HourSleaveSalary'] ?? 0);
            $h_nosal = intval($att['Prc_HourleaveNoSalary'] ?? 0);
            $hour_leave = $h_eleave + $h_sleave + $h_nosal;
            
            $total_takhir += $takhir;
            $total_tajil += $tajil;
            $total_hour_leave += $hour_leave;
        }
        
        $first_in = $att['Prc_FirstIn'] ?? -1000;
        $last_out = $att['Prc_LastOut'] ?? -1000;
        $worked = ($first_in > 0 && $last_out > 0) ? ($last_out - $first_in) : 0;
        
        if ($worked > 0) $present_days++;
        
        if ($is_holiday && $worked > 0) {
            $total_ot += $worked;
            $total_ot_weighted += $worked * $ot_holiday_factor;
        } elseif (!$is_holiday && $worked > 0) {
            if ($worked > $daily_work) {
                $ot = $worked - $daily_work;
                $total_ot += $ot;
                $total_ot_weighted += $ot * $ot_factor;
            } else {
                $total_under += $daily_work - $worked;
            }
        } elseif ($has_shift && $worked == 0) {
            $leave_days++;
            $total_under += $daily_work;
        }
    }
    
    $grand['ot'] += $total_ot;
    $grand['ot_weighted'] += $total_ot_weighted;
    $grand['under'] += $total_under;
    $grand['takhir'] += $total_takhir;
    $grand['tajil'] += $total_tajil;
    $grand['hour_leave'] += $total_hour_leave;
    $grand['present_days'] += $present_days;
    $grand['leave_days'] += $leave_days;
    
    $report_data[] = [
        'PCode' => $pcode,
        'Name' => $p['Name'],
        'Dept' => $p['Dept'],
        'GroupName' => $p['GroupName'] ?? '-',
        'total_work' => $total_work,
        'present_days' => $present_days,
        'total_ot' => $total_ot,
        'total_ot_weighted' => $total_ot_weighted,
        'total_under' => $total_under,
        'total_takhir' => $total_takhir,
        'total_tajil' => $total_tajil,
        'total_hour_leave' => $total_hour_leave,
        'leave_days' => $leave_days,
    ];
}
?>

<div class="card">
    <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:15px; text-align:center;">
        <div>
            <div style="font-size:12px; color:#666;">تعداد پرسنل</div>
            <div style="font-size:28px; color:#3498db; font-weight:bold;"><?php echo count($report_data); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع کل اضافه‌کاری</div>
            <div style="font-size:28px; color:#27ae60; font-weight:bold;"><?php echo minToDur($grand['ot']); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع کل تاخیر</div>
            <div style="font-size:28px; color:#f39c12; font-weight:bold;"><?php echo minToDur($grand['takhir']); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع مرخصی ساعتی</div>
            <div style="font-size:28px; color:#9b59b6; font-weight:bold;"><?php echo minToDur($grand['hour_leave']); ?></div>
        </div>
        <div>
            <div style="font-size:12px; color:#666;">جمع روزهای مرخصی</div>
            <div style="font-size:28px; color:#e74c3c; font-weight:bold;"><?php echo $grand['leave_days']; ?> روز</div>
        </div>
    </div>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>کد</th>
            <th>نام</th>
            <th>بخش</th>
            <th>گروه کاری</th>
            <th>روزهای حضور</th>
            <th>اضافه‌کاری</th>
            <th>اضافه‌کاری با ضریب</th>
            <th>کم‌کاری</th>
            <th>تاخیر</th>
            <th>تعجیل</th>
            <th>مرخصی ساعتی</th>
            <th>مرخصی روزانه</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($report_data as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['PCode']); ?></td>
            <td><a href="calculation.php?pcode=<?php echo $r['PCode']; ?>&month=<?php echo urlencode($month_filter); ?>" style="color:#3498db;"><?php echo htmlspecialchars($r['Name']); ?></a></td>
            <td><?php echo htmlspecialchars($r['Dept']); ?></td>
            <td><?php echo htmlspecialchars($r['GroupName']); ?></td>
            <td style="text-align:center;"><?php echo $r['present_days']; ?></td>
            <td style="color:#27ae60; font-weight:bold;"><?php echo minToHours($r['total_ot']); ?></td>
            <td style="color:#2ecc71;"><?php echo minToDur(round($r['total_ot_weighted'])); ?></td>
            <td style="color:#e74c3c;"><?php echo minToDur($r['total_under']); ?></td>
            <td style="color:#f39c12;"><?php echo minToDur($r['total_takhir']); ?></td>
            <td style="color:#d35400;"><?php echo minToDur($r['total_tajil']); ?></td>
            <td style="color:#9b59b6;"><?php echo minToDur($r['total_hour_leave']); ?></td>
            <td style="text-align:center; <?php echo ($r['leave_days'] > 0) ? 'color:#f39c12; font-weight:bold;' : ''; ?>"><?php echo $r['leave_days']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot style="background:#2c3e50; color:white; font-weight:bold;">
        <tr>
            <td colspan="4" style="text-align:left;">جمع کل:</td>
            <td style="text-align:center;"><?php echo $grand['present_days']; ?></td>
            <td><?php echo minToHours($grand['ot']); ?></td>
            <td><?php echo minToDur(round($grand['ot_weighted'])); ?></td>
            <td><?php echo minToDur($grand['under']); ?></td>
            <td><?php echo minToDur($grand['takhir']); ?></td>
            <td><?php echo minToDur($grand['tajil']); ?></td>
            <td><?php echo minToDur($grand['hour_leave']); ?></td>
            <td style="text-align:center;"><?php echo $grand['leave_days']; ?></td>
        </tr>
    </tfoot>
</table>
</div>

<div class="card" style="background:#fffbea; border:1px solid #f0e68c;">
    <h3 style="margin-top:0;">💡 راهنمای ستون‌ها</h3>
    <ul style="margin:0; padding-right:20px; line-height:1.8;">
        <li><strong>روزهای حضور:</strong> تعداد روزهایی که پرسنل تردد ثبت کرده.</li>
        <li><strong>اضافه‌کاری:</strong> مجموع دقایقی که بیشتر از کار روزانه کار کرده (به ساعت).</li>
        <li><strong>اضافه‌کاری با ضریب:</strong> با اعمال ضریب قانون (مثلاً 1.4).</li>
        <li><strong>کم‌کاری:</strong> مجموع دقایقی که کمتر از کار روزانه کار کرده.</li>
        <li><strong>تاخیر:</strong> مجموع تاخیرهای ورود در طول ماه (از ستون Prc_TakhirLessWork).</li>
        <li><strong>تعجیل:</strong> مجموع خروج‌های زودتر از موعد (از ستون Prc_TajilLessWork).</li>
        <li><strong>مرخصی ساعتی:</strong> مجموع مرخصی‌های ساعتی بین روز (استحقاقی + استعلاجی + بدون حقوق).</li>
        <li><strong>مرخصی روزانه:</strong> روزهایی که شیفت داشته ولی تردد ثبت نکرده.</li>
    </ul>
</div>

<?php renderFooter(); ?>