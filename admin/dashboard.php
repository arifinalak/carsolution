<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin();

$date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');
if (!DateTime::createFromFormat('Y-m-d', $date)) {
    $date = date('Y-m-d');
}

$mechanicsStmt = db()->query('SELECT id, name, phone FROM mechanics ORDER BY name ASC');
$mechanics = $mechanicsStmt->fetchAll();

$appointmentStmt = db()->prepare(
    'SELECT a.id, a.client_name, a.phone, a.car_reg_no, a.car_engine_no, a.appointment_date, a.complaint, a.status,
            m.id AS mechanic_id, m.name AS mechanic_name
     FROM appointments a
     JOIN mechanics m ON a.mechanic_id = m.id
     WHERE a.appointment_date = :appointment_date
     ORDER BY a.id DESC'
);
$appointmentStmt->execute(['appointment_date' => $date]);
$appointments = $appointmentStmt->fetchAll();

$title = 'Admin Dashboard';
require __DIR__ . '/../includes/header.php';
?>

<section class="card">
    <div class="row-between">
        <h2>Admin Dashboard</h2>
        <a class="link-button" href="/admin/logout.php">Logout</a>
    </div>
</section>

<section class="card">
    <h3>Mechanic Availability (<?= esc($date) ?>)</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Mechanic</th>
                <th>Phone</th>
                <th>Slots Left</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mechanics as $mechanic): ?>
            <tr>
                <td><?= esc($mechanic['name']) ?></td>
                <td><?= esc($mechanic['phone']) ?></td>
                <td><?= esc((string) mechanic_slots_left((int) $mechanic['id'], $date)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Appointment List</h3>
    <?php if (empty($appointments)): ?>
        <p>No appointments found for this date.</p>
    <?php else: ?>
        <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Car Reg</th>
                    <th>Engine No</th>
                    <th>Mechanic</th>
                    <th>Complaint</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $appointment): ?>
                <tr>
                    <td><?= esc((string) $appointment['id']) ?></td>
                    <td><?= esc($appointment['client_name']) ?></td>
                    <td><?= esc($appointment['phone']) ?></td>
                    <td><?= esc($appointment['car_reg_no']) ?></td>
                    <td><?= esc($appointment['car_engine_no']) ?></td>
                    <td><?= esc($appointment['mechanic_name']) ?></td>
                    <td><?= esc($appointment['complaint']) ?></td>
                    <td><span class="pill"><?= esc($appointment['status']) ?></span></td>
                    <td>
                        <form method="post" action="/admin/update_status.php" class="status-form">
                            <input type="hidden" name="appointment_id" value="<?= esc((string) $appointment['id']) ?>">
                            <input type="hidden" name="date" value="<?= esc($date) ?>">
                            <input type="date" name="appointment_date" value="<?= esc($appointment['appointment_date']) ?>" min="<?= esc(date('Y-m-d')) ?>" required>
                            <select name="mechanic_id" required>
                                <?php foreach ($mechanics as $mechanic): ?>
                                    <option value="<?= esc((string) $mechanic['id']) ?>" <?= (int) $appointment['mechanic_id'] === (int) $mechanic['id'] ? 'selected' : '' ?>>
                                        <?= esc($mechanic['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="status" required>
                                <?php
                                $statuses = ['Pending', 'In Progress', 'Completed', 'Cancelled'];
                                foreach ($statuses as $status):
                                ?>
                                    <option value="<?= esc($status) ?>" <?= $appointment['status'] === $status ? 'selected' : '' ?>>
                                        <?= esc($status) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</section>

<section class="card small-card">
    <h3>Add Mechanic</h3>
    <form method="post" action="/admin/add_mechanic.php">
        <label>Name
            <input type="text" name="name" maxlength="100" required>
        </label>

        <label>Phone
            <input type="text" name="phone" maxlength="30" required>
        </label>

        <button type="submit">Add</button>
    </form>
</section>

<?php require __DIR__ . '/../includes/footer.php';
