CREATE DATABASE IF NOT EXISTS cse391_appointments CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cse391_appointments;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS mechanics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    car_reg_no VARCHAR(60) NOT NULL,
    car_engine_no VARCHAR(60) NOT NULL,
    appointment_date DATE NOT NULL,
    complaint TEXT NOT NULL,
    mechanic_id INT NOT NULL,
    status ENUM('Pending', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_appointments_mechanic
        FOREIGN KEY (mechanic_id) REFERENCES mechanics(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_mechanic_date (mechanic_id, appointment_date),
    INDEX idx_car_date (car_reg_no, appointment_date)
);

INSERT INTO admins (username, password_hash)
VALUES ('admin', '$2y$10$zYH4pJsrQK0QbQ1PcQnYz.0xy8ykQd3f5YwQ9m7l2f0BgA6IKBfA2')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO mechanics (name, phone)
VALUES
    ('Jamal Uddin', '01825490012'),
    ('Ibrahim Khan', '01913888076'),
    ('Sayed Akmol', '01625598046'),
    ('Arun Babu', '01565887012')
ON DUPLICATE KEY UPDATE name = VALUES(name), phone = VALUES(phone);
