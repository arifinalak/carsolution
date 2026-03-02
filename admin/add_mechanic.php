<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/admin/dashboard.php');
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

if ($name === '' || $phone === '') {
    set_flash('Name and phone are required.', 'error');
    redirect('/admin/dashboard.php');
}

$stmt = db()->prepare('INSERT INTO mechanics (name, phone) VALUES (:name, :phone)');
$stmt->execute([
    'name' => $name,
    'phone' => $phone,
]);

set_flash('Mechanic added successfully.', 'success');
redirect('/admin/dashboard.php');
