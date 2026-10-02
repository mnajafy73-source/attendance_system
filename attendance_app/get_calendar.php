<?php
include 'config.php';
header('Content-Type: application/json; charset=utf-8');

$group_id = intval($_GET['group_id'] ?? 0);
$year = intval($_GET['year'] ?? 0);

if (!$group_id || !$year) {
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}

$calendar = [];
$like = $year . '/%';
$stmt = $conn->prepare("SELECT JalaliDate, ShiftID, IsHoliday, HolidayType FROM shift_calendar WHERE GroupID = ? AND JalaliDate LIKE ?");
$stmt->bind_param('is', $group_id, $like);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $calendar[$r['JalaliDate']] = [
        'ShiftID' => $r['ShiftID'],
        'IsHoliday' => $r['IsHoliday'],
        'HolidayType' => $r['HolidayType']
    ];
}

echo json_encode(['ok' => true, 'calendar' => $calendar]);
?>