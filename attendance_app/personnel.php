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
        <div style="flex:2;">
            <label>🔍 جستجو یا انتخاب از لیست</label>
            <input type="text" name="search" list="personnel_datalist" value="<?php echo htmlspecialchars($search); ?>" 
                   placeholder="اسم رو تایپ کن یا از لیست انتخاب کن..." autocomplete="off">
            <datalist id="personnel_datalist">
                <?php
                $ps = $conn->query("SELECT PCode, Name FROM personnel ORDER BY CAST(PCode AS UNSIGNED)");
                while ($p = $ps->fetch_assoc()) {
                    echo "<option value='" . htmlspecialchars($p['Name']) . "'>کد: " . htmlspecialchars($p['PCode']) . "</option>";
                }
                ?>
            </datalist>
            <small style="color:#666; display:block; margin-top:5px;">
                💡 اسم رو تایپ کن یا از لیست انتخاب کن. برای جستجو با کد، عدد رو دقیق بنویس.
            </small>
        </div>
        <div><button type="submit" class="btn btn-primary">جستجو</button></div>
        <div><a href="personnel.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
</form>
</div>

<div style="margin-bottom:15px;">
    <a href="personnel_form.php" class="btn btn-success">➕ افزودن پرسنل</a>
</div>

<div class="card" style="overflow-x:auto;">
<table>
    <tr>
        <th>کد پرسنلی</th>
        <th>نام</th>
        <th>بخش</th>
        <th>گروه کاری</th>
        <th>قانون اختصاصی</th>
        <th>قانون کلی</th>
        <th>عملیات</th>
    </tr>
    <?php
    $where = '';
    if (!empty($search)) {
        $s = $conn->real_escape_string($search);
        if (ctype_digit($s)) {
            $where = "WHERE p.PCode = '$s'";
        } else {
            $where = "WHERE p.Name LIKE '%$s%' OR p.PCode LIKE '%$s%'";
        }
    }
    $sql = "SELECT p.*, 
                   r1.RuleName AS SpecificRule, 
                   r2.RuleName AS GeneralRule, 
                   w.GroupName 
            FROM personnel p 
            LEFT JOIN rules r1 ON p.RuleID = r1.RuleID 
            LEFT JOIN rules r2 ON p.GeneralRuleID = r2.RuleID 
            LEFT JOIN work_groups w ON p.WorkGroupID = w.GroupID 
            $where
            ORDER BY CAST(p.PCode AS UNSIGNED)";
    $res = $conn->query($sql);
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $sr = $row['SpecificRule'];
            $gr = $row['GeneralRule'];
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['PCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Dept']) . "</td>";
            echo "<td>" . htmlspecialchars($row['GroupName'] ?? $row['WorkGroup'] ?? '-') . "</td>";
            echo "<td style='color:" . ($sr ? '#27ae60' : '#e74c3c') . ";'>" . ($sr ? htmlspecialchars($sr) : '❌ ندارد') . "</td>";
            echo "<td style='color:" . ($gr ? '#9b59b6' : '#e74c3c') . ";'>" . ($gr ? htmlspecialchars($gr) : '❌ ندارد') . "</td>";
            echo "<td class='actions'>";
            echo "<a href='personnel_form.php?pcode=" . $row['PCode'] . "' class='btn btn-warning' title='ویرایش'>✏️</a>";
            echo "<a href='personnel.php?delete=" . $row['PCode'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")' title='حذف'>🗑️</a>";
            echo "<a href='attendance.php?pcode=" . $row['PCode'] . "' class='btn btn-primary' title='مشاهده تردد'>⏰</a>";
            echo "<a href='calculation.php?pcode=" . $row['PCode'] . "' class='btn btn-success' title='محاسبه کارکرد'>🧮</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>موردی یافت نشد.</td></tr>";
    }
    ?>
</table>
</div>
<?php renderFooter(); ?>