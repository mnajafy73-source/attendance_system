<?php
include 'config.php';
include 'layout.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wg = ($_POST['wg_id'] !== '') ? intval($_POST['wg_id']) : null;
    $selected = $_POST['pcodes'] ?? [];
    foreach ($selected as $pc) {
        if ($wg === null) {
            $stmt = $conn->prepare("UPDATE personnel SET WorkGroupID = NULL WHERE PCode = ?");
            $stmt->bind_param('s', $pc);
        } else {
            $stmt = $conn->prepare("UPDATE personnel SET WorkGroupID = ? WHERE PCode = ?");
            $stmt->bind_param('is', $wg, $pc);
        }
        $stmt->execute();
    }
    header('Location: assign_work_group.php?msg=saved');
    exit;
}

$wgs = $conn->query("SELECT * FROM work_groups ORDER BY GroupName");
renderHeader('تخصیص گروه کاری');
?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">تخصیص انجام شد.</div><?php endif; ?>

<h1>🔗 تخصیص گروه کاری به پرسنل</h1>

<form method="post">
<div class="card">
    <div class="form-group">
        <label>انتخاب گروه کاری</label>
        <select name="wg_id">
            <option value="">-- حذف گروه --</option>
            <?php if ($wgs) { while ($w = $wgs->fetch_assoc()): ?>
                <option value="<?php echo $w['GroupID']; ?>"><?php echo htmlspecialchars($w['GroupName']); ?></option>
            <?php endwhile; } ?>
        </select>
    </div>
    <button type="submit" class="btn btn-success">💾 اعمال روی انتخاب‌شده‌ها</button>
</div>
<div class="card">
<table>
    <tr><th><input type="checkbox" onclick="document.querySelectorAll('.pc').forEach(c=>c.checked=this.checked)"></th><th>کد</th><th>نام</th><th>بخش</th><th>گروه کاری فعلی</th><th>قانون فعلی</th></tr>
    <?php
    $res = $conn->query("SELECT p.*, w.GroupName, r.RuleName FROM personnel p LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID LEFT JOIN rules r ON p.RuleID = r.RuleID ORDER BY CAST(p.PCode AS UNSIGNED)");
    while ($row = $res->fetch_assoc()) {
        echo "<tr>";
        echo "<td><input type='checkbox' name='pcodes[]' value='" . $row['PCode'] . "' class='pc'></td>";
        echo "<td>" . htmlspecialchars($row['PCode']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Dept']) . "</td>";
        echo "<td>" . ($row['GroupName'] ?: '-') . "</td>";
        echo "<td>" . ($row['RuleName'] ?: '-') . "</td>";
        echo "</tr>";
    }
    ?>
</table>
</div>
</form>
<?php renderFooter(); ?>