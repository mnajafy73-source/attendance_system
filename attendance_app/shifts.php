<?php
include 'config.php';
include 'layout.php';

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM shifts WHERE ShiftID = $id");
    header('Location: shifts.php?msg=deleted');
    exit;
}

renderHeader('شیفت‌ها');
?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">شیفت حذف شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">شیفت ذخیره شد.</div><?php endif; ?>

<h1>🕐 مدیریت شیفت‌ها</h1>

<div style="margin-bottom:15px;">
    <a href="shift_form.php" class="btn btn-success">➕ افزودن شیفت</a>
</div>

<div class="card">
<table>
    <tr>
        <th>کد</th>
        <th>نام شیفت</th>
        <th>بازه ۱</th>
        <th>بازه ۲</th>
        <th>بازه ۳</th>
        <th>رنگ</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM shifts ORDER BY ShiftID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $r1 = ($row['Start1'] || $row['End1']) ? htmlspecialchars($row['Start1']) . ' تا ' . htmlspecialchars($row['End1']) : '-';
            $r2 = ($row['Start2'] || $row['End2']) ? htmlspecialchars($row['Start2']) . ' تا ' . htmlspecialchars($row['End2']) : '-';
            $r3 = ($row['Start3'] || $row['End3']) ? htmlspecialchars($row['Start3']) . ' تا ' . htmlspecialchars($row['End3']) : '-';
            $color = 'background:' . getShiftColor($row['ColorCode']) . '; color: white; padding: 3px 10px; border-radius: 3px;';
            
            echo "<tr>";
            echo "<td>" . $row['ShiftID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['ShiftName']) . "</td>";
            echo "<td>" . $r1 . "</td>";
            echo "<td>" . $r2 . "</td>";
            echo "<td>" . $r3 . "</td>";
            echo "<td><span style='$color'>" . $row['ColorCode'] . "</span></td>";
            echo "<td class='actions'>";
            echo "<a href='shift_form.php?id=" . $row['ShiftID'] . "' class='btn btn-warning'>✏️</a>";
            echo "<a href='shifts.php?delete=" . $row['ShiftID'] . "' class='btn btn-danger' onclick='return confirm(\"مطمئن هستید؟\")'>🗑️</a>";
            echo "</td></tr>";
        }
    } else {
        echo "<tr><td colspan='7' style='text-align:center;'>هنوز شیفتی تعریف نشده.</td></tr>";
    }
    ?>
</table>
</div>

<div class="card">
    <h3>🎨 راهنمای رنگ‌ها</h3>
    <p>هر شیفت یک شماره رنگ داره (1 تا 9). این رنگ‌ها توی تقویم استفاده می‌شن:</p>
    <?php for ($i = 1; $i <= 9; $i++): ?>
        <span style="display:inline-block; background:<?php echo getShiftColor($i); ?>; color:white; padding:5px 15px; border-radius:3px; margin:3px;">
            رنگ <?php echo $i; ?>
        </span>
    <?php endfor; ?>
</div>

<?php
function getShiftColor($code) {
    $colors = [
        '1' => '#e74c3c',
        '2' => '#3498db',
        '3' => '#27ae60',
        '4' => '#f39c12',
        '5' => '#9b59b6',
        '6' => '#1abc9c',
        '7' => '#e67e22',
        '8' => '#34495e',
        '9' => '#c0392b',
    ];
    return $colors[$code] ?? '#95a5a6';
}
renderFooter();
?>