<?php
include 'config.php';

$action = $_GET['action'] ?? 'list';

// حذف
if ($action == 'delete') {
    $id = intval($_GET['id'] ?? 0);
    $conn->query("DELETE FROM rule_exceptions WHERE ExceptionID = $id");
    header('Location: exceptions.php?msg=deleted');
    exit;
}

// ذخیره
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['ExceptionID'] ?? 0);
    $df = $_POST['DateFrom'];
    $dt = $_POST['DateTo'];
    $sr = ($_POST['SpecificRuleID'] !== '') ? intval($_POST['SpecificRuleID']) : null;
    $gr = ($_POST['GeneralRuleID'] !== '') ? intval($_POST['GeneralRuleID']) : null;
    $note = $_POST['Note'];
    
    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE rule_exceptions SET DateFrom=?, DateTo=?, SpecificRuleID=?, GeneralRuleID=?, Note=? WHERE ExceptionID=?");
        $stmt->bind_param('ssiisi', $df, $dt, $sr, $gr, $note, $id);
    } else {
        $stmt = $conn->prepare("INSERT INTO rule_exceptions (DateFrom, DateTo, SpecificRuleID, GeneralRuleID, Note) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssiis', $df, $dt, $sr, $gr, $note);
    }
    $stmt->execute();
    header('Location: exceptions.php?msg=saved');
    exit;
}

// لیست کردن (برای AJAX یا نمایش مستقیم)
if ($action == 'list') {
    $specific_rules = [];
    $r = $conn->query("SELECT RuleID, RuleName FROM rules WHERE IsGeneral = 0 OR IsGeneral IS NULL ORDER BY RuleName");
    while ($row = $r->fetch_assoc()) $specific_rules[] = $row;
    
    $general_rules = [];
    $r = $conn->query("SELECT RuleID, RuleName FROM rules WHERE IsGeneral = 1 ORDER BY RuleName");
    while ($row = $r->fetch_assoc()) $general_rules[] = $row;
    
    $exceptions = [];
    $r = $conn->query("SELECT e.*, sr.RuleName as SName, gr.RuleName as GName 
                       FROM rule_exceptions e 
                       LEFT JOIN rules sr ON e.SpecificRuleID = sr.RuleID 
                       LEFT JOIN rules gr ON e.GeneralRuleID = gr.RuleID 
                       ORDER BY e.DateFrom");
    while ($row = $r->fetch_assoc()) $exceptions[] = $row;
    
    // اگه درخواست AJAX بود
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['exceptions' => $exceptions, 'specific' => $specific_rules, 'general' => $general_rules], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<title>استثناها</title>
<style>
* { box-sizing: border-box; }
body { font-family: Tahoma; margin: 0; padding: 15px; background: #f0f2f5; font-size: 13px; }
h2 { margin-top: 0; color: #2c3e50; }
.card { background: white; padding: 15px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 15px; }
table { width: 100%; border-collapse: collapse; background: white; }
th, td { padding: 8px 10px; text-align: right; border-bottom: 1px solid #eee; font-size: 12px; }
th { background: #9b59b6; color: white; font-weight: normal; }
tr:hover { background: #f7fbff; }
.btn { display: inline-block; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; cursor: pointer; border: none; }
.btn-danger { background: #e74c3c; color: white; }
.btn-success { background: #27ae60; color: white; }
.btn-warning { background: #f39c12; color: white; }
.btn-primary { background: #3498db; color: white; }
.form-group { margin-bottom: 10px; }
.form-group label { display: block; margin-bottom: 3px; font-weight: bold; color: #34495e; font-size: 12px; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 6px 10px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; font-size: 12px; }
.row { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.flash { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 10px; }
.grid3 { display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 8px; align-items: end; }
</style>
</head>
<body>
<h2>📅 استثناها</h2>

<?php if (($_GET['msg'] ?? '') == 'saved'): ?><div class="flash">ذخیره شد.</div><?php endif; ?>
<?php if (($_GET['msg'] ?? '') == 'deleted'): ?><div class="flash">حذف شد.</div><?php endif; ?>

<div class="card">
    <h3 style="margin-top:0; color:#9b59b6;">➕ افزودن استثنا</h3>
    <form method="post">
        <input type="hidden" name="ExceptionID" value="0">
        <div class="row">
            <div class="form-group">
                <label>از تاریخ (مثال: 1405/07/01)</label>
                <input type="text" name="DateFrom" placeholder="1405/07/01" required>
            </div>
            <div class="form-group">
                <label>تا تاریخ</label>
                <input type="text" name="DateTo" placeholder="1405/07/15" required>
            </div>
        </div>
        <div class="row">
            <div class="form-group">
                <label>قانون اختصاصی</label>
                <select name="SpecificRuleID">
                    <option value="">-- بدون تغییر --</option>
                    <?php foreach ($specific_rules as $s): ?>
                        <option value="<?php echo $s['RuleID']; ?>"><?php echo htmlspecialchars($s['RuleName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>قانون کلی</label>
                <select name="GeneralRuleID">
                    <option value="">-- بدون تغییر --</option>
                    <?php foreach ($general_rules as $g): ?>
                        <option value="<?php echo $g['RuleID']; ?>"><?php echo htmlspecialchars($g['RuleName']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label>توضیحات (اختیاری)</label>
            <input type="text" name="Note" placeholder="مثلاً: تعطیلات عید">
        </div>
        <button type="submit" class="btn btn-success">💾 ذخیره</button>
    </form>
</div>

<div class="card">
    <h3 style="margin-top:0;">📋 لیست استثناها</h3>
    <table>
        <tr>
            <th>از تاریخ</th>
            <th>تا تاریخ</th>
            <th>قانون اختصاصی</th>
            <th>قانون کلی</th>
            <th>توضیحات</th>
            <th>عملیات</th>
        </tr>
        <?php if (count($exceptions) > 0): ?>
            <?php foreach ($exceptions as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['DateFrom']); ?></td>
                    <td><?php echo htmlspecialchars($e['DateTo']); ?></td>
                    <td><?php echo htmlspecialchars($e['SName'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($e['GName'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($e['Note'] ?? '-'); ?></td>
                    <td>
                        <a href="exceptions.php?action=delete&id=<?php echo $e['ExceptionID']; ?>" 
                           class="btn btn-danger" onclick="return confirm('مطمئن هستید؟')">🗑️</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align:center;">هنوز استثنایی تعریف نشده.</td></tr>
        <?php endif; ?>
    </table>
</div>
</body>
</html>