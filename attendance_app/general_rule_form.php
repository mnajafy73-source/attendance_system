<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = [
    'RuleName'=>'',
    'OvertimeBeforeShiftStart'=>'01:00',
    'OvertimeAfterShiftHours'=>'04:00',
    'OvertimeCrossDay'=>0,
    'RoundEntryEnabled'=>0,
    'RoundExitEnabled'=>0,
    'RoundBlockMinutes'=>15,
    'RoundThresholdMinutes'=>6,
    'NoShiftAsHoliday'=>1,
    'HourlyLeaveAsLeave'=>1,
    'ThursdayWorkHours'=>'04:00',
    'LunchEnabled'=>0,
    'LunchStart'=>'13:00',
    'LunchEnd'=>'13:30',
    'NightDinnerEnabled'=>1,
    'NightDinnerStart'=>'00:00',
    'NightDinnerEnd'=>'00:30',
    'MorningTeaEnabled'=>0,
    'MorningTeaStart'=>'10:00',
    'MorningTeaEnd'=>'10:15',
    'EveningTeaEnabled'=>0,
    'EveningTeaStart'=>'16:00',
    'EveningTeaEnd'=>'16:15'
];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM rules WHERE RuleID = ? AND IsGeneral = 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if (!$row) { header('Location: general_rules.php'); exit; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['RuleName'];
    $obs = $_POST['OvertimeBeforeShiftStart'];
    $oas = $_POST['OvertimeAfterShiftHours'];
    $ocd = ($_POST['OvertimeCrossDay'] == '1') ? 1 : 0;
    $ree = isset($_POST['RoundEntryEnabled']) ? 1 : 0;
    $rxe = isset($_POST['RoundExitEnabled']) ? 1 : 0;
    $rbm = intval($_POST['RoundBlockMinutes']);
    $rtm = intval($_POST['RoundThresholdMinutes']);
    $nsah = isset($_POST['NoShiftAsHoliday']) ? 1 : 0;
    $hlal = isset($_POST['HourlyLeaveAsLeave']) ? 1 : 0;
    $twh = $_POST['ThursdayWorkHours'];
    $lunch_en = isset($_POST['LunchEnabled']) ? 1 : 0;
    $lunch_s = $_POST['LunchStart'];
    $lunch_e = $_POST['LunchEnd'];
    $nde = isset($_POST['NightDinnerEnabled']) ? 1 : 0;
    $nds = $_POST['NightDinnerStart'];
    $nde_e = $_POST['NightDinnerEnd'];
    $mtea_en = isset($_POST['MorningTeaEnabled']) ? 1 : 0;
    $mtea_s = $_POST['MorningTeaStart'];
    $mtea_e = $_POST['MorningTeaEnd'];
    $etea_en = isset($_POST['EveningTeaEnabled']) ? 1 : 0;
    $etea_s = $_POST['EveningTeaStart'];
    $etea_e = $_POST['EveningTeaEnd'];
    
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE rules SET RuleName=?, OvertimeBeforeShiftStart=?, OvertimeAfterShiftHours=?, OvertimeCrossDay=?, RoundEntryEnabled=?, RoundExitEnabled=?, RoundBlockMinutes=?, RoundThresholdMinutes=?, NoShiftAsHoliday=?, HourlyLeaveAsLeave=?, ThursdayWorkHours=?, LunchEnabled=?, LunchStart=?, LunchEnd=?, NightDinnerEnabled=?, NightDinnerStart=?, NightDinnerEnd=?, MorningTeaEnabled=?, MorningTeaStart=?, MorningTeaEnd=?, EveningTeaEnabled=?, EveningTeaStart=?, EveningTeaEnd=? WHERE RuleID=?");
        $stmt->bind_param('sssiiiiiiississsissiisssissi',
            $name, $obs, $oas, $ocd, $ree, $rxe, $rbm, $rtm,
            $nsah, $hlal, $twh,
            $lunch_en, $lunch_s, $lunch_e,
            $nde, $nds, $nde_e,
            $mtea_en, $mtea_s, $mtea_e,
            $etea_en, $etea_s, $etea_e, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO rules (RuleName, IsGeneral, OvertimeBeforeShiftStart, OvertimeAfterShiftHours, OvertimeCrossDay, RoundEntryEnabled, RoundExitEnabled, RoundBlockMinutes, RoundThresholdMinutes, NoShiftAsHoliday, HourlyLeaveAsLeave, ThursdayWorkHours, LunchEnabled, LunchStart, LunchEnd, NightDinnerEnabled, NightDinnerStart, NightDinnerEnd, MorningTeaEnabled, MorningTeaStart, MorningTeaEnd, EveningTeaEnabled, EveningTeaStart, EveningTeaEnd, EntryToleranceMinutes, ExitToleranceMinutes, DailyWorkMinutes) VALUES (?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 480)");
        $stmt->bind_param('sssiiiiiiississsissiisssiss',
            $name, $obs, $oas, $ocd, $ree, $rxe, $rbm, $rtm,
            $nsah, $hlal, $twh,
            $lunch_en, $lunch_s, $lunch_e,
            $nde, $nds, $nde_e,
            $mtea_en, $mtea_s, $mtea_e,
            $etea_en, $etea_s, $etea_e);
    }
    $stmt->execute();
    header('Location: general_rules.php?msg=saved');
    exit;
}

