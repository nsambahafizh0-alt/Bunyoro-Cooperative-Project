CREATE DATABASE IF NOT EXISTS bunyoro_cooperative CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bunyoro_cooperative;

DROP TABLE IF EXISTS fines;
DROP TABLE IF EXISTS loan_repayments;
DROP TABLE IF EXISTS loans;
DROP TABLE IF EXISTS savings;
DROP TABLE IF EXISTS members;

CREATE TABLE members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  phone VARCHAR(20) NOT NULL,
  role ENUM('Chairperson','Treasurer','Secretary','Member') NOT NULL DEFAULT 'Member',
  join_date DATE NOT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE savings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,0) NOT NULL,
  deposit_date DATE NOT NULL,
  method ENUM('Cash','MTN MoMo','Airtel Money') NOT NULL DEFAULT 'Cash',
  recorded_by VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB;

CREATE TABLE loans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  principal DECIMAL(12,0) NOT NULL,
  interest_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  issue_date DATE NOT NULL,
  due_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB;

CREATE TABLE loan_repayments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  loan_id INT UNSIGNED NOT NULL,
  amount DECIMAL(12,0) NOT NULL,
  paid_date DATE NOT NULL,
  method ENUM('Cash','MTN MoMo','Airtel Money') NOT NULL DEFAULT 'Cash',
  recorded_by VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (loan_id) REFERENCES loans(id)
) ENGINE=InnoDB;

CREATE TABLE fines (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_id INT UNSIGNED NOT NULL,
  reason VARCHAR(150) NOT NULL,
  amount DECIMAL(12,0) NOT NULL,
  fine_date DATE NOT NULL,
  status ENUM('Paid','Unpaid') NOT NULL DEFAULT 'Unpaid',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (member_id) REFERENCES members(id)
) ENGINE=InnoDB;

INSERT INTO members (full_name, phone, role, join_date, status) VALUES
('Asiimwe Grace',     '0772 410 551', 'Chairperson', '2025-01-12', 'Active'),
('Byaruhanga Moses',  '0701 238 774', 'Treasurer',   '2025-01-12', 'Active'),
('Kyomuhendo Sarah',  '0756 903 128', 'Secretary',   '2025-01-12', 'Active'),
('Tumusiime Robert',  '0782 664 309', 'Member',      '2025-02-02', 'Active'),
('Atuhaire Doreen',   '0703 117 845', 'Member',      '2025-02-02', 'Active');

INSERT INTO savings (member_id, amount, deposit_date, method, recorded_by)
SELECT m.id, 20000 + (m.id MOD 4) * 10000, d.dt,
       ELT(1 + (m.id MOD 3), 'Cash', 'MTN MoMo', 'Airtel Money'), 'Kyomuhendo Sarah'
FROM members m
CROSS JOIN (SELECT '2026-08-30' AS dt UNION SELECT '2026-09-27' UNION SELECT '2026-10-25') d
WHERE m.id <= 5;

INSERT INTO loans (member_id, principal, interest_rate, issue_date, due_date) VALUES
(4, 300000, 10.00, '2026-09-06', '2026-12-06');

INSERT INTO loan_repayments (loan_id, amount, paid_date, method, recorded_by) VALUES
(1, 100000, '2026-10-04', 'MTN MoMo',     'Byaruhanga Moses'),
(1,  80000, '2026-11-01', 'Cash',         'Byaruhanga Moses');

INSERT INTO fines (member_id, reason, amount, fine_date, status) VALUES
(5, 'Late arrival', 2000, '2026-09-27', 'Paid');
