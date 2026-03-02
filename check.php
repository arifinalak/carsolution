<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$appointments = [];
$carRegNo = strtoupper(trim($_GET['car_reg_no'] ?? ''));

if ($carRegNo !== '') {
    $stmt = db()->prepare(
        'SELECT a.id, a.client_name, a.phone, a.car_reg_no, a.appointment_date, a.status, m.name AS mechanic_name
         FROM appointments a
         JOIN mechanics m ON a.mechanic_id = m.id
         WHERE a.car_reg_no = :car_reg_no
         ORDER BY a.appointment_date DESC, a.id DESC'
    );
    $stmt->execute(['car_reg_no' => $carRegNo]);
    $appointments = $stmt->fetchAll();
}

$title = 'Check Appointments';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2>Check Appointment Status</h2>
    <form method="get" class="inline-filter">
        <label for="car_reg_no">Car Registration Number:</label>
        <input type="text" id="car_reg_no" name="car_reg_no" value="<?= esc($carRegNo) ?>" required>
        <button type="submit">Search</button>
    </form>
</section>

<?php if ($carRegNo !== ''): ?>
<section class="card">
    <h3>Results for <?= esc($carRegNo) ?></h3>
    <?php if (empty($appointments)): ?>
        <p>No appointment found for this car registration number.</p>
    <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Date</th>
                    <th>Mechanic</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= esc((string) $appointment['id']) ?></td>
                    <td><?= esc($appointment['client_name']) ?></td>
                    <td><?= esc($appointment['phone']) ?></td>
                    <td><?= esc($appointment['appointment_date']) ?></td>
                    <td><?= esc($appointment['mechanic_name']) ?></td>
                    <td><span class="pill"><?= esc($appointment['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php';
