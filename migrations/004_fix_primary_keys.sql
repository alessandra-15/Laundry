-- 004_fix_primary_keys.sql
-- Repairs tables that were imported WITHOUT a PRIMARY KEY / AUTO_INCREMENT.
--
-- Symptoms this fixes:
--   * New customers registered with Customer_ID = 0 -> "Session error: invalid customer ID"
--   * Rows duplicated 3-4x (the dump was imported more than once and nothing
--     stopped duplicate inserts)
--
-- For each table that has no PRIMARY KEY yet it:
--   1. deletes exact duplicate rows (keeps one copy)
--   2. resolves rows that share an ID but differ (customer_info only, see below)
--   3. gives rows with ID 0 a new unique ID
--   4. adds PRIMARY KEY + AUTO_INCREMENT so this can't happen again
-- Tables that already have a PRIMARY KEY, or don't exist, are skipped, so it is safe to run again.
--
-- HOW TO RUN: phpMyAdmin -> select the database -> Export (backup first!)
--             -> SQL tab -> paste this whole file -> Go.
-- After running, customers who were logged in must log out and log back in.

-- ─────────────────────────────────────────────
-- customer_info
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_info')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customer_info' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `customer_info` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `customer_info` t1 JOIN `customer_info` t2 ON t2.`_rid` < t1.`_rid` AND t1.`Customer_ID` <=> t2.`Customer_ID` AND t1.`first_name` <=> t2.`first_name` AND t1.`last_name` <=> t2.`last_name` AND t1.`email` <=> t2.`email` AND t1.`register_date` <=> t2.`register_date` AND t1.`contact_number` <=> t2.`contact_number` AND t1.`Address` <=> t2.`Address` AND t1.`account_type` <=> t2.`account_type` AND t1.`student_id_path` <=> t2.`student_id_path` AND t1.`discount_rate` <=> t2.`discount_rate` AND t1.`created_at` <=> t2.`created_at` AND t1.`updated_at` <=> t2.`updated_at` AND t1.`password` <=> t2.`password`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- same ID + same email (older edit of the same account): keep the newest
SET @s := IF(@need, 'DELETE t1 FROM `customer_info` t1 JOIN `customer_info` t2 ON t1.`Customer_ID` = t2.`Customer_ID` AND t1.`Customer_ID` <> 0 AND t1.`email` <=> t2.`email` AND (t1.`updated_at` < t2.`updated_at` OR (t1.`updated_at` <=> t2.`updated_at` AND t1.`_rid` < t2.`_rid`))', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
-- same ID but different accounts: newest keeps the ID, others get a new ID below
SET @s := IF(@need, 'UPDATE `customer_info` t1 JOIN `customer_info` t2 ON t1.`Customer_ID` = t2.`Customer_ID` AND t1.`Customer_ID` <> 0 AND (t1.`updated_at` < t2.`updated_at` OR (t1.`updated_at` <=> t2.`updated_at` AND t1.`_rid` < t2.`_rid`)) SET t1.`Customer_ID` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `customer_info` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`Customer_ID`), 0) INTO @m FROM `customer_info`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `customer_info` SET `Customer_ID` = (@m := @m + 1) WHERE `Customer_ID` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `customer_info` ADD PRIMARY KEY (`Customer_ID`), MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- admin
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `admin` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `admin` t1 JOIN `admin` t2 ON t2.`_rid` < t1.`_rid` AND t1.`Admin_ID` <=> t2.`Admin_ID` AND t1.`username` <=> t2.`username` AND t1.`password` <=> t2.`password`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `admin` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`Admin_ID`), 0) INTO @m FROM `admin`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `admin` SET `Admin_ID` = (@m := @m + 1) WHERE `Admin_ID` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `admin` ADD PRIMARY KEY (`Admin_ID`), MODIFY `Admin_ID` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- booking
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `booking` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `booking` t1 JOIN `booking` t2 ON t2.`_rid` < t1.`_rid` AND t1.`Booking_ID` <=> t2.`Booking_ID` AND t1.`Customer_ID` <=> t2.`Customer_ID` AND t1.`Admin_ID` <=> t2.`Admin_ID` AND t1.`Schedule_ID` <=> t2.`Schedule_ID` AND t1.`service` <=> t2.`service` AND t1.`add_ons` <=> t2.`add_ons` AND t1.`pick_deliver` <=> t2.`pick_deliver` AND t1.`special_instructions` <=> t2.`special_instructions` AND t1.`status` <=> t2.`status` AND t1.`total_amount` <=> t2.`total_amount` AND t1.`booking_date` <=> t2.`booking_date`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `booking` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`Booking_ID`), 0) INTO @m FROM `booking`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `booking` SET `Booking_ID` = (@m := @m + 1) WHERE `Booking_ID` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `booking` ADD PRIMARY KEY (`Booking_ID`), MODIFY `Booking_ID` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- delivery
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'delivery' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `delivery` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `delivery` t1 JOIN `delivery` t2 ON t2.`_rid` < t1.`_rid` AND t1.`delivery_id` <=> t2.`delivery_id` AND t1.`booking_id` <=> t2.`booking_id` AND t1.`online_booking_id` <=> t2.`online_booking_id` AND t1.`delivery_address` <=> t2.`delivery_address` AND t1.`delivery_status` <=> t2.`delivery_status` AND t1.`delivery_date` <=> t2.`delivery_date`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `delivery` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`delivery_id`), 0) INTO @m FROM `delivery`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `delivery` SET `delivery_id` = (@m := @m + 1) WHERE `delivery_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `delivery` ADD PRIMARY KEY (`delivery_id`), MODIFY `delivery_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- employees
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `employees` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `employees` t1 JOIN `employees` t2 ON t2.`_rid` < t1.`_rid` AND t1.`id` <=> t2.`id` AND t1.`name` <=> t2.`name` AND t1.`role` <=> t2.`role` AND t1.`status` <=> t2.`status` AND t1.`contact` <=> t2.`contact`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `employees` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`id`), 0) INTO @m FROM `employees`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `employees` SET `id` = (@m := @m + 1) WHERE `id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `employees` ADD PRIMARY KEY (`id`), MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- equipment
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'equipment' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `equipment` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `equipment` t1 JOIN `equipment` t2 ON t2.`_rid` < t1.`_rid` AND t1.`id` <=> t2.`id` AND t1.`name` <=> t2.`name` AND t1.`status` <=> t2.`status` AND t1.`notes` <=> t2.`notes` AND t1.`last_checked` <=> t2.`last_checked` AND t1.`updated_at` <=> t2.`updated_at` AND t1.`created_at` <=> t2.`created_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `equipment` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`id`), 0) INTO @m FROM `equipment`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `equipment` SET `id` = (@m := @m + 1) WHERE `id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `equipment` ADD PRIMARY KEY (`id`), MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- feedback
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `feedback` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `feedback` t1 JOIN `feedback` t2 ON t2.`_rid` < t1.`_rid` AND t1.`feedback_id` <=> t2.`feedback_id` AND t1.`user_id` <=> t2.`user_id` AND t1.`booking_id` <=> t2.`booking_id` AND t1.`rating` <=> t2.`rating` AND t1.`comment` <=> t2.`comment` AND t1.`created_at` <=> t2.`created_at` AND t1.`response` <=> t2.`response` AND t1.`admin_response` <=> t2.`admin_response` AND t1.`responded_at` <=> t2.`responded_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `feedback` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`feedback_id`), 0) INTO @m FROM `feedback`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `feedback` SET `feedback_id` = (@m := @m + 1) WHERE `feedback_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `feedback` ADD PRIMARY KEY (`feedback_id`), MODIFY `feedback_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- financial_records
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_records')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_records' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `financial_records` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `financial_records` t1 JOIN `financial_records` t2 ON t2.`_rid` < t1.`_rid` AND t1.`id` <=> t2.`id` AND t1.`date` <=> t2.`date` AND t1.`description` <=> t2.`description` AND t1.`category` <=> t2.`category` AND t1.`type` <=> t2.`type` AND t1.`amount` <=> t2.`amount`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `financial_records` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`id`), 0) INTO @m FROM `financial_records`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `financial_records` SET `id` = (@m := @m + 1) WHERE `id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `financial_records` ADD PRIMARY KEY (`id`), MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- inventory_categories
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_categories')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_categories' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `inventory_categories` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `inventory_categories` t1 JOIN `inventory_categories` t2 ON t2.`_rid` < t1.`_rid` AND t1.`category_id` <=> t2.`category_id` AND t1.`category_name` <=> t2.`category_name` AND t1.`description` <=> t2.`description` AND t1.`unit_type` <=> t2.`unit_type` AND t1.`created_at` <=> t2.`created_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_categories` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`category_id`), 0) INTO @m FROM `inventory_categories`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `inventory_categories` SET `category_id` = (@m := @m + 1) WHERE `category_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_categories` ADD PRIMARY KEY (`category_id`), MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- inventory_items
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_items')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_items' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `inventory_items` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `inventory_items` t1 JOIN `inventory_items` t2 ON t2.`_rid` < t1.`_rid` AND t1.`item_id` <=> t2.`item_id` AND t1.`category_id` <=> t2.`category_id` AND t1.`item_name` <=> t2.`item_name` AND t1.`item_code` <=> t2.`item_code` AND t1.`description` <=> t2.`description` AND t1.`unit` <=> t2.`unit` AND t1.`current_stock` <=> t2.`current_stock` AND t1.`min_stock_level` <=> t2.`min_stock_level` AND t1.`max_stock_level` <=> t2.`max_stock_level` AND t1.`cost_per_unit` <=> t2.`cost_per_unit` AND t1.`supplier_id` <=> t2.`supplier_id` AND t1.`location` <=> t2.`location` AND t1.`expiry_date` <=> t2.`expiry_date` AND t1.`status` <=> t2.`status` AND t1.`last_restock_date` <=> t2.`last_restock_date` AND t1.`created_at` <=> t2.`created_at` AND t1.`updated_at` <=> t2.`updated_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_items` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`item_id`), 0) INTO @m FROM `inventory_items`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `inventory_items` SET `item_id` = (@m := @m + 1) WHERE `item_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_items` ADD PRIMARY KEY (`item_id`), MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- inventory_notifications
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_notifications')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_notifications' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `inventory_notifications` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `inventory_notifications` t1 JOIN `inventory_notifications` t2 ON t2.`_rid` < t1.`_rid` AND t1.`notification_id` <=> t2.`notification_id` AND t1.`item_id` <=> t2.`item_id` AND t1.`notification_type` <=> t2.`notification_type` AND t1.`message` <=> t2.`message` AND t1.`current_stock` <=> t2.`current_stock` AND t1.`min_stock_level` <=> t2.`min_stock_level` AND t1.`is_read` <=> t2.`is_read` AND t1.`created_at` <=> t2.`created_at` AND t1.`read_at` <=> t2.`read_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_notifications` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`notification_id`), 0) INTO @m FROM `inventory_notifications`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `inventory_notifications` SET `notification_id` = (@m := @m + 1) WHERE `notification_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_notifications` ADD PRIMARY KEY (`notification_id`), MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- inventory_transactions
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'inventory_transactions' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `inventory_transactions` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `inventory_transactions` t1 JOIN `inventory_transactions` t2 ON t2.`_rid` < t1.`_rid` AND t1.`transaction_id` <=> t2.`transaction_id` AND t1.`item_id` <=> t2.`item_id` AND t1.`transaction_type` <=> t2.`transaction_type` AND t1.`quantity` <=> t2.`quantity` AND t1.`previous_stock` <=> t2.`previous_stock` AND t1.`new_stock` <=> t2.`new_stock` AND t1.`reference_type` <=> t2.`reference_type` AND t1.`reference_id` <=> t2.`reference_id` AND t1.`supplier_id` <=> t2.`supplier_id` AND t1.`unit_cost` <=> t2.`unit_cost` AND t1.`total_cost` <=> t2.`total_cost` AND t1.`notes` <=> t2.`notes` AND t1.`created_by` <=> t2.`created_by` AND t1.`transaction_date` <=> t2.`transaction_date`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_transactions` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`transaction_id`), 0) INTO @m FROM `inventory_transactions`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `inventory_transactions` SET `transaction_id` = (@m := @m + 1) WHERE `transaction_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `inventory_transactions` ADD PRIMARY KEY (`transaction_id`), MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- notifications_admin
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications_admin')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications_admin' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `notifications_admin` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `notifications_admin` t1 JOIN `notifications_admin` t2 ON t2.`_rid` < t1.`_rid` AND t1.`notif_id` <=> t2.`notif_id` AND t1.`booking_id` <=> t2.`booking_id` AND t1.`user_id` <=> t2.`user_id` AND t1.`message` <=> t2.`message` AND t1.`status` <=> t2.`status` AND t1.`created_at` <=> t2.`created_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `notifications_admin` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`notif_id`), 0) INTO @m FROM `notifications_admin`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `notifications_admin` SET `notif_id` = (@m := @m + 1) WHERE `notif_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `notifications_admin` ADD PRIMARY KEY (`notif_id`), MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- notifications_user
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications_user')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications_user' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `notifications_user` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `notifications_user` t1 JOIN `notifications_user` t2 ON t2.`_rid` < t1.`_rid` AND t1.`notif_id` <=> t2.`notif_id` AND t1.`booking_id` <=> t2.`booking_id` AND t1.`user_id` <=> t2.`user_id` AND t1.`title` <=> t2.`title` AND t1.`type` <=> t2.`type` AND t1.`icon` <=> t2.`icon` AND t1.`link` <=> t2.`link` AND t1.`message` <=> t2.`message` AND t1.`is_read` <=> t2.`is_read` AND t1.`read_at` <=> t2.`read_at` AND t1.`status` <=> t2.`status` AND t1.`created_at` <=> t2.`created_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `notifications_user` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`notif_id`), 0) INTO @m FROM `notifications_user`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `notifications_user` SET `notif_id` = (@m := @m + 1) WHERE `notif_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `notifications_user` ADD PRIMARY KEY (`notif_id`), MODIFY `notif_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- products_inventory
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products_inventory')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'products_inventory' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `products_inventory` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `products_inventory` t1 JOIN `products_inventory` t2 ON t2.`_rid` < t1.`_rid` AND t1.`id` <=> t2.`id` AND t1.`product_name` <=> t2.`product_name` AND t1.`category` <=> t2.`category` AND t1.`unit` <=> t2.`unit` AND t1.`stock` <=> t2.`stock` AND t1.`unit_cost` <=> t2.`unit_cost` AND t1.`total_value` <=> t2.`total_value` AND t1.`date_added` <=> t2.`date_added` AND t1.`remarks` <=> t2.`remarks`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `products_inventory` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`id`), 0) INTO @m FROM `products_inventory`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `products_inventory` SET `id` = (@m := @m + 1) WHERE `id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `products_inventory` ADD PRIMARY KEY (`id`), MODIFY `id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- purchase_order_items
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_order_items' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `purchase_order_items` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `purchase_order_items` t1 JOIN `purchase_order_items` t2 ON t2.`_rid` < t1.`_rid` AND t1.`po_item_id` <=> t2.`po_item_id` AND t1.`po_id` <=> t2.`po_id` AND t1.`item_id` <=> t2.`item_id` AND t1.`quantity` <=> t2.`quantity` AND t1.`unit_cost` <=> t2.`unit_cost` AND t1.`total_cost` <=> t2.`total_cost` AND t1.`received_quantity` <=> t2.`received_quantity` AND t1.`notes` <=> t2.`notes`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `purchase_order_items` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`po_item_id`), 0) INTO @m FROM `purchase_order_items`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `purchase_order_items` SET `po_item_id` = (@m := @m + 1) WHERE `po_item_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `purchase_order_items` ADD PRIMARY KEY (`po_item_id`), MODIFY `po_item_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- add_ons
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'add_ons')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'add_ons' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `add_ons` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `add_ons` t1 JOIN `add_ons` t2 ON t2.`_rid` < t1.`_rid` AND t1.`addon_id` <=> t2.`addon_id` AND t1.`service_id` <=> t2.`service_id` AND t1.`addon_name` <=> t2.`addon_name` AND t1.`addon_price` <=> t2.`addon_price` AND t1.`description` <=> t2.`description`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `add_ons` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`addon_id`), 0) INTO @m FROM `add_ons`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `add_ons` SET `addon_id` = (@m := @m + 1) WHERE `addon_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `add_ons` ADD PRIMARY KEY (`addon_id`), MODIFY `addon_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- complaints
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'complaints')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'complaints' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `complaints` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `complaints` t1 JOIN `complaints` t2 ON t2.`_rid` < t1.`_rid` AND t1.`complaint_id` <=> t2.`complaint_id` AND t1.`customer_id` <=> t2.`customer_id` AND t1.`subject` <=> t2.`subject` AND t1.`category` <=> t2.`category` AND t1.`booking_id` <=> t2.`booking_id` AND t1.`issue_description` <=> t2.`issue_description` AND t1.`status` <=> t2.`status` AND t1.`priority` <=> t2.`priority` AND t1.`date_reported` <=> t2.`date_reported` AND t1.`date_resolved` <=> t2.`date_resolved` AND t1.`remarks` <=> t2.`remarks` AND t1.`customer_response` <=> t2.`customer_response` AND t1.`handled_by` <=> t2.`handled_by` AND t1.`updated_at` <=> t2.`updated_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `complaints` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`complaint_id`), 0) INTO @m FROM `complaints`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `complaints` SET `complaint_id` = (@m := @m + 1) WHERE `complaint_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `complaints` ADD PRIMARY KEY (`complaint_id`), MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- ─────────────────────────────────────────────
-- purchase_orders
-- ─────────────────────────────────────────────
SET @need := (SELECT COUNT(*) = 1 FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders')
          AND (SELECT COUNT(*) = 0 FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchase_orders' AND CONSTRAINT_TYPE = 'PRIMARY KEY');
SET @s := IF(@need, 'ALTER TABLE `purchase_orders` ADD COLUMN `_rid` INT NOT NULL AUTO_INCREMENT, ADD UNIQUE KEY `_rid` (`_rid`)', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'DELETE t1 FROM `purchase_orders` t1 JOIN `purchase_orders` t2 ON t2.`_rid` < t1.`_rid` AND t1.`po_id` <=> t2.`po_id` AND t1.`po_number` <=> t2.`po_number` AND t1.`supplier_id` <=> t2.`supplier_id` AND t1.`order_date` <=> t2.`order_date` AND t1.`expected_delivery_date` <=> t2.`expected_delivery_date` AND t1.`delivery_date` <=> t2.`delivery_date` AND t1.`status` <=> t2.`status` AND t1.`total_amount` <=> t2.`total_amount` AND t1.`notes` <=> t2.`notes` AND t1.`created_at` <=> t2.`created_at` AND t1.`updated_at` <=> t2.`updated_at`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `purchase_orders` DROP INDEX `_rid`, DROP COLUMN `_rid`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'SELECT COALESCE(MAX(`po_id`), 0) INTO @m FROM `purchase_orders`', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'UPDATE `purchase_orders` SET `po_id` = (@m := @m + 1) WHERE `po_id` = 0', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
SET @s := IF(@need, 'ALTER TABLE `purchase_orders` ADD PRIMARY KEY (`po_id`), MODIFY `po_id` int(11) NOT NULL AUTO_INCREMENT', 'DO 0'); PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;
