<?php
session_start();
include('../config/conexion.php');

/* ── Auth context ── */
$isLoggedIn  = isset($_SESSION['user']);
$currentUser = $isLoggedIn ? $_SESSION['user'] : null;
$meInitial   = $isLoggedIn ? strtoupper(mb_substr($currentUser['full_name'], 0, 1)) : '';
$meName      = $isLoggedIn ? explode(' ', $currentUser['full_name'])[0] : '';

/* ── Validate seller ID ── */
if (!isset($_GET['seller_id']) || !is_numeric($_GET['seller_id'])) {
    header("Location: ./inde.php");
    exit();
}
$seller_id = (int) $_GET['seller_id'];

/* ── Fetch seller ── */
$res_seller = mysqli_query($conn,
    "SELECT id, full_name, email, phone, city, address, bio, profile_photo, created_at
     FROM users WHERE id = $seller_id LIMIT 1");
if (!$res_seller || mysqli_num_rows($res_seller) === 0) {
    header("Location: ./inde.php?error=vendedor_no_encontrado");
    exit();
}
$seller        = mysqli_fetch_assoc($res_seller);
$sellerInitial = strtoupper(mb_substr($seller['full_name'], 0, 1));
$sellerName    = $seller['full_name'] ?? 'Vendedor';
$sellerCity    = $seller['city']      ?? 'Colombia';
$sellerBio     = $seller['bio']       ?? 'Vendedor en ComercioLocal.';
$memberSince   = $seller['created_at'] ? date('Y', strtotime($seller['created_at'])) : date('Y');

/* ── Seller stats ── */
$res_stats = mysqli_query($conn,
    "SELECT COUNT(*) as total FROM products WHERE user_id = $seller_id AND status = 'disponible'");
$totalProducts = $res_stats ? (int)mysqli_fetch_assoc($res_stats)['total'] : 0;

/* ── Seller products with optional filters ── */
$filter_cat   = isset($_GET['cat'])       && is_numeric($_GET['cat'])  ? (int)$_GET['cat']                             : 0;
$filter_cond  = isset($_GET['cond'])      && in_array($_GET['cond'], ['nuevo','usado']) ? $_GET['cond']                : '';
$filter_pmin  = isset($_GET['pmin'])      && is_numeric($_GET['pmin']) ? (int)$_GET['pmin']                            : 0;
$filter_pmax  = isset($_GET['pmax'])      && is_numeric($_GET['pmax']) ? (int)$_GET['pmax']                            : 999999999;
$filter_order = isset($_GET['order'])     ? trim($_GET['order'])                                                       : 'recent';

$where = ["p.user_id = $seller_id", "p.status = 'disponible'"];
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
     WHERE p.user_id = $seller_id AND p.status = 'disponible'
     ORDER BY c.name ASC");

/* ── Reviews ── */
$reviews = [
    ['name' => 'Laura M.',  'rating' => 5, 'date' => '2024-11-20', 'text' => 'Excelente vendedor, muy rápido y el producto en perfecto estado.'],
    ['name' => 'Carlos R.', 'rating' => 4, 'date' => '2024-10-08', 'text' => 'Buena atención y el artículo llegó tal como lo describió. Recomendado.'],
    ['name' => 'Ana G.',    'rating' => 5, 'date' => '2024-09-14', 'text' => '100% confiable. Hicimos el intercambio sin problemas. Volvería a comprarle.'],
];

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

  <link rel="stylesheet" href="../style/seller_profile.css">
</head>
<body>

<!-- ══ NAVBAR ══ -->
<?php $basePath = '../'; include __DIR__ . '/../components/header.php'; ?>

<!-- ══ BREADCRUMB ══ -->
<div class="breadcrumb-bar">
  <div class="breadcrumb">
    <a href="./inde.php">Inicio</a>
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
            <img src="./uploads/avatars/<?php echo htmlspecialchars($seller['profile_photo']); ?>" alt="">
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
          <div class="seller-verified-badge"><i class="bi bi-patch-check-fill"></i> Verificado</div>
        </div>

        <h1 class="seller-name-lg"><?php echo htmlspecialchars($sellerName); ?></h1>

        <div class="seller-meta-row">
          <div class="seller-meta-item"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($sellerCity); ?></div>
          <div class="seller-meta-item"><i class="bi bi-calendar3"></i> Miembro desde <?php echo $memberSince; ?></div>
          <div class="seller-meta-item"><i class="bi bi-box-seam-fill"></i> <?php echo $totalProducts; ?> producto<?php echo $totalProducts !== 1 ? 's' : ''; ?></div>
          <div class="seller-meta-item"><i class="bi bi-clock-fill"></i> Responde en &lt;1 hora</div>
        </div>

        <div class="seller-rating-row">
          <div class="stars-lg">
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
          </div>
          <span class="rating-num">4.8</span>
          <span class="rating-count">(<?php echo count($reviews); ?> reseñas)</span>
        </div>

        <div class="seller-cta">
          <?php if ($isLoggedIn && $currentUser['id'] !== $seller_id): ?>
            <a href="./crear_conversacion_general.php?seller_id=<?php echo $seller_id; ?>" class="btn-msg">
              <i class="bi bi-chat-dots-fill"></i> Enviar mensaje
            </a>
          <?php elseif (!$isLoggedIn): ?>
            <a href="./auth/login.php" class="btn-msg">
              <i class="bi bi-chat-dots-fill"></i> Enviar mensaje
            </a>
          <?php endif; ?>
          <?php if (!empty($seller['phone'])): ?>
            <a href="tel:<?php echo htmlspecialchars($seller['phone']); ?>" class="btn-contact">
              <i class="bi bi-telephone-fill"></i> Contactar
            </a>
          <?php else: ?>
            <button class="btn-contact"><i class="bi bi-telephone-fill"></i> Contactar</button>
          <?php endif; ?>
          <button class="btn-follow" id="followBtn" onclick="toggleFollow(this)">
            <i class="bi bi-person-plus-fill"></i> Seguir
          </button>
        </div>
      </div>
    </div>

    <!-- Stats strip -->
    <div class="seller-stats-strip">
      <div class="sss-item"><div class="sss-num"><?php echo $totalProducts; ?></div><div class="sss-lbl">Anuncios activos</div></div>
      <div class="sss-item"><div class="sss-num">47</div><div class="sss-lbl">Ventas completadas</div></div>
      <div class="sss-item"><div class="sss-num">4.8</div><div class="sss-lbl">Calificación</div></div>
      <div class="sss-item"><div class="sss-num">98%</div><div class="sss-lbl">Tasa de respuesta</div></div>
      <div class="sss-item"><div class="sss-num">&lt;1h</div><div class="sss-lbl">Tiempo respuesta</div></div>
    </div>
  </div>

  <!-- ══ MAIN LAYOUT ══ -->
  <div class="main-layout">

    <!-- LEFT COL -->
    <div class="left-col">

      <!-- Trust bar -->
      <div class="trust-bar">
        <div class="trust-item">
          <div class="trust-icon ti-green"><i class="bi bi-shield-check-fill"></i></div>
          <div class="trust-text">
            <strong>Identidad verificada</strong>
            <span>Documento validado</span>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon ti-yellow"><i class="bi bi-lightning-charge-fill"></i></div>
          <div class="trust-text">
            <strong>Responde rápido</strong>
            <span>En menos de 1 hora</span>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon ti-blue"><i class="bi bi-hand-thumbs-up-fill"></i></div>
          <div class="trust-text">
            <strong>47 ventas exitosas</strong>
            <span>Sin disputas abiertas</span>
          </div>
        </div>
        <div class="trust-item">
          <div class="trust-icon ti-green"><i class="bi bi-star-fill"></i></div>
          <div class="trust-text">
            <strong>4.8 / 5 estrellas</strong>
            <span><?php echo count($reviews); ?> reseñas positivas</span>
          </div>
        </div>
      </div>

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
            $isNew = strtolower($prod['condition_type'] ?? '') === 'nuevo';
            $price = number_format($prod['price'], 0, ',', '.');
            $tAgo  = timeAgoShort($prod['created_at']);
        ?>
          <a href="./actions/detalleProducto.php?id=<?php echo $prod['id']; ?>" style="display:contents;">
            <div class="product-card">
              <div class="pc-badge <?php echo $isNew ? 'badge-new' : 'badge-used'; ?>">
                <?php echo $isNew ? 'Nuevo' : 'Usado'; ?>
              </div>
              <button class="pc-fav"
                      onclick="event.preventDefault();this.classList.toggle('active');this.querySelector('i').className=this.classList.contains('active')?'bi bi-heart-fill':'bi bi-heart';">
                <i class="bi bi-heart"></i>
              </button>
              <div class="pc-img">
                <?php if (!empty($prod['image_url'])): ?>
                  <img src="./productos/uploads/<?php echo htmlspecialchars($prod['image_url']); ?>" alt="">
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

      <!-- Reviews -->
      <div class="reviews-section">
        <div class="rev-header">
          <div class="rev-title">Reseñas del <span>vendedor</span></div>
        </div>

        <div class="rev-summary">
          <div class="rev-avg">
            <div class="rev-avg-num">4.8</div>
            <div class="rev-avg-stars">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
            </div>
            <div class="rev-avg-count"><?php echo count($reviews); ?> reseñas</div>
          </div>
          <div class="rev-divider"></div>
          <div class="rev-bars">
            <?php foreach ([5=>85, 4=>10, 3=>3, 2=>1, 1=>1] as $stars => $pct): ?>
              <div class="rev-bar-row">
                <span><?php echo $stars; ?></span>
                <div class="rev-bar-track"><div class="rev-bar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="rev-cards">
          <?php foreach ($reviews as $rev):
            $revInitial = strtoupper($rev['name'][0]);
          ?>
            <div class="rev-card">
              <div class="rev-card-top">
                <div class="rev-av"><?php echo $revInitial; ?></div>
                <div>
                  <div class="rev-name"><?php echo htmlspecialchars($rev['name']); ?></div>
                  <div class="rev-date"><?php echo date('d M Y', strtotime($rev['date'])); ?></div>
                </div>
                <div class="rev-stars">
                  <?php for ($s=1;$s<=5;$s++): ?>
                    <i class="bi <?php echo $s <= $rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                  <?php endfor; ?>
                </div>
              </div>
              <div class="rev-text"><?php echo htmlspecialchars($rev['text']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
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
          <div class="sc-row"><i class="bi bi-clock-fill"></i> Responde en menos de 1 hora</div>
          <div class="sc-row"><i class="bi bi-shield-check-fill"></i> Identidad verificada</div>
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
        <a href="<?php echo $isLoggedIn ? './crear.php' : './auth/register.php'; ?>">
          Publicar gratis <i class="bi bi-arrow-right"></i>
        </a>
      </div>

    </div><!-- /right-sidebar -->

  </div><!-- /main-layout -->

</div><!-- /page -->

<script src="../js/seller_profile.js"></script>

</body>
</html>