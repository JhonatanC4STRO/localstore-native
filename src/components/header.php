<?php
/**
 * Unified Header Component — ComercioLocal
 *
 * Required variables (set before including):
 *   $basePath   – relative path from current page to project root (e.g. '../../')
 *   $isLoggedIn – bool
 *   $user       – array|null (session user data)
 *   $userInitial– string (first letter of name)
 *   $userName   – string (first name)
 */

// Defaults for safety
$basePath    = $basePath    ?? '../../';
$isLoggedIn  = $isLoggedIn  ?? false;
$user        = $user        ?? null;
$userInitial = $userInitial ?? '';
$userName    = $userName    ?? '';

$userRole = strtolower($user['role'] ?? '');
$isAdmin  = $isLoggedIn && in_array($userRole, ['admin', 'super_admin'], true);

// Rutas actualizadas para MVC
$publicPath  = $basePath . 'public/';
$viewsPath   = $basePath . 'src/views/';
$apiPath     = $basePath . 'src/api/';
$ctrlPath    = $basePath . 'src/controllers/';
?>
<!-- ══ UNIFIED HEADER ══ -->
<link rel="stylesheet" href="<?= $publicPath ?>css/header.css">

<!-- Skip to main content (accessibility) -->
<a href="#main-content" class="cl-skip-link">Saltar al contenido principal</a>

