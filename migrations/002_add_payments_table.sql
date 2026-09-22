-- Create payments_online table
CREATE TABLE IF NOT EXISTS payments_online (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('Cash', 'GCash') NOT NULL,
    payment_status ENUM('Pending', 'Paid', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
    reference_number VARCHAR(50),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_proof VARCHAR(255),
    notes TEXT,
    CONSTRAINT fk_payments_booking FOREIGN KEY (booking_id) REFERENCES booking_online(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safely add payment_status column to booking_online only if it doesn't exist
-- This uses a small stored procedure to avoid syntax errors on servers that don't support
-- "ALTER TABLE ... ADD COLUMN IF NOT EXISTS".
-- The procedure is created, executed, and dropped in the same script so it is safe to run multiple times.
DELIMITER $$
CREATE PROCEDURE add_payment_status_if_missing()
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'booking_online'
          AND COLUMN_NAME = 'payment_status'
    ) THEN
        ALTER TABLE booking_online
        ADD COLUMN payment_status ENUM('Unpaid', 'Partially Paid', 'Paid') DEFAULT 'Unpaid';
    END IF;
END$$
CALL add_payment_status_if_missing()$$
DROP PROCEDURE add_payment_status_if_missing$$
DELIMITER ;