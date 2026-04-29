-- Midtrans and Payment Enhancement Migration
-- Run this SQL to update your payments table with new features

-- Check if payment_status column exists, if not add it
ALTER TABLE `rpos_payments` 
ADD COLUMN `payment_status` VARCHAR(50) DEFAULT 'pending' AFTER `pay_method`,
ADD COLUMN `payment_reference` VARCHAR(255) NULL AFTER `payment_status`,
ADD COLUMN `verified_by` VARCHAR(100) NULL AFTER `payment_reference`,
ADD COLUMN `verification_status` VARCHAR(50) DEFAULT 'unverified' AFTER `verified_by`,
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`,
ADD COLUMN `notes` TEXT NULL AFTER `updated_at`;

-- Add indexes for better performance
ALTER TABLE `rpos_payments` ADD INDEX `idx_payment_status` (`payment_status`);
ALTER TABLE `rpos_payments` ADD INDEX `idx_verification_status` (`verification_status`);
ALTER TABLE `rpos_payments` ADD INDEX `idx_order_code` (`order_code`);

-- Update existing orders to have proper status values
UPDATE `rpos_orders` SET `order_status` = 'Paid' WHERE `order_status` IS NOT NULL AND `order_status` != '';
UPDATE `rpos_orders` SET `order_status` = 'Pending' WHERE `order_status` = '';

-- Create a payment audit log table if it doesn't exist
CREATE TABLE IF NOT EXISTS `rpos_payment_audit` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(100) NOT NULL,
  `customer_id` VARCHAR(100) NOT NULL,
  `old_status` VARCHAR(50),
  `new_status` VARCHAR(50),
  `action` VARCHAR(100),
  `performed_by` VARCHAR(100),
  `ip_address` VARCHAR(45),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_order_code` (`order_code`),
  INDEX `idx_customer_id` (`customer_id`),
  INDEX `idx_created_at` (`created_at`)
);

-- Add payment_method column to orders if missing (for reference)
ALTER TABLE `rpos_orders` 
ADD COLUMN `payment_method` VARCHAR(50) DEFAULT 'pending' AFTER `order_status`;

-- Create payment method reference table
CREATE TABLE IF NOT EXISTS `rpos_payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `method_name` VARCHAR(100) NOT NULL UNIQUE,
  `method_code` VARCHAR(50) NOT NULL UNIQUE,
  `description` TEXT,
  `is_active` BOOLEAN DEFAULT TRUE,
  `requires_verification` BOOLEAN DEFAULT FALSE,
  `icon` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default payment methods
INSERT INTO `rpos_payment_methods` (`method_name`, `method_code`, `description`, `is_active`, `requires_verification`, `icon`) VALUES
('Cash', 'cash', 'Direct cash payment', 1, 0, 'fas fa-money-bill-wave'),
('PayPal', 'paypal', 'PayPal online payment', 1, 1, 'fab fa-paypal'),
('Midtrans', 'midtrans', 'Midtrans payment gateway (Cards, Bank Transfer, E-Wallet)', 1, 1, 'fas fa-credit-card')
ON DUPLICATE KEY UPDATE method_name=VALUES(method_name);