renderHeader($isEdit ? 'ویرایش قانون کلی' : 'افزودن قانون کلی');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش قانون کلی' : '➕ افزودن قانون کلی'; ?></h1>

<div class="card" style="background:#f0e6ff; border:2px solid #9b59b6;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <strong style="color:#7d3c98; font-size:15px;">📅 استثناها</strong>
            <p style="margin:5px 0 0; color:#666; font-size:13px;">برای بازه‌های خاص (مثل تعطیلات) قانون متفاوتی اعمال کن.</p>
        </div>
        <button type="button" onclick="openExceptions()" class="btn" style="background:#9b59b6; color:white; padding:10px 20px; font-size:14px;">🔧 مدیریت استثناها</button>
    </div>
</div>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>نام قانون کلی</label>
        <input type="text" name="RuleName" value="<?php echo htmlspecialchars($row['RuleName']); ?>" required placeholder="مثلاً: قوانین عادی">
    </div>

    <h3 style="color:#9b59b6;">⏰ تنظیمات زمانی</h3>
    <div style="background:#f8f0ff; padding:15px; border-radius:6px; border:1px solid #d8b8ff;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
            <div class="form-group">
                <label>۱. چند ساعت قبل از شیفت حق اضافه‌کار دارد؟</label>
                <input type="text" name="OvertimeBeforeShiftStart" value="<?php echo htmlspecialchars($row['OvertimeBeforeShiftStart']); ?>">
            </div>
            <div class="form-group">
                <label>۲. چند ساعت بعد از شیفت حق اضافه‌کار دارد؟</label>
                <input type="text" name="OvertimeAfterShiftHours" value="<?php echo htmlspecialchars($row['OvertimeAfterShiftHours']); ?>">
            </div>
        </div>
        <div class="form-group" style="margin-top:15px; background:white; padding:12px; border-radius:4px;">
            <label>۳. اگر اضافه‌کاری به روز بعد کشید، حساب شود؟</label>
            <select name="OvertimeCrossDay">
                <option value="0" <?php echo (empty($row['OvertimeCrossDay'])) ? 'selected' : ''; ?>>❌ خیر</option>
                <option value="1" <?php echo (!empty($row['OvertimeCrossDay'])) ? 'selected' : ''; ?>>✅ بله</option>
            </select>
        </div>
    </div>

    <h3 style="color:#2980b9;">📏 محاسبه ساعت کارکرد پنجشنبه</h3>
    <div style="background:#e8f4ff; padding:15px; border-radius:6px; border:1px solid #a8d4ff;">
        <div class="form-group">
            <label>روز پنجشنبه معادل چند ساعت کارکرد محاسبه شود؟</label>
            <input type="text" name="ThursdayWorkHours" value="<?php echo htmlspecialchars($row['ThursdayWorkHours']); ?>" placeholder="04:00">
            <small style="color:#666;">کارکرد مورد انتظار روز پنجشنبه (برای محاسبه اضافه‌کاری/کم‌کاری)</small>
        </div>
    </div>

    <h3 style="color:#e67e22;">🔄 رند کردن ساعت</h3>
    <div style="background:#fff8e8; padding:15px; border-radius:6px; border:1px solid #ffd88a;">
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="RoundEntryEnabled" value="1" <?php echo !empty($row['RoundEntryEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>🟢 رند کردن ساعت ورود</strong>
            </label>
        </div>
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="RoundExitEnabled" value="1" <?php echo !empty($row['RoundExitEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>🔴 رند کردن ساعت خروج</strong>
            </label>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div class="form-group"><label>بازه رند (دقیقه)</label><input type="number" name="RoundBlockMinutes" value="<?php echo $row['RoundBlockMinutes']; ?>"></div>
            <div class="form-group"><label>آستانه رند (دقیقه)</label><input type="number" name="RoundThresholdMinutes" value="<?php echo $row['RoundThresholdMinutes']; ?>"></div>
        </div>
    </div>

    <h3 style="color:#3498db;">📅 محاسبه روز بدون تردد</h3>
    <div style="background:#e8f4ff; padding:15px; border-radius:6px; border:1px solid #a8d4ff;">
        <div class="form-group">
            <label style="display:flex; align-items:flex-start; cursor:pointer;">
                <input type="checkbox" name="NoShiftAsHoliday" value="1" <?php echo !empty($row['NoShiftAsHoliday']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px; margin-top:3px;">
                <span>
                    <strong>⚪ روزی که شیفت تعریف نشده، تعطیل حساب شود</strong>
                    <br><small style="color:#666;">اگه تیک بخوره، روزهای بدون شیفت تعطیل حساب می‌شن و اگه تردد داشته باشن، اضافه‌کار محاسبه می‌شه.</small>
                </span>
            </label>
        </div>
        <div class="form-group" style="margin-top:15px;">
            <label style="display:flex; align-items:flex-start; cursor:pointer;">
                <input type="checkbox" name="HourlyLeaveAsLeave" value="1" <?php echo !empty($row['HourlyLeaveAsLeave']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px; margin-top:3px;">
                <span><strong>🔵 مرخصی‌های ساعتی بین روز، به عنوان مرخصی حساب شود</strong></span>
            </label>
        </div>
    </div>

    <h3 style="color:#e74c3c;">🍽️ ناهار (شیفت‌های روزکار)</h3>
    <div style="background:#ffe8e8; padding:15px; border-radius:6px; border:1px solid #ffb8b8;">
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="LunchEnabled" value="1" <?php echo !empty($row['LunchEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>فعال باشه</strong>
            </label>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div class="form-group"><label>ساعت شروع ناهار</label><input type="text" name="LunchStart" value="<?php echo htmlspecialchars($row['LunchStart']); ?>"></div>
            <div class="form-group"><label>ساعت پایان ناهار</label><input type="text" name="LunchEnd" value="<?php echo htmlspecialchars($row['LunchEnd']); ?>"></div>
        </div>
        <small style="color:#666; display:block;">💡 فقط اگه ناهار داخل محدوده اضافه‌کاری باشه، از اضافه‌کار کسر می‌شه.</small>
    </div>

    <h3 style="color:#34495e;">🌙 شام (شیفت‌های شب‌کار)</h3>
    <div style="background:#e8ecf5; padding:15px; border-radius:6px; border:1px solid #a8b8d8;">
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="NightDinnerEnabled" value="1" <?php echo !empty($row['NightDinnerEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>فعال باشه</strong>
            </label>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div class="form-group"><label>ساعت شروع شام</label><input type="text" name="NightDinnerStart" value="<?php echo htmlspecialchars($row['NightDinnerStart']); ?>" placeholder="00:00"></div>
            <div class="form-group"><label>ساعت پایان شام</label><input type="text" name="NightDinnerEnd" value="<?php echo htmlspecialchars($row['NightDinnerEnd']); ?>" placeholder="00:30"></div>
        </div>
        <small style="color:#666; display:block;">💡 فقط اگه شام داخل محدوده اضافه‌کاری باشه، از اضافه‌کار کسر می‌شه.</small>
    </div>

    <h3 style="color:#27ae60;">☕ چای صبح</h3>
    <div style="background:#e8ffe8; padding:15px; border-radius:6px; border:1px solid #a8e8a8;">
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="MorningTeaEnabled" value="1" <?php echo !empty($row['MorningTeaEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>فعال باشه</strong>
            </label>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div class="form-group"><label>ساعت شروع</label><input type="text" name="MorningTeaStart" value="<?php echo htmlspecialchars($row['MorningTeaStart']); ?>"></div>
            <div class="form-group"><label>ساعت پایان</label><input type="text" name="MorningTeaEnd" value="<?php echo htmlspecialchars($row['MorningTeaEnd']); ?>"></div>
        </div>
    </div>

    <h3 style="color:#f39c12;">🍵 چای عصر</h3>
    <div style="background:#fff5e8; padding:15px; border-radius:6px; border:1px solid #ffd8a8;">
        <div class="form-group">
            <label style="display:flex; align-items:center; cursor:pointer;">
                <input type="checkbox" name="EveningTeaEnabled" value="1" <?php echo !empty($row['EveningTeaEnabled']) ? 'checked' : ''; ?> style="width:auto; margin-left:10px;">
                <strong>فعال باشه</strong>
            </label>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-top:15px;">
            <div class="form-group"><label>ساعت شروع</label><input type="text" name="EveningTeaStart" value="<?php echo htmlspecialchars($row['EveningTeaStart']); ?>"></div>
            <div class="form-group"><label>ساعت پایان</label><input type="text" name="EveningTeaEnd" value="<?php echo htmlspecialchars($row['EveningTeaEnd']); ?>"></div>
        </div>
    </div>

    <div style="margin-top:20px;">
        <button type="submit" class="btn btn-success">💾 ذخیره</button>
        <a href="general_rules.php" class="btn btn-danger">انصراف</a>
    </div>
</form>
</div>

<div id="exceptionsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:white; width:90%; max-width:900px; height:85vh; border-radius:8px; display:flex; flex-direction:column;">
        <div style="padding:15px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; background:#9b59b6; color:white; border-radius:8px 8px 0 0;">
            <h3 style="margin:0;">📅 مدیریت استثناها</h3>
            <button onclick="closeExceptions()" style="background:transparent; border:none; color:white; font-size:24px; cursor:pointer;">×</button>
        </div>
        <iframe id="exceptionsFrame" src="" style="flex:1; border:none; border-radius:0 0 8px 8px;"></iframe>
    </div>
</div>

<script>
function openExceptions() {
    document.getElementById('exceptionsFrame').src = 'exceptions.php';
    document.getElementById('exceptionsModal').style.display = 'flex';
}
function closeExceptions() {
    document.getElementById('exceptionsModal').style.display = 'none';
    document.getElementById('exceptionsFrame').src = '';
}
</script>

<?php renderFooter(); ?>