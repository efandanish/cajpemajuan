-- Sistem Akaun Caj Pemajuan: clean installation database (structure only, no rows).
-- Target: MariaDB 10.4 (generated on MariaDB 10.4.27-MariaDB).
-- Import ONCE into a NEW, EMPTY database. Contains no users, passwords, financial records or receipts.
-- Includes migrations 001-005: do NOT run database/migrations/*.up.sql after importing this file.
-- There are deliberately no DROP statements: importing over an existing database stops with an error.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET time_zone = '+00:00';

-- Table `audit_logs`
CREATE TABLE `audit_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `action` varchar(20) NOT NULL,
  `entity_type` varchar(80) NOT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `actor_id` int(11) DEFAULT NULL,
  `actor_name` varchar(150) DEFAULT NULL,
  `actor_email` varchar(150) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `audit_entity` (`entity_type`,`entity_id`),
  KEY `audit_actor` (`actor_id`),
  KEY `audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `development_charges`
CREATE TABLE `development_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `developer_name` varchar(200) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `osc_id` varchar(100) DEFAULT NULL,
  `lot_number` varchar(100) DEFAULT NULL,
  `mukim` varchar(150) DEFAULT NULL,
  `file_number` varchar(150) DEFAULT NULL,
  `charge_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `osc_decision` enum('Lulus','Tangguh','Ditolak') DEFAULT 'Tangguh',
  `payment_plan` enum('Bayaran Penuh','3 Ansuran','4 Ansuran') NOT NULL DEFAULT 'Bayaran Penuh',
  `record_year` smallint(5) unsigned DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_charge_record_year` (`record_year`),
  CONSTRAINT `chk_charge_record_year` CHECK (`record_year` is null or `record_year` between 1000 and 9999)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `development_charge_payments`
CREATE TABLE `development_charge_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `development_charge_id` int(11) NOT NULL,
  `installment_no` int(11) NOT NULL DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Menunggu','Dibayar') NOT NULL DEFAULT 'Menunggu',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_charge_payment_charge` (`development_charge_id`),
  CONSTRAINT `fk_charge_payment_charge` FOREIGN KEY (`development_charge_id`) REFERENCES `development_charges` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `notifications`
CREATE TABLE `notifications` (
  `audience` varchar(20) NOT NULL DEFAULT 'legacy',
  `department` varchar(150) DEFAULT NULL,
  `reminder_key` varchar(150) DEFAULT NULL,
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('project','charge','payment','variation','system') NOT NULL,
  `action` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` varchar(255) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_project_reminder` (`reminder_key`),
  KEY `notification_entity_action` (`entity_type`,`entity_id`,`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `notification_reads`
CREATE TABLE `notification_reads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_notification_user` (`notification_id`,`user_id`),
  KEY `fk_notification_read_user` (`user_id`),
  CONSTRAINT `fk_notification_read_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notification_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `projects`
CREATE TABLE `projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` varchar(50) NOT NULL,
  `department` varchar(150) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `approval_status` enum('Diluluskan','Tangguh','Ditolak') DEFAULT 'Tangguh',
  `meeting_count` int(11) DEFAULT 0,
  `procurement_type` varchar(100) DEFAULT NULL,
  `sst_value` decimal(12,2) DEFAULT 0.00,
  `meeting_approval_value` decimal(12,2) DEFAULT 0.00,
  `payment_method` enum('One-off','Ansuran','Pajakan/Sewa') DEFAULT 'One-off',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `project_status` enum('Belum Mula','Dalam Proses','Selesai','Dibatalkan') DEFAULT 'Belum Mula',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `project_expenses`
CREATE TABLE `project_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `schedule_id` int(11) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `payment_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_expense_project` (`project_id`),
  KEY `fk_expense_schedule` (`schedule_id`),
  CONSTRAINT `fk_expense_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_expense_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `project_payment_schedules` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `project_payment_schedules`
CREATE TABLE `project_payment_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `claim_date` date NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('One-off','Ansuran','Pajakan/Sewa') NOT NULL,
  `status` enum('Menunggu','Dibayar') NOT NULL DEFAULT 'Menunggu',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_schedule_project` (`project_id`),
  CONSTRAINT `fk_schedule_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `project_variations`
CREATE TABLE `project_variations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `variation_type` enum('Tambah','Tolak') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `variation_date` date NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_variation_project` (`project_id`),
  CONSTRAINT `fk_variation_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table `users`
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `department` varchar(150) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
