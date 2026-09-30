<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM rules WHERE RuleID = $id");
    header('Location: rules.php?msg=deleted');
    exit;
}

renderHeader('قوانین');
?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">قانون حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">قانون ذخیره شد.</div><?php endif; ?>

<h1>📜 مدیریت قوانین</h1>

<div style="margin-bottom:15px;">
    <a href="rules_form.php" class="btn btn-success">➕ افزودن قانون</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد</th>
        <th>نام قانون</th>
        <th>کار روزانه</th>
        <th>اضافه‌کاری عادی</th>
        <th>اضافه‌کاری تعطیل</th>
        <th>مانده مرخصی</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM rules ORDER BY RuleID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['RuleID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['RuleName']) . "</td>";
            echo "<td>" . $row['DailyWorkMinutes'] . " دقیقه</td>";
            echo "<td>" . $row['OvertimeFactor'] . "</td>";
            echo "<td>" . $row['OvertimeHolidayFactor'] . "</td>";
            echo "<td>" . $row['LeaveBalanceDays'] . " روز</td>";
            echo "<td class='actions'>";
            echo "<a href='rule_details.php?id=" . $row['RuleID'] . "' class='btn btn-primary' title='جزئیات قوانین'>⚙️</a>";
            echo "<a href='rules_form.php?id=" . $row['RuleID'] . "' class='btn btn-warning' title='ویرایش'>✏️</a>";
            echo "<a href='rules.php?delete=" . $row['RuleID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")' title='حذف'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>هنوز قانونی تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>