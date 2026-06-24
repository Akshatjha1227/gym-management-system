-- =====================================================
--  IronForge Gym Management System — MySQL Schema
--  Run this file first: mysql -u root -p < gym_schema.sql
-- =====================================================

CREATE DATABASE IF NOT EXISTS ironforge_gym CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ironforge_gym;

-- MEMBERSHIP PLANS
CREATE TABLE membership_plans (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    price        DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL DEFAULT 30,
    features     TEXT COMMENT 'Comma-separated list',
    description  TEXT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- TRAINERS
CREATE TABLE trainers (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    name               VARCHAR(100) NOT NULL,
    specialization     VARCHAR(100) NOT NULL,
    phone              VARCHAR(20),
    experience_years   INT DEFAULT 0,
    certifications     VARCHAR(255),
    salary             DECIMAL(10,2) DEFAULT 0,
    join_date          DATE,
    created_at         TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- MEMBERS
CREATE TABLE members (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(100) NOT NULL,
    phone               VARCHAR(20) NOT NULL,
    email               VARCHAR(120),
    gender              ENUM('Male','Female','Other') DEFAULT 'Male',
    dob                 DATE,
    plan_id             INT,
    join_date           DATE NOT NULL,
    expiry_date         DATE NOT NULL,
    trainer_id          INT,
    address             TEXT,
    emergency_contact   VARCHAR(200),
    health_notes        TEXT,
    status              ENUM('active','expired','pending') DEFAULT 'active',
    created_at          TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id)    REFERENCES membership_plans(id) ON DELETE SET NULL,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
);

-- ATTENDANCE
CREATE TABLE attendance (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    member_id        INT NOT NULL,
    checkin_time     TIME NOT NULL,
    checkout_time    TIME,
    duration         VARCHAR(20),
    attendance_date  DATE NOT NULL DEFAULT (CURDATE()),
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    UNIQUE KEY uq_member_day (member_id, attendance_date)
);

-- PAYMENTS
CREATE TABLE payments (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    member_id     INT NOT NULL,
    plan_id       INT NOT NULL,
    amount        DECIMAL(10,2) NOT NULL,
    method        ENUM('Cash','UPI','Card','Net Banking') DEFAULT 'Cash',
    payment_date  DATE NOT NULL,
    receipt_no    VARCHAR(20) UNIQUE,
    notes         TEXT,
    status        ENUM('paid','pending','refunded') DEFAULT 'paid',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id)   REFERENCES membership_plans(id) ON DELETE CASCADE
);

-- GYM CLASSES / SCHEDULE
CREATE TABLE gym_classes (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    class_name    VARCHAR(100) NOT NULL,
    trainer_id    INT,
    day           ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
    time_slot     TIME NOT NULL,
    duration_mins INT DEFAULT 60,
    max_capacity  INT DEFAULT 20,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id) ON DELETE SET NULL
);

-- EQUIPMENT
CREATE TABLE equipment (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    category         VARCHAR(50) DEFAULT 'General',
    quantity         INT DEFAULT 1,
    condition_status ENUM('Excellent','Good','Fair','Needs Repair') DEFAULT 'Good',
    purchase_date    DATE,
    last_serviced    DATE,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─── SEED DATA ────────────────────────────────────────
INSERT INTO membership_plans (name, price, duration_days, features, description) VALUES
('Monthly Basic',  999.00,  30,  'Gym Access,Locker',                          'Basic gym floor access'),
('Monthly Pro',   1799.00,  30,  'Gym Access,Locker,Classes,Trainer Session',  'Pro monthly with extras'),
('Quarterly',     2499.00,  90,  'Gym Access,Locker,Classes,2 Trainer Sessions','3-month value pack'),
('Annual',        7999.00, 365,  'Full Access,Locker,All Classes,Unlimited Trainer,Sauna', 'Best yearly deal');

INSERT INTO trainers (name, specialization, phone, experience_years, certifications, salary, join_date) VALUES
('Rahul Sharma', 'Weight Training', '9876543210', 5, 'ACE, CSCS',  32000, '2022-01-15'),
('Priya Patel',  'Yoga & Cardio',   '9765432109', 4, 'RYT-200',    28000, '2022-06-01'),
('Amit Verma',   'CrossFit',        '9654321098', 6, 'CF-L2',      35000, '2021-09-10');

INSERT INTO members (name, phone, email, gender, plan_id, join_date, expiry_date, trainer_id, status) VALUES
('Arjun Singh',  '9111111111', 'arjun@email.com',  'Male',   2, '2024-01-10', DATE_ADD(CURDATE(), INTERVAL 15 DAY),  1, 'active'),
('Sneha Gupta',  '9222222222', 'sneha@email.com',  'Female', 4, '2024-02-01', DATE_ADD(CURDATE(), INTERVAL 200 DAY), 2, 'active'),
('Vikram Joshi', '9333333333', 'vikram@email.com', 'Male',   1, '2024-03-05', DATE_SUB(CURDATE(), INTERVAL 5 DAY),   NULL,'expired'),
('Nisha Rao',    '9444444444', 'nisha@email.com',  'Female', 3, '2024-04-01', DATE_ADD(CURDATE(), INTERVAL 60 DAY),  2, 'active'),
('Rohan Mehra',  '9555555555', 'rohan@email.com',  'Male',   2, '2024-05-01', DATE_ADD(CURDATE(), INTERVAL 3 DAY),   3, 'active');

INSERT INTO payments (member_id, plan_id, amount, method, payment_date, receipt_no) VALUES
(1, 2, 1799, 'UPI',         '2024-01-10', 'RCP00001'),
(2, 4, 7999, 'Card',        '2024-02-01', 'RCP00002'),
(3, 1,  999, 'Cash',        '2024-03-05', 'RCP00003'),
(4, 3, 2499, 'UPI',         '2024-04-01', 'RCP00004'),
(5, 2, 1799, 'Net Banking', '2024-05-01', 'RCP00005');

INSERT INTO gym_classes (class_name, trainer_id, day, time_slot, duration_mins, max_capacity) VALUES
('Morning Yoga',    2, 'Monday',    '06:30:00', 60, 15),
('CrossFit HIIT',   3, 'Monday',    '07:30:00', 45, 20),
('Zumba',           2, 'Wednesday', '06:00:00', 60, 25),
('Powerlifting',    1, 'Friday',    '08:00:00', 90, 10),
('Pilates',         2, 'Saturday',  '09:00:00', 60, 20);

INSERT INTO equipment (name, category, quantity, condition_status, purchase_date, last_serviced) VALUES
('Treadmill',     'Cardio',       5,  'Good',         '2022-01-01', '2024-03-01'),
('Dumbbells Set', 'Free Weights', 10, 'Excellent',    '2021-06-01', '2024-01-01'),
('Bench Press',   'Strength',     3,  'Good',         '2022-03-01', '2024-02-01'),
('Elliptical',    'Cardio',       3,  'Fair',         '2021-01-01', '2023-12-01'),
('Pull-up Bar',   'Strength',     4,  'Excellent',    '2023-01-01', '2024-01-01');

INSERT INTO attendance (member_id, checkin_time, checkout_time, duration, attendance_date) VALUES
(1, '06:45:00', '08:15:00', '1h 30m', CURDATE()),
(2, '07:00:00', '08:30:00', '1h 30m', CURDATE()),
(5, '09:00:00', NULL,       NULL,     CURDATE());
