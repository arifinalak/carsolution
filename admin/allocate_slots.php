<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	redirect('/admin/dashboard.php');
}

$mechanicId = (int) ($_POST['mechanic_id'] ?? 0);
$slotDate = trim($_POST['slot_date'] ?? '');
$totalSlots = (int) ($_POST['total_slots'] ?? -1);

if ($mechanicId <= 0 || !valid_future_or_today_date($slotDate) || $totalSlots < 0 || $totalSlots > 50) {
	set_flash('Invalid slot allocation request.', 'error');
	redirect('/admin/dashboard.php?date=' . urlencode($slotDate !== '' ? $slotDate : date('Y-m-d')));
}

$mechanicStmt = db()->prepare('SELECT id FROM mechanics WHERE id = :id');
$mechanicStmt->execute(['id' => $mechanicId]);
if (!$mechanicStmt->fetchColumn()) {
	set_flash('Selected mechanic does not exist.', 'error');
	redirect('/admin/dashboard.php?date=' . urlencode($slotDate));
}

$bookedStmt = db()->prepare(
	'SELECT COUNT(*)
	 FROM appointments
	 WHERE mechanic_id = :mechanic_id AND appointment_date = :appointment_date'
);
$bookedStmt->execute([
	'mechanic_id' => $mechanicId,
	'appointment_date' => $slotDate,
]);
$bookedCount = (int) $bookedStmt->fetchColumn();

if ($totalSlots < $bookedCount) {
	set_flash('Total slots cannot be less than already booked appointments (' . $bookedCount . ').', 'error');
	redirect('/admin/dashboard.php?date=' . urlencode($slotDate));
}

set_mechanic_daily_slots($mechanicId, $slotDate, $totalSlots);

set_flash('Slots updated successfully.', 'success');
redirect('/admin/dashboard.php?date=' . urlencode($slotDate));
