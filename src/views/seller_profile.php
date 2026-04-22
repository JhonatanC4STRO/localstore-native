<?php
session_start();
include('../config/conexion.php');
include_once('../config/user_settings.php');

/* ── Auth context ── */
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';

/* ── Validate seller ID ── */
if (!isset($_GET['seller_id']) || !is_numeric($_GET['seller_id'])) {
    header("Location: ./home.php");
    exit();
}
$seller_id = (int) $_GET['seller_id'];

/* ── Fetch seller ── */
$res_seller = mysqli_query($conn,
    "SELECT id, full_name, email, phone, city, address, bio, profile_photo, created_at
     FROM users WHERE id = $seller_id LIMIT 1");
if (!$res_seller || mysqli_num_rows($res_seller) === 0) {
    header("Location: ./home.php?error=vendedor_no_encontrado");
    exit();
}
$seller        = mysqli_fetch_assoc($res_seller);
$sellerInitial = strtoupper(mb_substr($seller['full_name'], 0, 1));
$sellerName    = $seller['full_name'] ?? 'Vendedor';
$sellerCity    = $seller['city']      ?? 'Colombia';
$sellerBio     = $seller['bio']       ?? 'Vendedor en ComercioLocal.';
$memberSince   = $seller['created_at'] ? date('Y', strtotime($seller['created_at'])) : date('Y');

/* ── Seller verification ── */
$resVerif = mysqli_query($conn,
    "SELECT id, verification_type FROM seller_verifications WHERE user_id = $seller_id AND status = 'approved' LIMIT 1");
$sellerVerif = $resVerif ? mysqli_fetch_assoc($resVerif) : null;
$isSellerVerified = !empty($sellerVerif);
$sellerVerifType  = $sellerVerif['verification_type'] ?? 'negocio';

/* ── Seller stats ── */
$res_stats = mysqli_query($conn,
    "SELECT COUNT(*) as total FROM products WHERE user_id = $seller_id AND status = 'disponible' AND admin_status = 'active'");
$totalProducts = $res_stats ? (int)mysqli_fetch_assoc($res_stats)['total'] : 0;

/* ── Seller products with optional filters ── */
$filter_cat   = isset($_GET['cat'])       && is_numeric($_GET['cat'])  ? (int)$_GET['cat']                             : 0;
$filter_cond  = isset($_GET['cond'])      && in_array($_GET['cond'], ['nuevo','usado']) ? $_GET['cond']                : '';
$filter_pmin  = isset($_GET['pmin'])      && is_numeric($_GET['pmin']) ? (int)$_GET['pmin']                            : 0;
$filter_pmax  = isset($_GET['pmax'])      && is_numeric($_GET['pmax']) ? (int)$_GET['pmax']                            : 999999999;
$filter_order = isset($_GET['order'])     ? trim($_GET['order'])                                                       : 'recent';

$where = ["p.user_id = $seller_id", "p.status = 'disponible'", "p.admin_status = 'active'"];
if ($filter_cat)  $where[] = "p.category_id = $filter_cat";
if ($filter_cond) $where[] = "LOWER(p.condition_type) = '".mysqli_real_escape_string($conn, $filter_cond)."'";
$where[] = "p.price BETWEEN $filter_pmin AND $filter_pmax";

$order_sql = match($filter_order) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    default      => 'p.id DESC',
};

$where_sql = 'WHERE ' . implode(' AND ', $where);

