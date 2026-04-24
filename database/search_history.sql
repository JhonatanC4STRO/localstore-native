-- ══════════════════════════════════════════════════════════════
-- Search history — guarda términos buscados por usuarios logueados
-- para alimentar el autocomplete (sección "Búsquedas recientes").
-- ══════════════════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `search_history` (
  `id`         INT NOT NULL AUTO_INCREMENT,
  `user_id`    INT NOT NULL,
  `query`      VARCHAR(150) NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  KEY `idx_query` (`query`),
  CONSTRAINT `fk_sh_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
