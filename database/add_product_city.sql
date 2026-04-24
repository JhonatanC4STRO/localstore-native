-- ══════════════════════════════════════════════════════════════
-- Agrega columna `city` a products para geocoding inverso al crear/editar.
-- Permite filtrar/buscar productos por la ciudad real donde se publicó
-- (no la del vendedor), usando reverse geocoding desde lat/lon.
-- ══════════════════════════════════════════════════════════════

ALTER TABLE `products`
  ADD COLUMN `city` VARCHAR(100) DEFAULT NULL AFTER `latitude`;

ALTER TABLE `products`
  ADD INDEX `idx_city` (`city`);

-- Backfill opcional: copiar la ciudad del vendedor a productos antiguos sin ciudad.
-- Comentá esto si NO querés que productos existentes hereden la ciudad del usuario.
UPDATE `products` p
JOIN   `users`    u ON u.id = p.user_id
SET    p.city = u.city
WHERE  p.city IS NULL
  AND  u.city IS NOT NULL
  AND  u.city != '';
