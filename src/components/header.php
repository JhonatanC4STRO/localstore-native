<?php
/**
 * Unified Header Component — ComercioLocal
 *
 * Required variables (set before including):
 *   $basePath   – relative path from current page to src/ (e.g. '../' or '../../')
 *   $isLoggedIn – bool
 *   $user       – array|null (session user data)
 *   $userInitial– string (first letter of name)
 *   $userName   – string (first name)
 */

// Defaults for safety
$basePath    = $basePath    ?? '../';
$isLoggedIn  = $isLoggedIn  ?? false;
$user        = $user        ?? null;
$userInitial = $userInitial ?? '';
$userName    = $userName    ?? '';
$appPath     = rtrim($basePath, '/') . '/app/';
?>
<!-- ══ UNIFIED HEADER ══ -->
<link rel="stylesheet" href="<?= $basePath ?>style/header.css">
<header class="cl-header">
  <a href="<?= $appPath ?>inde.php" class="cl-logo">
    <img src="<?= $appPath ?>Logo de Comercio Local.png" alt="ComercioLocal">
  </a>

  <div class="cl-search">
    <input type="text" id="clSearchInput" placeholder="Buscar productos, servicios..."
           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
    <button id="clSearchBtn"><i class="bi bi-search"></i></button>
  </div>
  <script>
  (function(){
    const inp = document.getElementById('clSearchInput');
    const btn = document.getElementById('clSearchBtn');
    const base = <?= json_encode($appPath) ?>;
    function go(){
      const q = inp?.value?.trim();
      if(!q) return;
      // On allProduct.php trigger inline search; elsewhere redirect
      if(typeof applyFiltersAP === 'function'){
        const si = document.getElementById('searchInput');
        if(si){ si.value = q; toggleClear(q); }
        applyFiltersAP();
      } else {
        window.location.href = base + 'allProduct.php?search=' + encodeURIComponent(q);
      }
    }
    inp?.addEventListener('keydown', e => { if(e.key === 'Enter') go(); });
    btn?.addEventListener('click', go);
  })();
  </script>

  <div class="cl-spacer"></div>

  <div class="cl-actions">
    <a href="<?= $appPath ?>chat.php" class="cl-icon-btn" title="Mensajes">
      <i class="bi bi-chat-dots"></i>
      <div class="cl-notif-dot"></div>
    </a>
    <div class="cl-icon-btn" title="Notificaciones">
      <i class="bi bi-bell"></i>
      <div class="cl-notif-dot"></div>
    </div>

    <?php if ($isLoggedIn): ?>
      <!-- ── Logged in: User chip + dropdown ── -->
      <div class="cl-user-wrap">
        <div class="cl-user-chip">
          <div style="position:relative;">
            <div class="cl-user-av"><?= htmlspecialchars($userInitial) ?></div>
            <div class="cl-user-online"></div>
          </div>
          <div class="cl-user-info">
            <div class="cl-user-greet">Hola,</div>
            <div class="cl-user-name"><?= htmlspecialchars($userName) ?></div>
          </div>
          <i class="bi bi-chevron-down cl-user-arrow"></i>
        </div>

        <div class="cl-dropdown">
          <div class="cl-dd-head">
            <div class="cl-dd-av"><?= htmlspecialchars($userInitial) ?></div>
            <div>
              <div class="cl-dd-name"><?= htmlspecialchars($user['full_name'] ?? '') ?></div>
              <div class="cl-dd-email"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            </div>
          </div>
          <div class="cl-dd-body">
            <a class="cl-dd-link" href="<?= $appPath ?>dashboard.php">
              <i class="bi bi-speedometer2"></i> Mi panel
            </a>
            <a class="cl-dd-link" href="<?= $appPath ?>crear.php">
              <i class="bi bi-plus-square"></i> Publicar anuncio
            </a>
            <a class="cl-dd-link" href="<?= $appPath ?>misProductos.php">
              <i class="bi bi-box-seam"></i> Mis productos
            </a>
            <a class="cl-dd-link" href="<?= $appPath ?>chat.php">
              <i class="bi bi-chat-dots"></i> Mensajes
            </a>
            <div class="cl-dd-sep"></div>
            <a class="cl-dd-link" href="<?= $appPath ?>perfil.php">
              <i class="bi bi-person-circle"></i> Mi perfil
            </a>
            <?php if (!empty($user['id'])): ?>
              <a class="cl-dd-link" href="<?= $appPath ?>seller_profile.php?seller_id=<?= $user['id'] ?>">
                <i class="bi bi-shop-window"></i> Perfil público
              </a>
            <?php endif; ?>
            <div class="cl-dd-sep"></div>
            <a class="cl-dd-logout" href="<?= $basePath ?>controller/logout.php">
              <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </a>
          </div>
        </div>
      </div>

      <a class="cl-btn-publish" href="<?= $appPath ?>dashboard.php">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Publicar</span>
      </a>

    <?php else: ?>
      <!-- ── Not logged in ── -->
      <a class="cl-btn-ghost" href="<?= $appPath ?>auth/login.php">
        <i class="bi bi-person"></i> Iniciar sesión
      </a>
      <a class="cl-btn-ghost" href="<?= $appPath ?>auth/register.php">
        Registrarse
      </a>
      <a class="cl-btn-publish" href="<?= $appPath ?>auth/login.php">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Publicar</span>
      </a>
    <?php endif; ?>
  </div>
</header>
<script src="<?= $basePath ?>js/header.js" defer></script>