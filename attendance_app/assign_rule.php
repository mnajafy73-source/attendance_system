<?php
include 'config.php';
include 'layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rule = ($_POST['rule_id'] !== '') ? intval($_POST['rule_id']) : null;
    $selected = $_POST['pcodes'] ?? [];
    foreach ($selected as $pc) {
        if ($rule === null) {
            $stmt = $conn->prepare("UPDATE personnel SET RuleID = NULL WHERE PCode = ?");
            $stmt->bind_param('s', $pc);
        } else {
            $stmt = $conn->prepare("UPDATE personnel SET RuleID = ? WHERE PCode = ?");
            $stmt->bind_param('is', $rule, $pc);
        }
        $stmt->execute();
    }
    header('Location: assign_rule.php?msg=saved');
    exit;
}

$rules = $conn->query("SELECT * FROM rules ORDER BY RuleName");
renderHeader('تخصیص قانون به پرسنل');
?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">تخصیص انجام شد.</div><?php endif; ?>

<form method="post">
<div class="card">
    <div class="form-group">
        <label>انتخاب قانون برای تخصیص</label>
        <select name="rule_id">
            <option value="">-- حذف قانون --</option>
            <?php if ($rules) { while ($r = $rules->fetch_assoc()): ?>
                <option value="<?php echo $r['RuleID']; ?>"><?php echo htmlspecialchars($r['RuleName']); ?></option>
            <?php endwhile; } ?>
        </select>
    </div>
    <button type="submit" class="btn btn-success">💾 اعمال روی انتخاب‌شده‌ها</button>
</div>
<div class="card">
<table>
    <tr><th><input type="checkbox" onclick="document.querySelectorAll('.pc').forEach(c=>c.checked=this.checked)"></th><th>کد</th><th>نام</th><th>بخش</th><th>قانون فعلی</th></tr>
    <?php
    $res = $conn->query("SELECT p.*, r.RuleName FROM personnel p LEFT JOIN rules r ON p.RuleID = r.RuleID ORDER BY CAST(p.PCode AS UNSIGNED)");
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td><input type='checkbox' name='pcodes[]' value='" . $row['PCode'] . "' class='pc'></td>";
        echo "<td>" . htmlspecialchars($row['PCode']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Dept']) . "</td>";
        echo "<td>" . ($row['RuleName'] ?: '-') . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</div>
</form>
<?php renderFooter(); ?>