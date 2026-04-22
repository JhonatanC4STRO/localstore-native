<?php
/**
 * Unified Sidebar Component — ComercioLocal
 * Matches the exact design of dashboard.php
 *
 * @var string $activeTab  - Current active item ('dashboard', 'crear', 'productos', 'chat', 'perfil', 'verificacion', 'promociones')
 * @var string $basePath   - Relative path to project root
 * @var int    $totalProducts - Product count for the badge
 */

$activeTab = $activeTab ?? '';
$basePath  = $basePath  ?? '../../';
$totalProducts = $totalProducts ?? 0;

// Resolve paths
$viewsPath = $basePath . 'src/views/';
$ctrlPath  = $basePath . 'src/controllers/';

// Contar mensajes no leídos reales
$_sb_unread = 0;
if (isset($_SESSION['user']['id']) && isset($conn)) {
    $_sb_uid = (int) $_SESSION['user']['id'];
    $_sb_res = mysqli_query($conn,
        "SELECT COUNT(*) AS total
         FROM messages m
         JOIN conversations c ON m.conversation_id = c.id
         WHERE (c.buyer_id = $_sb_uid OR c.seller_id = $_sb_uid)
           AND m.sender_id != $_sb_uid
           AND m.is_read = 0
           AND NOT (c.buyer_id  = $_sb_uid AND c.hidden_by_buyer  = 1)
           AND NOT (c.seller_id = $_sb_uid AND c.hidden_by_seller = 1)");
    if ($_sb_res) $_sb_unread = (int) mysqli_fetch_assoc($_sb_res)['total'];
}
?>

<!-- ══════════════════════════════════
   UNIFIED SIDEBAR
══════════════════════════════════ -->
<aside class="sidebar">
  <div class="sb-section-label">Principal</div>

  <a class="sb-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>" href="<?= $viewsPath ?>dashboard.php">
    <i class="bi bi-speedometer2"></i> Dashboard
  </a>

  <a class="sb-link <?= $activeTab === 'crear' ? 'active' : '' ?>" href="<?= $viewsPath ?>products/crear.php">
    <i class="bi bi-plus-square-fill"></i> Publicar producto
  </a>

  <a class="sb-link <?= $activeTab === 'productos' ? 'active' : '' ?>" href="<?= $viewsPath ?>products/mis_productos.php">
    <i class="bi bi-box-seam"></i> Mis productos
    <span class="sb-badge"><?= $totalProducts ?></span>
  </a>

  <a class="sb-link <?= $activeTab === 'chat' ? 'active' : '' ?>" href="<?= $viewsPath ?>chat/chat.php">
    <i class="bi bi-chat-dots"></i> Mensajes
    <?php if ($_sb_unread > 0): ?>
      <span class="sb-badge yellow"><?= $_sb_unread ?></span>
    <?php endif; ?>
  </a>

<?php
  // Contar favoritos
  $_sb_favs = 0;
  if (isset($_SESSION['user']['id']) && isset($conn)) {
      $_sb_fav_res = mysqli_query($conn,
          "SELECT COUNT(*) AS total FROM favorites WHERE user_id = $_sb_uid");
      if ($_sb_fav_res) $_sb_favs = (int) mysqli_fetch_assoc($_sb_fav_res)['total'];
  }
  ?>
  <a class="sb-link <?= $activeTab === 'favoritos' ? 'active' : '' ?>" href="<?= $viewsPath ?>favoritos.php">
    <i class="bi bi-heart"></i> Favoritos
    <?php if ($_sb_favs > 0): ?>
      <span class="sb-badge"><?= $_sb_favs ?></span>
    <?php endif; ?>
  </a>

  <div class="sb-divider"></div>
  <div class="sb-section-label">Cuenta</div>

  <a class="sb-link <?= $activeTab === 'perfil' ? 'active' : '' ?>" href="<?= $viewsPath ?>perfil.php">
    <i class="bi bi-person-circle"></i> Mi perfil
  </a>

  <a class="sb-link <?= $activeTab === 'verificacion' ? 'active' : '' ?>" href="<?= $viewsPath ?>solicitar_verificacion.php">
    <i class="bi bi-patch-check-fill"></i> Verificación
  </a>

  <a class="sb-link <?= $activeTab === 'resenas' ? 'active' : '' ?>" href="<?= $viewsPath ?>resenas.php">
    <i class="bi bi-star"></i> Reseñas
  </a>

  <a class="sb-link <?= $activeTab === 'promociones' ? 'active' : '' ?>" href="<?= $viewsPath ?>promociones.php">
    <i class="bi bi-rocket-takeoff-fill"></i> Promociones
  </a>

  <a class="sb-link <?= $activeTab === 'configuracion' ? 'active' : '' ?>" href="<?= $viewsPath ?>configuracion.php">
    <i class="bi bi-gear"></i> Configuración
  </a>

  <a class="sb-link" href="<?= $ctrlPath ?>auth_logout.php" style="color:rgba(239,68,68,.7);">
    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
  </a>

  <div class="sb-divider"></div>

  <div class="sb-promo">
    <div class="sb-promo-icon">🚀</div>
    <p>Destaca tu anuncio y llega a 10× más compradores hoy.</p>
    <a href="<?= $viewsPath ?>promociones.php">Ver planes</a>
  </div>
</aside>