<header class="cl-header" role="banner">
  <!-- Logo -->
  <a href="<?= $viewsPath ?>home.php" class="cl-logo" aria-label="ComercioLocal — Ir al inicio">
    <img src="<?= $publicPath ?>img/logo.png" alt="ComercioLocal">
  </a>

  <!-- Search (desktop) -->
  <form class="cl-search" role="search" aria-label="Buscar productos" action="<?= $viewsPath ?>products/all.php" method="get" id="clSearchForm">
    <label for="clSearchInput" class="sr-only">Buscar productos</label>
    <input type="text" id="clSearchInput" name="search" placeholder="Buscar productos, servicios..."
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" autocomplete="off">
    <button type="submit" aria-label="Buscar">
      <i class="bi bi-search" aria-hidden="true"></i>
    </button>
  </form>

  <!-- Browse all products (desktop) -->
  <a href="<?= $viewsPath ?>products/all.php" class="cl-nav-link">
    <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i>
    <span>Productos</span>
  </a>

  <div class="cl-spacer"></div>

  <!-- Right Actions -->
  <nav class="cl-actions" aria-label="Acciones principales">
    <!-- Search toggle (mobile only) -->
    <button class="cl-icon-btn cl-search-toggle" id="clSearchToggle" aria-label="Abrir buscador" aria-expanded="false">
      <i class="bi bi-search" aria-hidden="true"></i>
    </button>

    <a href="<?= $viewsPath ?>favoritos.php" class="cl-icon-btn" aria-label="Mis favoritos" id="clFavBtn">
      <i class="bi bi-heart" aria-hidden="true"></i>
    </a>

    <a href="<?= $viewsPath ?>chat/chat.php" class="cl-icon-btn" aria-label="Mensajes" id="clChatBtn">
      <i class="bi bi-chat-dots" aria-hidden="true"></i>
      <span class="cl-notif-dot cl-msg-dot" id="clMsgDot" style="display:none;" aria-hidden="true"></span>
      <span class="cl-badge-count" id="clMsgCount" style="display:none;" aria-hidden="true"></span>
      <span class="sr-only">Tienes mensajes nuevos</span>
    </a>

    <div class="cl-notif-wrap" id="clNotifWrap">
      <button class="cl-icon-btn" aria-label="Notificaciones" id="clNotifBtn" aria-haspopup="true" aria-expanded="false">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span class="cl-notif-dot" id="clNotifDot" style="display:none;" aria-hidden="true"></span>
        <span class="cl-badge-count" id="clNotifCount" style="display:none;" aria-hidden="true"></span>
        <span class="sr-only">Tienes notificaciones nuevas</span>
      </button>

      <!-- Notification dropdown -->
      <div class="cl-notif-dropdown" id="clNotifDropdown" aria-hidden="true">
        <div class="cl-nd-header">
          <h3>Notificaciones</h3>
          <span class="cl-nd-total" id="clNdTotal"></span>
        </div>
        <div class="cl-nd-list" id="clNdList">
          <div class="cl-nd-empty" id="clNdEmpty">
            <i class="bi bi-bell-slash"></i>
            <p>Sin notificaciones nuevas</p>
          </div>
        </div>
        <a href="<?= $viewsPath ?>chat/chat.php" class="cl-nd-footer">
          <i class="bi bi-chat-dots-fill"></i> Ver todos los mensajes
        </a>
      </div>
    </div>

    <?php if ($isLoggedIn): ?>
      <!-- ── Logged in: User chip + dropdown ── -->
      <div class="cl-user-wrap" id="clUserWrap">
        <button class="cl-user-chip" id="clUserChip"
                aria-haspopup="true" aria-expanded="false" aria-controls="clUserDropdown">
          <span style="position:relative;">
            <span class="cl-user-av" aria-hidden="true"><?= htmlspecialchars($userInitial) ?></span>
            <span class="cl-user-online" aria-hidden="true"></span>
          </span>
          <span class="cl-user-info">
            <span class="cl-user-greet">Hola,</span>
            <span class="cl-user-name"><?= htmlspecialchars($userName) ?></span>
          </span>
          <i class="bi bi-chevron-down cl-user-arrow" aria-hidden="true"></i>
        </button>

        <div class="cl-dropdown" id="clUserDropdown" role="menu" aria-label="Menú de usuario">
          <div class="cl-dd-head">
            <div class="cl-dd-av" aria-hidden="true"><?= htmlspecialchars($userInitial) ?></div>
            <div>
              <div class="cl-dd-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
              <div class="cl-dd-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
          </div>
          <div class="cl-dd-body">
            <?php if ($isAdmin): ?>
              <a class="cl-dd-link cl-dd-admin" href="<?= $viewsPath ?>admin/dashboard.php" role="menuitem">
                <i class="bi bi-shield-lock-fill" aria-hidden="true"></i> Panel admin
              </a>
              <div class="cl-dd-sep" role="separator"></div>
            <?php endif; ?>
            <a class="cl-dd-link" href="<?= $viewsPath ?>home.php" role="menuitem">
              <i class="bi bi-speedometer2" aria-hidden="true"></i> Inicio
            </a>
            <a class="cl-dd-link" href="<?= $viewsPath ?>products/crear.php" role="menuitem">
              <i class="bi bi-plus-square" aria-hidden="true"></i> Publicar anuncio
            </a>
            <a class="cl-dd-link" href="<?= $viewsPath ?>products/mis_productos.php" role="menuitem">
              <i class="bi bi-box-seam" aria-hidden="true"></i> Mis productos
            </a>
            <a class="cl-dd-link" href="<?= $viewsPath ?>chat/chat.php" role="menuitem">
              <i class="bi bi-chat-dots" aria-hidden="true"></i> Mensajes
            </a>
            <a class="cl-dd-link" href="<?= $viewsPath ?>favoritos.php" role="menuitem">
              <i class="bi bi-heart" aria-hidden="true"></i> Favoritos
            </a>
            <div class="cl-dd-sep" role="separator"></div>
            <a class="cl-dd-link" href="<?= $viewsPath ?>perfil.php" role="menuitem">
              <i class="bi bi-person-circle" aria-hidden="true"></i> Mi perfil
            </a>
            <?php if (!empty($user['id'])): ?>
              <a class="cl-dd-link" href="<?= $viewsPath ?>seller_profile.php?seller_id=<?= $user['id'] ?>" role="menuitem">
                <i class="bi bi-shop-window" aria-hidden="true"></i> Perfil público
              </a>
            <?php endif; ?>
            <div class="cl-dd-sep" role="separator"></div>
            <a class="cl-dd-logout" href="<?= $ctrlPath ?>auth_logout.php" role="menuitem">
              <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Cerrar sesión
            </a>
          </div>
        </div>
      </div>

      <?php if ($isAdmin): ?>
        <a class="cl-btn-admin" href="<?= $viewsPath ?>admin/dashboard.php" aria-label="Ir al panel de administración">
          <i class="bi bi-shield-lock-fill" aria-hidden="true"></i>
          <span>Panel admin</span>
        </a>
      <?php endif; ?>

      <a class="cl-btn-publish" href="<?= $viewsPath ?>products/crear.php" aria-label="Publicar producto">
        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
        <span>Publicar</span>
      </a>

    <?php else: ?>
      <!-- ── Not logged in ── -->
      <a class="cl-btn-ghost" href="<?= $viewsPath ?>auth/login.php">
        <i class="bi bi-person" aria-hidden="true"></i> Iniciar sesión
      </a>
      <a class="cl-btn-ghost cl-btn-register" href="<?= $viewsPath ?>auth/register.php">
        Registrarse
      </a>
      <a class="cl-btn-publish" href="<?= $viewsPath ?>auth/login.php" aria-label="Publicar producto">
        <i class="bi bi-plus-circle-fill" aria-hidden="true"></i>
        <span>Publicar</span>
      </a>
    <?php endif; ?>

    <!-- Hamburger (mobile) -->
    <button class="cl-hamburger" id="clHamburger" aria-label="Abrir menú" aria-expanded="false" aria-controls="clMobileDrawer">
      <span class="cl-hamburger-line"></span>
      <span class="cl-hamburger-line"></span>
      <span class="cl-hamburger-line"></span>
    </button>
  </nav>
</header>

