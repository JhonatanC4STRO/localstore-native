-- ============================================================
--  ComercioLocal – Product Promotions Module
--  Database: tiendalocal
-- ============================================================

CREATE TABLE IF NOT EXISTS `product_promotions` (
  `id`          INT             NOT NULL AUTO_INCREMENT,
  `product_id`  INT             NOT NULL,
  `plan_type`   ENUM('basic','recommended','premium') NOT NULL,
  `price`       DECIMAL(10,2)   NOT NULL,
  `start_date`  DATETIME        NOT NULL,
  `end_date`    DATETIME        NOT NULL,
  `status`      ENUM('pending','active','expired') NOT NULL DEFAULT 'pending',
  `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_status` (`product_id`, `status`),
  KEY `idx_end_date`       (`end_date`),
  CONSTRAINT `fk_pp_product`
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-expire event (requires EVENT privilege on MySQL user)
CREATE EVENT IF NOT EXISTS `expire_promotions`
  ON SCHEDULE EVERY 1 HOUR
  DO
    UPDATE `product_promotions`
    SET    `status` = 'expired'
    WHERE  `status` = 'active'
      AND  `end_date` < NOW();

-- Add promo_priority column to products (skip if already exists)
DROP PROCEDURE IF EXISTS add_promo_priority;
DELIMITER //
CREATE PROCEDURE add_promo_priority()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'products'
      AND COLUMN_NAME  = 'promo_priority'
  ) THEN
    ALTER TABLE `products`
      ADD COLUMN `promo_priority` TINYINT UNSIGNED NOT NULL DEFAULT 0
        COMMENT '0=normal 1=basic 2=recommended 3=premium';
  END IF;
END //
DELIMITER ;
CALL add_promo_priority();
DROP PROCEDURE IF EXISTS add_promo_priority;
