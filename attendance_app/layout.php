<?php
function renderHeader($title = 'سیستم حضور و غیاب') {
?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<title><?php echo $title; ?></title>
<style>
* { box-sizing: border-box; }
body { font-family: Tahoma, Arial; margin: 0; background: #f0f2f5; }
.app { display: flex; min-height: 100vh; }
.sidebar { width: 240px; background: #2c3e50; color: white; padding: 20px 0; }
.sidebar h2 { text-align: center; margin: 0 0 30px; font-size: 18px; }
.sidebar a { display: block; color: #ecf0f1; text-decoration: none; padding: 12px 20px; transition: 0.2s; }
.sidebar a:hover { background: #34495e; border-right: 4px solid #3498db; }
.content { flex: 1; padding: 20px 30px; }
h1 { color: #2c3e50; margin-top: 0; }
table { width: 100%; border-collapse: collapse; background: white; border-radius: 6px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
th, td { padding: 10px 12px; text-align: right; border-bottom: 1px solid #eee; }
th { background: #3498db; color: white; font-weight: normal; }
tr:hover { background: #f7fbff; }
.btn { display: inline-block; padding: 6px 14px; border-radius: 4px; text-decoration: none; font-size: 13px; cursor: pointer; border: none; }
.btn-primary { background: #3498db; color: white; }
.btn-danger { background: #e74c3c; color: white; }
.btn-warning { background: #f39c12; color: white; }
.btn-success { background: #27ae60; color: white; }
.card { background: white; padding: 20px; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #34495e; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; }
.filter-row { display: flex; gap: 15px; flex-wrap: wrap; align-items: end; }
.filter-row > div { flex: 1; min-width: 150px; }
.actions { display: flex; gap: 6px; }
.flash { background: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; }
.flash.err { background: #f8d7da; color: #721c24; }
.sidebar hr { border-color: #34495e; margin: 15px 0; }
.sidebar .section-title { padding: 0 20px; color: #95a5a6; font-size: 12px; margin-bottom: 5px; }
.sidebar .sync-btn { display: block; margin: 15px; padding: 10px; background: #16a085; color: white; text-align: center; border-radius: 4px; text-decoration: none; font-weight: bold; cursor: pointer; border: none; width: calc(100% - 30px); font-family: inherit; font-size: 14px; }
.sidebar .sync-btn:hover { background: #1abc9c; }
.sync-info { padding: 0 20px; color: #7f8c8d; font-size: 11px; margin-bottom: 10px; }

#syncToast {
    display: none;
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    background: #2c3e50;
    color: white;
    padding: 20px 25px;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.4);
    z-index: 9999;
    min-width: 400px;
    max-width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    font-size: 13px;
    transition: opacity 0.5s;
}
#syncToast.show { display: block; }
#syncToast .toast-title { font-size: 16px; font-weight: bold; margin-bottom: 10px; }
#syncToast .toast-title.success { color: #2ecc71; }
#syncToast .toast-title.error { color: #e74c3c; }
#syncToast .toast-log { background: #1a252f; padding: 10px; border-radius: 4px; direction: ltr; text-align: left; font-family: monospace; font-size: 12px; white-space: pre-wrap; margin-top: 10px; max-height: 300px; overflow-y: auto; }
</style>
</head>
<body>
<div class="app">
<aside class="sidebar">
    <h2>📋 حضور و غیاب</h2>
    <a href="index.php">🏠 داشبورد</a>
    <a href="personnel.php">👥 پرسنل</a>
    <a href="attendance.php">⏰ ترددها</a>
    <a href="calculation.php">🧮 محاسبه کارکرد</a>
    <a href="monthly_report.php">📊 گزارش ماهانه</a>
    <a href="yearly_report.php">📆 گزارش سالانه</a>
    <hr>
    <div class="section-title">تنظیمات</div>
    <a href="departments.php">🏢 بخش‌ها</a>
    <a href="work_groups.php">👷 گروه‌های کاری</a>
    <a href="shifts.php">🕐 شیفت‌ها</a>
    <a href="rules.php">📜 قوانین</a>
    <a href="general_rules.php">📋 قوانین کلی</a>
    <hr>
    <div class="section-title">تخصیص</div>
    <a href="assign_rule.php">📜 تخصیص قانون</a>
    <a href="assign_work_group.php">👷 تخصیص گروه کاری</a>
    <hr>
    <button id="syncBtn" class="sync-btn" onclick="doSync()">🔄 همگام‌سازی دستی</button>
    <?php if (isset($_SESSION['auto_sync_time'])): ?>
        <div class="sync-info">آخرین sync خودکار: <?php echo $_SESSION['auto_sync_time']; ?></div>
    <?php endif; ?>
</aside>
<main class="content">

<div id="syncToast">
    <div class="toast-title" id="syncToastTitle"></div>
    <div id="syncToastLog"></div>
</div>

<script>
function doSync() {
    if (!confirm('همگام‌سازی دستی انجام بشه؟')) return;
    
    const btn = document.getElementById('syncBtn');
    const toast = document.getElementById('syncToast');
    const toastTitle = document.getElementById('syncToastTitle');
    const toastLog = document.getElementById('syncToastLog');
    
    btn.disabled = true;
    btn.textContent = '⏳ در حال همگام‌سازی...';
    
    toastTitle.className = 'toast-title';
    toastTitle.textContent = '⏳ در حال همگام‌سازی...';
    toastLog.innerHTML = '';
    toast.classList.add('show');
    toast.style.opacity = '1';
    
    fetch('sync_now.php')
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '🔄 همگام‌سازی دستی';
            
            if (data.ok) {
                toastTitle.className = 'toast-title success';
                toastTitle.textContent = '✅ همگام‌سازی با موفقیت انجام شد (' + data.elapsed + ' ثانیه)';
            } else {
                toastTitle.className = 'toast-title error';
                toastTitle.textContent = '❌ خطا در همگام‌سازی';
            }
            toastLog.innerHTML = '<div class="toast-log">' + escapeHtml(data.log) + '</div>';
            
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.classList.remove('show');
                    toast.style.opacity = '1';
                }, 500);
            }, 2000);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = '🔄 همگام‌سازی دستی';
            toastTitle.className = 'toast-title error';
            toastTitle.textContent = '❌ خطای شبکه';
            toastLog.innerHTML = '<div class="toast-log">' + escapeHtml(String(err)) + '</div>';
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    toast.classList.remove('show');
                    toast.style.opacity = '1';
                }, 500);
            }, 2000);
        });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php
}
function renderFooter() {
?>
</main>
</div>
</body>
</html>
<?php
}
function numToTime($num) {
    if ($num == -1000 || $num == 0 || $num == '' || $num < 0) return '-';
    $h = floor($num / 60);
    $m = $num % 60;
    $suffix = '';
    if ($h >= 24) {
        $h = $h - 24;
        $suffix = '+';
    }
    return str_pad($h, 2, '0', STR_PAD_LEFT) . ':' . str_pad($m, 2, '0', STR_PAD_LEFT) . $suffix;
}
function timeToNum($time) {
    if (empty($time) || $time == '-' || $time == '00:00') return -1000;
    $parts = explode(':', $time);
    if (count($parts) != 2) return -1000;
    return intval($parts[0]) * 60 + intval($parts[1]);
}
?>