<!-- Mobile search overlay -->
<div class="cl-mobile-search" id="clMobileSearch" aria-hidden="true">
  <form class="cl-mobile-search-inner" role="search" aria-label="Buscar productos" action="<?= $viewsPath ?>products/all.php" method="get">
    <label for="clMobileSearchInput" class="sr-only">Buscar productos</label>
    <input type="text" id="clMobileSearchInput" name="search" placeholder="Buscar productos, servicios..." autocomplete="off">
    <button type="submit" aria-label="Buscar"><i class="bi bi-search" aria-hidden="true"></i></button>
    <button type="button" class="cl-mobile-search-close" id="clMobileSearchClose" aria-label="Cerrar buscador">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </form>
</div>

<!-- Mobile drawer overlay -->
<div class="cl-drawer-overlay" id="clDrawerOverlay" aria-hidden="true"></div>

<!-- Mobile drawer -->
<aside class="cl-drawer" id="clMobileDrawer" aria-label="Menú de navegación" aria-hidden="true">
  <div class="cl-drawer-header">
    <?php if ($isLoggedIn): ?>
      <div class="cl-drawer-user">
        <div class="cl-drawer-av"><?= htmlspecialchars($userInitial) ?></div>
        <div>
          <div class="cl-drawer-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
          <div class="cl-drawer-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
        </div>
      </div>
    <?php else: ?>
      <div class="cl-drawer-brand">
        <img src="<?= $publicPath ?>img/logo.png" alt="ComercioLocal" style="height:36px;">
      </div>
    <?php endif; ?>
    <button class="cl-drawer-close" id="clDrawerClose" aria-label="Cerrar menú">
      <i class="bi bi-x-lg" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="cl-drawer-nav" aria-label="Navegación principal">
    <a class="cl-drawer-link" href="<?= $viewsPath ?>home.php">
      <i class="bi bi-house-door" aria-hidden="true"></i> Inicio
    </a>
    <a class="cl-drawer-link" href="<?= $viewsPath ?>products/all.php">
      <i class="bi bi-grid-3x3-gap" aria-hidden="true"></i> Todos los productos
    </a>

    <?php if ($isLoggedIn): ?>
      <div class="cl-drawer-sep"></div>
      <?php if ($isAdmin): ?>
        <a class="cl-drawer-link cl-drawer-admin" href="<?= $viewsPath ?>admin/dashboard.php">
          <i class="bi bi-shield-lock-fill" aria-hidden="true"></i> Panel admin
        </a>
      <?php endif; ?>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>dashboard.php">
        <i class="bi bi-speedometer2" aria-hidden="true"></i> Dashboard
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>products/crear.php">
        <i class="bi bi-plus-square" aria-hidden="true"></i> Publicar anuncio
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>products/mis_productos.php">
        <i class="bi bi-box-seam" aria-hidden="true"></i> Mis productos
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>chat/chat.php">
        <i class="bi bi-chat-dots" aria-hidden="true"></i> Mensajes
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>favoritos.php">
        <i class="bi bi-heart" aria-hidden="true"></i> Favoritos
      </a>
      <div class="cl-drawer-sep"></div>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>solicitar_verificacion.php">
        <i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verificación
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>resenas.php">
        <i class="bi bi-star" aria-hidden="true"></i> Reseñas
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>promociones.php">
        <i class="bi bi-rocket-takeoff-fill" aria-hidden="true"></i> Promociones
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>configuracion.php">
        <i class="bi bi-gear" aria-hidden="true"></i> Configuración
      </a>
      <div class="cl-drawer-sep"></div>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>perfil.php">
        <i class="bi bi-person-circle" aria-hidden="true"></i> Mi perfil
      </a>
      <?php if (!empty($user['id'])): ?>
        <a class="cl-drawer-link" href="<?= $viewsPath ?>seller_profile.php?seller_id=<?= $user['id'] ?>">
          <i class="bi bi-shop-window" aria-hidden="true"></i> Perfil público
        </a>
      <?php endif; ?>
      <div class="cl-drawer-sep"></div>
      <a class="cl-drawer-link cl-drawer-logout" href="<?= $ctrlPath ?>auth_logout.php">
        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Cerrar sesión
      </a>
    <?php else: ?>
      <div class="cl-drawer-sep"></div>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>auth/login.php">
        <i class="bi bi-person" aria-hidden="true"></i> Iniciar sesión
      </a>
      <a class="cl-drawer-link" href="<?= $viewsPath ?>auth/register.php">
        <i class="bi bi-person-plus" aria-hidden="true"></i> Registrarse
      </a>
    <?php endif; ?>
  </nav>

  <?php if ($isLoggedIn): ?>
  <div class="cl-drawer-footer">
    <a class="cl-drawer-publish" href="<?= $viewsPath ?>products/crear.php">
      <i class="bi bi-plus-circle-fill" aria-hidden="true"></i> Publicar producto
    </a>
  </div>
  <?php endif; ?>
</aside>

<?php if ($isLoggedIn): ?>
<script>
  window.__CL = {
    apiPath: '<?= $apiPath ?>',
    viewsPath: '<?= $viewsPath ?>',
    userId: <?= (int)($user['id'] ?? 0) ?>
  };
</script>
<?php endif; ?>
<script src="<?= $publicPath ?>js/header.js" defer></script>
