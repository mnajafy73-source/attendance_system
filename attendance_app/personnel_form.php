<?php
include 'config.php';
include 'layout.php';

$pcode = $_GET['pcode'] ?? '';
$row = ['PCode'=>'', 'Name'=>'', 'Dept'=>'', 'WorkGroup'=>'', 'RuleID'=>''];
$isEdit = false;

if (!empty($pcode)) {
    $isEdit = true;
    $stmt = $conn->prepare("SELECT * FROM personnel WHERE PCode = ?");
    $stmt->bind_param('s', $pcode);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['Name'];
    $dept = $_POST['Dept'];
    $wg = $_POST['WorkGroup'];
    $rule = ($_POST['RuleID'] !== '') ? intval($_POST['RuleID']) : null;
    if ($isEdit) {
        $stmt = $conn->prepare("UPDATE personnel SET Name=?, Dept=?, WorkGroup=?, RuleID=? WHERE PCode=?");
        $stmt->bind_param('sssis', $name, $dept, $wg, $rule, $pcode);
    } else {
        $np = $_POST['PCode'];
        $stmt = $conn->prepare("INSERT INTO personnel (PCode, Name, Dept, WorkGroup, RuleID) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssssi', $np, $name, $dept, $wg, $rule);
    }
    $stmt->execute();
    header('Location: personnel.php?msg=saved');
    exit;
}

$rules = $conn->query("SELECT * FROM rules ORDER BY RuleName");
renderHeader($isEdit ? 'ویرایش پرسنل' : 'افزودن پرسنل');
?>
<div class="card">
<form method="post">
    <div class="form-group"><label>کد پرسنلی</label><input type="text" name="PCode" value="<?php echo htmlspecialchars($row['PCode']); ?>" <?php echo $isEdit ? 'readonly' : 'required'; ?>></div>
    <div class="form-group"><label>نام و نام خانوادگی</label><input type="text" name="Name" value="<?php echo htmlspecialchars($row['Name']); ?>" required></div>
    <div class="form-group"><label>بخش</label><input type="text" name="Dept" value="<?php echo htmlspecialchars($row['Dept']); ?>"></div>
    <div class="form-group"><label>گروه کاری</label><input type="text" name="WorkGroup" value="<?php echo htmlspecialchars($row['WorkGroup']); ?>"></div>
    <div class="form-group">
        <label>قانون محاسباتی</label>
        <select name="RuleID">
            <option value="">-- انتخاب --</option>
            <?php if ($rules) { while ($r = $rules->fetch_assoc()): ?>
                <option value="<?php echo $r['RuleID']; ?>" <?php echo ($row['RuleID'] == $r['RuleID']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($r['RuleName']); ?></option>
            <?php endwhile; } ?>
        </select>
    </div>
    <button type="submit" class="btn btn-success">💾 ذخیره</button>
    <a href="personnel.php" class="btn btn-danger">انصراف</a>
</form>
</div>
<?php renderFooter(); ?>