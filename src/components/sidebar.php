<?php
/**
 * Unified Sidebar Component — ComercioLocal Panel
 *
 * Required: $activeTab (string) — 'dashboard'|'crear'|'productos'|'chat'|'perfil'
 */
$activeTab = $activeTab ?? 'dashboard';
?>
<!-- ══ SIDEBAR ══ -->
<aside class="cl-sidebar" id="clSidebar">
  <div class="cl-sb-section-label">Principal</div>

  <a class="cl-sb-link <?= $activeTab === 'dashboard' ? 'active' : '' ?>"
     href="#" data-tab="dashboard">
    <i class="bi bi-speedometer2"></i> Inicio
  </a>

  <a class="cl-sb-link <?= $activeTab === 'crear' ? 'active' : '' ?>"
     href="#" data-tab="crear">
    <i class="bi bi-plus-square-fill"></i> Publicar producto
  </a>

  <a class="cl-sb-link <?= $activeTab === 'productos' ? 'active' : '' ?>"
     href="#" data-tab="productos">
    <i class="bi bi-box-seam"></i> Mis productos
    <span class="cl-sb-badge"><?= $totalProducts ?? 0 ?></span>
  </a>

  <a class="cl-sb-link <?= $activeTab === 'chat' ? 'active' : '' ?>"
     href="#" data-tab="chat">
    <i class="bi bi-chat-dots"></i> Mensajes
    <span class="cl-sb-badge cl-sb-badge-yellow">5</span>
  </a>

  <a class="cl-sb-link" href="#">
    <i class="bi bi-heart"></i> Favoritos
  </a>

  <div class="cl-sb-divider"></div>
  <div class="cl-sb-section-label">Cuenta</div>

  <a class="cl-sb-link <?= $activeTab === 'perfil' ? 'active' : '' ?>"
     href="#" data-tab="perfil">
    <i class="bi bi-person-circle"></i> Mi perfil
  </a>

  <a class="cl-sb-link" href="#">
    <i class="bi bi-star"></i> Reseñas
  </a>

  <a class="cl-sb-link" href="#">
    <i class="bi bi-gear"></i> Configuración
  </a>

  <a class="cl-sb-link cl-sb-logout" href="../controller/logout.php">
    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
  </a>

  <div class="cl-sb-divider"></div>

  <div class="cl-sb-promo">
    <div class="cl-sb-promo-icon">⭐</div>
    <p>Destaca tu anuncio y llega a 10× más compradores hoy.</p>
    <a href="#">Ver planes</a>
  </div>
</aside>
