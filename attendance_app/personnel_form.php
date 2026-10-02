<?php
include 'config.php';
include 'layout.php';

$pcode = $_GET['pcode'] ?? '';
$row = ['PCode'=>'', 'Name'=>'', 'Dept'=>'', 'WorkGroup'=>'', 'RuleID'=>'', 'WorkGroupID'=>'', 'GeneralRuleID'=>''];
$isEdit = false;
$error = '';

if (!empty($pcode)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM personnel WHERE PCode = ?");
    $stmt->bind_param('s', $pcode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Name'];
    $dept = $_POST['Dept'];
    $wg = $_POST['WorkGroup'];
    $rule = ($_POST['RuleID'] !== '') ? intval($_POST['RuleID']) : null;
    $general_rule = ($_POST['GeneralRuleID'] !== '') ? intval($_POST['GeneralRuleID']) : null;
    
    // چک کن هر دو قانون انتخاب شدن
    if (empty($rule)) {
        $error = '⚠️ لطفاً یک قانون اختصاصی انتخاب کن.';
    } elseif (empty($general_rule)) {
        $error = '⚠️ لطفاً یک قانون کلی انتخاب کن.';
    } else {
        $wg_id = null;
        if (!empty($wg)) {
            $s = $conn->prepare("SELECT GroupID FROM work_groups WHERE GroupName = ?");
            $s->bind_param('s', $wg);
            $s->execute();
            $r = $s->get_result()->fetch_assoc();
            if ($r) $wg_id = $r['GroupID'];
        }
        
        if ($isEdit) {
            $stmt = $conn->prepare("UPDATE personnel SET Name=?, Dept=?, WorkGroup=?, RuleID=?, WorkGroupID=?, GeneralRuleID=? WHERE PCode=?");
            $stmt->bind_param('sssiiis', $name, $dept, $wg, $rule, $wg_id, $general_rule, $pcode);
        } else {
            $np = $_POST['PCode'];
            $stmt = $conn->prepare("INSERT INTO personnel (PCode, Name, Dept, WorkGroup, RuleID, WorkGroupID, GeneralRuleID) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('ssssiii', $np, $name, $dept, $wg, $rule, $wg_id, $general_rule);
        }
        $stmt->execute();
        header('Location: personnel.php?msg=saved');
        exit;
    }
}

$departments = $conn->query("SELECT * FROM departments ORDER BY DeptName");
$work_groups = $conn->query("SELECT * FROM work_groups ORDER BY GroupName");
$rules = $conn->query("SELECT * FROM rules WHERE IsGeneral = 0 OR IsGeneral IS NULL ORDER BY RuleName");
$general_rules = $conn->query("SELECT * FROM rules WHERE IsGeneral = 1 ORDER BY RuleName");

renderHeader($isEdit ? 'ویرایش پرسنل' : 'افزودن پرسنل');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش پرسنل' : '➕ افزودن پرسنل'; ?></h1>

<?php if ($error): ?><div class="flash err"><?php echo $error; ?></div><?php endif; ?>

<div class="card" style="background:#e8f4ff; border:1px solid #3498db;">
    <p style="margin:0; font-size:13px;">
        💡 هر پرسنل باید <strong>هم قانون اختصاصی، هم قانون کلی</strong> داشته باشد. قانون اختصاصی برای کار روزانه و ضرایب، قانون کلی برای تنظیمات زمانی و محاسبه روز بدون تردد استفاده می‌شود.
    </p>
</div>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>کد پرسنلی</label>
        <input type="text" name="PCode" value="<?php echo htmlspecialchars($row['PCode']); ?>" <?php echo $isEdit ? 'readonly' : 'required'; ?>>
    </div>
    
    <div class="form-group">
        <label>نام و نام خانوادگی</label>
        <input type="text" name="Name" value="<?php echo htmlspecialchars($row['Name']); ?>" required>
    </div>
    
    <div class="form-group">
        <label>بخش</label>
        <select name="Dept">
            <option value="">-- انتخاب بخش --</option>
            <?php if ($departments && $departments->num_rows > 0): ?>
                <?php while ($d = $departments->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($d['DeptName']); ?>" 
                        <?php echo ($row['Dept'] == $d['DeptName']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['DeptName']); ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <div class="form-group">
        <label>گروه کاری</label>
        <select name="WorkGroup">
            <option value="">-- انتخاب گروه کاری --</option>
            <?php if ($work_groups && $work_groups->num_rows > 0): ?>
                <?php while ($w = $work_groups->fetch_assoc()): ?>
                    <option value="<?php echo htmlspecialchars($w['GroupName']); ?>"
                        <?php echo ($row['WorkGroup'] == $w['GroupName'] || $row['WorkGroupID'] == $w['GroupID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($w['GroupName']); ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
    </div>
    
    <div class="form-group" style="background:#fff5e8; padding:15px; border-radius:6px; border:1px solid #ffd8a8;">
        <label>📜 قانون اختصاصی (اجباری)</label>
        <select name="RuleID" required>
            <option value="">-- انتخاب قانون اختصاصی --</option>
            <?php if ($rules && $rules->num_rows > 0): ?>
                <?php while ($r = $rules->fetch_assoc()): ?>
                    <option value="<?php echo $r['RuleID']; ?>" <?php echo ($row['RuleID'] == $r['RuleID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($r['RuleName']); ?>
                    </option>
                <?php endwhile; ?>
            <?php else: ?>
                <option value="" disabled>هنوز قانون اختصاصی نداری - از منوی «قوانین» بساز</option>
            <?php endif; ?>
        </select>
        <small style="color:#666; display:block; margin-top:5px;">
            شامل: کار روزانه، ضریب اضافه‌کاری، ضریب تعطیل، حداکثر اضافه‌کاری، مانده مرخصی
        </small>
    </div>
    
    <div class="form-group" style="background:#f8f0ff; padding:15px; border-radius:6px; border:1px solid #d8b8ff;">
        <label>📋 قانون کلی (اجباری)</label>
        <select name="GeneralRuleID" required>
            <option value="">-- انتخاب قانون کلی --</option>
            <?php if ($general_rules && $general_rules->num_rows > 0): ?>
                <?php while ($g = $general_rules->fetch_assoc()): ?>
                    <option value="<?php echo $g['RuleID']; ?>" <?php echo ($row['GeneralRuleID'] == $g['RuleID']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($g['RuleName']); ?>
                    </option>
                <?php endwhile; ?>
            <?php else: ?>
                <option value="" disabled>هنوز قانون کلی نداری - از منوی «قوانین کلی» بساز</option>
            <?php endif; ?>
        </select>
        <small style="color:#666; display:block; margin-top:5px;">
            شامل: تنظیمات زمانی، رند کردن ساعت، ناهار، چای صبح و عصر، محاسبه روز بدون تردد
        </small>
    </div>
    
    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="personnel.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>