<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM departments WHERE DeptID = $id");
    header('Location: departments.php?msg=deleted');
    exit;
}

renderHeader('بخش‌ها');
?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">بخش حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">بخش ذخیره شد.</div><?php endif; ?>

<h1>🏢 مدیریت بخش‌ها</h1>

<div style="margin-bottom:15px;">
    <a href="department_form.php" class="btn btn-success">➕ افزودن بخش</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد</th>
        <th>نام بخش</th>
        <th>توضیحات</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM departments ORDER BY DeptID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['DeptID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['DeptName']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
            echo "<td class='actions'>";
            echo "<a href='department_form.php?id=" . $row['DeptID'] . "' class='btn btn-warning'>✏️</a>";
            echo "<a href='departments.php?delete=" . $row['DeptID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='4' style='text-align:center;'>هنوز بخشی تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>            