<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = ['ShiftName'=>'', 'Start1'=>'', 'End1'=>'', 'Start2'=>'', 'End2'=>'', 'Start3'=>'', 'End3'=>'', 'ColorCode'=>'1', 'Description'=>''];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM shifts WHERE ShiftID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => $_POST['ShiftName'],
        's1' => $_POST['Start1'], 'e1' => $_POST['End1'],
        's2' => $_POST['Start2'], 'e2' => $_POST['End2'],
        's3' => $_POST['Start3'], 'e3' => $_POST['End3'],
        'color' => $_POST['ColorCode'],
        'desc' => $_POST['Description']
    ];
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE shifts SET ShiftName=?, Start1=?, End1=?, Start2=?, End2=?, Start3=?, End3=?, ColorCode=?, Description=? WHERE ShiftID=?");
        $stmt->bind_param('sssssssssi', $data['name'], $data['s1'], $data['e1'], $data['s2'], $data['e2'], $data['s3'], $data['e3'], $data['color'], $data['desc'], $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO shifts (ShiftName, Start1, End1, Start2, End2, Start3, End3, ColorCode, Description) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param('sssssssss', $data['name'], $data['s1'], $data['e1'], $data['s2'], $data['e2'], $data['s3'], $data['e3'], $data['color'], $data['desc']);
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

    <h3>⏰ بازه‌های زمانی</h3>
    <p style="color:#666; font-size:13px;">
        برای ساعت‌هایی که به <strong>روز بعد</strong> می‌رن (بعد از نیمه‌شب)، انتهای ساعت یک علامت <strong>+</strong> بذار. مثال: <code>04:30+</code>
    </p>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
        <div class="form-group">
            <label>بازه ۱ - از</label>
            <input type="text" name="Start1" value="<?php echo htmlspecialchars($row['Start1']); ?>" placeholder="08:00">
        </div>
        <div class="form-group">
            <label>بازه ۱ - تا</label>
            <input type="text" name="End1" value="<?php echo htmlspecialchars($row['End1']); ?>" placeholder="12:00">
        </div>

        <div class="form-group">
            <label>بازه ۲ - از</label>
            <input type="text" name="Start2" value="<?php echo htmlspecialchars($row['Start2']); ?>" placeholder="13:00">
        </div>
        <div class="form-group">
            <label>بازه ۲ - تا</label>
            <input type="text" name="End2" value="<?php echo htmlspecialchars($row['End2']); ?>" placeholder="17:00">
        </div>

        <div class="form-group">
            <label>بازه ۳ - از</label>
            <input type="text" name="Start3" value="<?php echo htmlspecialchars($row['Start3']); ?>" placeholder="18:00">
        </div>
        <div class="form-group">
            <label>بازه ۳ - تا</label>
            <input type="text" name="End3" value="<?php echo htmlspecialchars($row['End3']); ?>" placeholder="20:00">
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