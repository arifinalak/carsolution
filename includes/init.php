<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function parse_sql_statements(string $sql): array
{
    $statements = [];
    $buffer = '';
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prevChar = $i > 0 ? $sql[$i - 1] : '';

        if ($char === "'" && !$inDoubleQuote && $prevChar !== '\\') {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($char === '"' && !$inSingleQuote && $prevChar !== '\\') {
            $inDoubleQuote = !$inDoubleQuote;
        }

        if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
            $statement = trim($buffer);
            if ($statement !== '') {
                $statements[] = $statement;
            }
            $buffer = '';
            continue;
        }

        $buffer .= $char;
    }

    $statement = trim($buffer);
    if ($statement !== '') {
        $statements[] = $statement;
    }

    return $statements;
}

function bootstrap_database_schema(): void
{
    $serverDsn = sprintf('mysql:host=%s;port=%d;charset=utf8mb4', DB_HOST, DB_PORT);
    $serverOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $serverPdo = new PDO($serverDsn, DB_USER, DB_PASS, $serverOptions);
    $serverPdo->exec(sprintf('CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci', DB_NAME));
    $serverPdo->exec(sprintf('USE `%s`', DB_NAME));

    $schemaPath = __DIR__ . '/../db/schema.sql';
    $schemaSql = file_get_contents($schemaPath);

    if ($schemaSql === false) {
        throw new RuntimeException('Could not read db/schema.sql.');
    }

    $statements = parse_sql_statements($schemaSql);

    foreach ($statements as $statement) {
        $serverPdo->exec($statement);
    }
}

function ensure_default_admin_credentials(PDO $pdo): void
{
    $username = 'admin';
    $plainPassword = 'admin123';

    $selectStmt = $pdo->prepare('SELECT id, password_hash FROM admins WHERE username = :username LIMIT 1');
    $selectStmt->execute(['username' => $username]);
    $admin = $selectStmt->fetch();

    if (!$admin) {
        $insertStmt = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:username, :password_hash)');
        $insertStmt->execute([
            'username' => $username,
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
        ]);
        return;
    }

    if (!password_verify($plainPassword, $admin['password_hash'])) {
        $updateStmt = $pdo->prepare('UPDATE admins SET password_hash = :password_hash WHERE id = :id');
        $updateStmt->execute([
            'password_hash' => password_hash($plainPassword, PASSWORD_DEFAULT),
            'id' => $admin['id'],
        ]);
    }
}

function ensure_default_mechanics(PDO $pdo): void
{
    $defaultMechanics = [
        ['name' => 'Jamal Uddin', 'phone' => '01825490012'],
        ['name' => 'Ibrahim Khan', 'phone' => '01913888076'],
        ['name' => 'Sayed Akmol', 'phone' => '01625598046'],
        ['name' => 'Arun Babu', 'phone' => '01565887012'],
    ];

    $existingStmt = $pdo->query('SELECT id FROM mechanics ORDER BY id ASC');
    $existingIds = $existingStmt->fetchAll(PDO::FETCH_COLUMN);

    $updateStmt = $pdo->prepare('UPDATE mechanics SET name = :name, phone = :phone WHERE id = :id');
    $insertStmt = $pdo->prepare('INSERT INTO mechanics (name, phone) VALUES (:name, :phone)');

    foreach ($defaultMechanics as $index => $mechanic) {
        if (isset($existingIds[$index])) {
            $updateStmt->execute([
                'id' => $existingIds[$index],
                'name' => $mechanic['name'],
                'phone' => $mechanic['phone'],
            ]);
        } else {
            $insertStmt->execute([
                'name' => $mechanic['name'],
                'phone' => $mechanic['phone'],
            ]);
        }
    }
}

