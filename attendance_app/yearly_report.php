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

$pcode_filter = $_GET['pcode'] ?? '';
$year_filter = $_GET['year'] ?? '1405';

renderHeader('گزارش سالانه');

// لیست پرسنل برای dropdown
$personnel_list = [];
$res = $conn->query("SELECT PCode, Name FROM personnel ORDER BY CAST(PCode AS UNSIGNED)");
if ($res) { while ($r = $res->fetch_assoc()) $personnel_list[] = $r; }

$month_names = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
?>

<h1>📆 گزارش سالانه پرسنل</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div>
            <label>👤 انتخاب پرسنل</label>
            <select name="pcode" required>
                <option value="">-- انتخاب کنید --</option>
                <?php foreach ($personnel_list as $p): ?>
                    <option value="<?php echo $p['PCode']; ?>" <?php echo ($pcode_filter == $p['PCode']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['Name']); ?> (<?php echo $p['PCode']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>📅 سال</label>
            <input type="text" name="year" value="<?php echo htmlspecialchars($year_filter); ?>" placeholder="1405">
        </div>
        <div><button type="submit" class="btn btn-primary">🔍 نمایش گزارش</button></div>
        <div><a href="yearly_report.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
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

// تقویم شیفت این گروه برای کل سال
$calendar = [];
if ($person['GroupID']) {
    $like = $year_filter . '/%';
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

// ترددهای کل سال
$like = $year_filter . '/%';
$stmt = $conn->prepare("SELECT * FROM attendance WHERE LPAD(Prc_PCode, 8, '0') = LPAD(?, 8, '0') AND Prc_Date LIKE ? AND RIGHT(Prc_Date, 2) != '00' ORDER BY Prc_Date");
$stmt->bind_param('ss', $pcode_filter, $like);
$stmt->execute();
$attendance = [];
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $attendance[$r['Prc_Date']] = $r;

$daily_work = $person['DailyWorkMinutes'] ?? 480;
$ot_factor = $person['OvertimeFactor'] ?? 1.40;
$ot_holiday_factor = $person['OvertimeHolidayFactor'] ?? 1.96;

// محاسبه ماه‌به‌ماه
$monthly_data = [];
$grand = [
    'work' => 0, 'ot' => 0, 'ot_weighted' => 0, 'under' => 0,
    'takhir' => 0, 'tajil' => 0, 'hour_leave' => 0,
    'present_days' => 0, 'leave_days' => 0, 'holiday_work' => 0
];

for ($m = 1; $m <= 12; $m++) {
    $days_in_month = ($m <= 6) ? 31 : (($m <= 11) ? 30 : 29);
    
    $m_work = 0; $m_ot = 0; $m_ot_w = 0; $m_under = 0;
    $m_takhir = 0; $m_tajil = 0; $m_hour_leave = 0;
    $m_present = 0; $m_leave = 0; $m_holiday_work = 0;
    
    for ($d = 1; $d <= $days_in_month; $d++) {
        $date_str = sprintf('%04d/%02d/%02d', $year_filter, $m, $d);
        $cal = $calendar[$date_str] ?? null;
        $att = $attendance[$date_str] ?? null;
        
        $is_holiday = false;
        $has_shift = false;
        $expected = $daily_work;
        
        if ($cal) {
            if ($cal['IsHoliday'] == 1) {
                $is_holiday = true;
            } elseif ($cal['ShiftID'] && isset($shifts[$cal['ShiftID']])) {
                $has_shift = true;
                $s = $shifts[$cal['ShiftID']];
                $sh_start = timeToMinHelper($s['Start1']);
                $sh_end = timeToMinHelper($s['End1']);
                if ($sh_start > 0 && $sh_end > 0 && $sh_end > $sh_start) {
                    $expected = $sh_end - $sh_start;
                }
            }
        }
        
        if ($att) {
            $m_takhir += intval($att['Prc_TakhirLessWork'] ?? 0);
            $m_tajil += intval($att['Prc_TajilLessWork'] ?? 0);
            $h_eleave = intval($att['Prc_HourEleaveSalary'] ?? 0);
            $h_sleave = intval($att['Prc_HourSleaveSalary'] ?? 0);
            $h_nosal = intval($att['Prc_HourleaveNoSalary'] ?? 0);
            $m_hour_leave += ($h_eleave + $h_sleave + $h_nosal);
        }
        
        $first_in = $att['Prc_FirstIn'] ?? -1000;
        $last_out = $att['Prc_LastOut'] ?? -1000;
        $worked = ($first_in > 0 && $last_out > 0) ? ($last_out - $first_in) : 0;
        
        if ($worked > 0) $m_present++;
        
        if ($is_holiday && $worked > 0) {
            $m_ot += $worked;
            $m_ot_w += $worked * $ot_holiday_factor;
            $m_holiday_work += $worked;
        } elseif (!$is_holiday && $worked > 0) {
            $m_work += $worked;
            if ($worked > $expected) {
                $ot = $worked - $expected;
                $m_ot += $ot;
                $m_ot_w += $ot * $ot_factor;
            } else {
                $m_under += $expected - $worked;
            }
        } elseif ($has_shift && $worked == 0) {
            $m_leave++;
            $m_under += $expected;
        }
    }
    
    $monthly_data[$m] = [
        'work' => $m_work, 'ot' => $m_ot, 'ot_w' => $m_ot_w, 'under' => $m_under,
        'takhir' => $m_takhir, 'tajil' => $m_tajil, 'hour_leave' => $m_hour_leave,
        'present' => $m_present, 'leave' => $m_leave, 'holiday_work' => $m_holiday_work
    ];
    
    $grand['work'] += $m_work;
    $grand['ot'] += $m_ot;
    $grand['ot_weighted'] += $m_ot_w;
    $grand['under'] += $m_under;
    $grand['takhir'] += $m_takhir;
    $grand['tajil'] += $m_tajil;
    $grand['hour_leave'] += $m_hour_leave;
    $grand['present_days'] += $m_present;
    $grand['leave_days'] += $m_leave;
    $grand['holiday_work'] += $m_holiday_work;
}

function timeToMinHelper($t) {
    if (empty($t)) return -1;
    $plus = (substr($t, -1) === '+') ? 1440 : 0;
    $t = rtrim($t, '+');
    $p = explode(':', $t);
    if (count($p) != 2) return -1;
    return intval($p[0]) * 60 + intval($p[1]) + $plus;
}
?>

<div class="card">
    <h2>👤 <?php echo htmlspecialchars($person['Name']); ?> (کد: <?php echo $person['PCode']; ?>)</h2>
    <p>
        <strong>گروه کاری:</strong> <?php echo $person['GroupName'] ?: 'تعریف نشده'; ?> &nbsp;|&nbsp;
        <strong>قانون:</strong> <?php echo $person['RuleName'] ?: 'تعریف نشده'; ?> &nbsp;|&nbsp;
        <strong>کار روزانه:</strong> <?php echo minToDur($daily_work); ?> &nbsp;|&nbsp;
        <strong>ضریب اضافه‌کاری:</strong> <?php echo $ot_factor; ?> &nbsp;|&nbsp;
        <strong>سال:</strong> <?php echo htmlspecialchars($year_filter); ?>
    </p>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <thead>
        <tr>
            <th>ماه</th>
            <th>روزهای حضور</th>
            <th>کارکرد</th>
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
        <?php for ($m = 1; $m <= 12; $m++):
            $d = $monthly_data[$m];
            $has_data = ($d['present'] > 0 || $d['leave'] > 0 || $d['takhir'] > 0 || $d['tajil'] > 0);
        ?>
        <tr style="<?php echo !$has_data ? 'opacity:0.5;' : ''; ?>">
            <td><strong><?php echo $month_names[$m-1]; ?></strong></td>
            <td style="text-align:center;"><?php echo $d['present']; ?></td>
            <td><?php echo minToDur($d['work']); ?></td>
            <td style="color:#27ae60;"><?php echo minToDur($d['ot']); ?></td>
            <td style="color:#2ecc71;"><?php echo minToDur(round($d['ot_w'])); ?></td>
            <td style="color:#e74c3c;"><?php echo minToDur($d['under']); ?></td>
            <td style="color:#f39c12;"><?php echo minToDur($d['takhir']); ?></td>
            <td style="color:#d35400;"><?php echo minToDur($d['tajil']); ?></td>
            <td style="color:#9b59b6;"><?php echo minToDur($d['hour_leave']); ?></td>
            <td style="text-align:center; <?php echo ($d['leave'] > 0) ? 'color:#f39c12; font-weight:bold;' : ''; ?>"><?php echo $d['leave']; ?></td>
        </tr>
        <?php endfor; ?>
    </tbody>
    <tfoot style="background:#2c3e50; color:white; font-weight:bold;">
        <tr>
            <td>جمع کل سال</td>
            <td style="text-align:center;"><?php echo $grand['present_days']; ?></td>
            <td><?php echo minToDur($grand['work']); ?></td>
            <td><?php echo minToDur($grand['ot']); ?></td>
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

<div class="card" style="background:#2c3e50; color:white;">
    <h2 style="margin-top:0; color:white;">📊 خلاصه سالانه <?php echo htmlspecialchars($year_filter); ?></h2>
    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:15px;">
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل کارکرد</div>
            <div style="font-size:24px;"><?php echo minToDur($grand['work']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل اضافه‌کاری</div>
            <div style="font-size:24px; color:#2ecc71;"><?php echo minToDur($grand['ot']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">اضافه‌کاری با ضریب</div>
            <div style="font-size:24px; color:#f39c12;"><?php echo minToDur(round($grand['ot_weighted'])); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل کم‌کاری</div>
            <div style="font-size:24px; color:#e74c3c;"><?php echo minToDur($grand['under']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل تاخیر</div>
            <div style="font-size:24px; color:#f39c12;"><?php echo minToDur($grand['takhir']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">کل تعجیل</div>
            <div style="font-size:24px; color:#d35400;"><?php echo minToDur($grand['tajil']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">مرخصی ساعتی</div>
            <div style="font-size:24px; color:#9b59b6;"><?php echo minToDur($grand['hour_leave']); ?></div>
        </div>
        <div style="text-align:center;">
            <div style="font-size:12px; color:#95a5a6;">مرخصی روزانه</div>
            <div style="font-size:24px; color:#e67e22;"><?php echo $grand['leave_days']; ?> روز</div>
        </div>
    </div>
</div>

<div class="card" style="background:#fffbea; border:1px solid #f0e68c;">
    <h3 style="margin-top:0;">💡 راهنما</h3>
    <ul style="margin:0; padding-right:20px; line-height:1.8;">
        <li><strong>روزهای حضور:</strong> تعداد روزهایی که پرسنل تردد ثبت کرده.</li>
        <li><strong>کارکرد:</strong> مجموع ساعات کار در روزهای عادی.</li>
        <li><strong>اضافه‌کاری:</strong> ساعات بیشتر از کار روزانه شیفت.</li>
        <li><strong>اضافه‌کاری با ضریب:</strong> با اعمال ضریب قانون (عادی ۱.۴ و تعطیل ۱.۹۶).</li>
        <li><strong>کم‌کاری:</strong> ساعات کمتر از کار روزانه شیفت.</li>
        <li><strong>تاخیر:</strong> مجموع تاخیرهای ورود در ماه.</li>
        <li><strong>تعجیل:</strong> مجموع خروج‌های زودتر از موعد.</li>
        <li><strong>مرخصی ساعتی:</strong> مجموع مرخصی‌های ساعتی بین روز.</li>
        <li><strong>مرخصی روزانه:</strong> روزهایی که شیفت داشته ولی تردد ثبت نکرده.</li>
    </ul>
</div>

<?php renderFooter(); ?>