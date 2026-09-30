<?php
include 'config.php';
include 'layout.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: rules.php'); exit; }

$stmt = $conn->prepare("SELECT * FROM rules WHERE RuleID = ?");
$stmt->bind_param('i', $id);
$stmt->execute();
$rule = $stmt->get_result()->fetch_assoc();
if (!$rule) { renderHeader('خطا'); echo "<div class='flash err'>قانون یافت نشد.</div>"; renderFooter(); exit; }

renderHeader('جزئیات قانون');
?>
<h1>⚙️ جزئیات قانون: <?php echo htmlspecialchars($rule['RuleName']); ?></h1>

<div class="card">
    <p><strong>کد:</strong> <?php echo $rule['RuleID']; ?></p>
    <p><strong>توضیحات:</strong> <?php echo htmlspecialchars($rule['Description']); ?></p>
</div>

<div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
    <a href="rules_form.php?id=<?php echo $id; ?>" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">📊</div>
        <h3>قوانین کارکرد</h3>
        <p style="color:#666; font-size:13px;">کار روزانه، حداقل کار</p>
    </a>
    <a href="rules_form.php?id=<?php echo $id; ?>#underwork" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">📉</div>
        <h3>قوانین کم‌کاری</h3>
        <p style="color:#666; font-size:13px;">ضریب، ساعت شروع</p>
    </a>
    <a href="rules_form.php?id=<?php echo $id; ?>#overtime" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">📈</div>
        <h3>قوانین اضافه‌کاری</h3>
        <p style="color:#666; font-size:13px;">ضرایب، حداقل، حداکثر</p>
    </a>
    <a href="rules_form.php?id=<?php echo $id; ?>#leave" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">🏖️</div>
        <h3>قوانین مرخصی</h3>
        <p style="color:#666; font-size:13px;">ضریب، مانده سالانه</p>
    </a>
    <a href="rules_form.php?id=<?php echo $id; ?>#mission" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">✈️</div>
        <h3>قوانین ماموریت</h3>
        <p style="color:#666; font-size:13px;">ضریب ماموریت</p>
    </a>
    <a href="rules_form.php?id=<?php echo $id; ?>#misc" class="card" style="text-decoration:none; text-align:center; padding:25px; color:#2c3e50;">
        <div style="font-size:40px;">⚙️</div>
        <h3>قوانین متفرقه</h3>
        <p style="color:#666; font-size:13px;">شب‌کاری، جمعه، تعطیل</p>
    </a>
</div>

<div style="margin-top:20px;">
    <a href="rules.php" class="btn btn-primary">⬅️ بازگشت به لیست قوانین</a>
</div>
<?php renderFooter(); ?>