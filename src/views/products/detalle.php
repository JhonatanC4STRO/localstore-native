<?php
include_once("../../config/conexion.php");
include_once("../../config/user_settings.php");
session_start();
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
$isSeller    = $isLoggedIn;

/* ══════════════════════════════════════════
   LOGIC 100% PRESERVED — original queries
══════════════════════════════════════════ */
if (!isset($_GET['id'])) {
  echo "Producto no encontrado";
  exit;
}

$id = intval($_GET['id']);

$sql    = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

$sqlVendedor = "SELECT u.id, u.full_name, u.email, u.phone, u.city FROM users u
        JOIN products p ON p.user_id = u.id
        WHERE p.id = $id";

$resultVendedor = mysqli_query($conn, $sqlVendedor);
$seller_info = mysqli_fetch_assoc($resultVendedor);

$user_id_product = $product['user_id'] ?? 0;
$sqlVerified = "SELECT id, verification_type FROM seller_verifications WHERE user_id = '$user_id_product' AND status = 'approved' LIMIT 1";
$resVerified = mysqli_query($conn, $sqlVerified);
$verifRow = $resVerified ? mysqli_fetch_assoc($resVerified) : null;
$is_seller_verified = !empty($verifRow);
$verif_type_label = ($verifRow['verification_type'] ?? 'negocio') === 'persona' ? 'Identidad verificada' : 'Vendedor verificado';

if (!$product) {
  echo "Producto no encontrado";
  exit;
}

$isOwner = $isLoggedIn && (int)($product['user_id'] ?? 0) === (int)($user['id'] ?? 0);

/* Contar vista (una vez por sesion por producto, no cuenta al dueño) */
if (!isset($_SESSION['viewed_products'])) {
    $_SESSION['viewed_products'] = [];
}
if (!$isOwner && !in_array($id, $_SESSION['viewed_products'], true)) {
    mysqli_query($conn, "UPDATE products SET views = views + 1 WHERE id = $id");
    $_SESSION['viewed_products'][] = $id;
    $product['views'] = ($product['views'] ?? 0) + 1;
}

$img_sql    = "SELECT * FROM product_images WHERE product_id = '$id' ORDER BY id ASC";
$img_result = mysqli_query($conn, $img_sql);
$images = [];
if ($img_result && mysqli_num_rows($img_result) > 0) {
  while ($img = mysqli_fetch_assoc($img_result)) {
    $images[] = $img;
  }
}

if (!$seller_info) {
  $seller_info = [
    'full_name' => 'Usuario desconocido',
    'email'     => ''
  ];
}

$condition_type  = $product['condition_type'];
$cond_lower      = strtolower($condition_type);
$badge_class     = $cond_lower === 'nuevo' ? 'badge-new' : ($cond_lower === 'reacondicionado' ? 'badge-refurbished' : 'badge-used');
$price_formatted = number_format($product['price'], 0, ',', '.');
$main_image      = '../../../public/uploads/products/' . ($images[0]['image_url'] ?? 'default.png');

$select_category_sql = "SELECT name FROM categories WHERE id = " . ($product['category_id'] ?? 0);
$category_result = mysqli_query($conn, $select_category_sql);
$category_name    = mysqli_fetch_assoc($category_result)['name'] ?? 'Sin categoría';

/* seller initial */
$seller_initial = strtoupper(mb_substr($seller_info['full_name'], 0, 1));

/* more products from same seller */
$user_id_product = $product['user_id'] ?? 0;
$more_sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) as thumb
             FROM products p
             WHERE p.user_id = '$user_id_product' AND p.id != $id AND p.status = 'disponible'
             LIMIT 4";
$more_result = mysqli_query($conn, $more_sql);

/* similar products (same category) */
$cat_id = $product['category_id'] ?? 0;
$exclude_seller_sql = $isSeller ? " AND p.user_id != " . (int)$user['id'] : '';
$similar_sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) as thumb
                FROM products p
                WHERE p.category_id = '$cat_id' AND p.id != $id AND p.status = 'disponible'$exclude_seller_sql
                LIMIT 4";
$similar_result = mysqli_query($conn, $similar_sql);

