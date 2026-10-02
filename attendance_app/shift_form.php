<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = ['ShiftName'=>'', 'Start1'=>'', 'End1'=>'', 'ColorCode'=>'1', 'Description'=>''];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM shifts WHERE ShiftID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['ShiftName'];
    $s1 = $_POST['Start1'];
    $e1 = $_POST['End1'];
    $color = $_POST['ColorCode'];
    $desc = $_POST['Description'];
    
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE shifts SET ShiftName=?, Start1=?, End1=?, Start2=NULL, End2=NULL, Start3=NULL, End3=NULL, ColorCode=?, Description=? WHERE ShiftID=?");
        $stmt->bind_param('sssssi', $name, $s1, $e1, $color, $desc, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO shifts (ShiftName, Start1, End1, ColorCode, Description) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $name, $s1, $e1, $color, $desc);
    }
    $stmt->execute();
    header('Location: shifts.php?msg=saved');
    exit;
}

renderHeader($isEdit ? 'ویرایش شیفت' : 'افزودن شیفت');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش شیفت' : '➕ افزودن شیفت'; ?></h1>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>نام شیفت</label>
        <input type="text" name="ShiftName" value="<?php echo htmlspecialchars($row['ShiftName']); ?>" required placeholder="مثلاً روزکار، شب کار">
    </div>

    <h3>⏰ بازه زمانی کارکرد عادی</h3>
    <p style="color:#666; font-size:13px;">
        این بازه، ساعات کارکرد عادی روزانه رو مشخص می‌کنه. مثلاً <code>07:00</code> تا <code>15:00</code>.<br>
        برای ساعت‌هایی که به <strong>روز بعد</strong> می‌رن (بعد از نیمه‌شب)، انتهای ساعت یک علامت <strong>+</strong> بذار. مثال: <code>04:30+</code>
    </p>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
        <div class="form-group">
            <label>از ساعت</label>
            <input type="text" name="Start1" value="<?php echo htmlspecialchars($row['Start1']); ?>" placeholder="07:00" required>
        </div>
        <div class="form-group">
            <label>تا ساعت</label>
            <input type="text" name="End1" value="<?php echo htmlspecialchars($row['End1']); ?>" placeholder="15:00" required>
        </div>
    </div>

    <div class="form-group">
        <label>شماره رنگ (1 تا 9)</label>
        <select name="ColorCode">
            <?php for ($i = 1; $i <= 9; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($row['ColorCode'] == $i) ? 'selected' : ''; ?>>رنگ <?php echo $i; ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="form-group">
        <label>توضیحات (اختیاری)</label>
        <textarea name="Description" rows="2"><?php echo htmlspecialchars($row['Description']); ?></textarea>
    </div>

    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="shifts.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>