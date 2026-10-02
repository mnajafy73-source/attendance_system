<?php
include 'config.php';
include 'layout.php';

$pcode_filter = $_GET['pcode'] ?? '';
$search_filter = $_GET['search'] ?? '';
$month_filter = $_GET['month'] ?? '1405/06';

// اگه از datalist انتخاب شده
if (!empty($pcode_filter)) {
    $search_filter = $pcode_filter;
}

renderHeader('ترددها');
?>
<h1>⏰ ترددهای پرسنل</h1>

<div class="card">
<form method="get">
    <div class="filter-row">
        <div style="flex:2;">
            <label>🔍 جستجو یا انتخاب از لیست</label>
            <input type="text" name="search" list="personnel_datalist" value="<?php echo htmlspecialchars($search_filter); ?>" 
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
        <div>
            <label>📅 ماه</label>
            <select name="month">
                <?php
                // ماه‌های 1405 و 1406
                $years = ['1405', '1406'];
                $month_names = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
                foreach ($years as $y) {
                    echo "<optgroup label='سال $y'>";
                    for ($m = 1; $m <= 12; $m++) {
                        $val = sprintf('%s/%02d', $y, $m);
                        $sel = ($month_filter == $val) ? 'selected' : '';
                        echo "<option value='$val' $sel>" . $month_names[$m-1] . " $y</option>";
                    }
                    echo "</optgroup>";
                }
                ?>
            </select>
        </div>
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
    
    // فیلتر جستجو
    if (!empty($search_filter)) {
        $s = $conn->real_escape_string($search_filter);
        if (ctype_digit($s)) {
            $where[] = "(LPAD(a.Prc_PCode, 8, '0') = LPAD('$s', 8, '0'))";
        } else {
            $where[] = "p.Name LIKE '%$s%'";
        }
    }
    
    // فیلتر ماه
    if (!empty($month_filter)) {
        $m = $conn->real_escape_string($month_filter);
        $where[] = "a.Prc_Date LIKE '$m%'";
        $where[] = "RIGHT(a.Prc_Date, 2) != '00'";
    }
    
    $where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    $sql = "SELECT a.*, p.Name FROM attendance a 
            LEFT JOIN personnel p ON LPAD(a.Prc_PCode, 8, '0') = LPAD(p.PCode, 8, '0') 
            $where_sql 
            ORDER BY a.Prc_Date ASC, CAST(a.Prc_PCode AS UNSIGNED) ASC 
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