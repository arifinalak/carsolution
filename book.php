<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$clientName = trim($_POST['client_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$carRegNo = strtoupper(trim($_POST['car_reg_no'] ?? ''));
$carEngineNo = trim($_POST['car_engine_no'] ?? '');
$appointmentDate = trim($_POST['appointment_date'] ?? '');
$complaint = trim($_POST['complaint'] ?? '');
$mechanicId = (int) ($_POST['mechanic_id'] ?? 0);

if (
    $clientName === '' || $address === '' || $phone === '' || $carRegNo === '' ||
    $carEngineNo === '' || $appointmentDate === '' || $complaint === '' || $mechanicId <= 0
) {
    set_flash('All fields are required.', 'error');
    redirect('/index.php?date=' . urlencode($appointmentDate ?: date('Y-m-d')));
}

if (!valid_future_or_today_date($appointmentDate)) {
    set_flash('Appointment date is invalid.', 'error');
    redirect('/index.php?date=' . urlencode(date('Y-m-d')));
}

if (!ctype_digit($carEngineNo)) {
    set_flash('Car engine number must contain numbers only.', 'error');
    redirect('/index.php?date=' . urlencode($appointmentDate));
}

$pdo = db();

$mechanicExistsStmt = $pdo->prepare('SELECT id FROM mechanics WHERE id = :id');
$mechanicExistsStmt->execute(['id' => $mechanicId]);
if (!$mechanicExistsStmt->fetchColumn()) {
    set_flash('Selected mechanic does not exist.', 'error');
    redirect('/index.php?date=' . urlencode($appointmentDate));
}

$duplicateStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM appointments WHERE car_reg_no = :car_reg_no AND appointment_date = :appointment_date'
);
$duplicateStmt->execute([
    'car_reg_no' => $carRegNo,
    'appointment_date' => $appointmentDate,
]);

if ((int) $duplicateStmt->fetchColumn() > 0) {
    set_flash('This car already has an appointment on the selected date.', 'error');
    redirect('/index.php?date=' . urlencode($appointmentDate));
}

if (mechanic_slots_left($mechanicId, $appointmentDate) <= 0) {
    set_flash('Selected mechanic is fully booked for this date.', 'error');
    redirect('/index.php?date=' . urlencode($appointmentDate));
}

$insertStmt = $pdo->prepare(
    'INSERT INTO appointments
     (client_name, address, phone, car_reg_no, car_engine_no, appointment_date, complaint, mechanic_id)
     VALUES
     (:client_name, :address, :phone, :car_reg_no, :car_engine_no, :appointment_date, :complaint, :mechanic_id)'
);

$insertStmt->execute([
    'client_name' => $clientName,
    'address' => $address,
    'phone' => $phone,
    'car_reg_no' => $carRegNo,
    'car_engine_no' => $carEngineNo,
    'appointment_date' => $appointmentDate,
    'complaint' => $complaint,
    'mechanic_id' => $mechanicId,
]);

set_flash('Appointment booked successfully.', 'success');
redirect('/index.php?date=' . urlencode($appointmentDate));
