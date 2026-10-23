-- =====================================================================
-- ePalmHaul — Web-Based Palm Harvest Hauling and Logistics Management
-- Database schema for Kenram Palm Oil Industry Incorporation (KPII)
-- ---------------------------------------------------------------------
-- Target : MySQL 5.7+ / MariaDB 10.3+ (XAMPP / phpMyAdmin)
-- Engine : InnoDB  |  Charset : utf8mb4  |  Collation : utf8mb4_general_ci
-- Author : system installer script
--
-- SECURITY NOTE
--   * No plaintext passwords are stored. The `users.password` column
--     holds a PHP password_hash() (bcrypt/argon2) string.
--   * Do NOT hardcode a public administrator account here. A secure
--     first-admin setup is handled by the application installer
--     (see the note at the bottom of this file).
-- =====================================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ---------------------------------------------------------------------
-- Drop in reverse-dependency order so the script is re-runnable
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `payment_computations`;
DROP TABLE IF EXISTS `scale_tickets`;
DROP TABLE IF EXISTS `pricing_settings`;
DROP TABLE IF EXISTS `deliveries`;
DROP TABLE IF EXISTS `hauling_schedules`;
DROP TABLE IF EXISTS `harvest_cycles`;
DROP TABLE IF EXISTS `hauling_requests`;
DROP TABLE IF EXISTS `trucks`;
DROP TABLE IF EXISTS `truck_maintenance`;
DROP TABLE IF EXISTS `drivers`;
DROP TABLE IF EXISTS `farmers`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- =====================================================================
-- 1. users — authentication credentials, role, account status
-- =====================================================================
CREATE TABLE `users` (
    `user_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name`    VARCHAR(150)  NOT NULL,
    `email`        VARCHAR(191)  NOT NULL,
    `password`     VARCHAR(255)  NOT NULL COMMENT 'PHP password_hash() output',
    `phone`        VARCHAR(20)   DEFAULT NULL,
    `role`         ENUM('admin','farmer','driver','weighbridge') NOT NULL DEFAULT 'farmer',
    `status`       ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    `last_login`   DATETIME      DEFAULT NULL,
    `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uq_users_email` (`email`),
    KEY `idx_users_role` (`role`),
    KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 2. farmers — farmer profile and farm information
-- =====================================================================
CREATE TABLE `farmers` (
    `farmer_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`           INT UNSIGNED NOT NULL,
    `fullname`          VARCHAR(150) NOT NULL,
    `contact_no`        VARCHAR(20)  NOT NULL,
    `farm_location`     VARCHAR(255) NOT NULL,
    `estimated_tons`    DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'estimated harvest volume (tons)',
    `distance_to_mill`  DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'km to mill',
    `last_harvest_date` DATE         DEFAULT NULL,
    `status`            ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`farmer_id`),
    UNIQUE KEY `uq_farmers_user` (`user_id`),
    KEY `idx_farmers_location` (`farm_location`),
    CONSTRAINT `fk_farmers_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 3. drivers — driver profile and availability
-- =====================================================================
CREATE TABLE `drivers` (
    `driver_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED NOT NULL,
    `fullname`        VARCHAR(150) NOT NULL,
    `contact_no`      VARCHAR(20)  NOT NULL,
    `license_no`      VARCHAR(50)  DEFAULT NULL,
    `license_expiry`  DATE         DEFAULT NULL,
    `home_base`       VARCHAR(255) DEFAULT NULL,
    `availability`    ENUM('available','on_trip','unavailable') NOT NULL DEFAULT 'available',
    `status`          ENUM('active','inactive') NOT NULL DEFAULT 'active',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`driver_id`),
    UNIQUE KEY `uq_drivers_user` (`user_id`),
    KEY `idx_drivers_availability` (`availability`),
    CONSTRAINT `fk_drivers_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 4. trucks — plate number, type, capacity, status
-- =====================================================================
CREATE TABLE `trucks` (
    `truck_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `plate_number`      VARCHAR(30)  NOT NULL,
    `truck_type`        ENUM('tipper','flatbed','container','tractor') NOT NULL DEFAULT 'tipper',
    `carrying_capacity` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'max load in tons',
    `status`            ENUM('available','in_use','maintenance','retired') NOT NULL DEFAULT 'available',
    `current_location`  VARCHAR(255) DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`truck_id`),
    UNIQUE KEY `uq_trucks_plate` (`plate_number`),
    KEY `idx_trucks_status` (`status`),
    KEY `idx_trucks_type` (`truck_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 5. hauling_requests — farmer request details and approval status
-- =====================================================================
CREATE TABLE `hauling_requests` (
    `request_id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farmer_id`              INT UNSIGNED NOT NULL,
    `preferred_date`         DATE         NOT NULL,
    `preferred_time`         TIME         NOT NULL,
    `farm_location`          VARCHAR(255) NOT NULL,
    `estimated_fresh_weight` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT 'estimated FFB tons',
    `requested_truck_type`   ENUM('tipper','flatbed','container','tractor') NOT NULL DEFAULT 'tipper',
    `distance_to_mill`       DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    `notes`                  TEXT         DEFAULT NULL,
    `status`                 ENUM('pending','approved','rejected','scheduled','cancelled') NOT NULL DEFAULT 'pending',
    `reviewed_by`            INT UNSIGNED DEFAULT NULL COMMENT 'admin user_id who reviewed',
    `review_date`            DATETIME     DEFAULT NULL,
    `review_remarks`         VARCHAR(255) DEFAULT NULL,
    `created_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`request_id`),
    KEY `idx_requests_farmer` (`farmer_id`),
    KEY `idx_requests_status` (`status`),
    KEY `idx_requests_preferred_date` (`preferred_date`),
    CONSTRAINT `fk_requests_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_requests_reviewer` FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 6. hauling_schedules — approved schedule, assigned driver/truck
