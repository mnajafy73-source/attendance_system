<?php
include 'config.php';
include 'layout.php';

$pcode_filter = $_GET['pcode'] ?? '';
$search_filter = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? '1405/06';

renderHeader('ترددها');
?>
<h1>⏰ ترددهای پرسنل</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div>
            <label>🔍 جستجو (نام یا کد)</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search_filter); ?>" placeholder="نام یا کد پرسنلی..." autofocus>
            <?php if ($pcode_filter): ?>
                <input type="hidden" name="pcode" value="<?php echo htmlspecialchars($pcode_filter); ?>">
            <?php endif; ?>
        </div>
        <div><label>ماه</label><input type="text" name="month" value="<?php echo htmlspecialchars($month_filter); ?>" placeholder="1405/06"></div>
        <div><button type="submit" class="btn btn-primary">🔍 جستجو</button></div>
        <div><a href="attendance.php" class="btn btn-warning">پاک کردن</a></div>
    </div>
</form>
</div>

<div class="card">
<table>
    <tr>
        <th>کد</th><th>نام</th><th>تاریخ</th><th>ورود</th><th>خروج</th><th>اضافه‌کاری</th><th>تاخیر</th><th>عملیات</th>
    </tr>
    <?php
    $where = [];
    
    // فیلتر دقیق با کد پرسنلی (از دکمه ⏰)
    if (!empty($pcode_filter)) {
        $p_escaped = $conn->real_escape_string($pcode_filter);
        $where[] = "LPAD(a.Prc_PCode, 8, '0') = LPAD('$p_escaped', 8, '0')";
    }
    
    // فیلتر جستجو با نام یا کد
    if (!empty($search_filter)) {
        $s = $conn->real_escape_string($search_filter);
        $where[] = "(p.Name LIKE '%$s%' OR a.Prc_PCode LIKE '%$s%')";
    }
    
    // فیلتر ماه
    if (!empty($month_filter)) {
        $m = $conn->real_escape_string($month_filter);
        $where[] = "a.Prc_Date LIKE '$m%'";
    }
    
    $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT a.*, p.Name FROM attendance a 
            LEFT JOIN personnel p ON LPAD(a.Prc_PCode, 8, '0') = LPAD(p.PCode, 8, '0') 
            $where_sql 
            ORDER BY a.Prc_Date DESC, CAST(a.Prc_PCode AS UNSIGNED) ASC 
            LIMIT 500";
    $res = $conn->query($sql);
    $count = 0;
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $count++;
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Prc_PCode']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($row['Prc_Date']) . "</td>";
            echo "<td style='color:#27ae60;'>" . numToTime($row['Prc_FirstIn']) . "</td>";
            echo "<td style='color:#e74c3c;'>" . numToTime($row['Prc_LastOut']) . "</td>";
            echo "<td>" . numToTime($row['Prc_ValidAddWork']) . "</td>";
            echo "<td>" . ($row['Prc_TakhirLessWork'] ?: '0') . "</td>";
            echo "<td><a href='attendance_edit.php?id=" . $row['Prc_PCode'] . "&date=" . urlencode($row['Prc_Date']) . "' class='btn btn-warning' title='ویرایش'>✏️</a></td>";
            echo "</tr>";
        }
    }
    if ($count == 0) echo "<tr><td colspan='8' style='text-align:center;'>موردی یافت نشد.</td></tr>";
    ?>
</table>
</div>
<?php renderFooter(); ?>