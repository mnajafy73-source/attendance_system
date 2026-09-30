<?php
include 'config.php';
include 'layout.php';

$pcode = $_GET['id'] ?? '';
$date = $_GET['date'] ?? '';

if (empty($pcode) || empty($date)) { header('Location: attendance.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = ['Prc_FirstIn', 'Prc_FirstOut', 'Prc_SecondIn', 'Prc_SecondOut', 'Prc_ThirdIn', 'Prc_ThirdOut', 'Prc_FourthIn', 'Prc_FourthOut', 'Prc_LastIn', 'Prc_LastOut'];
    $vals = [];
    foreach ($fields as $f) $vals[$f] = timeToNum($_POST[$f] ?? '');
    $sql = "UPDATE attendance SET Prc_FirstIn=?, Prc_FirstOut=?, Prc_SecondIn=?, Prc_SecondOut=?, Prc_ThirdIn=?, Prc_ThirdOut=?, Prc_FourthIn=?, Prc_FourthOut=?, Prc_LastIn=?, Prc_LastOut=? WHERE Prc_PCode=? AND Prc_Date=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iiiiiiiiiiss',
        $vals['Prc_FirstIn'], $vals['Prc_FirstOut'], $vals['Prc_SecondIn'], $vals['Prc_SecondOut'],
        $vals['Prc_ThirdIn'], $vals['Prc_ThirdOut'], $vals['Prc_FourthIn'], $vals['Prc_FourthOut'],
        $vals['Prc_LastIn'], $vals['Prc_LastOut'], $pcode, $date);
    $stmt->execute();
    header('Location: attendance.php?pcode=' . $pcode);
    exit;
}

$stmt = $conn->prepare("SELECT a.*, p.Name FROM attendance a LEFT JOIN personnel p ON a.Prc_PCode = p.PCode WHERE a.Prc_PCode = ? AND a.Prc_Date = ?");
$stmt->bind_param('ss', $pcode, $date);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { renderHeader('خطا'); echo "<div class='flash err'>رکورد یافت نشد.</div>"; renderFooter(); exit; }

renderHeader('ویرایش تردد');
?>
<div class="card">
    <p><strong>پرسنل:</strong> <?php echo htmlspecialchars($row['Name'] ?? '-'); ?> (کد: <?php echo htmlspecialchars($row['Prc_PCode']); ?>)</p>
    <p><strong>تاریخ:</strong> <?php echo htmlspecialchars($row['Prc_Date']); ?></p>
</div>
<form method="post">
<div class="card">
    <h3>ساعت‌ها (فرمت HH:MM - خالی بذار اگه ثبت نشده)</h3>
    <div style="display:grid; grid-template-columns: repeat(2, 1fr); gap:15px;">
        <?php
        $labels = ['Prc_FirstIn'=>'ورود اول','Prc_FirstOut'=>'خروج اول','Prc_SecondIn'=>'ورود دوم','Prc_SecondOut'=>'خروج دوم','Prc_ThirdIn'=>'ورود سوم','Prc_ThirdOut'=>'خروج سوم','Prc_FourthIn'=>'ورود چهارم','Prc_FourthOut'=>'خروج چهارم','Prc_LastIn'=>'آخرین ورود','Prc_LastOut'=>'آخرین خروج'];
        foreach ($labels as $f => $label) {
            $v = numToTime($row[$f]); if ($v === '-') $v = '';
            echo "<div class='form-group'><label>$label</label><input type='text' name='$f' value='$v' placeholder='HH:MM'></div>";
        }
        ?>
    </div>
</div>
<button type="submit" class="btn btn-success">💾 ذخیره</button>
<a href="attendance.php" class="btn btn-danger">انصراف</a>
</form>
<?php renderFooter(); ?>