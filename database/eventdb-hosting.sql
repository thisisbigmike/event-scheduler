-- =====================================================================
-- Event Scheduling Management System - database for SHARED HOSTING
-- (InfinityFree, cPanel hosts, etc.)
--
-- Use this file instead of eventdb.sql when your host gives you a database
-- that already exists: open phpMyAdmin, select YOUR database on the left,
-- click Import and choose this file. It has no CREATE DATABASE / CREATE USER
-- lines, because shared hosts do not allow them.
--
-- Demo accounts are the same as in eventdb.sql (admin 08030000001 / Admin@123,
-- security answer: enugu).
-- =====================================================================

DROP TABLE IF EXISTS notification_log;
DROP TABLE IF EXISTS event_attendee;
DROP TABLE IF EXISTS event;
DROP TABLE IF EXISTS register;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS country;

-- ---------------------------------------------------------------------
-- country : the VENUES where events hold (name kept from Chapter 4)
--   country  = venue name, location = building / campus
-- ---------------------------------------------------------------------
CREATE TABLE country (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  country   VARCHAR(100) NOT NULL UNIQUE,
  location  VARCHAR(150) NOT NULL,
  capacity  INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- staff : list of valid IMT staff IDs. Registration is only allowed
--         with an ID on this list that has not been used yet.
-- ---------------------------------------------------------------------
CREATE TABLE staff (
  staffid     VARCHAR(20) PRIMARY KEY,
  fullname    VARCHAR(100) NOT NULL,
  department  VARCHAR(100) NOT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- register : user accounts (admin and normal users)
--   pn = phone number (used to log in), pass = password_hash() value
-- ---------------------------------------------------------------------
CREATE TABLE register (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  fname     VARCHAR(100) NOT NULL,
  gender    ENUM('Male','Female') NOT NULL,
  address   VARCHAR(255) NOT NULL,
  photo     VARCHAR(255) NOT NULL DEFAULT '',
  pn        VARCHAR(15)  NOT NULL UNIQUE,
  pass      VARCHAR(255) NOT NULL,
  datereg   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  email     VARCHAR(150) NOT NULL DEFAULT '',
  staffid   VARCHAR(20)  NOT NULL UNIQUE,
  role      ENUM('admin','user') NOT NULL DEFAULT 'user',
  active    TINYINT(1) NOT NULL DEFAULT 0,
  secq      VARCHAR(150) NOT NULL,
  seca      VARCHAR(255) NOT NULL,
  FOREIGN KEY (staffid) REFERENCES staff(staffid)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- event : scheduled events
--   fname = event title, messageofEvent = description,
--   feedback = admin remarks, contact = organiser phone,
--   datesent = date the record was created
-- ---------------------------------------------------------------------
CREATE TABLE event (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  fname           VARCHAR(150) NOT NULL,
  messageofEvent  TEXT NOT NULL,
  feedback        TEXT NULL,
  status          ENUM('Scheduled','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  contact         VARCHAR(15) NOT NULL,
  datesent        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  venue_id        INT NOT NULL,
  event_date      DATE NOT NULL,
  start_time      TIME NOT NULL,
  end_time        TIME NOT NULL,
  created_by      INT NOT NULL,
  reminder_sent   TINYINT(1) NOT NULL DEFAULT 0,
  FOREIGN KEY (venue_id)   REFERENCES country(id),
  FOREIGN KEY (created_by) REFERENCES register(id),
  INDEX idx_venue_date (venue_id, event_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- event_attendee : who is invited to which event (+ their feedback)
-- ---------------------------------------------------------------------
CREATE TABLE event_attendee (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  event_id       INT NOT NULL,
  user_id        INT NOT NULL,
  notified       TINYINT(1) NOT NULL DEFAULT 0,
  feedback       TEXT NULL,
  feedback_date  DATETIME NULL,
  UNIQUE KEY uq_event_user (event_id, user_id),
  FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES register(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- notification_log : every SMS / e-mail the system sends (or would send)
--   status: sent = delivered to gateway, demo = demo mode (not sent),
--           failed = gateway / internet error
-- ---------------------------------------------------------------------
CREATE TABLE notification_log (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NULL,
  event_id    INT NULL,
  channel     ENUM('sms','email') NOT NULL,
  recipient   VARCHAR(150) NOT NULL,
  message     TEXT NOT NULL,
  status      ENUM('sent','demo','failed') NOT NULL,
  response    TEXT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================================
-- DEMO DATA
-- =====================================================================

INSERT INTO country (country, location, capacity) VALUES
('Main Auditorium',        'Campus III, Agbani Road',   1200),
('Senate Chamber',         'Administrative Block',        80),
('ICT Centre Hall',        'Computer Science Dept.',     150),
('Multipurpose Hall',      'Campus I, Independence Layout', 500);

INSERT INTO staff (staffid, fullname, department) VALUES
('IMT/STF/0101', 'Admin Officer',     'Registry'),
('IMT/STF/0102', 'Chinedu Okafor',    'Computer Science'),
('IMT/STF/0103', 'Ngozi Eze',         'Accountancy'),
('IMT/STF/0104', 'Emeka Nwankwo',     'Mass Communication'),
('IMT/STF/0105', 'Adaeze Obi',        'Computer Science'),
('IMT/STF/0106', 'Spare Staff One',   'Computer Science'),
('IMT/STF/0107', 'Spare Staff Two',   'Business Admin'),
('IMT/STF/0108', 'Spare Staff Three', 'Estate Management'),
('IMT/STF/0109', 'Spare Staff Four',  'Library'),
('IMT/STF/0110', 'Spare Staff Five',  'Works Department');

-- Password hashes: Admin@123 / User@123. Security answer hash: "enugu".
INSERT INTO register (id, fname, gender, address, photo, pn, pass, email, staffid, role, active, secq, seca) VALUES
(1, 'Admin Officer',  'Male',   'Registry, IMT Campus III, Enugu', '', '08030000001',
 '$2y$10$Y3438Bv.rwTNKJPER2RtuOe1oYawplrr8QNgJ0jg09KZGkiYrhqyW', 'admin@imt.local',   'IMT/STF/0101', 'admin', 1,
 'In what city were you born?', '$2y$10$vLwu43zuqaIxEFXDlIyWbeZt9NZUOq6sccKcg3kSqEyUD4UfxbCry'),
(2, 'Chinedu Okafor', 'Male',   '12 Ogui Road, Enugu',             '', '08030000002',
 '$2y$10$X5FPUZOpBuxcuKxnrjgwJujONse9RZpbqv2OVicWBvaHamCTgLJCi', 'chinedu@imt.local', 'IMT/STF/0102', 'user',  1,
 'In what city were you born?', '$2y$10$vLwu43zuqaIxEFXDlIyWbeZt9NZUOq6sccKcg3kSqEyUD4UfxbCry'),
(3, 'Ngozi Eze',      'Female', '4 Zik Avenue, Uwani, Enugu',      '', '08030000003',
 '$2y$10$X5FPUZOpBuxcuKxnrjgwJujONse9RZpbqv2OVicWBvaHamCTgLJCi', 'ngozi@imt.local',   'IMT/STF/0103', 'user',  1,
 'In what city were you born?', '$2y$10$vLwu43zuqaIxEFXDlIyWbeZt9NZUOq6sccKcg3kSqEyUD4UfxbCry'),
(4, 'Emeka Nwankwo',  'Male',   '9 Chime Avenue, New Haven, Enugu','', '08030000004',
 '$2y$10$X5FPUZOpBuxcuKxnrjgwJujONse9RZpbqv2OVicWBvaHamCTgLJCi', 'emeka@imt.local',   'IMT/STF/0104', 'user',  1,
 'In what city were you born?', '$2y$10$vLwu43zuqaIxEFXDlIyWbeZt9NZUOq6sccKcg3kSqEyUD4UfxbCry'),
(5, 'Adaeze Obi',     'Female', '21 Abakpa Road, Enugu',           '', '08030000005',
 '$2y$10$X5FPUZOpBuxcuKxnrjgwJujONse9RZpbqv2OVicWBvaHamCTgLJCi', 'adaeze@imt.local',  'IMT/STF/0105', 'user',  0,
 'In what city were you born?', '$2y$10$vLwu43zuqaIxEFXDlIyWbeZt9NZUOq6sccKcg3kSqEyUD4UfxbCry');

-- Event dates are relative to the day you import, so the demo is always "upcoming".
INSERT INTO event (id, fname, messageofEvent, feedback, status, contact, venue_id, event_date, start_time, end_time, created_by, reminder_sent) VALUES
(1, 'Academic Board Meeting', 'Quarterly academic board meeting. All HODs to attend with reports.', NULL,
 'Scheduled', '08030000001', 2, CURDATE() + INTERVAL 1 DAY, '10:00:00', '12:00:00', 1, 0),
(2, 'ICT Training Workshop',  'Hands-on workshop on the new staff portal and e-mail system.', NULL,
 'Scheduled', '08030000001', 3, CURDATE() + INTERVAL 3 DAY, '09:00:00', '13:00:00', 1, 0),
(3, 'Matriculation Rehearsal', 'Rehearsal for the matriculation ceremony.', NULL,
 'Scheduled', '08030000001', 1, CURDATE() + INTERVAL 7 DAY, '14:00:00', '17:00:00', 1, 0),
(4, 'Staff Welfare Meeting',  'Monthly staff welfare meeting.', 'Meeting held; minutes circulated.',
 'Completed', '08030000001', 4, CURDATE() - INTERVAL 5 DAY, '11:00:00', '13:00:00', 1, 1);

INSERT INTO event_attendee (event_id, user_id, notified, feedback, feedback_date) VALUES
(1, 2, 1, NULL, NULL),
(1, 3, 1, NULL, NULL),
(2, 2, 1, NULL, NULL),
(2, 4, 1, NULL, NULL),
(3, 2, 1, NULL, NULL),
(3, 3, 1, NULL, NULL),
(3, 4, 1, NULL, NULL),
(4, 3, 1, 'Good meeting, but please start on time.', NOW() - INTERVAL 4 DAY),
(4, 4, 1, 'Thank you for the update on allowances.', NOW() - INTERVAL 4 DAY);
