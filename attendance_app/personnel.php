<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $pcode = $conn->real_escape_string($_GET['delete']);
    $conn->query("DELETE FROM personnel WHERE PCode = '$pcode'");
    header('Location: personnel.php?msg=deleted');
    exit;
}

$search = $_GET['search'] ?? '';

renderHeader('لیست پرسنل');
?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">پرسنل با موفقیت حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">اطلاعات با موفقیت ذخیره شد.</div><?php endif; ?>

<h1>👥 لیست پرسنل</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div>
            <label>🔍 جستجو (نام یا کد پرسنلی)</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="مثلاً: موسوی یا 1" autofocus>
        </div>
        <div><button type="submit" class="btn btn-primary">جستجو</button></div>
        <div><a href="personnel.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
</form>
</div>

<div style="margin-bottom:15px;">
    <a href="personnel_form.php" class="btn btn-success">➕ افزودن پرسنل</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد پرسنلی</th>
        <th>نام</th>
        <th>بخش</th>
        <th>گروه کاری</th>
        <th>قانون</th>
        <th>عملیات</th>
    </tr>
    <?php
    $where = '';
    if (!empty($search)) {
        $s = $conn->real_escape_string($search);
        $where = "WHERE p.Name LIKE '%$s%' OR p.PCode LIKE '%$s%'";
    }
    $sql = "SELECT p.*, r.RuleName, w.GroupName FROM personnel p 
            LEFT JOIN rules r ON p.RuleID = r.RuleID 
            LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID 
            $where
            ORDER BY CAST(p.PCode AS UNSIGNED)";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['PCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Dept']) . "</td>";
            echo "<td>" . htmlspecialchars($row['GroupName'] ?? $row['WorkGroup'] ?? '-') . "</td>";
            echo "<td>" . ($row['RuleName'] ?: '-') . "</td>";
            echo "<td class='actions'>";
            echo "<a href='personnel_form.php?pcode=" . $row['PCode'] . "' class='btn btn-warning' title='ویرایش'>✏️</a>";
            echo "<a href='personnel.php?delete=" . $row['PCode'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")' title='حذف'>🗑️</a>";
            echo "<a href='attendance.php?pcode=" . $row['PCode'] . "' class='btn btn-primary' title='مشاهده تردد'>⏰</a>";
            echo "<a href='calculation.php?pcode=" . $row['PCode'] . "' class='btn btn-success' title='محاسبه کارکرد'>🧮</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='6' style='text-align:center;'>موردی یافت نشد.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>