-- =====================================================================
CREATE TABLE `hauling_schedules` (
    `schedule_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_id`      INT UNSIGNED NOT NULL,
    `farmer_id`       INT UNSIGNED NOT NULL,
    `driver_id`       INT UNSIGNED DEFAULT NULL,
    `truck_id`        INT UNSIGNED DEFAULT NULL,
    `scheduled_date`  DATE         NOT NULL,
    `scheduled_time`  TIME         NOT NULL,
    `status`          ENUM('scheduled','in_transit','arrived','delivered','completed','cancelled')
                      NOT NULL DEFAULT 'scheduled',
    `created_by`      INT UNSIGNED DEFAULT NULL COMMENT 'admin user_id',
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`schedule_id`),
    KEY `idx_sched_request` (`request_id`),
    KEY `idx_sched_driver` (`driver_id`),
    KEY `idx_sched_truck` (`truck_id`),
    KEY `idx_sched_date` (`scheduled_date`),
    -- One driver and one truck cannot be double-booked for the same date+time slot.
    UNIQUE KEY `uq_sched_driver_slot` (`driver_id`, `scheduled_date`, `scheduled_time`),
    UNIQUE KEY `uq_sched_truck_slot` (`truck_id`, `scheduled_date`, `scheduled_time`),
    CONSTRAINT `fk_sched_request` FOREIGN KEY (`request_id`)
        REFERENCES `hauling_requests` (`request_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sched_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_sched_driver` FOREIGN KEY (`driver_id`)
        REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sched_truck` FOREIGN KEY (`truck_id`)
        REFERENCES `trucks` (`truck_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_sched_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 7. harvest_cycles — previous and next expected harvest (20-day cycle)
-- =====================================================================
CREATE TABLE `harvest_cycles` (
    `cycle_id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `farmer_id`            INT UNSIGNED NOT NULL,
    `previous_harvest_date` DATE        NOT NULL,
    `next_harvest_date`     DATE        NOT NULL,
    `cycle_days`           SMALLINT UNSIGNED NOT NULL DEFAULT 20,
    `is_confirmed`         TINYINT(1)  NOT NULL DEFAULT 0 COMMENT '1 only when actual harvest recorded',
    `confirmed_by`         INT UNSIGNED DEFAULT NULL,
    `created_at`           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`cycle_id`),
    KEY `idx_cycle_farmer` (`farmer_id`),
    KEY `idx_cycle_next` (`next_harvest_date`),
    CONSTRAINT `fk_cycle_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_confirmed_by` FOREIGN KEY (`confirmed_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 8. deliveries — movement of a scheduled trip through the workflow
-- =====================================================================
CREATE TABLE `deliveries` (
    `delivery_id`       INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `schedule_id`       INT UNSIGNED NOT NULL,
    `driver_id`         INT UNSIGNED DEFAULT NULL,
    `truck_id`          INT UNSIGNED DEFAULT NULL,
    `departure_time`    DATETIME     DEFAULT NULL,
    `estimated_arrival` DATETIME     DEFAULT NULL,
    `actual_arrival`    DATETIME     DEFAULT NULL,
    `completion_time`   DATETIME     DEFAULT NULL,
    `status`            ENUM('scheduled','in_transit','arrived','delivered','completed','cancelled')
                        NOT NULL DEFAULT 'scheduled',
    `notes`             TEXT         DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`delivery_id`),
    UNIQUE KEY `uq_delivery_schedule` (`schedule_id`),
    KEY `idx_delivery_status` (`status`),
    CONSTRAINT `fk_delivery_schedule` FOREIGN KEY (`schedule_id`)
        REFERENCES `hauling_schedules` (`schedule_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_delivery_driver` FOREIGN KEY (`driver_id`)
        REFERENCES `drivers` (`driver_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_delivery_truck` FOREIGN KEY (`truck_id`)
        REFERENCES `trucks` (`truck_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 9. scale_tickets — weighbridge record per delivery
-- =====================================================================
CREATE TABLE `scale_tickets` (
    `ticket_id`     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_number` VARCHAR(40)  NOT NULL,
    `delivery_id`   INT UNSIGNED NOT NULL,
    `farmer_id`     INT UNSIGNED NOT NULL,
    `truck_id`      INT UNSIGNED DEFAULT NULL,
    `farm_location` VARCHAR(255) DEFAULT NULL,
    `harvest_date`  DATE         DEFAULT NULL,
    `fruit_grade`   VARCHAR(20)  DEFAULT NULL COMMENT 'fruit grade / class',
    `gross_weight`  DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'kg',
    `tare_weight`   DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'kg',
    `net_weight`    DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'gross - tare (kg)',
    `weighed_by`    INT UNSIGNED DEFAULT NULL COMMENT 'weighbridge operator user_id',
    `registered_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ticket_id`),
    UNIQUE KEY `uq_ticket_number` (`ticket_number`),
    UNIQUE KEY `uq_ticket_delivery` (`delivery_id`),
    KEY `idx_ticket_farmer` (`farmer_id`),
    CONSTRAINT `fk_ticket_delivery` FOREIGN KEY (`delivery_id`)
        REFERENCES `deliveries` (`delivery_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ticket_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ticket_truck` FOREIGN KEY (`truck_id`)
        REFERENCES `trucks` (`truck_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_ticket_weighed_by` FOREIGN KEY (`weighed_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 10. pricing_settings — FFB price, haulage rate, incentives, bonuses
-- =====================================================================
CREATE TABLE `pricing_settings` (
    `pricing_id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ffb_price_per_kg`   DECIMAL(10,4) NOT NULL DEFAULT 0.0000,
    `haulage_rate_ton`   DECIMAL(10,4) NOT NULL DEFAULT 0.0000 COMMENT 'deduction per ton hauled',
    `incentive_rate`     DECIMAL(6,4)  NOT NULL DEFAULT 0.0000 COMMENT 'fractional incentive on gross',
    `quality_bonus_rate` DECIMAL(6,4)  NOT NULL DEFAULT 0.0000 COMMENT 'fractional bonus for grade',
    `effective_from`     DATE          NOT NULL,
    `effective_to`       DATE          DEFAULT NULL COMMENT 'NULL = currently active',
    `created_by`         INT UNSIGNED  DEFAULT NULL,
    `created_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`pricing_id`),
    KEY `idx_pricing_effective` (`effective_from`, `effective_to`),
    CONSTRAINT `fk_pricing_created_by` FOREIGN KEY (`created_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 11. payment_computations — final payout breakdown per ticket
-- =====================================================================
CREATE TABLE `payment_computations` (
    `computation_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ticket_id`         INT UNSIGNED NOT NULL,
    `farmer_id`         INT UNSIGNED NOT NULL,
    `pricing_id`        INT UNSIGNED DEFAULT NULL,
    `net_weight_kg`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `gross_payment`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `incentive_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `quality_bonus`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `haulage_deduction` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_deductions`  DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `net_payable`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `computed_by`       INT UNSIGNED DEFAULT NULL,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`computation_id`),
    UNIQUE KEY `uq_payment_ticket` (`ticket_id`),
    KEY `idx_payment_farmer` (`farmer_id`),
    CONSTRAINT `fk_payment_ticket` FOREIGN KEY (`ticket_id`)
        REFERENCES `scale_tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_farmer` FOREIGN KEY (`farmer_id`)
        REFERENCES `farmers` (`farmer_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_pricing` FOREIGN KEY (`pricing_id`)
        REFERENCES `pricing_settings` (`pricing_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_computed_by` FOREIGN KEY (`computed_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 12. notifications — recipient, message, type, read status
-- =====================================================================
CREATE TABLE `notifications` (
    `notification_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recipient_user_id` INT UNSIGNED NOT NULL,
    `title`             VARCHAR(150) NOT NULL,
    `message`           TEXT         NOT NULL,
    `type`              ENUM('info','success','warning','error') NOT NULL DEFAULT 'info',
    `link`              VARCHAR(255) DEFAULT NULL,
    `is_read`           TINYINT(1)   NOT NULL DEFAULT 0,
    `created_at`        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`notification_id`),
    KEY `idx_notif_recipient` (`recipient_user_id`, `is_read`),
    CONSTRAINT `fk_notif_recipient` FOREIGN KEY (`recipient_user_id`)
        REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 13. truck_maintenance — maintenance date, description, status
-- =====================================================================
CREATE TABLE `truck_maintenance` (
    `maintenance_id`   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `truck_id`         INT UNSIGNED NOT NULL,
    `maintenance_date` DATE         NOT NULL,
    `description`      VARCHAR(255) NOT NULL,
    `cost`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status`           ENUM('scheduled','in_progress','completed') NOT NULL DEFAULT 'scheduled',
    `performed_by`     INT UNSIGNED DEFAULT NULL,
    `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`maintenance_id`),
    KEY `idx_maint_truck` (`truck_id`),
    KEY `idx_maint_date` (`maintenance_date`),
    CONSTRAINT `fk_maint_truck` FOREIGN KEY (`truck_id`)
        REFERENCES `trucks` (`truck_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_maint_performed_by` FOREIGN KEY (`performed_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 14. activity_logs — authorized actions, user, timestamp, record
-- =====================================================================
CREATE TABLE `activity_logs` (
    `log_id`      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED DEFAULT NULL,
    `action`      VARCHAR(100) NOT NULL,
    `table_name`  VARCHAR(64)  DEFAULT NULL,
    `record_id`   VARCHAR(50)  DEFAULT NULL,
    `details`     TEXT         DEFAULT NULL,
    `ip_address`  VARCHAR(45)  DEFAULT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `idx_log_user` (`user_id`),
    KEY `idx_log_created` (`created_at`),
    CONSTRAINT `fk_log_user` FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- 15. system_settings — configurable operational settings
-- =====================================================================
CREATE TABLE `system_settings` (
    `setting_id`    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100) NOT NULL,
    `setting_value` VARCHAR(255) NOT NULL,
    `description`   VARCHAR(255) DEFAULT NULL,
    `updated_by`    INT UNSIGNED DEFAULT NULL,
    `updated_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `uq_setting_key` (`setting_key`),
    CONSTRAINT `fk_setting_updated_by` FOREIGN KEY (`updated_by`)
        REFERENCES `users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- =====================================================================
-- Safe initial configuration data (no credentials, no PII)
-- =====================================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('harvest_cycle_days', '20', 'Default palm harvest cycle length in days'),
('company_name', 'Kenram Palm Oil Industry Incorporation', 'Operating company name'),
('currency', 'SYP', 'Currency used for payments (Syrian Pound)'),
('reminder_days_before', '3', 'Days before next harvest date to send reminder'),
('allow_double_booking', '0', 'Whether driver/truck may hold overlapping schedules');

INSERT INTO `pricing_settings`
    (`ffb_price_per_kg`, `haulage_rate_ton`, `incentive_rate`, `quality_bonus_rate`, `effective_from`, `effective_to`)
VALUES
    (1.5000, 25.0000, 0.0500, 0.0300, CURDATE(), NULL);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- SECURE FIRST-ADMINISTRATOR SETUP
-- ---------------------------------------------------------------------
-- An administrator is intentionally NOT inserted here to avoid shipping
-- hardcoded/public credentials. To bootstrap the system, run once:
--
--   php setup_admin.php   (application installer that:
--     1. prompts for full name, email and password,
--     2. stores the password via password_hash($pw, PASSWORD_DEFAULT),
--     3. inserts a single row into `users` with role = 'admin'.)
--
-- Example (do not paste a real password into this file):
--   INSERT INTO users (full_name, email, password, role, status)
--   VALUES (:name, :email, :password_hash, 'admin', 'active');
-- =====================================================================
