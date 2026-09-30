<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = ['GroupCode'=>'', 'GroupName'=>'', 'Year'=>'1405', 'Description'=>''];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM work_groups WHERE GroupID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['GroupCode'];
    $name = $_POST['GroupName'];
    $year = $_POST['Year'];
    $desc = $_POST['Description'];
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE work_groups SET GroupCode=?, GroupName=?, Year=?, Description=? WHERE GroupID=?");
        $stmt->bind_param('ssssi', $code, $name, $year, $desc, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO work_groups (GroupCode, GroupName, Year, Description) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $code, $name, $year, $desc);
    }
    $stmt->execute();
    header('Location: work_groups.php?msg=saved');
    exit;
}

renderHeader($isEdit ? 'ویرایش گروه کاری' : 'افزودن گروه کاری');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش گروه کاری' : '➕ افزودن گروه کاری'; ?></h1>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>کد گروه</label>
        <input type="text" name="GroupCode" value="<?php echo htmlspecialchars($row['GroupCode']); ?>" placeholder="مثلاً 1">
    </div>
    <div class="form-group">
        <label>نام گروه</label>
        <input type="text" name="GroupName" value="<?php echo htmlspecialchars($row['GroupName']); ?>" required placeholder="مثلاً روزکار، شب کار">
    </div>
    <div class="form-group">
        <label>سال تقویم</label>
        <input type="text" name="Year" value="<?php echo htmlspecialchars($row['Year']); ?>" placeholder="1405">
    </div>
    <div class="form-group">
        <label>توضیحات (اختیاری)</label>
        <textarea name="Description" rows="3"><?php echo htmlspecialchars($row['Description']); ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="work_groups.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>