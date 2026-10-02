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
        <th>ساعت شروع</th>
        <th>ساعت پایان</th>
        <th>مدت</th>
        <th>رنگ</th>
        <th>عملیات</th>
    </tr>
    <?php
    $res = $conn->query("SELECT * FROM shifts ORDER BY ShiftID");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $start = htmlspecialchars($row['Start1']);
            $end = htmlspecialchars($row['End1']);
            
            // محاسبه مدت زمان
            $duration = '-';
            if ($start && $end) {
                $s = timeToNumHelper($start);
                $e = timeToNumHelper($end);
                if ($s > 0 && $e > 0) {
                    $diff = $e - $s;
                    if ($diff > 0) {
                        $h = floor($diff / 60);
                        $m = $diff % 60;
                        $duration = $h . ':' . str_pad($m, 2, '0', STR_PAD_LEFT);
                    }
                }
            }
            
            $color = 'background:' . getShiftColor($row['ColorCode']) . '; color: white; padding: 3px 10px; border-radius: 3px;';
            
            echo "<tr>";
            echo "<td>" . $row['ShiftID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['ShiftName']) . "</td>";
            echo "<td style='color:#27ae60;'>" . $start . "</td>";
            echo "<td style='color:#e74c3c;'>" . $end . "</td>";
            echo "<td>" . $duration . "</td>";
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

function timeToNumHelper($t) {
    if (empty($t)) return -1;
    $plus = (substr($t, -1) === '+') ? 1440 : 0;
    $t = rtrim($t, '+');
    $p = explode(':', $t);
    if (count($p) != 2) return -1;
    return intval($p[0]) * 60 + intval($p[1]) + $plus;
}

renderFooter();
?>