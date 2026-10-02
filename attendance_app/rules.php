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

<h1>📜 مدیریت قوانین اختصاصی</h1>

<div class="card" style="background:#e8f4ff; border:1px solid #3498db;">
    <p style="margin:0; font-size:13px;">
        💡 این قوانین، قانون اختصاصی هر پرسنل هستن. تنظیمات زمانی و رند کردن ساعت توی «قوانین کلی» تعریف می‌شن.
    </p>
</div>

<div style="margin-bottom:15px;">
    <a href="rules_form.php" class="btn btn-success">➕ افزودن قانون</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد</th>
        <th>نام قانون</th>
        <th>توضیحات</th>
        <th>مرخصی ماهانه</th>
        <th>تعداد پرسنل</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM rules WHERE IsGeneral = 0 OR IsGeneral IS NULL ORDER BY RuleID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $r2 = $conn->query("SELECT COUNT(*) as c FROM personnel WHERE RuleID = " . $row['RuleID']);
            $pc = $r2->fetch_assoc()['c'];
            
            echo "<tr>";
            echo "<td>" . $row['RuleID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['RuleName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Description'] ?? '-') . "</td>";
            echo "<td><span style='background:#9b59b6;color:white;padding:3px 10px;border-radius:10px;font-size:12px;'>" . htmlspecialchars($row['MonthlyLeaveHours'] ?? '17:30') . "</span></td>";
            echo "<td style='text-align:center;'><span style='background:#3498db;color:white;padding:2px 10px;border-radius:10px;font-size:12px;'>" . $pc . " نفر</span></td>";
            echo "<td class='actions'>";
            echo "<a href='rules_form.php?id=" . $row['RuleID'] . "' class='btn btn-warning' title='ویرایش'>✏️</a>";
            echo "<a href='rules.php?delete=" . $row['RuleID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")' title='حذف'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center;'>هنوز قانونی تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>