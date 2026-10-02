<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$PYTHON_EXE  = 'C:\Users\Robat\AppData\Local\Python\pythoncore-3.14-64\python.exe';
$SYNC_SCRIPT = 'F:\attendance_project\sync.py';

$output = [];
$start = microtime(true);

$cmd = '"' . $PYTHON_EXE . '" "' . $SYNC_SCRIPT . '" 2>&1';
exec($cmd, $output, $return_var);

$elapsed = round(microtime(true) - $start, 2);

// ریست کردن فلگ تا دفعه بعد دوباره خودکار sync بشه
unset($_SESSION['auto_sync_done']);

echo json_encode([
    'ok' => ($return_var === 0),
    'elapsed' => $elapsed,
    'log' => implode("\n", $output)
], JSON_UNESCAPED_UNICODE);