/* ═══════════════════════════════════════════════════════════════
   Migration 2026-04-21
   - Limpia datos huérfanos/zero-sentinel
   - Relaja NOT NULL donde se necesita para FK ON DELETE SET NULL
   - Añade índices faltantes
   - Añade UNIQUE en conversations
   - Añade foreign keys faltantes
   - Convierte products.latitude/longitude a DECIMAL
   - Elimina users.type_user (reemplazada por users.role)
   ═══════════════════════════════════════════════════════════════ */

START TRANSACTION;

/* ── 1. Limpieza de datos ───────────────────────────────────── */

/* activity_log: admin_id=0 (legado) → NULL. Hacer nullable primero. */
ALTER TABLE activity_log
    MODIFY admin_id INT NULL,
    MODIFY action TEXT NULL;
UPDATE activity_log SET admin_id = NULL WHERE admin_id = 0 OR admin_id NOT IN (SELECT id FROM users);

/* conversations: product_id=0 o buyer_id=0 (legado) → NULL */
UPDATE conversations SET product_id = NULL WHERE product_id = 0 OR product_id NOT IN (SELECT id FROM products);
UPDATE conversations SET buyer_id  = NULL WHERE buyer_id  = 0 OR buyer_id  NOT IN (SELECT id FROM users);
UPDATE conversations SET seller_id = NULL WHERE seller_id = 0 OR seller_id NOT IN (SELECT id FROM users);

/* messages: sender_id=0 → NULL */
UPDATE messages SET sender_id = NULL WHERE sender_id = 0 OR sender_id NOT IN (SELECT id FROM users);
UPDATE messages SET conversation_id = NULL WHERE conversation_id IS NOT NULL AND conversation_id NOT IN (SELECT id FROM conversations);

/* product_reports: product_id huérfano → eliminar (el producto se borró). */
DELETE FROM product_reports WHERE product_id NOT IN (SELECT id FROM products);
UPDATE product_reports SET reporter_id = NULL WHERE reporter_id IS NOT NULL AND reporter_id NOT IN (SELECT id FROM users);
UPDATE product_reports SET reviewed_by = NULL WHERE reviewed_by IS NOT NULL AND reviewed_by NOT IN (SELECT id FROM users);

/* user_reports: limpiar huérfanos por si acaso */
DELETE FROM user_reports WHERE reported_user_id NOT IN (SELECT id FROM users);
UPDATE user_reports SET reporter_id = NULL WHERE reporter_id IS NOT NULL AND reporter_id NOT IN (SELECT id FROM users);
UPDATE user_reports SET reviewed_by = NULL WHERE reviewed_by IS NOT NULL AND reviewed_by NOT IN (SELECT id FROM users);

/* seller_verifications: reviewed_by huérfano → NULL */
UPDATE seller_verifications SET reviewed_by = NULL WHERE reviewed_by IS NOT NULL AND reviewed_by NOT IN (SELECT id FROM users);

/* Para permitir FK con SET NULL donde sea adecuado */
ALTER TABLE product_reports MODIFY reporter_id INT NULL;
ALTER TABLE user_reports    MODIFY reporter_id INT NULL;


/* ── 2. Conversión de tipos ─────────────────────────────────── */

/* Normalizar valores vacíos antes de convertir */
UPDATE products SET latitude  = NULL WHERE latitude  = '' OR latitude  NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$';
UPDATE products SET longitude = NULL WHERE longitude = '' OR longitude NOT REGEXP '^-?[0-9]+(\\.[0-9]+)?$';

ALTER TABLE products
    MODIFY latitude  DECIMAL(10,7) NULL,
    MODIFY longitude DECIMAL(11,7) NULL;


/* ── 3. Índices faltantes (ayuda de performance) ────────────── */

ALTER TABLE messages
    ADD INDEX idx_conv_time (conversation_id, id),
    ADD INDEX idx_sender    (sender_id),
    ADD INDEX idx_unread    (conversation_id, is_read);

ALTER TABLE conversations
    ADD INDEX idx_buyer   (buyer_id),
    ADD INDEX idx_seller  (seller_id),
    ADD INDEX idx_product (product_id);

ALTER TABLE products
    ADD INDEX idx_admin_status (admin_status, status),
    ADD INDEX idx_created      (created_at);

ALTER TABLE user_settings
    ADD INDEX idx_who_can_message (privacy_who_can_message);


/* ── 4. UNIQUE constraint ───────────────────────────────────── */
/* Previene duplicar la conversación entre mismo comprador/vendedor/producto */
ALTER TABLE conversations
    ADD UNIQUE KEY uk_conv_triplet (product_id, buyer_id, seller_id);


/* ── 5. Foreign Keys faltantes ──────────────────────────────── */

ALTER TABLE conversations
    ADD CONSTRAINT fk_conv_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_conv_buyer   FOREIGN KEY (buyer_id)   REFERENCES users(id)    ON DELETE SET NULL,
    ADD CONSTRAINT fk_conv_seller  FOREIGN KEY (seller_id)  REFERENCES users(id)    ON DELETE SET NULL;

ALTER TABLE messages
    ADD CONSTRAINT fk_msg_conv   FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id)       REFERENCES users(id)         ON DELETE SET NULL;

ALTER TABLE product_reports
    ADD CONSTRAINT fk_preport_product  FOREIGN KEY (product_id)  REFERENCES products(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_preport_reporter FOREIGN KEY (reporter_id) REFERENCES users(id)    ON DELETE SET NULL,
    ADD CONSTRAINT fk_preport_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id)    ON DELETE SET NULL;

ALTER TABLE user_reports
    ADD CONSTRAINT fk_ureport_target   FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_ureport_reporter FOREIGN KEY (reporter_id)      REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ureport_reviewer FOREIGN KEY (reviewed_by)      REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE user_settings
    ADD CONSTRAINT fk_usettings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;

ALTER TABLE seller_verifications
    ADD CONSTRAINT fk_sv_user     FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    ADD CONSTRAINT fk_sv_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL;

ALTER TABLE activity_log
    ADD CONSTRAINT fk_alog_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL;


/* ── 6. Eliminar columna obsoleta ───────────────────────────── */
/* users.type_user queda reemplazada por users.role */
ALTER TABLE users DROP COLUMN type_user;


COMMIT;
