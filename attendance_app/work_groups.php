<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM work_groups WHERE GroupID = $id");
    header('Location: work_groups.php?msg=deleted');
    exit;
}

renderHeader('گروه‌های کاری');
?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">گروه کاری حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">گروه کاری ذخیره شد.</div><?php endif; ?>

<h1>👷 مدیریت گروه‌های کاری</h1>

<div style="margin-bottom:15px;">
    <a href="work_group_form.php" class="btn btn-success">➕ افزودن گروه کاری</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد گروه</th>
        <th>نام گروه</th>
        <th>سال</th>
        <th>توضیحات</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM work_groups ORDER BY GroupID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['GroupCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['GroupName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Year']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
            echo "<td class='actions'>";
            echo "<a href='shift_calendar.php?group_id=" . $row['GroupID'] . "' class='btn btn-primary' title='تقویم شیفت'>📅</a>";
            echo "<a href='work_group_form.php?id=" . $row['GroupID'] . "' class='btn btn-warning' title='ویرایش'>✏️</a>";
            echo "<a href='work_groups.php?delete=" . $row['GroupID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")' title='حذف'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='5' style='text-align:center;'>هنوز گروه کاری تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>