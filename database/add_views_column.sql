-- Agregar columna de vistas a productos
ALTER TABLE `products` ADD COLUMN `views` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `promo_priority`;
