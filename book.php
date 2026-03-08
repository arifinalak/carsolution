<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/index.php');
}

$clientName = trim($_POST['client_name'] ?? '');
$address = trim($_POST['address'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$carRegNumbers = $_POST['car_reg_no'] ?? [];
$carEngineNumbers = $_POST['car_engine_no'] ?? [];
$appointmentDates = $_POST['appointment_date'] ?? [];
$complaints = $_POST['complaint'] ?? [];
$mechanicIds = $_POST['mechanic_id'] ?? [];

$redirectDate = date('Y-m-d');
if (is_array($appointmentDates) && isset($appointmentDates[0])) {
    $firstDate = trim((string) $appointmentDates[0]);
    if ($firstDate !== '') {
        $redirectDate = $firstDate;
    }
}

if ($clientName === '' || $address === '' || $phone === '') {
    set_flash('All fields are required.', 'error');
    redirect('/index.php?date=' . urlencode($redirectDate));
}

if (
    !is_array($carRegNumbers) ||
    !is_array($carEngineNumbers) ||
    !is_array($appointmentDates) ||
    !is_array($complaints) ||
    !is_array($mechanicIds)
) {
    set_flash('Invalid request format.', 'error');
    redirect('/index.php?date=' . urlencode($redirectDate));
}

$totalCars = count($carRegNumbers);
if (
    $totalCars === 0 ||
    $totalCars !== count($carEngineNumbers) ||
    $totalCars !== count($appointmentDates) ||
    $totalCars !== count($complaints) ||
    $totalCars !== count($mechanicIds)
) {
    set_flash('Car details are incomplete. Please review all car sections.', 'error');
    redirect('/index.php?date=' . urlencode($redirectDate));
}

$pdo = db();

$mechanicIdsNormalized = array_map(
    static function ($id): int {
        return (int) $id;
    },
    $mechanicIds
);
$uniqueMechanicIds = array_values(array_unique(array_filter(
    $mechanicIdsNormalized,
    static function ($id): bool {
        return $id > 0;
    }
)));

if (empty($uniqueMechanicIds)) {
    set_flash('Please select at least one mechanic.', 'error');
    redirect('/index.php?date=' . urlencode($redirectDate));
}

$placeholders = implode(',', array_fill(0, count($uniqueMechanicIds), '?'));
$mechanicExistsStmt = $pdo->prepare('SELECT id FROM mechanics WHERE id IN (' . $placeholders . ')');
$mechanicExistsStmt->execute($uniqueMechanicIds);
$existingMechanicIds = array_map('intval', $mechanicExistsStmt->fetchAll(PDO::FETCH_COLUMN));
$existingMechanicMap = array_fill_keys($existingMechanicIds, true);

$duplicateStmt = $pdo->prepare(
    'SELECT COUNT(*) FROM appointments WHERE car_reg_no = :car_reg_no AND appointment_date = :appointment_date'
);

$insertStmt = $pdo->prepare(
    'INSERT INTO appointments
     (client_name, address, phone, car_reg_no, car_engine_no, appointment_date, complaint, mechanic_id)
     VALUES
     (:client_name, :address, :phone, :car_reg_no, :car_engine_no, :appointment_date, :complaint, :mechanic_id)'
);

$requestedCarDateMap = [];
$requestedSlotsByMechanicDate = [];

try {
    $pdo->beginTransaction();

    for ($i = 0; $i < $totalCars; $i++) {
        $carRegNo = strtoupper(trim((string) ($carRegNumbers[$i] ?? '')));
        $carEngineNo = trim((string) ($carEngineNumbers[$i] ?? ''));
        $appointmentDate = trim((string) ($appointmentDates[$i] ?? ''));
        $complaint = trim((string) ($complaints[$i] ?? ''));
        $mechanicId = (int) ($mechanicIds[$i] ?? 0);
        $carNumber = $i + 1;

        if (
            $carRegNo === '' ||
            $carEngineNo === '' ||
            $appointmentDate === '' ||
            $complaint === '' ||
            $mechanicId <= 0
        ) {
            throw new RuntimeException('All fields are required for vehicle #' . $carNumber . '.');
        }

        if (!valid_future_or_today_date($appointmentDate)) {
            throw new RuntimeException('Appointment date is invalid for vehicle #' . $carNumber . '.');
        }

        if (!ctype_digit($carEngineNo)) {
            throw new RuntimeException('Car engine number must contain numbers only for vehicle #' . $carNumber . '.');
        }

        if (!isset($existingMechanicMap[$mechanicId])) {
            throw new RuntimeException('Selected mechanic does not exist for vehicle #' . $carNumber . '.');
        }

        $carDateKey = $carRegNo . '|' . $appointmentDate;
        if (isset($requestedCarDateMap[$carDateKey])) {
            throw new RuntimeException('Duplicate car/date found in this request for vehicle #' . $carNumber . '.');
        }
        $requestedCarDateMap[$carDateKey] = true;

        $duplicateStmt->execute([
            'car_reg_no' => $carRegNo,
            'appointment_date' => $appointmentDate,
        ]);
        if ((int) $duplicateStmt->fetchColumn() > 0) {
            throw new RuntimeException('This car already has an appointment on ' . $appointmentDate . ' (vehicle #' . $carNumber . ').');
        }

        $slotKey = $mechanicId . '|' . $appointmentDate;
        if (!isset($requestedSlotsByMechanicDate[$slotKey])) {
            $requestedSlotsByMechanicDate[$slotKey] = 0;
        }
        $requestedSlotsByMechanicDate[$slotKey]++;

        if (mechanic_slots_left($mechanicId, $appointmentDate) < $requestedSlotsByMechanicDate[$slotKey]) {
            throw new RuntimeException('Selected mechanic is fully booked for vehicle #' . $carNumber . '.');
        }

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
    }

    $pdo->commit();
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    set_flash($exception->getMessage(), 'error');
    redirect('/index.php?date=' . urlencode($redirectDate));
}

set_flash($totalCars . ' appointment(s) booked successfully.', 'success');
redirect('/index.php?date=' . urlencode($redirectDate));