function ensure_mechanic_slot_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS mechanic_daily_slots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            mechanic_id INT NOT NULL,
            slot_date DATE NOT NULL,
            total_slots INT NOT NULL DEFAULT 4,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_daily_slots_mechanic
                FOREIGN KEY (mechanic_id) REFERENCES mechanics(id)
                ON DELETE CASCADE ON UPDATE CASCADE,
            UNIQUE KEY uq_mechanic_date (mechanic_id, slot_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    // Auto-migrate older installs where this table exists without new columns.
    $hasTotalSlotsStmt = $pdo->query("SHOW COLUMNS FROM mechanic_daily_slots LIKE 'total_slots'");
    if (!$hasTotalSlotsStmt->fetch()) {
        $pdo->exec('ALTER TABLE mechanic_daily_slots ADD COLUMN total_slots INT NOT NULL DEFAULT 4 AFTER slot_date');
    }

    $hasUpdatedAtStmt = $pdo->query("SHOW COLUMNS FROM mechanic_daily_slots LIKE 'updated_at'");
    if (!$hasUpdatedAtStmt->fetch()) {
        $pdo->exec('ALTER TABLE mechanic_daily_slots ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at');
    }

    $hasUniqueKeyStmt = $pdo->query("SHOW INDEX FROM mechanic_daily_slots WHERE Key_name = 'uq_mechanic_date'");
    if (!$hasUniqueKeyStmt->fetch()) {
        $pdo->exec('ALTER TABLE mechanic_daily_slots ADD UNIQUE KEY uq_mechanic_date (mechanic_id, slot_date)');
    }
}

function render_database_error_page(string $message): void
{
    http_response_code(500);
    $safeMessage = esc($message);
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Error</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; color: #1f2937; }
        .wrap { max-width: 760px; margin: 60px auto; background: #fff; padding: 24px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,.08); }
        h1 { margin-top: 0; font-size: 24px; }
        code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
        ul { line-height: 1.6; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>Database Setup Required</h1>
        <p>The application could not connect to MySQL.</p>
        <p><strong>Details:</strong> {$safeMessage}</p>
        <ul>
            <li>Make sure MySQL server is running.</li>
            <li>Verify database credentials in <code>config/database.php</code>.</li>
            <li>Import <code>db/schema.sql</code> to create required tables.</li>
            <li>If message includes <code>could not find driver</code>, enable PHP extension <code>pdo_mysql</code>.</li>
        </ul>
    </div>
</body>
</html>
HTML;
    exit;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $exception) {
            $message = $exception->getMessage();
            $isUnknownDatabase = str_contains($message, 'Unknown database');

            if ($isUnknownDatabase) {
                try {
                    bootstrap_database_schema();
                    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                } catch (Throwable $bootstrapException) {
                    render_database_error_page($bootstrapException->getMessage());
                }
            } else {
                render_database_error_page($message);
            }
        }

        ensure_default_admin_credentials($pdo);
        ensure_default_mechanics($pdo);
        ensure_mechanic_slot_table($pdo);
    }

    return $pdo;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!is_admin_logged_in()) {
        redirect('/admin/login.php');
    }
}

function valid_future_or_today_date(string $date): bool
{
    $appointmentDate = DateTime::createFromFormat('Y-m-d', $date);
    if (!$appointmentDate) {
        return false;
    }

    $today = new DateTime('today');
    return $appointmentDate >= $today;
}

function mechanic_slots_left(int $mechanicId, string $date): int
{
    $capacity = mechanic_total_slots($mechanicId, $date);

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM appointments WHERE mechanic_id = :mechanic_id AND appointment_date = :appointment_date'
    );
    $stmt->execute([
        'mechanic_id' => $mechanicId,
        'appointment_date' => $date,
    ]);

    $booked = (int) $stmt->fetchColumn();

    return max(0, $capacity - $booked);
}

function mechanic_total_slots(int $mechanicId, string $date): int
{
    $defaultCapacity = 4;

    $stmt = db()->prepare(
        'SELECT total_slots
         FROM mechanic_daily_slots
         WHERE mechanic_id = :mechanic_id AND slot_date = :slot_date
         LIMIT 1'
    );
    $stmt->execute([
        'mechanic_id' => $mechanicId,
        'slot_date' => $date,
    ]);

    $capacity = $stmt->fetchColumn();
    if ($capacity === false) {
        return $defaultCapacity;
    }

    return max(0, (int) $capacity);
}

function set_mechanic_daily_slots(int $mechanicId, string $date, int $totalSlots): void
{
    $totalSlots = max(0, $totalSlots);

    $stmt = db()->prepare(
        'INSERT INTO mechanic_daily_slots (mechanic_id, slot_date, total_slots)
         VALUES (:mechanic_id, :slot_date, :total_slots)
         ON DUPLICATE KEY UPDATE total_slots = VALUES(total_slots)'
    );
    $stmt->execute([
        'mechanic_id' => $mechanicId,
        'slot_date' => $date,
        'total_slots' => $totalSlots,
    ]);
}
