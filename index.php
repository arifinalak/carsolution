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
                <th>Available Slots</th>
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
            <label>Client Name
                <input type="text" name="client_name" maxlength="100" required>
            </label>

            <label>Phone
                <input type="text" name="phone" maxlength="30" required>
            </label>

            <label class="full">Address
                <textarea name="address" rows="3" required></textarea>
            </label>
        </div>

        <div id="vehicleList" class="vehicle-list">
            <div class="vehicle-card" data-vehicle-index="1">
                <div class="vehicle-card-head">
                    <h4>Vehicle 1</h4>
                </div>

                <div class="grid">
                    <label>Car Registration Number
                        <input type="text" name="car_reg_no[]" maxlength="60" required>
                    </label>

                    <label>Car Engine Number
                        <input type="text" name="car_engine_no[]" maxlength="60" inputmode="numeric" pattern="[0-9]+" required>
                    </label>

                    <label>Appointment Date
                        <input type="date" name="appointment_date[]" value="<?= esc($date) ?>" min="<?= esc(date('Y-m-d')) ?>" required>
                    </label>

                    <label>Choose Mechanic
                        <select name="mechanic_id[]" required>
                            <option value="">Select mechanic</option>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <option value="<?= esc((string) $mechanic['id']) ?>">
                                    <?= esc($mechanic['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="full">Complaint
                        <textarea name="complaint[]" rows="4" required></textarea>
                    </label>
                </div>
            </div>
        </div>

        <div class="row-between add-car-wrap">
            <button type="button" id="addCarButton" class="secondary-btn">+ Add Another Car</button>
        </div>

        <template id="vehicleTemplate">
            <div class="vehicle-card" data-vehicle-index="__INDEX__">
                <div class="vehicle-card-head">
                    <h4>Vehicle __INDEX__</h4>
                    <button type="button" class="danger-btn remove-car-btn">Remove</button>
                </div>

                <div class="grid">
                    <label>Car Registration Number
                        <input type="text" name="car_reg_no[]" maxlength="60" required>
                    </label>

                    <label>Car Engine Number
                        <input type="text" name="car_engine_no[]" maxlength="60" inputmode="numeric" pattern="[0-9]+" required>
                    </label>

                    <label>Appointment Date
                        <input type="date" name="appointment_date[]" value="<?= esc($date) ?>" min="<?= esc(date('Y-m-d')) ?>" required>
                    </label>

                    <label>Choose Mechanic
                        <select name="mechanic_id[]" required>
                            <option value="">Select mechanic</option>
                            <?php foreach ($mechanics as $mechanic): ?>
                                <option value="<?= esc((string) $mechanic['id']) ?>">
                                    <?= esc($mechanic['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label class="full">Complaint
                        <textarea name="complaint[]" rows="4" required></textarea>
                    </label>
                </div>
            </div>
        </template>

        <button type="submit">Book Appointment(s)</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php';