$res_products = mysqli_query($conn,
    "SELECT p.id, p.title, p.price, p.condition_type, p.created_at,
            c.name AS cat_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS image_url
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     $where_sql
     ORDER BY $order_sql");

/* ── Categories for filter (this seller only) ── */
$res_cats = mysqli_query($conn,
    "SELECT DISTINCT c.id, c.name FROM products p
     JOIN categories c ON p.category_id = c.id
     WHERE p.user_id = $seller_id AND p.status = 'disponible' AND p.admin_status = 'active'
     ORDER BY c.name ASC");

function timeAgoShort($date) {
    if (!$date) return 'Recientemente';
    $diff = time() - strtotime($date);
    if ($diff < 3600)   return 'Hace '.floor($diff/60).' min';
    if ($diff < 86400)  return 'Hace '.floor($diff/3600).' h';
    if ($diff < 604800) return 'Hace '.floor($diff/86400).' días';
    return date('d/m/Y', strtotime($date));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($sellerName); ?> – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

  <link rel="stylesheet" href="../../public/css/seller_profile.css">
</head>
<body>

<!-- ══ NAVBAR ══ -->
<?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

<!-- ══ BREADCRUMB ══ -->
<div class="breadcrumb-bar">
  <div class="breadcrumb">
    <a href="./home.php">Inicio</a>
    <i class="bi bi-chevron-right"></i>
    <a href="#">Vendedores</a>
    <i class="bi bi-chevron-right"></i>
    <span><?php echo htmlspecialchars($sellerName); ?></span>
  </div>
</div>

<!-- ══ PAGE ══ -->
<div class="page">

  <!-- ══ SELLER HERO ══ -->
  <div class="seller-hero">
    <div class="hero-blobs">
      <div class="hero-blob hb1"></div>
      <div class="hero-blob hb2"></div>
    </div>

    <div class="hero-body">
      <!-- Avatar -->
      <div class="seller-av-wrap">
        <div class="seller-av-lg">
          <?php if (!empty($seller['profile_photo'])): ?>
            <img src="../../public/uploads/avatars/<?php echo htmlspecialchars($seller['profile_photo']); ?>" alt="">
          <?php else: ?>
            <?php echo $sellerInitial; ?>
          <?php endif; ?>
        </div>
        <div class="seller-av-online"></div>
      </div>

      <!-- Info -->
      <div class="seller-info">
        <div class="seller-badge-row">
          <div class="seller-type-badge"><i class="bi bi-person-fill"></i> Vendedor particular</div>
          <?php if ($isSellerVerified): ?>
            <div class="seller-verified-badge">
              <i class="bi bi-patch-check-fill"></i>
              <?= $sellerVerifType === 'persona' ? 'Identidad verificada' : 'Vendedor verificado' ?>
            </div>
          <?php endif; ?>
        </div>

        <h1 class="seller-name-lg"><?php echo htmlspecialchars($sellerName); ?></h1>

        <div class="seller-meta-row">
          <div class="seller-meta-item"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($sellerCity); ?></div>
          <div class="seller-meta-item"><i class="bi bi-calendar3"></i> Miembro desde <?php echo $memberSince; ?></div>
          <div class="seller-meta-item"><i class="bi bi-box-seam-fill"></i> <?php echo $totalProducts; ?> producto<?php echo $totalProducts !== 1 ? 's' : ''; ?></div>
        </div>



        <div class="seller-cta">
          <?php
            $seller_prefs = get_user_settings($conn, $seller_id);
            $buyer_id_ctx = $isLoggedIn ? (int)$user['id'] : 0;
            $chat_check   = can_message_seller($conn, $buyer_id_ctx, $seller_id);
            $show_phone   = !empty($seller['phone']) && (int)$seller_prefs['privacy_show_phone'] === 1;
          ?>
          <?php if ($isLoggedIn && $user['id'] !== $seller_id): ?>
            <?php if ($chat_check['ok']): ?>
              <a href="../api/chat/crear_conversacion_general.php?seller_id=<?php echo $seller_id; ?>" class="btn-msg">
                <i class="bi bi-chat-dots-fill"></i> Enviar mensaje
              </a>
            <?php else: ?>
              <button class="btn-msg" style="opacity:.6;cursor:not-allowed;" disabled
                      title="<?php echo htmlspecialchars($chat_check['reason']); ?>">
                <i class="bi bi-chat-dots-fill"></i> Chat no disponible
              </button>
            <?php endif; ?>
          <?php elseif (!$isLoggedIn): ?>
            <a href="./auth/login.php" class="btn-msg">
              <i class="bi bi-chat-dots-fill"></i> Enviar mensaje
            </a>
          <?php endif; ?>
          <?php if ($show_phone):
            $wa_raw = preg_replace('/\D/', '', $seller['phone']);
            if (strlen($wa_raw) === 10 && $wa_raw[0] === '3') {
                $wa_raw = '57' . $wa_raw;
            } elseif (strlen($wa_raw) > 0 && $wa_raw[0] === '0') {
                $wa_raw = '57' . ltrim($wa_raw, '0');
            }
            $wa_url = 'https://wa.me/' . $wa_raw;
          ?>
            <a href="<?php echo $wa_url; ?>" target="_blank" rel="noopener noreferrer" class="btn-contact">
              <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
            </a>
          <?php else: ?>
            <button class="btn-contact" disabled style="opacity:.6;cursor:not-allowed;">
              <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
            </button>
          <?php endif; ?>
          <button class="btn-follow" id="followBtn" onclick="toggleFollow(this)">
            <i class="bi bi-person-plus-fill"></i> Seguir
          </button>
          <?php if ($isLoggedIn && $user['id'] !== $seller_id): ?>
            <button type="button" class="btn-report-user" onclick="openReportModal()"
              style="padding:12px 18px;border-radius:14px;border:1.5px solid rgba(229,57,53,.4);background:rgba(229,57,53,.08);color:#E53935;font-weight:700;font-size:.88rem;display:inline-flex;align-items:center;gap:8px;cursor:pointer;transition:all .2s;"
              onmouseover="this.style.background='rgba(229,57,53,.15)'"
              onmouseout="this.style.background='rgba(229,57,53,.08)'">
              <i class="bi bi-flag-fill"></i> Reportar
            </button>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="seller-stats-strip">
      <div class="sss-item"><div class="sss-num"><?php echo $totalProducts; ?></div><div class="sss-lbl">Anuncios activos</div></div>
    </div>
  </div>

  <!-- ══ MAIN LAYOUT ══ -->
  <div class="main-layout">

    <!-- LEFT COL -->
    <div class="left-col">



      <!-- Products header + filters -->
      <div class="products-section-header">
        <div>
          <div class="products-title">Productos del <span>vendedor</span></div>
          <div class="products-count-badge">
            <i class="bi bi-grid-3x3-gap-fill"></i>
            <?php echo $totalProducts; ?> producto<?php echo $totalProducts !== 1 ? 's' : ''; ?> publicados
          </div>
        </div>
      </div>

      <!-- Filter row — GET form (logic preserved) -->
      <div class="filter-row">
        <form method="GET" action="">
          <input type="hidden" name="seller_id" value="<?php echo $seller_id; ?>">

          <!-- Category -->
          <select name="cat" class="fr-select" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            <?php if ($res_cats): while ($cat = mysqli_fetch_assoc($res_cats)): ?>
              <option value="<?php echo $cat['id']; ?>" <?php echo $filter_cat == $cat['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($cat['name']); ?>
              </option>
            <?php endwhile; endif; ?>
          </select>

          <!-- Condition -->
          <select name="cond" class="fr-select" onchange="this.form.submit()">
            <option value="">Cualquier condición</option>
            <option value="nuevo" <?php echo $filter_cond === 'nuevo' ? 'selected' : ''; ?>>Nuevo</option>
            <option value="usado" <?php echo $filter_cond === 'usado' ? 'selected' : ''; ?>>Usado</option>
          </select>

          <!-- Price range -->
          <div class="fr-price">
            <input type="number" name="pmin" placeholder="Precio mín" value="<?php echo $filter_pmin > 0 ? $filter_pmin : ''; ?>">
            <span>–</span>
            <input type="number" name="pmax" placeholder="Precio máx" value="<?php echo $filter_pmax < 999999999 ? $filter_pmax : ''; ?>">
          </div>

          <!-- Order -->
          <select name="order" class="fr-select" onchange="this.form.submit()">
            <option value="recent"     <?php echo $filter_order === 'recent'     ? 'selected' : ''; ?>>Más recientes</option>
            <option value="price_asc"  <?php echo $filter_order === 'price_asc'  ? 'selected' : ''; ?>>Precio ↑</option>
            <option value="price_desc" <?php echo $filter_order === 'price_desc' ? 'selected' : ''; ?>>Precio ↓</option>
          </select>

          <button type="submit" class="btn-fr"><i class="bi bi-funnel-fill"></i> Filtrar</button>
          <a href="?seller_id=<?php echo $seller_id; ?>" class="btn-fr-reset">Limpiar</a>
        </form>
      </div>

      <!-- Product grid -->
      <div class="product-grid">
        <?php if ($res_products && mysqli_num_rows($res_products) > 0):
          while ($prod = mysqli_fetch_assoc($res_products)):
            $spCond = strtolower($prod['condition_type'] ?? '');
            $isNew = $spCond === 'nuevo';
            $isRefurb = $spCond === 'reacondicionado';
            $price = number_format($prod['price'], 0, ',', '.');
            $tAgo  = timeAgoShort($prod['created_at']);
        ?>
          <a href="./products/detalle.php?id=<?php echo $prod['id']; ?>" style="display:contents;">
            <div class="product-card">
              <div class="pc-badge <?php echo $isNew ? 'badge-new' : ($isRefurb ? 'badge-refurbished' : 'badge-used'); ?>">
                <?php echo $isNew ? 'Nuevo' : ($isRefurb ? 'Reacondicionado' : 'Usado'); ?>
              </div>
              <button class="pc-fav" data-product-id="<?php echo (int)$prod['id']; ?>">
                <i class="bi bi-heart"></i>
              </button>
              <div class="pc-img">
                <?php if (!empty($prod['image_url'])): ?>
                  <img src="../../public/uploads/products/<?php echo htmlspecialchars($prod['image_url']); ?>" alt="">
                <?php else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>
              </div>
              <div class="pc-body">
                <div class="pc-price">$<?php echo $price; ?></div>
                <div class="pc-title"><?php echo htmlspecialchars($prod['title']); ?></div>
                <div class="pc-meta">
                  <div class="pc-meta-row"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($sellerCity); ?></div>
                  <div class="pc-meta-row"><i class="bi bi-clock-fill"></i> <?php echo $tAgo; ?></div>
                  <?php if (!empty($prod['cat_name'])): ?>
                    <div class="pc-meta-row"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($prod['cat_name']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </a>
        <?php endwhile; else: ?>
          <div class="empty-state">
            <i class="bi bi-box-seam"></i>
            <h3>Sin productos con estos filtros</h3>
            <p>Prueba cambiando los filtros o <a href="?seller_id=<?php echo $seller_id; ?>" style="color:var(--g500);">ver todos</a>.</p>
          </div>
        <?php endif; ?>
      </div>



    </div><!-- /left-col -->

    <!-- RIGHT SIDEBAR -->
    <div class="right-sidebar">

      <!-- About card -->
      <div class="sidebar-card">
        <div class="sc-header">
          <i class="bi bi-person-lines-fill"></i>
          <h3>Sobre el vendedor</h3>
        </div>
        <div class="sc-body">
          <p class="bio-text"><?php echo htmlspecialchars($sellerBio); ?></p>
          <?php if (!empty($seller['city'])): ?>
            <div class="sc-row"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($seller['city']); ?></div>
          <?php endif; ?>
          <?php if (!empty($seller['address'])): ?>
            <div class="sc-row"><i class="bi bi-house-fill"></i> <?php echo htmlspecialchars($seller['address']); ?></div>
          <?php endif; ?>
          <?php if (!empty($seller['phone'])): ?>
            <div class="sc-row"><i class="bi bi-telephone-fill"></i> <?php echo htmlspecialchars($seller['phone']); ?></div>
          <?php endif; ?>
          <div class="sc-row"><i class="bi bi-calendar3"></i> Miembro desde <?php echo $memberSince; ?></div>
        </div>
      </div>

      <!-- Safety tips -->
      <div class="sidebar-card">
        <div class="sc-header">
          <i class="bi bi-shield-fill"></i>
          <h3>Consejos de seguridad</h3>
        </div>
        <div class="sc-body">
          <?php
          $tips = [
            ['bi-people-fill',          'Reúnete en lugares públicos'],
            ['bi-eye-fill',             'Verifica el producto antes de pagar'],
            ['bi-cash-coin',            'Prefiere pago en efectivo o transferencia segura'],
            ['bi-chat-dots-fill',       'Comunícate solo dentro de la plataforma'],
            ['bi-exclamation-triangle', 'Desconfía de precios demasiado bajos'],
          ];
          foreach ($tips as [$icon, $text]):
          ?>
            <div class="sc-row"><i class="bi <?php echo $icon; ?>"></i> <?php echo $text; ?></div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Promo -->
      <div class="promo-card">
        <div class="pt">🚀 ComercioLocal</div>
        <h4>¿Tienes algo para vender?</h4>
        <p>Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.</p>
        <a href="<?php echo $isLoggedIn ? './products/crear.php' : './auth/register.php'; ?>">
          Publicar gratis <i class="bi bi-arrow-right"></i>
        </a>
      </div>

    </div><!-- /right-sidebar -->

  </div><!-- /main-layout -->

</div><!-- /page -->

<?php if ($isLoggedIn && $user['id'] !== $seller_id): ?>
<!-- ══ REPORT USER MODAL ══ -->
<div id="reportModal" class="rep-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(11,46,23,.65);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;padding:20px;">
  <div class="rep-modal-card" style="background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:92vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.35);">
    <div style="background:linear-gradient(135deg,#E53935,#C62828);color:#fff;padding:22px 26px;display:flex;align-items:center;gap:14px;">
      <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
        <i class="bi bi-flag-fill"></i>
      </div>
      <div style="flex:1;">
        <h3 style="margin:0;font-size:1.1rem;font-weight:800;">Reportar a <?php echo htmlspecialchars($sellerName); ?></h3>
        <p style="margin:2px 0 0;font-size:.8rem;opacity:.9;">Ayúdanos a mantener una comunidad segura</p>
      </div>
      <button type="button" onclick="closeReportModal()" style="background:rgba(255,255,255,.2);border:0;color:#fff;width:34px;height:34px;border-radius:10px;cursor:pointer;font-size:1.1rem;">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>
    <form id="reportForm" enctype="multipart/form-data" style="padding:22px 26px;overflow-y:auto;">
      <input type="hidden" name="type" value="user">
      <input type="hidden" name="target_id" value="<?php echo $seller_id; ?>">

      <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:700;color:#0B2E17;margin-bottom:8px;font-size:.88rem;">Motivo del reporte *</label>
        <select name="reason" required style="width:100%;padding:12px 14px;border:1.5px solid #D6D9D1;border-radius:12px;font-size:.9rem;font-family:inherit;background:#fff;">
          <option value="">Selecciona un motivo</option>
          <option value="suplantacion">Suplantación de identidad</option>
          <option value="fraude">Fraude o estafa</option>
          <option value="spam">Spam o contenido repetitivo</option>
          <option value="contenido_ofensivo">Contenido ofensivo o abusivo</option>
          <option value="otro">Otro</option>
        </select>
      </div>

      <div style="margin-bottom:16px;">
        <label style="display:block;font-weight:700;color:#0B2E17;margin-bottom:8px;font-size:.88rem;">Descripción detallada *</label>
        <textarea name="description" required minlength="20" maxlength="1000" rows="5"
          placeholder="Describe con detalle lo que sucedió (mínimo 20 caracteres)…"
          style="width:100%;padding:12px 14px;border:1.5px solid #D6D9D1;border-radius:12px;font-size:.9rem;font-family:inherit;resize:vertical;"></textarea>
        <div style="font-size:.72rem;color:#8A8F80;margin-top:4px;">Entre 20 y 1000 caracteres</div>
      </div>

      <div style="margin-bottom:18px;">
        <label style="display:block;font-weight:700;color:#0B2E17;margin-bottom:8px;font-size:.88rem;">Evidencia (opcional)</label>
        <input type="file" name="evidence" accept="image/png,image/jpeg,image/webp"
          style="width:100%;padding:10px;border:1.5px dashed #D6D9D1;border-radius:12px;font-size:.82rem;background:#F7F8F4;cursor:pointer;">
        <div style="font-size:.72rem;color:#8A8F80;margin-top:4px;">Formatos: JPG, PNG, WEBP — máx. 5 MB</div>
      </div>

      <div id="reportMsg" style="display:none;padding:10px 14px;border-radius:10px;font-size:.85rem;margin-bottom:14px;"></div>

      <div style="display:flex;gap:10px;justify-content:flex-end;">
        <button type="button" onclick="closeReportModal()" style="padding:12px 20px;border-radius:12px;border:1.5px solid #D6D9D1;background:#fff;color:#0B2E17;font-weight:700;cursor:pointer;">Cancelar</button>
        <button type="submit" id="btnSubmitReport" style="padding:12px 22px;border-radius:12px;border:0;background:linear-gradient(135deg,#E53935,#C62828);color:#fff;font-weight:800;cursor:pointer;display:inline-flex;align-items:center;gap:8px;">
          <i class="bi bi-send-fill"></i> Enviar reporte
        </button>
      </div>
    </form>
  </div>
</div>

<script>
  function openReportModal() {
    const m = document.getElementById('reportModal');
    m.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function closeReportModal() {
    const m = document.getElementById('reportModal');
    m.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('reportForm').reset();
    document.getElementById('reportMsg').style.display = 'none';
  }
  document.getElementById('reportModal').addEventListener('click', (e) => {
    if (e.target.id === 'reportModal') closeReportModal();
  });
  document.getElementById('reportForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form  = e.target;
    const btn   = document.getElementById('btnSubmitReport');
    const msgEl = document.getElementById('reportMsg');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando…';
    try {
      const res  = await fetch('../api/reports.php', { method: 'POST', body: new FormData(form) });
      const data = await res.json();
      msgEl.style.display = 'block';
      if (data.ok) {
        msgEl.style.background = '#E7F5EC'; msgEl.style.color = '#0B5D2C'; msgEl.style.border = '1px solid #B5E0C4';
        msgEl.innerHTML = '<i class="bi bi-check-circle-fill"></i> ' + data.message;
        setTimeout(closeReportModal, 1800);
      } else {
        msgEl.style.background = '#FDECEA'; msgEl.style.color = '#B71C1C'; msgEl.style.border = '1px solid #F5B5B0';
        msgEl.innerHTML = '<i class="bi bi-exclamation-circle-fill"></i> ' + (data.error || 'Error al enviar');
      }
    } catch (err) {
      msgEl.style.display = 'block';
      msgEl.style.background = '#FDECEA'; msgEl.style.color = '#B71C1C'; msgEl.style.border = '1px solid #F5B5B0';
      msgEl.textContent = 'Error de conexión';
    }
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-send-fill"></i> Enviar reporte';
  });
</script>
<?php endif; ?>

<script src="../../public/js/seller_profile.js"></script>
<script src="../../public/js/favorites.js"></script>
<script>
  (async () => {
    const base = '../../';
    await Favorites.load(base);
    Favorites.applyToButtons('.pc-fav');
    Favorites.bindButtons('.pc-fav', base);
  })();
</script>

</body>
</html>














