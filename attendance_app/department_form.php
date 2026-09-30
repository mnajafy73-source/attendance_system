<?php
include 'config.php';
include 'layout.php';

$id = $_GET['id'] ?? '';
$row = ['DeptName'=>'', 'Description'=>''];
$isEdit = false;

if (!empty($id)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM departments WHERE DeptID = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['DeptName'];
    $desc = $_POST['Description'];
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE departments SET DeptName=?, Description=? WHERE DeptID=?");
        $stmt->bind_param('ssi', $name, $desc, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO departments (DeptName, Description) VALUES (?, ?)");
        $stmt->bind_param('ss', $name, $desc);
    }
    $stmt->execute();
    header('Location: departments.php?msg=saved');
    exit;
}

renderHeader($isEdit ? 'ویرایش بخش' : 'افزودن بخش');
?>
<h1><?php echo $isEdit ? '✏️ ویرایش بخش' : '➕ افزودن بخش'; ?></h1>

<div class="card">
<form method="post">
    <div class="form-group">
        <label>نام بخش</label>
        <input type="text" name="DeptName" value="<?php echo htmlspecialchars($row['DeptName']); ?>" required placeholder="مثلاً: تولیدی، اداری، فروش">
    </div>
    <div class="form-group">
        <label>توضیحات (اختیاری)</label>
        <textarea name="Description" rows="3"><?php echo htmlspecialchars($row['Description']); ?></textarea>
    </div>
    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="departments.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>