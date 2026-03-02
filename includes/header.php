<?php
/** @var string $title */
if (!isset($title)) {
    $title = 'Appointment System';
}
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container nav-wrap">
        <h1 class="logo"><a href="/index.php">Car Solution - Vehicle Appointment System</a></h1>
        <nav>
            <a href="/index.php">Book Appointment</a>
            <a href="/admin/login.php">Admin</a>
        </nav>
    </div>
</header>
<main class="container main-content">
    <?php if ($flash): ?>
        <div class="alert <?= esc($flash['type']) ?>"><?= esc($flash['message']) ?></div>
    <?php endif; ?>
