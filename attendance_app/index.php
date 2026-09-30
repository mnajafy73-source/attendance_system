<?php
include 'config.php';
include 'layout.php';

$pcount = $conn->query("SELECT COUNT(*) as c FROM personnel")->fetch_assoc()['c'];
$acount = $conn->query("SELECT COUNT(*) as c FROM attendance")->fetch_assoc()['c'];
$r = $conn->query("SELECT COUNT(*) as c FROM rules");
$rcount = $r ? $r->fetch_assoc()['c'] : 0;

renderHeader('داشبورد');
?>
<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:20px;">
    <div class="card"><h3>👥 پرسنل</h3><p style="font-size:32px; color:#3498db;"><?php echo $pcount; ?></p><a href="personnel.php" class="btn btn-primary">مشاهده</a></div>
    <div class="card"><h3>⏰ رکوردهای تردد</h3><p style="font-size:32px; color:#27ae60;"><?php echo $acount; ?></p><a href="attendance.php" class="btn btn-primary">مشاهده</a></div>
    <div class="card"><h3>📜 قوانین</h3><p style="font-size:32px; color:#e67e22;"><?php echo $rcount; ?></p><a href="rules.php" class="btn btn-primary">مشاهده</a></div>
</div>
<?php renderFooter(); ?>