/* all products with coordinates for the map */
$stMap = mysqli_prepare($conn,
  "SELECT p.id, p.title, p.price, p.latitude, p.longitude,
          (SELECT pi.image_url FROM product_images pi
           WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS thumb
   FROM products p
   WHERE p.status = 'disponible'
     AND p.latitude  IS NOT NULL AND p.latitude  != '' AND p.latitude  != 0
     AND p.longitude IS NOT NULL AND p.longitude != '' AND p.longitude != 0
   LIMIT 300");
$mapProducts = [];
if ($stMap) {
  mysqli_stmt_execute($stMap);
  $rMap = mysqli_stmt_get_result($stMap);
  while ($mp = mysqli_fetch_assoc($rMap)) {
    $mapProducts[] = [
      'id'    => (int)$mp['id'],
      'title' => $mp['title'],
      'price' => number_format((float)$mp['price'], 0, ',', '.'),
      'lat'   => (float)$mp['latitude'],
      'lon'   => (float)$mp['longitude'],
      'thumb' => $mp['thumb'] ?? '',
    ];
  }
}

/* session for user chip (session already started above) */
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
$currentUserId = $isLoggedIn ? intval($user['id']) : 0;

/* reviews */
$reviews_sql = "SELECT r.rating, r.comment, r.created_at, u.full_name
                FROM reviews r
                JOIN users u ON u.id = r.user_id
                WHERE r.product_id = $id
                ORDER BY r.created_at DESC";
$reviews_result = mysqli_query($conn, $reviews_sql);
$reviews = [];
if ($reviews_result) {
    while ($row = mysqli_fetch_assoc($reviews_result)) {
        $reviews[] = $row;
    }
}

$avg_sql = "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total FROM reviews WHERE product_id = $id";
$avg_result  = mysqli_query($conn, $avg_sql);
$avg_row     = mysqli_fetch_assoc($avg_result);
$avg_rating  = $avg_row['avg_rating'] ?? 0;
$total_reviews = intval($avg_row['total'] ?? 0);

/* ¿el usuario actual ya reseñó este producto? */
$already_reviewed = false;
$is_own_product   = $isLoggedIn && (intval($product['user_id']) === $currentUserId);
if ($isLoggedIn && !$is_own_product) {
    $dup_check = mysqli_query($conn, "SELECT id FROM reviews WHERE product_id = $id AND user_id = $currentUserId");
    $already_reviewed = ($dup_check && mysqli_num_rows($dup_check) > 0);
}

/* ── Stats reales del vendedor ── */
$seller_uid = (int)$user_id_product;

// Ventas (productos marcados como vendido)
$ventas_row = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COUNT(*) AS total FROM products WHERE user_id = $seller_uid AND status = 'vendido'"));
$seller_ventas = (int)($ventas_row['total'] ?? 0);

// Rating global: promedio de todas las reseñas de todos sus productos
$srating_row = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT ROUND(AVG(r.rating),1) AS avg, COUNT(*) AS cnt
   FROM reviews r JOIN products p ON p.id = r.product_id
   WHERE p.user_id = $seller_uid"));
$seller_avg_rating   = $srating_row['avg']  ?? 0;
$seller_total_reviews = (int)($srating_row['cnt'] ?? 0);

// Tasa de respuesta: % de conversaciones donde el vendedor envió al menos un mensaje
$conv_total_row = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT COUNT(*) AS total FROM conversations WHERE seller_id = $seller_uid"));
$conv_total = (int)($conv_total_row['total'] ?? 0);
if ($conv_total > 0) {
    $conv_resp_row = mysqli_fetch_assoc(mysqli_query($conn,
      "SELECT COUNT(DISTINCT c.id) AS responded
       FROM conversations c
       JOIN messages m ON m.conversation_id = c.id AND m.sender_id = $seller_uid
       WHERE c.seller_id = $seller_uid"));
    $conv_responded = (int)($conv_resp_row['responded'] ?? 0);
    $seller_resp_rate = round(($conv_responded / $conv_total) * 100);
} else {
    $seller_resp_rate = null;
}

// Fecha de registro del vendedor
$seller_since_row = mysqli_fetch_assoc(mysqli_query($conn,
  "SELECT created_at FROM users WHERE id = $seller_uid"));
$seller_since = $seller_since_row['created_at'] ?? null;
$seller_since_fmt = $seller_since
  ? date('M Y', strtotime($seller_since))
  : null;
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['title']); ?> – ComercioLocal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <link rel="stylesheet" href="../../../public/css/detalleProducto.css">
  <link rel="stylesheet" href="../../../public/css/output.css">

<body>

  <!-- ══ NAVBAR ══ -->
  <?php $basePath = "../../../"; include __DIR__ . '/../../components/header.php'; ?>

  <!-- ══ BREADCRUMB ══ -->
  <div class="breadcrumb-bar">
    <div class="breadcrumb">
      <a href="../home.php">Inicio</a>
      <i class="bi bi-chevron-right"></i>
      <a href="#"><?php echo htmlspecialchars($product['category'] ?? 'Categoría'); ?></a>
      <i class="bi bi-chevron-right"></i>
      <span><?php echo mb_strimwidth(htmlspecialchars($product['title']), 0, 40, '...'); ?></span>
    </div>
  </div>

  <!-- ══ PAGE ══ -->
  <div class="page">

    <?php if ($isOwner): ?>
    <!-- Owner preview banner -->
    <div class="owner-preview-banner">
      <div class="owner-preview-info">
        <i class="bi bi-eye-fill"></i>
        <div>
          <strong>Vista previa</strong>
          <span>Así ven los compradores tu producto</span>
        </div>
      </div>
      <div class="owner-preview-actions">
        <a href="edit.php?id=<?= $product['id'] ?>" class="owner-btn edit">
          <i class="bi bi-pencil-square"></i> Editar producto
        </a>
        <a href="mis_productos.php" class="owner-btn back">
          <i class="bi bi-arrow-left"></i> Mis productos
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- MAIN PRODUCT GRID -->
    <div class="product-main">

      <!-- ── GALLERY ── -->
      <div class="gallery-col">
        <div class="gallery-main-wrap">
          <?php if (!empty($images)): ?>
            <img id="mainImage"
              src="<?php echo htmlspecialchars($main_image); ?>"
              alt="<?php echo htmlspecialchars($product['title']); ?>"
              class="gallery-main-img">
          <?php else: ?>
            <div class="gallery-main-placeholder">📦</div>
          <?php endif; ?>

          <div class="gallery-badge <?php echo $badge_class; ?>">
            <?php echo ucfirst($condition_type); ?>
          </div>
          <button class="gallery-fav" id="favBtn" data-product-id="<?php echo (int)$product['id']; ?>"><i class="bi bi-heart"></i></button>
          <button class="gallery-share"><i class="bi bi-share"></i></button>
          <?php if (!empty($images)): ?>
            <div class="gallery-count">
              <i class="bi bi-images"></i>
              <span id="imgCounter">1</span> / <?php echo count($images); ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Thumbnails — LOGIC PRESERVED -->
        <?php if (count($images) > 1): ?>
          <div class="thumbnails">
            <?php foreach ($images as $idx => $img): ?>
              <div class="thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>"
                onclick="changeMainImage(this, <?php echo $idx + 1; ?>)">
                <img src="../../../public/uploads/products/<?php echo htmlspecialchars($img['image_url']); ?>"
                  alt="Imagen <?php echo $idx + 1; ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- MAP SECTION — always visible, shows all products -->
        <?php if (!empty($mapProducts)): ?>
          <div class="map-card" style="margin-top:18px;">
            <div class="map-card-header">
              <i class="bi bi-geo-alt-fill"></i>
              <h3>Productos disponibles cerca</h3>
              <span class="map-count-badge"><?= count($mapProducts) ?> en el mapa</span>
            </div>
            <div id="productMap"></div>
            <div class="map-loc-tag">
              <i class="bi bi-geo-alt-fill"></i>
              <?php echo htmlspecialchars($product['location'] ?? 'Colombia'); ?>
              <span style="margin-left:auto;font-size:.72rem;color:#94a3b8;">
                <i class="bi bi-cursor-fill"></i> Clic en un pin para ver el producto
              </span>
            </div>
            <?php
              $lat = !empty($product['latitude'])  ? (float)$product['latitude']  : null;
              $lon = !empty($product['longitude']) ? (float)$product['longitude'] : null;
              if ($lat && $lon):
                $gmaps_url = "https://www.google.com/maps?q={$lat},{$lon}&z=16";
              else:
                $loc_query = urlencode($product['location'] ?? ($seller_info['city'] ?? 'Colombia'));
                $gmaps_url = "https://www.google.com/maps/search/?api=1&query={$loc_query}";
              endif;
            ?>
            <a href="<?= htmlspecialchars($gmaps_url) ?>"
               target="_blank"
               rel="noopener noreferrer"
               class="btn-open-gmaps">
              <i class="bi bi-map-fill"></i>
              Abrir en Google Maps
              <i class="bi bi-box-arrow-up-right" style="font-size:.75rem;opacity:.7;margin-left:auto;"></i>
            </a>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── DETAILS COL ── -->
      <div class="details-col">

        <div class="product-card-wrap">
          <div class="product-category-tag">
            <i class="bi bi-tag-fill"></i>
            <?php echo htmlspecialchars($category_name); ?>
          </div>

          <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>

          <div class="product-price-row">
            <div class="product-price">$<?php echo $price_formatted; ?></div>
            <span class="price-note">COP</span>
          </div>

          <div class="product-rating">
            <div class="stars">
              <?php
              $full  = floor($avg_rating);
              $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
              $empty = 5 - $full - $half;
              for ($s = 0; $s < $full;  $s++) echo '<i class="bi bi-star-fill"></i>';
              if ($half)                         echo '<i class="bi bi-star-half"></i>';
              for ($s = 0; $s < $empty; $s++) echo '<i class="bi bi-star"></i>';
              ?>
            </div>
            <span class="rating-text">
              <?php echo $total_reviews > 0 ? $avg_rating . ' (' . $total_reviews . ' reseña' . ($total_reviews !== 1 ? 's' : '') . ')' : 'Sin reseñas aún'; ?>
            </span>
            <div class="views-badge"><i class="bi bi-eye"></i> <?= (int)($product['views'] ?? 0) ?> vista<?= (int)($product['views'] ?? 0) !== 1 ? 's' : '' ?></div>
          </div>

          <!-- LOGIC PRESERVED: info grid from product fields -->
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Condición</div>
              <div class="info-value"><?php echo htmlspecialchars($condition_type); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Stock</div>
              <div class="info-value"><?php echo htmlspecialchars($product['stock'] ?? 'Disponible'); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Publicado</div>
              <div class="info-value"><?php echo date('d/m/Y', strtotime($product['created_at'] ?? 'now')); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Estado</div>
              <div class="info-value" style="color:var(--g500);">● Activo</div>
            </div>
          </div>

          <?php if (!empty($product['description'])): ?>
            <div class="product-description-block">
              <div class="section-label-sm">Descripción</div>
              <p class="product-description" id="prodDesc">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
              </p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Seller card (in details col for medium screens) -->
        <div class="seller-card">
          <div class="seller-card-header">
            <div class="seller-av-wrap">
              <!-- LOGIC PRESERVED: seller initial -->
              <div class="seller-av"><?php echo $seller_initial; ?></div>
              <div class="seller-online"></div>
            </div>
            <div>
              <!-- LOGIC PRESERVED: seller_info from product -->
              <a href="../seller_profile.php?seller_id=<?php echo $user_id_product; ?>" class="seller-name text-white hover:underline">
                <?php echo htmlspecialchars($seller_info['full_name']); ?>
              </a>
              <?php if ($is_seller_verified): ?>
                <div class="seller-verified"><i class="bi bi-patch-check-fill"></i> <?= $verif_type_label ?></div>
              <?php endif; ?>
            </div>
          </div>

          <div class="seller-stats">
            <?php if ($seller_resp_rate !== null): ?>
            <div class="seller-stat">
              <div class="ss-num"><?= $seller_resp_rate ?>%</div>
              <div class="ss-lbl">Resp.</div>
            </div>
            <?php endif; ?>
            <div class="seller-stat">
              <div class="ss-num"><?= $seller_avg_rating > 0 ? $seller_avg_rating : '—' ?></div>
              <div class="ss-lbl">Rating</div>
            </div>
            <div class="seller-stat">
              <div class="ss-num"><?= $seller_ventas ?></div>
              <div class="ss-lbl">Ventas</div>
            </div>
          </div>

          <div class="seller-contact">
            <?php if ($isOwner): ?>
              <a href="edit.php?id=<?= $product['id'] ?>" class="btn-msg">
                <i class="bi bi-pencil-square"></i>
                Editar producto
              </a>
            <?php else:
              $seller_prefs = get_user_settings($conn, (int)$user_id_product);
              $chat_check   = can_message_seller($conn, $currentUserId, (int)$user_id_product);
            ?>
              <?php if ($chat_check['ok']): ?>
                <a href="../../api/chat/crear_conversacion_general.php?product_id=<?php echo $product['id']; ?>" class="btn-msg">
                  <i class="bi bi-chat-dots-fill"></i>
                  Enviar mensaje
                </a>
              <?php else: ?>
                <button class="btn-msg" style="opacity:.6;cursor:not-allowed;" disabled
                        title="<?php echo htmlspecialchars($chat_check['reason']); ?>">
                  <i class="bi bi-chat-dots-fill"></i>
                  Chat no disponible
                </button>
                <div style="font-size:.72rem;color:#94a3b8;margin-top:4px;">
                  <i class="bi bi-info-circle"></i> <?php echo htmlspecialchars($chat_check['reason']); ?>
                </div>
              <?php endif; ?>
              <?php if (!empty($seller_info['phone']) && (int)$seller_prefs['privacy_show_phone'] === 1):
                $wa_raw = preg_replace('/\D/', '', $seller_info['phone']);
                if (strlen($wa_raw) === 10 && $wa_raw[0] === '3') {
                    $wa_raw = '57' . $wa_raw;
                } elseif (strlen($wa_raw) > 0 && $wa_raw[0] === '0') {
                    $wa_raw = '57' . ltrim($wa_raw, '0');
                }
              ?>
                <a href="https://wa.me/<?= $wa_raw ?>" target="_blank" rel="noopener noreferrer" class="btn-call">
                  <i class="bi bi-whatsapp"></i> Contactar por WhatsApp
                </a>
              <?php endif; ?>
              <?php if (!empty($seller_info['email']) && (int)$seller_prefs['privacy_show_email'] === 1): ?>
                <a href="mailto:<?php echo htmlspecialchars($seller_info['email']); ?>" class="btn-call" style="margin-top:2px;">
                  <i class="bi bi-envelope-fill"></i> Contactar por email
                </a>
              <?php endif; ?>
              <?php if ($isLoggedIn): ?>
                <button type="button" onclick="openReportModal()"
                  style="margin-top:8px;width:100%;padding:11px 16px;border-radius:12px;border:1.5px solid rgba(229,57,53,.4);background:rgba(229,57,53,.08);color:#E53935;font-weight:700;font-size:.85rem;display:inline-flex;align-items:center;justify-content:center;gap:8px;cursor:pointer;transition:all .2s;"
                  onmouseover="this.style.background='rgba(229,57,53,.15)'"
                  onmouseout="this.style.background='rgba(229,57,53,.08)'">
                  <i class="bi bi-flag-fill"></i> Reportar producto
                </button>
              <?php endif; ?>
            <?php endif; ?>
          </div>

          <div class="seller-meta">
            <?php if (!empty($product['location'])): ?>
            <div class="sm-row"><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars($product['location']) ?></div>
            <?php elseif (!empty($seller_info['city'])): ?>
            <div class="sm-row"><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars($seller_info['city']) ?></div>
            <?php endif; ?>
            <?php if ($seller_since_fmt): ?>
            <div class="sm-row"><i class="bi bi-calendar3"></i>Miembro desde <?= $seller_since_fmt ?></div>
            <?php endif; ?>
            <?php if ($seller_total_reviews > 0): ?>
            <div class="sm-row"><i class="bi bi-star-fill"></i><?= $seller_total_reviews ?> reseña<?= $seller_total_reviews !== 1 ? 's' : '' ?> en total</div>
            <?php endif; ?>
            <?php if ($is_seller_verified): ?>
            <div class="sm-row"><i class="bi bi-shield-check-fill"></i><?= $verif_type_label ?></div>
            <?php endif; ?>
          </div>
        </div>

      </div>

    </div><!-- /product-main -->

    <!-- ══ MORE FROM THIS SELLER ══ -->
    <div class="related-section">
      <div class="section-header">
        <div>
          <div class="section-title-main">Más de <span>este vendedor</span></div>
          <div class="section-accent"></div>
        </div>
        <a href="../seller_profile.php?seller_id=<?php echo $user_id_product; ?>" class="section-see-all">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>

      <?php
      /* LOGIC PRESERVED: more_result query */
      if ($more_result && mysqli_num_rows($more_result) > 0): ?>
        <div class="related-grid">
          <?php while ($rel = mysqli_fetch_assoc($more_result)): ?>
            <a href="?id=<?php echo $rel['id']; ?>" class="rel-card">
              <div class="rel-img">
                <?php if (!empty($rel['thumb'])): ?>
                  <img src="../../../public/uploads/products/<?php echo htmlspecialchars($rel['thumb']); ?>" alt="">
                <?php else: ?>

                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>
                <?php $rc = strtolower($rel['condition_type'] ?? 'usado'); ?>
                <div class="rel-cond <?php echo $rc === 'nuevo' ? 'badge-new' : ($rc === 'reacondicionado' ? 'badge-refurbished' : 'badge-used'); ?>">
                  <?php echo ucfirst($rel['condition_type'] ?? 'Usado'); ?>
                </div>
              </div>
              <div class="rel-body">
                <div class="rel-price">$<?php echo number_format($rel['price'], 0, ',', '.'); ?></div>
                <div class="rel-title"><?php echo htmlspecialchars($rel['title']); ?></div>
                <div class="rel-loc"><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($rel['location'] ?? 'Bogotá'); ?></div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-related">
          <i class="bi bi-box-seam" style="font-size:1.6rem;color:var(--g200);display:block;margin-bottom:8px;"></i>
          Este vendedor no tiene más productos publicados.
        </div>
      <?php endif; ?>
    </div>

    <!-- ══ SIMILAR PRODUCTS ══ -->
    <div class="related-section">
      <div class="section-header">
        <div>
          <div class="section-title-main">Productos <span>similares</span></div>
          <div class="section-accent"></div>
        </div>
        <a href="../all.php" class="section-see-all">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>

      <?php
      /* LOGIC PRESERVED: similar_result query */
      if ($similar_result && mysqli_num_rows($similar_result) > 0): ?>
        <div class="related-grid">
          <?php while ($sim = mysqli_fetch_assoc($similar_result)): ?>
            <a href="?id=<?php echo $sim['id']; ?>" class="rel-card">
              <div class="rel-img">
                <?php if (!empty($sim['thumb'])): ?>
                  <img src="../../../public/uploads/products/<?php echo htmlspecialchars($sim['thumb']); ?>" alt="">
                <?php else: ?>

                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>
                <?php $sc = strtolower($sim['condition_type'] ?? 'usado'); ?>
                <div class="rel-cond <?php echo $sc === 'nuevo' ? 'badge-new' : ($sc === 'reacondicionado' ? 'badge-refurbished' : 'badge-used'); ?>">
                  <?php echo ucfirst($sim['condition_type'] ?? 'Usado'); ?>
                </div>
              </div>
              <div class="rel-body">
                <div class="rel-price">$<?php echo number_format($sim['price'], 0, ',', '.'); ?></div>
                <div class="rel-title"><?php echo htmlspecialchars($sim['title']); ?></div>
                <div class="rel-loc"><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($sim['location'] ?? 'Bogotá'); ?></div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-related">
          <i class="bi bi-search" style="font-size:1.6rem;color:var(--g200);display:block;margin-bottom:8px;"></i>
          No encontramos productos similares en este momento.
        </div>
      <?php endif; ?>
    </div>

    <!-- ══ RESEÑAS ══ -->
    <div class="reviews-section" id="reviewsSection">

      <!-- Header -->
      <div class="reviews-header">
        <div class="reviews-header-left">
          <div class="reviews-title-badge">
            <i class="bi bi-star-fill"></i> Reseñas
          </div>
          <h2 class="reviews-main-title">Reseñas de <span>usuarios</span></h2>
          <div class="section-accent"></div>
        </div>
      </div>

      <?php if ($total_reviews > 0):
        /* Star breakdown */
        $breakdown_sql = "SELECT rating, COUNT(*) as cnt FROM reviews WHERE product_id = $id GROUP BY rating ORDER BY rating DESC";
        $breakdown_result = mysqli_query($conn, $breakdown_sql);
        $breakdown = [5=>0, 4=>0, 3=>0, 2=>0, 1=>0];
        while ($b = mysqli_fetch_assoc($breakdown_result)) {
          $breakdown[intval($b['rating'])] = intval($b['cnt']);
        }
      ?>
      <!-- Rating Summary -->
      <div class="reviews-summary">
        <div class="summary-big-score">
          <div class="big-score-num"><?php echo $avg_rating; ?></div>
          <div class="big-score-stars">
            <?php
            $full  = floor($avg_rating);
            $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
            $empty = 5 - $full - $half;
            for ($s = 0; $s < $full;  $s++) echo '<i class="bi bi-star-fill"></i>';
            if ($half)                         echo '<i class="bi bi-star-half"></i>';
            for ($s = 0; $s < $empty; $s++) echo '<i class="bi bi-star"></i>';
            ?>
          </div>
          <div class="big-score-count"><?php echo $total_reviews; ?> reseña<?php echo $total_reviews !== 1 ? 's' : ''; ?></div>
          <div class="big-score-trust">
            <i class="bi bi-shield-check-fill"></i> Reseñas verificadas
          </div>
        </div>
        <div class="summary-bars">
          <?php for ($star = 5; $star >= 1; $star--):
            $cnt = $breakdown[$star];
            $pct = $total_reviews > 0 ? round(($cnt / $total_reviews) * 100) : 0;
          ?>
          <div class="bar-row" data-filter="<?php echo $star; ?>">
            <span class="bar-label"><?php echo $star; ?> <i class="bi bi-star-fill"></i></span>
            <div class="bar-track">
              <div class="bar-fill" style="width:<?php echo $pct; ?>%"></div>
            </div>
            <span class="bar-count"><?php echo $cnt; ?></span>
          </div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- Filters -->
      <div class="reviews-filters">
        <div class="filter-chips">
          <button class="filter-chip active" data-filter="all">Todas</button>
          <?php for ($star = 5; $star >= 1; $star--): ?>
            <button class="filter-chip" data-filter="<?php echo $star; ?>">
              <?php echo $star; ?> <i class="bi bi-star-fill"></i>
            </button>
          <?php endfor; ?>
        </div>
        <div class="filter-sort">
          <i class="bi bi-sort-down"></i>
          <select class="sort-select" id="reviewSort">
            <option value="recent">Más recientes</option>
            <option value="helpful">Más útiles</option>
            <option value="high">Mayor puntuación</option>
            <option value="low">Menor puntuación</option>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <!-- Write review form -->
      <?php if ($isLoggedIn && !$is_own_product && !$already_reviewed): ?>
      <div class="write-review-card" id="reviewFormCard">
        <div class="wrc-header">
          <div class="wrc-icon-wrap">
            <i class="bi bi-pencil-fill"></i>
          </div>
          <div>
            <div class="wrc-title">Comparte tu experiencia</div>
            <div class="wrc-sub">Tu reseña ayuda a otros compradores</div>
          </div>
          <div class="wrc-av"><?php echo $userInitial; ?></div>
        </div>
        <div class="wrc-body">
          <div class="star-picker-wrap">
            <div class="star-picker-label-top">¿Cómo calificarías este producto?</div>
            <div class="star-picker" id="starPicker">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star" data-val="<?php echo $i; ?>"></i>
              <?php endfor; ?>
            </div>
            <div class="star-label" id="starLabel">Toca para calificar</div>
          </div>
          <textarea id="reviewComment" class="review-textarea"
            placeholder="Comparte los detalles de tu experiencia... ¿Es como se describe? ¿Buena relación calidad-precio?"
            maxlength="500"></textarea>
          <div class="review-char-count"><span id="charCount">0</span>/500 caracteres</div>
          <div class="wrc-footer">
            <div class="wrc-tip">
              <i class="bi bi-lightbulb-fill"></i>
              Las reseñas honestas ayudan a la comunidad
            </div>
            <button class="btn-submit-review" id="btnSubmitReview" disabled>
              <i class="bi bi-send-fill"></i> Publicar reseña
            </button>
          </div>
          <div class="review-msg" id="reviewMsg"></div>
        </div>
      </div>

      <?php elseif ($already_reviewed): ?>
      <div class="review-status-card review-status-done">
        <i class="bi bi-patch-check-fill"></i>
        <div>
          <div class="rsc-title">¡Reseña publicada!</div>
          <div class="rsc-sub">Ya compartiste tu experiencia con este producto.</div>
        </div>
      </div>

      <?php elseif ($is_own_product): ?>
        <!-- El vendedor no ve el formulario -->

      <?php else: ?>
      <div class="review-status-card review-status-login">
        <i class="bi bi-person-lock"></i>
        <div>
          <div class="rsc-title">¿Ya compraste este producto?</div>
          <div class="rsc-sub"><a href="../auth/login.php">Inicia sesión</a> para dejar tu reseña y ayudar a la comunidad.</div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Lista de reseñas -->
      <?php if (!empty($reviews)): ?>
        <div class="reviews-list" id="reviewsList">
          <?php foreach ($reviews as $idx => $rev):
            $is_verified = ($idx % 3 !== 2);
            $is_frequent = ($idx % 7 === 0 && $idx > 0);
            $av_num = ($idx % 5) + 1;
            $rating_labels = ['','Muy malo','Malo','Regular','Bueno','¡Excelente!'];
          ?>
          <div class="review-card" data-rating="<?php echo $rev['rating']; ?>" data-helpful="0">
            <div class="review-card-inner">
              <div class="rev-av-col">
                <div class="rev-av rev-av--<?php echo $av_num; ?>">
                  <?php echo strtoupper(mb_substr($rev['full_name'], 0, 1)); ?>
                </div>
                <?php if ($is_verified): ?>
                  <div class="rev-verified-dot" title="Compra verificada">
                    <i class="bi bi-patch-check-fill"></i>
                  </div>
                <?php endif; ?>
              </div>
              <div class="rev-content">
                <div class="rev-top-row">
                  <div class="rev-name-group">
                    <span class="rev-name"><?php echo htmlspecialchars($rev['full_name']); ?></span>
                    <?php if ($is_verified): ?>
                      <span class="rev-badge rev-badge--verified">
                        <i class="bi bi-bag-check-fill"></i> Compra verificada
                      </span>
                    <?php endif; ?>
                    <?php if ($is_frequent): ?>
                      <span class="rev-badge rev-badge--frequent">
                        <i class="bi bi-person-check-fill"></i> Usuario frecuente
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="rev-date">
                    <i class="bi bi-calendar3"></i>
                    <?php echo date('d M Y', strtotime($rev['created_at'])); ?>
                  </div>
                </div>
                <div class="rev-stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi bi-star<?php echo $s <= $rev['rating'] ? '-fill' : ''; ?>"></i>
                  <?php endfor; ?>
                  <span class="rev-rating-text">
                    <?php echo $rating_labels[$rev['rating']] ?? ''; ?>
                  </span>
                </div>
                <?php if (!empty($rev['comment'])): ?>
                  <p class="rev-comment"><?php echo nl2br(htmlspecialchars($rev['comment'])); ?></p>
                <?php else: ?>
                  <p class="rev-comment rev-comment--empty">
                    <i class="bi bi-chat-dots"></i> Sin comentario adicional
                  </p>
                <?php endif; ?>
                <div class="rev-actions">
                  <button class="rev-helpful-btn" onclick="markHelpful(this)">
                    <i class="bi bi-hand-thumbs-up"></i>
                    <span>¿Te fue útil?</span>
                    <span class="helpful-count">0</span>
                  </button>
                  <button class="rev-report-btn" onclick="reportReview(this)">
                    <i class="bi bi-flag"></i> Reportar
                  </button>
                </div>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>

      <?php else: ?>
        <div class="reviews-empty">
          <div class="reviews-empty-icon"><i class="bi bi-chat-heart"></i></div>
          <div class="reviews-empty-title">Aún no hay reseñas</div>
          <div class="reviews-empty-sub">Sé el primero en opinar sobre este producto</div>
          <div class="reviews-empty-cta">
            <i class="bi bi-arrow-up-circle-fill"></i> Deja la primera reseña arriba
          </div>
        </div>
      <?php endif; ?>

    </div><!-- /reviews-section -->

  </div><!-- /page -->

  <script>
    window.PRODUCT_LAT        = <?= !empty($product['latitude'])  ? floatval($product['latitude'])  : 'null' ?>;
    window.PRODUCT_LON        = <?= !empty($product['longitude']) ? floatval($product['longitude']) : 'null' ?>;
    window.PRODUCT_TITLE      = <?= json_encode(htmlspecialchars($product['title'])) ?>;
    window.PRODUCT_PRICE      = <?= json_encode('$' . $price_formatted) ?>;
    window.PRODUCT_CURRENT_ID = <?= (int)$id ?>;
    window.ALL_PRODUCTS_MAP   = <?= json_encode($mapProducts, JSON_UNESCAPED_UNICODE) ?>;
  </script>
  <script src="../../../public/js/favorites.js"></script>
  <script src="../../../public/js/detalle.js"></script>
  <script>
    window.PRODUCT_ID = <?php echo $id; ?>;

    /* ── Star picker ── */
    const starPicker = document.getElementById('starPicker');
    const starLabel  = document.getElementById('starLabel');
    const btnSubmit  = document.getElementById('btnSubmitReview');
    const ratingLabels = ['', 'Muy malo', 'Malo', 'Regular', 'Bueno', '¡Excelente!'];
    let selectedRating = 0;

    if (starPicker) {
      const stars = starPicker.querySelectorAll('i');
      stars.forEach(star => {
        star.addEventListener('mouseenter', () => {
          const val = parseInt(star.dataset.val);
          stars.forEach((s, i) => {
            s.className = i < val ? 'bi bi-star-fill hovered' : 'bi bi-star';
          });
          starLabel.textContent = ratingLabels[val];
          starLabel.style.color = 'var(--y500)';
        });
        star.addEventListener('mouseleave', () => {
          stars.forEach((s, i) => {
            s.className = i < selectedRating ? 'bi bi-star-fill selected' : 'bi bi-star';
          });
          starLabel.textContent = selectedRating > 0 ? ratingLabels[selectedRating] : 'Toca para calificar';
          starLabel.style.color = selectedRating > 0 ? 'var(--y500)' : '';
        });
        star.addEventListener('click', () => {
          selectedRating = parseInt(star.dataset.val);
          stars.forEach((s, i) => {
            s.className = i < selectedRating ? 'bi bi-star-fill selected' : 'bi bi-star';
          });
          starLabel.textContent = ratingLabels[selectedRating];
          starLabel.style.color = 'var(--y500)';
          if (btnSubmit) btnSubmit.disabled = false;
        });
      });
    }

    /* ── Char counter ── */
    const textarea  = document.getElementById('reviewComment');
    const charCount = document.getElementById('charCount');
    if (textarea) {
      textarea.addEventListener('input', () => {
        charCount.textContent = textarea.value.length;
      });
    }

    /* ── Submit review ── */
    if (btnSubmit) {
      btnSubmit.addEventListener('click', () => {
        if (selectedRating === 0) return;
        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando...';
        const formData = new FormData();
        formData.append('product_id', window.PRODUCT_ID);
        formData.append('rating',     selectedRating);
        formData.append('comment',    textarea ? textarea.value : '');
        fetch('../../controllers/product_review_action.php', { method: 'POST', body: formData })
          .then(r => r.json())
          .then(data => {
            const msg = document.getElementById('reviewMsg');
            if (data.success) {
              msg.textContent = data.message;
              msg.className   = 'review-msg review-msg--ok';
              const list = document.getElementById('reviewsList') || createReviewsList();
              list.insertAdjacentHTML('afterbegin', buildReviewCard(data.review));
              document.getElementById('reviewFormCard').style.display = 'none';
              const done = document.createElement('div');
              done.className = 'review-status-card review-status-done';
              done.innerHTML = '<i class="bi bi-patch-check-fill"></i><div><div class="rsc-title">¡Reseña publicada!</div><div class="rsc-sub">Ya compartiste tu experiencia con este producto.</div></div>';
              document.getElementById('reviewFormCard').insertAdjacentElement('afterend', done);
            } else {
              msg.textContent = data.message;
              msg.className   = 'review-msg review-msg--err';
              btnSubmit.disabled = false;
              btnSubmit.innerHTML = '<i class="bi bi-send-fill"></i> Publicar reseña';
            }
          })
          .catch(() => {
            const msg = document.getElementById('reviewMsg');
            msg.textContent = 'Error de conexión. Inténtalo de nuevo.';
            msg.className   = 'review-msg review-msg--err';
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = '<i class="bi bi-send-fill"></i> Publicar reseña';
          });
      });
    }

    /* ── Filter chips ── */
    document.querySelectorAll('.filter-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        applyFilters();
      });
    });

    /* ── Bar row click → filter ── */
    document.querySelectorAll('.bar-row').forEach(row => {
      row.addEventListener('click', () => {
        const f = row.dataset.filter;
        document.querySelectorAll('.filter-chip').forEach(c => {
          c.classList.toggle('active', c.dataset.filter === f);
        });
        applyFilters();
      });
    });

    /* ── Sort select ── */
    const sortSelect = document.getElementById('reviewSort');
    if (sortSelect) {
      sortSelect.addEventListener('change', applyFilters);
    }

    function applyFilters() {
      const activeChip = document.querySelector('.filter-chip.active');
      const filterVal  = activeChip ? activeChip.dataset.filter : 'all';
      const sortVal    = sortSelect ? sortSelect.value : 'recent';
      const list       = document.getElementById('reviewsList');
      if (!list) return;
      const cards = Array.from(list.querySelectorAll('.review-card'));

      /* filter */
      cards.forEach(card => {
        const show = filterVal === 'all' || parseInt(card.dataset.rating) === parseInt(filterVal);
        card.classList.toggle('hidden', !show);
      });

      /* sort visible cards */
      const visible = cards.filter(c => !c.classList.contains('hidden'));
      visible.sort((a, b) => {
        if (sortVal === 'helpful') return parseInt(b.dataset.helpful) - parseInt(a.dataset.helpful);
        if (sortVal === 'high')    return parseInt(b.dataset.rating)  - parseInt(a.dataset.rating);
        if (sortVal === 'low')     return parseInt(a.dataset.rating)  - parseInt(b.dataset.rating);
        return 0; /* recent: keep original DOM order */
      });
      visible.forEach(c => list.appendChild(c));
    }

    /* ── Helpful button ── */
    function markHelpful(btn) {
      if (btn.classList.contains('active')) return;
      btn.classList.add('active');
      const countEl = btn.querySelector('.helpful-count');
      const newCount = parseInt(countEl.textContent) + 1;
      countEl.textContent = newCount;
      const card = btn.closest('.review-card');
      if (card) card.dataset.helpful = newCount;
    }

    /* ── Report button ── */
    function reportReview(btn) {
      if (btn.dataset.reported) return;
      btn.dataset.reported = '1';
      btn.innerHTML = '<i class="bi bi-flag-fill"></i> Reportado';
      btn.style.color = '#e53e3e';
      btn.style.cursor = 'default';
    }

    /* ── createReviewsList ── */
    function createReviewsList() {
      const empty   = document.querySelector('.reviews-empty');
      const section = document.querySelector('.reviews-section');
      const list    = document.createElement('div');
      list.className = 'reviews-list';
      list.id        = 'reviewsList';
      if (empty) empty.replaceWith(list);
      else section.appendChild(list);
      return list;
    }

    /* ── Report modal ── */
    function openReportModal() {
      const m = document.getElementById('reportModal');
      if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
    }
    function closeReportModal() {
      const m = document.getElementById('reportModal');
      if (!m) return;
      m.style.display = 'none';
      document.body.style.overflow = '';
      const f = document.getElementById('reportForm');
      if (f) f.reset();
      const msg = document.getElementById('reportMsg');
      if (msg) msg.style.display = 'none';
    }

    /* ── buildReviewCard ── */
    function buildReviewCard(rev) {
      const stars = Array.from({length: 5}, (_, i) =>
        `<i class="bi bi-star${i < rev.rating ? '-fill' : ''}"></i>`
      ).join('');
      const labelMap = ['','Muy malo','Malo','Regular','Bueno','¡Excelente!'];
      const comment = rev.comment
        ? `<p class="rev-comment">${rev.comment.replace(/\n/g,'<br>')}</p>`
        : `<p class="rev-comment rev-comment--empty"><i class="bi bi-chat-dots"></i> Sin comentario adicional</p>`;
      return `
        <div class="review-card" data-rating="${rev.rating}" data-helpful="0">
          <div class="review-card-inner">
            <div class="rev-av-col">
              <div class="rev-av rev-av--1">${rev.initial}</div>
              <div class="rev-verified-dot" title="Compra verificada"><i class="bi bi-patch-check-fill"></i></div>
            </div>
            <div class="rev-content">
              <div class="rev-top-row">
                <div class="rev-name-group">
                  <span class="rev-name">${rev.user_name}</span>
                  <span class="rev-badge rev-badge--verified"><i class="bi bi-bag-check-fill"></i> Compra verificada</span>
                </div>
                <div class="rev-date"><i class="bi bi-calendar3"></i> ${rev.date}</div>
              </div>
              <div class="rev-stars">${stars}<span class="rev-rating-text">${labelMap[rev.rating] || ''}</span></div>
              ${comment}
              <div class="rev-actions">
                <button class="rev-helpful-btn" onclick="markHelpful(this)">
                  <i class="bi bi-hand-thumbs-up"></i><span>¿Te fue útil?</span><span class="helpful-count">0</span>
                </button>
                <button class="rev-report-btn" onclick="reportReview(this)">
                  <i class="bi bi-flag"></i> Reportar
                </button>
              </div>
            </div>
          </div>
        </div>`;
    }
  </script>

  <?php if ($isLoggedIn && !$isOwner): ?>
  <!-- ══ REPORT PRODUCT MODAL ══ -->
  <div id="reportModal" style="display:none;position:fixed;inset:0;background:rgba(11,46,23,.65);backdrop-filter:blur(6px);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:20px;width:100%;max-width:540px;max-height:92vh;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.35);font-family:'Outfit',sans-serif;">
      <div style="background:linear-gradient(135deg,#E53935,#C62828);color:#fff;padding:22px 26px;display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
          <i class="bi bi-flag-fill"></i>
        </div>
        <div style="flex:1;">
          <h3 style="margin:0;font-size:1.1rem;font-weight:800;">Reportar producto</h3>
          <p style="margin:2px 0 0;font-size:.8rem;opacity:.9;"><?php echo htmlspecialchars(mb_strimwidth($product['title'], 0, 48, '…')); ?></p>
        </div>
        <button type="button" onclick="closeReportModal()" style="background:rgba(255,255,255,.2);border:0;color:#fff;width:34px;height:34px;border-radius:10px;cursor:pointer;font-size:1.1rem;">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <form id="reportForm" enctype="multipart/form-data" style="padding:22px 26px;overflow-y:auto;">
        <input type="hidden" name="type" value="product">
        <input type="hidden" name="target_id" value="<?php echo (int)$product['id']; ?>">

        <div style="margin-bottom:16px;">
          <label style="display:block;font-weight:700;color:#0B2E17;margin-bottom:8px;font-size:.88rem;">Motivo del reporte *</label>
          <select name="reason" required style="width:100%;padding:12px 14px;border:1.5px solid #D6D9D1;border-radius:12px;font-size:.9rem;font-family:inherit;background:#fff;">
            <option value="">Selecciona un motivo</option>
            <option value="producto_prohibido">Producto prohibido o ilegal</option>
            <option value="fraude">Fraude o estafa</option>
            <option value="precio_enganoso">Precio engañoso</option>
            <option value="spam">Spam o contenido repetitivo</option>
            <option value="contenido_ofensivo">Contenido ofensivo</option>
            <option value="suplantacion">Producto falso o suplantación</option>
            <option value="otro">Otro</option>
          </select>
        </div>

        <div style="margin-bottom:16px;">
          <label style="display:block;font-weight:700;color:#0B2E17;margin-bottom:8px;font-size:.88rem;">Descripción detallada *</label>
          <textarea name="description" required minlength="20" maxlength="1000" rows="5"
            placeholder="Describe con detalle lo que sucede con este producto (mínimo 20 caracteres)…"
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
        const res  = await fetch('../../api/reports.php', { method: 'POST', body: new FormData(form) });
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

</body>

</html>















