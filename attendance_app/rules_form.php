<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = [
    'RuleName'=>'', 'Description'=>'', 'MonthlyLeaveHours'=>'17:30',
    'LeaveHoursNormal'=>'08:00',
    'LeaveHoursThursday'=>'04:00',
    'LeaveHoursFriday'=>'08:00',
    'ShiftNoAttendanceAsLeave'=>1
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
    $name = $_POST['RuleName'];
    $desc = $_POST['Description'] ?? '';
    $mlh = $_POST['MonthlyLeaveHours'];
    $lhn = $_POST['LeaveHoursNormal'];
    $lht = $_POST['LeaveHoursThursday'];
    $lhf = $_POST['LeaveHoursFriday'];
    $snal = isset($_POST['ShiftNoAttendanceAsLeave']) ? 1 : 0;
    
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE rules SET RuleName=?, Description=?, MonthlyLeaveHours=?, LeaveHoursNormal=?, LeaveHoursThursday=?, LeaveHoursFriday=?, ShiftNoAttendanceAsLeave=? WHERE RuleID=?");
        $stmt->bind_param('ssssssii', $name, $desc, $mlh, $lhn, $lht, $lhf, $snal, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO rules (RuleName, Description, MonthlyLeaveHours, LeaveHoursNormal, LeaveHoursThursday, LeaveHoursFriday, ShiftNoAttendanceAsLeave, IsGeneral) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
        $stmt->bind_param('ssssssi', $name, $desc, $mlh, $lhn, $lht, $lhf, $snal);
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
        <input type="text" name="RuleName" value="<?php echo htmlspecialchars($row['RuleName']); ?>" required placeholder="مثلاً: قانون نگهبان شب">
    </div>
    <div class="form-group">
        <label>توضیحات (اختیاری)</label>
        <textarea name="Description" rows="2"><?php echo htmlspecialchars($row['Description'] ?? ''); ?></textarea>
    </div>

    <h3 style="color:#9b59b6;">🏖️ مرخصی ماهانه</h3>
    <div style="background:#f8f0ff; padding:15px; border-radius:6px; border:1px solid #d8b8ff; margin-bottom:15px;">
        <div class="form-group">
            <label>مرخصی ماهانه (به ساعت)</label>
            <input type="text" name="MonthlyLeaveHours" value="<?php echo htmlspecialchars($row['MonthlyLeaveHours']); ?>" placeholder="17:30" required>
            <small style="color:#666;">مقدار کل مرخصی ماهانه. مثلاً 17:30 = ۱۷ ساعت و ۳۰ دقیقه</small>
        </div>
    </div>

    <h3 style="color:#e74c3c;">📅 محاسبه روزهای بدون تردد</h3>
    <div style="background:#ffe8e8; padding:15px; border-radius:6px; border:1px solid #ffb8b8;">
        <div class="form-group" style="margin-bottom:20px;">
            <label style="display:flex; align-items:flex-start; cursor:pointer; background:white; padding:12px; border-radius:4px;">
                <input type="checkbox" name="ShiftNoAttendanceAsLeave" value="1" <?php echo !empty($row['ShiftNoAttendanceAsLeave']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px; margin-top:3px;">
                <span>
                    <strong>🟢 روزی که شیفت تعریف شده ولی فرد تردد نداشته، مرخصی حساب شود</strong>
                    <br><small style="color:#666;">مقدار مرخصی از فیلدهای زیر خونده می‌شه.</small>
                </span>
            </label>
        </div>
        
        <p style="margin:0 0 15px; color:#666; font-size:13px;">
            به ازای هر روزی که شیفت داشت ولی نیامد، به اندازه مقدار زیر مرخصی حساب می‌شه:
        </p>
        
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>روزهای عادی هفته</label>
                <input type="text" name="LeaveHoursNormal" value="<?php echo htmlspecialchars($row['LeaveHoursNormal']); ?>" placeholder="08:00">
                <small style="color:#666;">پیش‌فرض ۸ ساعت</small>
            </div>
            <div class="form-group">
                <label>روزهای پنجشنبه</label>
                <input type="text" name="LeaveHoursThursday" value="<?php echo htmlspecialchars($row['LeaveHoursThursday']); ?>" placeholder="04:00">
                <small style="color:#666;">پیش‌فرض ۴ ساعت</small>
            </div>
            <div class="form-group">
                <label>روزهای جمعه</label>
                <input type="text" name="LeaveHoursFriday" value="<?php echo htmlspecialchars($row['LeaveHoursFriday']); ?>" placeholder="08:00">
                <small style="color:#666;">پیش‌فرض ۸ ساعت</small>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-success">💾 ذخیره</button>
        <a href="rules.php" class="btn btn-danger">انصراف</a>
    </div>
</form>
</div>
<?php renderFooter(); ?>