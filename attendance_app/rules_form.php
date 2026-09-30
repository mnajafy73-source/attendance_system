<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = [
    'RuleName'=>'', 'Description'=>'',
    'DailyWorkMinutes'=>480, 'MinWorkForOvertime'=>0,
    'UnderworkStart'=>'17:00', 'UnderworkFactor'=>1.00,
    'OvertimeFactor'=>1.40, 'OvertimeHolidayFactor'=>1.96, 'OvertimeMaxMinutes'=>240, 'MinOvertimeMinutes'=>0,
    'LeaveFactor'=>1.00, 'LeaveBalanceDays'=>26,
    'MissionFactor'=>1.00,
    'NightWorkFactor'=>1.35, 'FridayWorkFactor'=>1.96, 'HolidayWorkFactor'=>1.96
];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM rules WHERE RuleID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['RuleName','Description','DailyWorkMinutes','MinWorkForOvertime','UnderworkStart','UnderworkFactor','OvertimeFactor','OvertimeHolidayFactor','OvertimeMaxMinutes','MinOvertimeMinutes','LeaveFactor','LeaveBalanceDays','MissionFactor','NightWorkFactor','FridayWorkFactor','HolidayWorkFactor'];
    $vals = [];
    foreach ($fields as $f) $vals[$f] = $_POST[$f] ?? '';
    
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE rules SET RuleName=?, Description=?, DailyWorkMinutes=?, MinWorkForOvertime=?, UnderworkStart=?, UnderworkFactor=?, OvertimeFactor=?, OvertimeHolidayFactor=?, OvertimeMaxMinutes=?, MinOvertimeMinutes=?, LeaveFactor=?, LeaveBalanceDays=?, MissionFactor=?, NightWorkFactor=?, FridayWorkFactor=?, HolidayWorkFactor=? WHERE RuleID=?");
        $stmt->bind_param('ssiissddiiddidddi',
            $vals['RuleName'],$vals['Description'],$vals['DailyWorkMinutes'],$vals['MinWorkForOvertime'],$vals['UnderworkStart'],$vals['UnderworkFactor'],$vals['OvertimeFactor'],$vals['OvertimeHolidayFactor'],$vals['OvertimeMaxMinutes'],$vals['MinOvertimeMinutes'],$vals['LeaveFactor'],$vals['LeaveBalanceDays'],$vals['MissionFactor'],$vals['NightWorkFactor'],$vals['FridayWorkFactor'],$vals['HolidayWorkFactor'],$id);
    } else {
        $stmt = $conn->prepare("INSERT INTO rules (RuleName, Description, DailyWorkMinutes, MinWorkForOvertime, UnderworkStart, UnderworkFactor, OvertimeFactor, OvertimeHolidayFactor, OvertimeMaxMinutes, MinOvertimeMinutes, LeaveFactor, LeaveBalanceDays, MissionFactor, NightWorkFactor, FridayWorkFactor, HolidayWorkFactor) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('ssiissddiiddiddd',
            $vals['RuleName'],$vals['Description'],$vals['DailyWorkMinutes'],$vals['MinWorkForOvertime'],$vals['UnderworkStart'],$vals['UnderworkFactor'],$vals['OvertimeFactor'],$vals['OvertimeHolidayFactor'],$vals['OvertimeMaxMinutes'],$vals['MinOvertimeMinutes'],$vals['LeaveFactor'],$vals['LeaveBalanceDays'],$vals['MissionFactor'],$vals['NightWorkFactor'],$vals['FridayWorkFactor'],$vals['HolidayWorkFactor']);
    }
    $stmt->execute();
    header('Location: rules.php?msg=saved');
    exit;
}

renderHeader($isEdit ? 'ویرایش قانون' : 'افزودن قانون');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش قانون' : '➕ افزودن قانون'; ?></h1>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>نام قانون</label>
        <input type="text" name="RuleName" value="<?php echo htmlspecialchars($row['RuleName']); ?>" required placeholder="مثلاً: قانون عادی">
    </div>
    <div class="form-group">
        <label>توضیحات</label>
        <textarea name="Description" rows="2"><?php echo htmlspecialchars($row['Description']); ?></textarea>
    </div>

    <h3>📊 قوانین کارکرد</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
        <div class="form-group"><label>کار روزانه استاندارد (دقیقه)</label><input type="number" name="DailyWorkMinutes" value="<?php echo $row['DailyWorkMinutes']; ?>"><small>مثلاً 480 = 8 ساعت</small></div>
        <div class="form-group"><label>حداقل کار برای تعلق اضافه‌کاری (دقیقه)</label><input type="number" name="MinWorkForOvertime" value="<?php echo $row['MinWorkForOvertime']; ?>"></div>
    </div>

    <h3>📉 قوانین کم‌کاری</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
        <div class="form-group"><label>ساعت شروع محاسبه کم‌کاری</label><input type="text" name="UnderworkStart" value="<?php echo $row['UnderworkStart']; ?>" placeholder="17:00"></div>
        <div class="form-group"><label>ضریب کم‌کاری</label><input type="text" name="UnderworkFactor" value="<?php echo $row['UnderworkFactor']; ?>"><small>مثلاً 1 = کسر کامل</small></div>
    </div>

    <h3>📈 قوانین اضافه‌کاری</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
        <div class="form-group"><label>ضریب اضافه‌کاری عادی</label><input type="text" name="OvertimeFactor" value="<?php echo $row['OvertimeFactor']; ?>"><small>مثلاً 1.40</small></div>
        <div class="form-group"><label>ضریب اضافه‌کاری تعطیل/جمعه</label><input type="text" name="OvertimeHolidayFactor" value="<?php echo $row['OvertimeHolidayFactor']; ?>"><small>مثلاً 1.96</small></div>
        <div class="form-group"><label>حداکثر اضافه‌کاری روزانه (دقیقه)</label><input type="number" name="OvertimeMaxMinutes" value="<?php echo $row['OvertimeMaxMinutes']; ?>"><small>مثلاً 240 = 4 ساعت</small></div>
        <div class="form-group"><label>حداقل اضافه‌کاری برای تعلق (دقیقه)</label><input type="number" name="MinOvertimeMinutes" value="<?php echo $row['MinOvertimeMinutes']; ?>"></div>
    </div>

    <h3>🏖️ قوانین مرخصی</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
        <div class="form-group"><label>ضریب مرخصی</label><input type="text" name="LeaveFactor" value="<?php echo $row['LeaveFactor']; ?>"></div>
        <div class="form-group"><label>مانده مرخصی سالانه (روز)</label><input type="number" name="LeaveBalanceDays" value="<?php echo $row['LeaveBalanceDays']; ?>"></div>
    </div>

    <h3>✈️ قوانین ماموریت</h3>
    <div class="form-group"><label>ضریب ماموریت</label><input type="text" name="MissionFactor" value="<?php echo $row['MissionFactor']; ?>"></div>

    <h3>⚙️ قوانین متفرقه</h3>
    <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
        <div class="form-group"><label>ضریب شب‌کاری</label><input type="text" name="NightWorkFactor" value="<?php echo $row['NightWorkFactor']; ?>"></div>
        <div class="form-group"><label>ضریب جمعه‌کاری</label><input type="text" name="FridayWorkFactor" value="<?php echo $row['FridayWorkFactor']; ?>"></div>
        <div class="form-group"><label>ضریب تعطیل‌کاری</label><input type="text" name="HolidayWorkFactor" value="<?php echo $row['HolidayWorkFactor']; ?>"></div>
    </div>

    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="rules.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>