<?php
include 'config.php';
header('Content-Type: application/json');

$group_id = intval($_POST['group_id'] ?? 0);
$date = $_POST['date'] ?? '';
$shift_id = isset($_POST['shift_id']) && $_POST['shift_id'] !== '' ? intval($_POST['shift_id']) : null;
$holiday = intval($_POST['holiday'] ?? 0);
$holiday_type = $_POST['holiday_type'] ?? null;

if (empty($group_id) || empty($date)) {
    echo json_encode(['ok' => false, 'error' => 'missing params']);
    exit;
}

// چک کن رکورد قبلی هست یا نه
$stmt = $conn->prepare("SELECT ID FROM shift_calendar WHERE GroupID=? AND JalaliDate=?");
$stmt->bind_param('is', $group_id, $date);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();

if ($exists) {
    $stmt = $conn->prepare("UPDATE shift_calendar SET ShiftID=?, IsHoliday=?, HolidayType=? WHERE GroupID=? AND JalaliDate=?");
    $stmt->bind_param('iisis', $shift_id, $holiday, $holiday_type, $group_id, $date);
} else {
    $stmt = $conn->prepare("INSERT INTO shift_calendar (ShiftID, IsHoliday, HolidayType, GroupID, JalaliDate) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iisis', $shift_id, $holiday, $holiday_type, $group_id, $date);
}
$ok = $stmt->execute();
echo json_encode(['ok' => $ok]);
?>