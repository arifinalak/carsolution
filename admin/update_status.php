<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/dashboard.php');
}

$appointmentId = (int) ($_POST['appointment_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$mechanicId = (int) ($_POST['mechanic_id'] ?? 0);
$date = trim($_POST['date'] ?? date('Y-m-d'));

$allowedStatuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
if (
    $appointmentId <= 0 ||
    !in_array($status, $allowedStatuses, true) ||
    $mechanicId <= 0 ||
    !valid_future_or_today_date($appointmentDate)
) {
    set_flash('Invalid update request.', 'error');
    redirect('/admin/dashboard.php?date=' . urlencode($date));
}

$pdo = db();

$appointmentStmt = $pdo->prepare('SELECT car_reg_no FROM appointments WHERE id = :id');
$appointmentStmt->execute(['id' => $appointmentId]);
$appointment = $appointmentStmt->fetch();

if (!$appointment) {
    set_flash('Appointment not found.', 'error');
    redirect('/admin/dashboard.php?date=' . urlencode($date));
}

$mechanicExistsStmt = $pdo->prepare('SELECT id FROM mechanics WHERE id = :id');
$mechanicExistsStmt->execute(['id' => $mechanicId]);
if (!$mechanicExistsStmt->fetchColumn()) {
    set_flash('Selected mechanic does not exist.', 'error');
    redirect('/admin/dashboard.php?date=' . urlencode($date));
}

$duplicateStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM appointments
     WHERE car_reg_no = :car_reg_no
       AND appointment_date = :appointment_date
       AND id <> :id'
);
$duplicateStmt->execute([
    'car_reg_no' => $appointment['car_reg_no'],
    'appointment_date' => $appointmentDate,
    'id' => $appointmentId,
]);

if ((int) $duplicateStmt->fetchColumn() > 0) {
    set_flash('This car already has an appointment on the selected date.', 'error');
    redirect('/admin/dashboard.php?date=' . urlencode($date));
}

$slotStmt = $pdo->prepare(
    'SELECT COUNT(*)
     FROM appointments
     WHERE mechanic_id = :mechanic_id
       AND appointment_date = :appointment_date
       AND id <> :id'
);
$slotStmt->execute([
    'mechanic_id' => $mechanicId,
    'appointment_date' => $appointmentDate,
    'id' => $appointmentId,
]);

$bookedByOthers = (int) $slotStmt->fetchColumn();
$capacity = mechanic_total_slots($mechanicId, $appointmentDate);

if ($bookedByOthers >= $capacity) {
    set_flash('Selected mechanic is fully booked for this date.', 'error');
    redirect('/admin/dashboard.php?date=' . urlencode($date));
}

$updateStmt = $pdo->prepare(
    'UPDATE appointments
     SET status = :status,
         appointment_date = :appointment_date,
         mechanic_id = :mechanic_id
     WHERE id = :id'
);
$updateStmt->execute([
    'status' => $status,
    'appointment_date' => $appointmentDate,
    'mechanic_id' => $mechanicId,
    'id' => $appointmentId,
]);

set_flash('Appointment updated successfully.', 'success');
redirect('/admin/dashboard.php?date=' . urlencode($date));
