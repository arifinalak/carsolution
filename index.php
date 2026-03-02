<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$date = isset($_GET['date']) && $_GET['date'] !== '' ? $_GET['date'] : date('Y-m-d');
if (!valid_future_or_today_date($date)) {
    $date = date('Y-m-d');
}

$mechanicStmt = db()->query('SELECT id, name, phone FROM mechanics ORDER BY name ASC');
$mechanics = $mechanicStmt->fetchAll();

$title = 'Book Appointment';
require __DIR__ . '/includes/header.php';
?>

<section class="card">
    <h2>Make an Appointment</h2>
    <p>Select date, choose an available mechanic, and submit your vehicle details.</p>

    <table class="table">
        <thead>
            <tr>
                <th>Mechanic</th>
                <th>Phone</th>
                <th>Available Slots (Max 4)</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($mechanics as $mechanic): ?>
            <?php $slotsLeft = mechanic_slots_left((int) $mechanic['id'], $date); ?>
            <tr>
                <td><?= esc($mechanic['name']) ?></td>
                <td><?= esc($mechanic['phone']) ?></td>
                <td><?= esc((string) $slotsLeft) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>

<section class="card">
    <h3>Appointment Form</h3>
    <form method="post" action="/book.php" id="appointmentForm">
        <div class="grid">
            <label>Appointment Date
                <input type="date" name="appointment_date" value="<?= esc($date) ?>" min="<?= esc(date('Y-m-d')) ?>" required>
            </label>

            <label>Client Name
                <input type="text" name="client_name" maxlength="100" required>
            </label>

            <label>Phone
                <input type="text" name="phone" maxlength="30" required>
            </label>

            <label>Car Registration Number
                <input type="text" name="car_reg_no" maxlength="60" required>
            </label>

            <label>Car Engine Number
                <input type="text" name="car_engine_no" maxlength="60" inputmode="numeric" pattern="[0-9]+" required>
            </label>

            <label class="full">Address
                <textarea name="address" rows="3" required></textarea>
            </label>

            <label class="full">Complaint
                <textarea name="complaint" rows="4" required></textarea>
            </label>

            <label>Choose Mechanic
                <select name="mechanic_id" required>
                    <option value="">Select mechanic</option>
                    <?php foreach ($mechanics as $mechanic): ?>
                        <?php $slotsLeft = mechanic_slots_left((int) $mechanic['id'], $date); ?>
                        <option value="<?= esc((string) $mechanic['id']) ?>" <?= $slotsLeft === 0 ? 'disabled' : '' ?>>
                            <?= esc($mechanic['name']) ?> (Slots left: <?= esc((string) $slotsLeft) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>

        <button type="submit">Book Appointment</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
