<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $r = $conn->query("SELECT COUNT(*) as c FROM personnel WHERE GeneralRuleID = $id");
    $cnt = $r->fetch_assoc()['c'];
    if ($cnt > 0) {
        header('Location: general_rules.php?msg=in_use');
        exit;
    }
    $conn->query("DELETE FROM rules WHERE RuleID = $id AND IsGeneral = 1");
    header('Location: general_rules.php?msg=deleted');
    exit;
}

renderHeader('قوانین کلی');
?>
<h1>📋 قوانین کلی</h1>

<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">قانون کلی ذخیره شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">قانون کلی حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'in_use'): ?><div class="flash err">این قانون کلی توسط بعضی پرسنل استفاده می‌شه و قابل حذف نیست.</div><?php endif; ?>

<div class="card" style="background:#e8f4ff; border:1px solid #3498db;">
    <p style="margin:0; font-size:14px;">
        💡 این قوانین کلی برای هر پرسنلی که توی پروفایلشون یکی از این‌ها رو انتخاب کرده باشن، اعمال می‌شه.
    </p>
</div>

<div style="margin-bottom:15px;">
    <a href="general_rule_form.php" class="btn btn-success">➕ افزودن قانون کلی</a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <tr>
        <th>کد</th>
        <th>نام</th>
        <th>اضافه‌کار قبل شیفت</th>
        <th>اضافه‌کار بعد شیفت</th>
        <th>رند ورود</th>
        <th>رند خروج</th>
        <th>بازه رند</th>
        <th>آستانه</th>
        <th>پرسنل</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM rules WHERE IsGeneral = 1 ORDER BY RuleID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $r2 = $conn->query("SELECT COUNT(*) as c FROM personnel WHERE GeneralRuleID = " . $row['RuleID']);
            $personnel_count = $r2->fetch_assoc()['c'];
            
            $re = !empty($row['RoundEntryEnabled']) ? '<span style="color:#27ae60;">✅</span>' : '<span style="color:#999;">-</span>';
            $rx = !empty($row['RoundExitEnabled']) ? '<span style="color:#27ae60;">✅</span>' : '<span style="color:#999;">-</span>';
            
            echo "<tr>";
            echo "<td>" . $row['RuleID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['RuleName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['OvertimeBeforeShiftStart'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['OvertimeAfterShiftHours'] ?? '-') . "</td>";
            echo "<td style='text-align:center;'>$re</td>";
            echo "<td style='text-align:center;'>$rx</td>";
            echo "<td style='text-align:center;'>" . ($row['RoundBlockMinutes'] ?? 15) . " د</td>";
            echo "<td style='text-align:center;'>" . ($row['RoundThresholdMinutes'] ?? 6) . " د</td>";
            echo "<td style='text-align:center;'><span style='background:#3498db;color:white;padding:2px 10px;border-radius:10px;font-size:12px;'>" . $personnel_count . " نفر</span></td>";
            echo "<td class='actions'>";
            echo "<a href='general_rule_form.php?id=" . $row['RuleID'] . "' class='btn btn-warning'>✏️</a>";
            echo "<a href='general_rules.php?delete=" . $row['RuleID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='10' style='text-align:center;'>هنوز قانون کلی تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>