-- HOME TASTE FOODS - Upgrade to multi-item cart + Dine-in/Takeaway
-- Run this once on your existing rposystem database

ALTER TABLE `rpos_orders`
  ADD COLUMN IF NOT EXISTS `order_type` VARCHAR(50) DEFAULT NULL AFTER `order_status`,
  ADD COLUMN IF NOT EXISTS `packaging_fee` DECIMAL(12,2) DEFAULT 0 AFTER `order_type`;

-- Optional: stop requiring prod_code going forward (keep column for old data)
-- ALTER TABLE `rpos_products` MODIFY `prod_code` VARCHAR(200) NULL;

-- Update any old orders to a known status if needed
-- UPDATE rpos_orders SET order_status = 'Completed' WHERE order_status = '' OR order_status IS NULL;

