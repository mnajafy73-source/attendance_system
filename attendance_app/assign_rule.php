<?php
include 'config.php';
include 'layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule = ($_POST['rule_id'] !== '') ? intval($_POST['rule_id']) : null;
    $general_rule = ($_POST['general_rule_id'] !== '') ? intval($_POST['general_rule_id']) : null;
    $selected = $_POST['pcodes'] ?? [];
    
    foreach ($selected as $pc) {
        // ساخت کوئری بر اساس اینکه کدوم قانون انتخاب شده
        $updates = [];
        $params = [];
        $types = '';
        
        if (isset($_POST['apply_specific']) && $_POST['apply_specific'] == '1') {
            $updates[] = "RuleID = ?";
            $params[] = $rule;
            $types .= 'i';
        }
        if (isset($_POST['apply_general']) && $_POST['apply_general'] == '1') {
            $updates[] = "GeneralRuleID = ?";
            $params[] = $general_rule;
            $types .= 'i';
        }
        
        if (count($updates) > 0) {
            $params[] = $pc;
            $types .= 's';
            $sql = "UPDATE personnel SET " . implode(', ', $updates) . " WHERE PCode = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
        }
    }
    header('Location: assign_rule.php?msg=saved');
    exit;
}

$specific_rules = $conn->query("SELECT * FROM rules WHERE IsGeneral = 0 OR IsGeneral IS NULL ORDER BY RuleName");
$general_rules = $conn->query("SELECT * FROM rules WHERE IsGeneral = 1 ORDER BY RuleName");
renderHeader('تخصیص قانون');
?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">تخصیص انجام شد.</div><?php endif; ?>

<h1>🔗 تخصیص قانون به پرسنل</h1>

<div class="card" style="background:#e8f4ff; border:1px solid #3498db;">
    <p style="margin:0; font-size:13px;">
        💡 هر پرسنل باید <strong>هم قانون اختصاصی، هم قانون کلی</strong> داشته باشد.
        می‌توانید هر کدام را جداگانه یا با هم تخصیص دهید.
    </p>
</div>

<form method="post">
<div class="card">
    <h3 style="color:#e67e22;">📜 قانون اختصاصی</h3>
    <div style="background:#fff5e8; padding:15px; border-radius:6px; border:1px solid #ffd8a8; margin-bottom:15px;">
        <label style="display:flex; align-items:center; cursor:pointer; margin-bottom:10px;">
            <input type="checkbox" name="apply_specific" value="1" style="width:auto; margin-left:10px;" checked>
            <strong>تخصیص قانون اختصاصی</strong>
        </label>
        <select name="rule_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <option value="">-- حذف قانون اختصاصی --</option>
            <?php if ($specific_rules) { while ($r = $specific_rules->fetch_assoc()): ?>
                <option value="<?php echo $r['RuleID']; ?>"><?php echo htmlspecialchars($r['RuleName']); ?></option>
            <?php endwhile; } ?>
        </select>
        <small style="color:#666; display:block; margin-top:5px;">
            شامل: کار روزانه، ضریب اضافه‌کاری، ضریب تعطیل، حداکثر اضافه‌کاری، مانده مرخصی
        </small>
    </div>

    <h3 style="color:#9b59b6;">📋 قانون کلی</h3>
    <div style="background:#f8f0ff; padding:15px; border-radius:6px; border:1px solid #d8b8ff; margin-bottom:15px;">
        <label style="display:flex; align-items:center; cursor:pointer; margin-bottom:10px;">
            <input type="checkbox" name="apply_general" value="1" style="width:auto; margin-left:10px;" checked>
            <strong>تخصیص قانون کلی</strong>
        </label>
        <select name="general_rule_id" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
            <option value="">-- حذف قانون کلی --</option>
            <?php if ($general_rules) { while ($g = $general_rules->fetch_assoc()): ?>
                <option value="<?php echo $g['RuleID']; ?>"><?php echo htmlspecialchars($g['RuleName']); ?></option>
            <?php endwhile; } ?>
        </select>
        <small style="color:#666; display:block; margin-top:5px;">
            شامل: تنظیمات زمانی، رند کردن ساعت، ناهار، چای، محاسبه روز بدون تردد
        </small>
    </div>

    <button type="submit" class="btn btn-success" style="font-size:15px; padding:10px 25px;">💾 اعمال روی انتخاب‌شده‌ها</button>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <tr>
        <th><input type="checkbox" onclick="document.querySelectorAll('.pc').forEach(c=>c.checked=this.checked)"></th>
        <th>کد</th>
        <th>نام</th>
        <th>بخش</th>
        <th>قانون اختصاصی</th>
        <th>قانون کلی</th>
    </tr>
    <?php
    $res = $conn->query("SELECT p.*, r1.RuleName as SpecificRule, r2.RuleName as GeneralRule 
                         FROM personnel p 
                         LEFT JOIN rules r1 ON p.RuleID = r1.RuleID 
                         LEFT JOIN rules r2 ON p.GeneralRuleID = r2.RuleID 
                         ORDER BY CAST(p.PCode AS UNSIGNED)");
    while ($row = $res->fetch_assoc()) {
        $sr = $row['SpecificRule'];
        $gr = $row['GeneralRule'];
        echo "<tr>";
        echo "<td><input type='checkbox' name='pcodes[]' value='" . $row['PCode'] . "' class='pc'></td>";
        echo "<td>" . htmlspecialchars($row['PCode']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Dept']) . "</td>";
        echo "<td style='color:" . ($sr ? '#27ae60' : '#e74c3c') . ";'>" . ($sr ?: '❌ ندارد') . "</td>";
        echo "<td style='color:" . ($gr ? '#27ae60' : '#e74c3c') . ";'>" . ($gr ?: '❌ ندارد') . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</div>
</form>
<?php renderFooter(); ?>