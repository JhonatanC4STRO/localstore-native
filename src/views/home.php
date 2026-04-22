<?php
session_start();
include '../config/conexion.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName = $isLoggedIn ? explode(' ', $user['full_name'])[0] : ''; // First name only

// Hero: 3 productos premium activos para las mini-cards
$isSeller = $isLoggedIn && in_array($user['role'], ['seller', 'admin']);
$heroExclude = $isLoggedIn ? 'AND p.user_id != ?' : '';

$stHero = mysqli_prepare($conn,
  "SELECT p.id, p.title, p.price, pi.image_url
   FROM product_promotions pp
   INNER JOIN products p  ON p.id = pp.product_id AND p.status = 'disponible'
   LEFT  JOIN product_images pi
          ON pi.product_id = p.id
         AND pi.id = (SELECT MIN(p2.id) FROM product_images p2 WHERE p2.product_id = p.id)
   WHERE pp.plan_type = 'premium'
     AND pp.status   = 'active'
     AND pp.end_date  > NOW()
     $heroExclude
   ORDER BY pp.id DESC
   LIMIT 3");
$heroPremium = [];
if ($stHero) {
  if ($isLoggedIn) {
    mysqli_stmt_bind_param($stHero, 'i', $user['id']);
  }
  mysqli_stmt_execute($stHero);
  $rHero = mysqli_stmt_get_result($stHero);
  while ($hp = mysqli_fetch_assoc($rHero)) $heroPremium[] = $hp;
}
?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ComercioLocal – Compra y vende en tu ciudad</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&family=Syne:wght@400;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/input.css">
  <link rel="stylesheet" href="../../public/css/output.css">
  <style>
    /* ── Anuncios Destacados ── */
    .featured-ads-section {
      padding: 56px 24px 48px;
      background: linear-gradient(180deg,#f0fdf4 0%,#fff 100%);
    }
    .featured-ads-inner { max-width: 1280px; margin: 0 auto; }
    .featured-ads-eyebrow {
      display: inline-flex; align-items: center; gap: 7px;
      background: #dcfce7; color: #15803d;
      border-radius: 20px; padding: 5px 14px;
      font-size: .78rem; font-weight: 700; letter-spacing: .06em;
      text-transform: uppercase; margin-bottom: 10px;
    }
    .featured-ads-title {
      font-family: 'Syne', sans-serif;
      font-size: clamp(1.5rem, 3vw, 2.2rem);
      font-weight: 800; color: #0f172a; margin: 0 0 4px;
    }
    .featured-ads-title span { color: #16a34a; }
    .featured-ads-sub { color: #64748b; font-size: .9rem; margin: 0 0 32px; }
    .featured-ads-header {
      display: flex; justify-content: space-between; align-items: flex-end;
      margin-bottom: 28px; flex-wrap: wrap; gap: 12px;
    }
    .featured-see-all {
      color: #16a34a; font-size: .875rem; font-weight: 700;
      text-decoration: none; display: flex; align-items: center; gap: 5px;
    }
    .featured-see-all:hover { text-decoration: underline; }
    /* Grid */
    .featured-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
      gap: 22px;
    }
    /* Card */
    .featured-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,.07);
      border: 1.5px solid rgba(0,0,0,.06);
      transition: transform .22s ease, box-shadow .22s ease;
      text-decoration: none; color: inherit; display: block;
      position: relative;
    }
    .featured-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 14px 40px rgba(0,0,0,.12);
    }
    /* Image */
    .fc-img {
      width: 100%; height: 190px; object-fit: cover;
      display: block; background: #f1f5f9;
      display: flex; align-items: center; justify-content: center;
      font-size: 2.5rem; position: relative; overflow: hidden;
    }
    .fc-img img { width: 100%; height: 100%; object-fit: cover; display: block; }
    /* Plan badges on card */
    .fc-plan-badge {
      position: absolute; top: 10px; left: 10px; z-index: 2;
      padding: 4px 10px; border-radius: 20px;
      font-size: .7rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: .06em;
      display: flex; align-items: center; gap: 5px;
      backdrop-filter: blur(4px);
    }
    .fc-badge-premium {
      background: rgba(18,18,18,.82);
      color: #d4a017;
      border: 1px solid rgba(212,160,23,.4);
    }
    .fc-badge-recommended {
      background: rgba(22,163,74,.9);
      color: #fff;
    }
    .fc-badge-basic {
      background: rgba(30,41,59,.78);
      color: #e2e8f0;
    }
    /* Body */
    .fc-body { padding: 14px 16px 16px; }
    .fc-price {
      font-size: 1.15rem; font-weight: 900; color: #16a34a; margin-bottom: 4px;
    }
    .fc-title {
      font-size: .88rem; font-weight: 600; color: #1e293b;
      line-height: 1.35; margin-bottom: 8px;
      display: -webkit-box; -webkit-line-clamp: 2;
      -webkit-box-orient: vertical; overflow: hidden;
    }
    .fc-meta {
      display: flex; justify-content: space-between; align-items: center;
      font-size: .76rem; color: #64748b; margin-bottom: 10px;
    }
    .fc-meta span { display: flex; align-items: center; gap: 4px; }
    .fc-seller {
      display: flex; align-items: center; gap: 8px;
      padding-top: 10px; border-top: 1px solid #f1f5f9;
    }
    .fc-seller-av {
      width: 28px; height: 28px; border-radius: 50%;
      background: #dcfce7; color: #16a34a;
      display: flex; align-items: center; justify-content: center;
      font-size: .72rem; font-weight: 700; flex-shrink: 0;
    }
    .fc-seller-name { font-size: .78rem; font-weight: 600; color: #334155; }
    /* Premium glow ring */
    .featured-card.is-premium {
      border-color: rgba(212,160,23,.45);
      box-shadow: 0 4px 20px rgba(212,160,23,.1);
    }
    .featured-card.is-premium:hover {
      box-shadow: 0 14px 40px rgba(212,160,23,.25);
    }
    @media (max-width:600px) {
      .featured-grid { grid-template-columns: 1fr 1fr; gap: 12px; }
      .fc-img { height: 140px; }
    }
    /* ── Hero search shake ── */
    @keyframes shake {
      0%,100% { transform: translateX(0); }
      20%,60%  { transform: translateX(-6px); }
      40%,80%  { transform: translateX(6px); }
    }
    .shake { animation: shake .4s ease; }
    /* ── Promo pill (grid general – PHP render + AJAX) ── */
    .promo-pill-wrap { position: relative; }
    .promo-pill {
      position: absolute; top: 8px; left: 8px; z-index: 3;
      padding: 3px 9px; border-radius: 20px;
      font-size: .67rem; font-weight: 800;
      text-transform: uppercase; letter-spacing: .06em;
      display: flex; align-items: center; gap: 4px;
      backdrop-filter: blur(3px);
    }
    .promo-badge-premium     { background: rgba(18,18,18,.82); color: #d4a017; border: 1px solid rgba(212,160,23,.4); }
    .promo-badge-recommended { background: rgba(22,163,74,.9);  color: #fff; }
    .promo-badge-basic       { background: rgba(30,41,59,.78);  color: #e2e8f0; }

    /* ================================================================
       RESPONSIVE — tablet y móvil
       ================================================================ */
    @media (max-width: 1024px) {
      .hero { padding: 60px 24px 48px; gap: 32px; }
      .hero-illustration { transform: scale(.9); }
      .featured-ads-section { padding: 40px 20px; }
      .content-section { padding: 0 20px; }
      .how-section, .cta-banner, .sponsored-section { padding: 40px 20px; }
    }

    @media (max-width: 768px) {
      /* Hero */
      .hero {
        flex-direction: column;
        padding: 40px 20px 32px;
        gap: 24px;
        min-height: auto;
        text-align: center;
      }
      .hero-content { width: 100%; }
      .hero h1 { font-size: clamp(1.6rem, 6vw, 2.2rem); margin-bottom: 12px; }
      .hero p { font-size: .95rem; margin-bottom: 20px; max-width: 100%; }
      .hero-badge { margin-bottom: 14px; }
      .hero-search {
        flex-direction: column;
        border-radius: 20px;
        padding: 10px;
        gap: 8px;
        max-width: 100%;
      }
      .hero-search input,
      .hero-search select {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
      }
      .hero-search .sep { display: none; }
      .hero-search .btn-hero-search {
        width: 100%;
        padding: 12px;
        border-radius: 12px;
        justify-content: center;
      }
      .hero-stats { flex-wrap: wrap; justify-content: center; gap: 20px; }
      .hero-illustration { display: none; }

      /* Categorías */
      .categories-section { padding: 32px 20px; }

      /* Featured grid → 2 columnas */
      .featured-grid { grid-template-columns: 1fr 1fr !important; gap: 14px !important; }
      .fc-img { height: 150px; }
      .featured-ads-header { flex-direction: column; gap: 14px; align-items: flex-start; }

      /* Productos cerca/recomendados */
      .product-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 14px; }
      .section-header { flex-direction: column; align-items: flex-start; gap: 12px; }

      /* Ocultar sidebar de filtros en móvil, contenido ocupa 100% */
      .main-layout { display: block !important; padding: 0 16px; }
      .sidebar { display: none !important; }
      .content-area { width: 100% !important; max-width: 100% !important; }

      /* Cómo funciona / CTA */
      .how-header h2, .cta-banner h2 { font-size: 1.6rem !important; }
      .how-section, .cta-banner, .sponsored-section { padding: 32px 16px; }

      /* Secciones internas */
      .nearby-section, .content-section { padding: 0 16px; margin-bottom: 32px; }
    }

    @media (max-width: 480px) {
      .hero { padding: 28px 16px 24px; }
      .hero h1 { font-size: 1.5rem; }
      .hero p { font-size: .88rem; }
      .hero-badge { font-size: .72rem; padding: 4px 11px; }

      .featured-grid { grid-template-columns: 1fr !important; }
      .fc-img { height: 200px; }

      .product-grid { grid-template-columns: 1fr 1fr; gap: 10px; }

      .categories-section { padding: 24px 14px; }
      .featured-ads-section { padding: 28px 14px; }
    }
  </style>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />


</head>



<body>

  <!-- ======= NAVBAR ======= -->
  <?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

  <!-- ======= HERO ======= -->
  <section class="hero">
    <div class="hero-dots"></div>
    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-lightning-charge-fill"></i>
        +48,000 anuncios activos hoy
      </div>



      <?php if ($isLoggedIn): ?>
        <h1>¡Bienvenido,<br><em><?php echo htmlspecialchars($userName); ?>!</em></h1>

        <p>Qué bueno tenerte de vuelta. Explora los mejores productos de tu ciudad o publica algo nuevo hoy.</p>
      <?php else: ?>
        <h1>ComercioLocal<br><em>Compra y vende</em><br>en tu ciudad</h1>
        <p>La plataforma que conecta vecinos, emprendedores y compradores en tu comunidad. Rápido, seguro y local.</p>
      <?php endif; ?>

      <div class="hero-search">
        <input type="text" id="heroSearchInput" placeholder="¿Qué estás buscando hoy?">
        <div class="sep"></div>
        <select id="heroSearchCity">
          <option value="">Toda Colombia</option>
          <option value="Bogotá">Bogotá</option>
          <option value="Medellín">Medellín</option>
          <option value="Cali">Cali</option>
          <option value="Barranquilla">Barranquilla</option>
        </select>
        <button class="btn-hero-search" id="heroSearchBtn">
          <i class="bi bi-search"></i> Buscar
        </button>
      </div>

      <div class="hero-stats">
        <div class="hero-stat">
          <div class="num">48K+</div>
          <div class="lbl">Anuncios activos</div>
        </div>
        <div class="hero-stat">
          <div class="num">120K</div>
          <div class="lbl">Usuarios registrados</div>
        </div>
        <div class="hero-stat">
          <div class="num">32</div>
          <div class="lbl">Ciudades</div>
        </div>
      </div>
    </div>

    <div class="hero-illustration">
      <?php if (!empty($heroPremium)):
        foreach ($heroPremium as $i => $hp):
          $hp_price = '$' . number_format($hp['price'], 0, ',', '.');
          $hp_title = htmlspecialchars($hp['title']);
          $hp_img   = !empty($hp['image_url'])
            ? '../../public/uploads/products/' . htmlspecialchars($hp['image_url'])
            : null;
      ?>
        <a href="products/detalle.php?id=<?= (int)$hp['id'] ?>"
           class="hero-mini-card <?= $i === 0 ? 'featured' : '' ?>"
           style="text-decoration:none;color:inherit;display:block;position:relative;">
          <!-- Badge premium -->
          <div style="position:absolute;top:8px;left:8px;z-index:2;
                      background:rgba(18,18,18,.82);color:#d4a017;
                      border:1px solid rgba(212,160,23,.4);
                      border-radius:20px;padding:3px 9px;
                      font-size:.62rem;font-weight:800;letter-spacing:.06em;
                      text-transform:uppercase;display:flex;align-items:center;gap:4px;
                      backdrop-filter:blur(4px);">
            💎 Premium
          </div>
          <?php if ($hp_img): ?>
            <img class="img-hero" width="150" height="150"
                 src="<?= $hp_img ?>" alt="<?= $hp_title ?>">
          <?php else: ?>
            <div class="img-hero" style="display:flex;align-items:center;justify-content:center;
                 background:rgba(255,255,255,.08);font-size:2.5rem;">🏷</div>
          <?php endif; ?>
          <div class="hmc-info">
            <div class="hmc-price" style="<?= $i > 0 ? 'color:var(--yellow-400);' : '' ?>">
              <?= $hp_price ?>
            </div>
            <div class="hmc-title"><?= $hp_title ?></div>
          </div>
        </a>
      <?php endforeach;
      else:
        // Fallback: mostrar 3 productos recientes si no hay premium activos
        $fallbackExclude = $isLoggedIn ? 'AND p.user_id != ' . (int)$user['id'] : '';
        $stFallback = mysqli_prepare($conn,
          "SELECT p.id, p.title, p.price,
                  (SELECT pi.image_url FROM product_images pi
                   WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS image_url
           FROM products p
           WHERE p.status = 'disponible' AND p.admin_status = 'active' $fallbackExclude
           ORDER BY p.id DESC
           LIMIT 3");
        $fallbackProducts = [];
        if ($stFallback) {
          mysqli_stmt_execute($stFallback);
          $rFb = mysqli_stmt_get_result($stFallback);
          while ($fb = mysqli_fetch_assoc($rFb)) $fallbackProducts[] = $fb;
        }

        if (!empty($fallbackProducts)):
          foreach ($fallbackProducts as $i => $fb):
            $fb_price = '$' . number_format($fb['price'], 0, ',', '.');
            $fb_title = htmlspecialchars($fb['title']);
            $fb_img   = !empty($fb['image_url'])
              ? '../../public/uploads/products/' . htmlspecialchars($fb['image_url'])
              : null;
      ?>
        <a href="products/detalle.php?id=<?= (int)$fb['id'] ?>"
           class="hero-mini-card <?= $i === 0 ? 'featured' : '' ?>"
           style="text-decoration:none;color:inherit;display:block;">
          <?php if ($fb_img): ?>
            <img class="img-hero" width="150" height="150"
                 src="<?= $fb_img ?>" alt="<?= $fb_title ?>">
          <?php else: ?>
            <div class="img-hero" style="display:flex;align-items:center;justify-content:center;
                 background:rgba(255,255,255,.08);font-size:2.5rem;">🏷</div>
          <?php endif; ?>
          <div class="hmc-info">
            <div class="hmc-price" style="<?= $i > 0 ? 'color:var(--yellow-400);' : '' ?>">
              <?= $fb_price ?>
            </div>
            <div class="hmc-title"><?= $fb_title ?></div>
          </div>
        </a>
      <?php endforeach;
        else: ?>
          <div class="hero-mini-card featured">
            <div class="img-hero" style="display:flex;align-items:center;justify-content:center;
                 background:rgba(255,255,255,.08);font-size:2.5rem;">🏷</div>
            <div class="hmc-info">
              <div class="hmc-price">—</div>
              <div class="hmc-title">No hay productos aún</div>
            </div>
          </div>
        <?php endif;
      endif; ?>
    </div>
  </section>

  <!-- ======= CATEGORIES ======= -->
  <?php
  $sqlCategories = "SELECT c.id, c.name, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id";
  $resultCategories = $conn->query($sqlCategories);

  // Mapa de iconos por nombre de categoría (personalizable)
$iconMap = [
  'Vehículos' => 'bi-car-front-fill',
  'Propiedades en venta' => 'bi-house-door-fill',
  'Propiedades en alquiler' => 'bi-key-fill',
  'Electrónica' => 'bi-lightning-charge-fill',
  'Celulares y accesorios' => 'bi-phone-fill',
  'Computadores y tablets' => 'bi-laptop-fill',
  'Hogar y muebles' => 'bi-house-fill',
  'Electrodomésticos' => 'bi-plug-fill',
  'Ropa y accesorios' => 'bi-bag-heart-fill',
  'Calzado' => 'bi-bootstrap-fill',
  'Belleza y cuidado personal' => 'bi-stars',
  'Deportes y fitness' => 'bi-trophy-fill',
  'Juguetes y juegos' => 'bi-controller',
  'Mascotas' => 'bi-heart-fill',
  'Herramientas' => 'bi-tools',
  'Jardín y exterior' => 'bi bi-leaf-fill',
  'Instrumentos musicales' => 'bi-music-note-beamed',
  'Arte y coleccionables' => 'bi-palette-fill',
  'Libros y revistas' => 'bi-book-fill',
  'Videojuegos y consolas' => 'bi-controller',
  'Bicicletas' => 'bi-bicycle',
  'Motocicletas' => 'bi-bicycle', // puedes cambiar si quieres
  'Servicios' => 'bi-briefcase-fill'
];
 function getIcon($name, $map)
{
  return $map[$name] ?? 'bi-grid-fill';
}

  function formatCount($count)
  {
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return $count;
  }
  ?>

  <!-- Swiper CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

  <style>
    .categories-swiper .swiper-wrapper {
        transition-timing-function: linear !important;
    }
    .categories-swiper .swiper-button-next, 
    .categories-swiper .swiper-button-prev {
        display: none !important;
    }
  </style>

  <section class="categories-section">
    <div class="section-header">
      <h2 class="section-title">Explorar <span>categorías</span></h2>
      <a class="section-link" href="products/all.php">Ver todas <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="swiper categories-swiper select-none">
      <div class="swiper-wrapper">
        <?php if ($resultCategories && $resultCategories->num_rows > 0): ?>
          <?php $first = true;
          while ($cat = $resultCategories->fetch_assoc()): ?>
            <div class="swiper-slide">
              <div class="cat-card <?= $first ? 'active' : '' ?>" data-id="<?= (int)$cat['id'] ?>">
                <div class="cat-icon">
                  <i class="bi <?= getIcon($cat['name'], $iconMap) ?>"></i>
                </div>
                <div class="cat-label"><?= htmlspecialchars($cat['name']) ?></div>
              </div>
            </div>
          <?php $first = false;
          endwhile; ?>
        <?php else: ?>
          <div class="swiper-slide">
            <p class="no-categories">No hay categorías disponibles.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <!-- ======= ANUNCIOS DESTACADOS (datos reales de product_promotions) ======= -->
  <?php
  /*
   * Query UNION que trae máx 3 productos por plan_type con promoción activa,
   * ordenados premium → recommended → basic.
   * Usa prepared statements; solo muestra productos con status='disponible'.
   */

  // Auto-expirar primero
  mysqli_query($conn,
    "UPDATE product_promotions SET status='expired'
     WHERE status='active' AND end_date <= NOW()");

  $stFeat = mysqli_prepare($conn,
    "SELECT sub.plan_type, sub.plan_rank,
            p.id, p.title, p.price, u.city,
            u.full_name,
            pi.image_url
     FROM (
       -- Máx 3 premium activos
       (SELECT 'premium' AS plan_type, 3 AS plan_rank, product_id
       FROM product_promotions
       WHERE plan_type = 'premium'
         AND status   = 'active'
         AND end_date  > NOW()
       LIMIT 3)

       UNION ALL

       -- Máx 3 recommended activos
       (SELECT 'recommended', 2, product_id
       FROM product_promotions
       WHERE plan_type = 'recommended'
         AND status   = 'active'
         AND end_date  > NOW()
       LIMIT 3)

       UNION ALL

       -- Máx 3 basic activos
       (SELECT 'basic', 1, product_id
       FROM product_promotions
       WHERE plan_type = 'basic'
         AND status   = 'active'
         AND end_date  > NOW()
       LIMIT 3)
     ) sub
     INNER JOIN products p  ON p.id        = sub.product_id
                           AND p.status    = 'disponible'
     INNER JOIN users u     ON u.id        = p.user_id
     LEFT  JOIN product_images pi
                           ON pi.product_id = p.id
                           AND pi.id = (
                             SELECT MIN(pid2.id)
                             FROM product_images pid2
                             WHERE pid2.product_id = p.id
                           )
     " . ($isLoggedIn ? "WHERE p.user_id != ?" : "") . "
     ORDER BY sub.plan_rank DESC, p.id DESC");

  $featuredProducts = [];
  if ($stFeat) {
    if ($isLoggedIn) {
      mysqli_stmt_bind_param($stFeat, 'i', $user['id']);
    }
    mysqli_stmt_execute($stFeat);
    $rFeat = mysqli_stmt_get_result($stFeat);
    while ($rf = mysqli_fetch_assoc($rFeat)) {
      $featuredProducts[] = $rf;
    }
  }
  ?>

  <?php if (!empty($featuredProducts)): ?>
  <section class="featured-ads-section">
    <div class="featured-ads-inner">
      <div class="featured-ads-header">
        <div>
          <div class="featured-ads-eyebrow">
            <i class="bi bi-stars"></i> Anuncios promocionados
          </div>
          <h2 class="featured-ads-title">Anuncios <span>Destacados</span></h2>
          <p class="featured-ads-sub">Productos con mayor visibilidad seleccionados para ti</p>
        </div>
        <a class="featured-see-all" href="products/all.php">
          Ver todos <i class="bi bi-arrow-right"></i>
        </a>
      </div>

      <div class="featured-grid">
        <?php
        $planBadgeMap = [
          'premium'     => ['css' => 'fc-badge-premium',     'icon' => '💎', 'label' => 'Premium'],
          'recommended' => ['css' => 'fc-badge-recommended', 'icon' => '🚀', 'label' => 'Recomendado'],
          'basic'       => ['css' => 'fc-badge-basic',       'icon' => '⭐', 'label' => 'Destacado'],
        ];
        foreach ($featuredProducts as $fp):
          $fp_price    = number_format($fp['price'], 0, ',', '.');
          $fp_initials = '';
          foreach (explode(' ', trim($fp['full_name'] ?? 'U')) as $part)
            if ($part) $fp_initials .= strtoupper($part[0]);
          $fp_initials  = substr($fp_initials, 0, 2) ?: 'U';
          $fp_plan      = $fp['plan_type'];
          $fp_badge     = $planBadgeMap[$fp_plan] ?? $planBadgeMap['basic'];
          $fp_isPremium = $fp_plan === 'premium';
        ?>
          <a href="products/detalle.php?id=<?= (int)$fp['id'] ?>"
             class="featured-card <?= $fp_isPremium ? 'is-premium' : '' ?>">
            <!-- Image -->
            <div class="fc-img">
              <?php if (!empty($fp['image_url'])): ?>
                <img src="../../public/uploads/products/<?= htmlspecialchars($fp['image_url']) ?>" alt="">
              <?php else: ?>
                <i class="bi bi-box-seam" style="color:#cbd5e1"></i>
              <?php endif; ?>
              <!-- Plan badge overlay -->
              <div class="fc-plan-badge <?= $fp_badge['css'] ?>">
                <?= $fp_badge['icon'] ?> <?= $fp_badge['label'] ?>
              </div>
            </div>
            <!-- Body -->
            <div class="fc-body">
              <div class="fc-price">$<?= $fp_price ?></div>
              <div class="fc-title"><?= htmlspecialchars($fp['title']) ?></div>
              <div class="fc-meta">
                <span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($fp['city'] ?? 'Colombia') ?></span>
              </div>
              <div class="fc-seller">
                <div class="fc-seller-av"><?= $fp_initials ?></div>
                <div class="fc-seller-name">
                  <?= htmlspecialchars(explode(' ', $fp['full_name'] ?? 'Vendedor')[0]) ?>
                </div>
              </div>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; /* !empty($featuredProducts) */ ?>

  <!-- ======= MAIN LAYOUT ======= -->
  <div class="main-layout">
    <?php include __DIR__ . '/../components/filter_sidebar.php'; ?>

    <div class="content-area">
      <?php
      // Consulta para obtener los primeros 4 productos con información del usuario
      $excludeSeller = $isLoggedIn ? " AND p.user_id != " . (int)$user['id'] : '';
      $sqlProducts = "SELECT p.id, p.title, p.price, p.condition_type, p.created_at, u.city, u.full_name, p.user_id,
                      (SELECT pp.plan_type FROM product_promotions pp
                       WHERE pp.product_id = p.id AND pp.status = 'active' AND pp.end_date > NOW()
                       ORDER BY FIELD(pp.plan_type,'premium','recommended','basic') ASC LIMIT 1) AS promotion_type,
                      COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
                      (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS total_reviews,
                      (SELECT sv.id FROM seller_verifications sv WHERE sv.user_id = p.user_id AND sv.status = 'approved' LIMIT 1) AS seller_verified
                      FROM products p
                      LEFT JOIN users u ON p.user_id = u.id
                      WHERE p.status = 'disponible' AND p.admin_status = 'active'$excludeSeller
                      ORDER BY p.id DESC
                      LIMIT 4";
      $resultProducts = $conn->query($sqlProducts);

      function getInitials($name)
      {
        if (empty($name)) return 'U';
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach ($parts as $part) {
          if (!empty($part)) {
            $initials .= strtoupper($part[0]);
          }
        }
        return substr($initials, 0, 2);
      }

      function timeAgo($date)
      {
        if (empty($date)) return 'Recientemente';
        $time = strtotime($date);
        $diff = time() - $time;

        if ($diff < 60) return 'Hace unos segundos';
        if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
        if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
        return date('d/m/Y', $time);
      }
      ?>

      <div class="content-section">
        <div class="nearby-section">
          <div class="section-header">
            <div>
              <div class="nearby-badge"><i class="bi bi-geo-alt-fill"></i> Tu zona</div>
              <h2 class="section-title">Productos cerca <span>de ti</span></h2>
            </div>
            <a class="section-link" href="products/all.php">Ver más <i class="bi bi-arrow-right"></i></a>
          </div>
          <!-- /////////////////////////////// productos aleatorios -->
          <?php
          // Consulta para obtener 4 productos aleatorios (solo disponibles)
          $sqlRandomProducts = "SELECT p.id, p.title, p.price, p.condition_type, p.created_at, u.city, u.full_name, p.user_id,
                                (SELECT pp.plan_type FROM product_promotions pp
                                 WHERE pp.product_id = p.id AND pp.status = 'active' AND pp.end_date > NOW()
                                 ORDER BY FIELD(pp.plan_type,'premium','recommended','basic') ASC LIMIT 1) AS promotion_type,
                                COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
                                (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS total_reviews,
                                (SELECT sv.id FROM seller_verifications sv WHERE sv.user_id = p.user_id AND sv.status = 'approved' LIMIT 1) AS seller_verified
                                FROM products p
                                LEFT JOIN users u ON p.user_id = u.id
                                WHERE p.status = 'disponible' AND p.admin_status = 'active'$excludeSeller
                                ORDER BY RAND()
                                LIMIT 4";
          $resultRandomProducts = $conn->query($sqlRandomProducts);
          ?>
          <div class="product-grid">
            <?php if ($resultRandomProducts && $resultRandomProducts->num_rows > 0): ?>
              <?php
              // Reusable promo badge map for static grids
              $staticPlanBadge = [
                'premium'     => ['css' => 'promo-badge-premium',     'icon' => '💎', 'label' => 'Premium'],
                'recommended' => ['css' => 'promo-badge-recommended', 'icon' => '🚀', 'label' => 'Recomendado'],
                'basic'       => ['css' => 'promo-badge-basic',       'icon' => '⭐', 'label' => 'Destacado'],
              ];
              while ($randomProduct = $resultRandomProducts->fetch_assoc()):
                $rCond     = strtolower($randomProduct['condition_type']);
                $isBadge   = $rCond === 'nuevo' ? 'badge-new' : ($rCond === 'reacondicionado' ? 'badge-refurbished' : 'badge-used');
                $badgeText = ucfirst($randomProduct['condition_type']);
                $price     = number_format($randomProduct['price'], 0, ',', '.');
                $initials  = getInitials($randomProduct['full_name']);
                $timeString = timeAgo($randomProduct['created_at']);
                $location  = $randomProduct['city'] ?? 'Colombia';
                $rp_promo  = $randomProduct['promotion_type'];
                $rp_badge  = $rp_promo ? ($staticPlanBadge[$rp_promo] ?? null) : null;
                // Fetch image with prepared statement
                $img_st = mysqli_prepare($conn, "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1");
                mysqli_stmt_bind_param($img_st, 'i', $randomProduct['id']);
                mysqli_stmt_execute($img_st);
                $img_res = mysqli_stmt_get_result($img_st);
                $img_row = mysqli_fetch_assoc($img_res);
              ?>
                <a href="products/detalle.php?id=<?= (int)$randomProduct['id'] ?>" class="product-link">
                  <div class="product-card">
                    <div class="<?= $isBadge ?>"><?= $badgeText ?></div>
                    <button class="btn-fav" data-product-id="<?= (int)$randomProduct['id'] ?>"><i class="bi bi-heart"></i></button>
                    <div class="product-img-placeholder promo-pill-wrap">
                      <?php if ($rp_badge): ?>
                        <div class="promo-pill <?= $rp_badge['css'] ?>"><?= $rp_badge['icon'] ?> <?= $rp_badge['label'] ?></div>
                      <?php endif; ?>
                      <?php if ($img_row): ?>
                        <img src="../../public/uploads/products/<?= htmlspecialchars($img_row['image_url']) ?>" alt="">
                      <?php else: ?>
                        <i class="bi bi-box-seam"></i>
                      <?php endif; ?>
                    </div>
                    <div class="product-body">
                      <div class="product-price">$<?= $price ?></div>
                      <div class="product-title"><?= htmlspecialchars($randomProduct['title']) ?></div>
                      <div class="product-meta">
                        <div class="product-meta-row"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($location) ?></div>
                        <div class="product-meta-row"><i class="bi bi-clock"></i> <?= $timeString ?></div>
                      </div>
                      <div class="product-seller">
                        <div class="seller-avatar"><?= $initials ?></div>
                        <div class="seller-info">
                          <div class="seller-name"><?= htmlspecialchars($randomProduct['full_name'] ?? 'Usuario') ?><?php if (!empty($randomProduct['seller_verified'])): ?> <i class="bi bi-patch-check-fill" style="color:#25883f;font-size:.75rem;" title="Verificado"></i><?php endif; ?></div>
                          <div class="seller-rating">
                            <?php 
                            $rating = isset($randomProduct['avg_rating']) ? (float)$randomProduct['avg_rating'] : 0;
                            $fullStars = floor($rating);
                            $halfStar = ($rating - $fullStars) >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            for ($i = 0; $i < $fullStars; $i++) echo '<i class="bi bi-star-fill"></i>';
                            if ($halfStar) echo '<i class="bi bi-star-half"></i>';
                            for ($i = 0; $i < $emptyStars; $i++) echo '<i class="bi bi-star"></i>';
                            ?>
                            <span><?= number_format($rating, 1) ?> (<?= isset($randomProduct['total_reviews']) ? $randomProduct['total_reviews'] : 0 ?>)</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </a>
              <?php endwhile; ?>
            <?php else: ?>
              <p style="text-align: center; padding: 20px; color: var(--text-soft);">No hay productos disponibles.</p>
            <?php endif; ?>
          </div>
          <!-- ///////// productos aleatorios -->
        </div>
      </div>
      <!-- xxxxxxxxxxxxxxxx -->
      <div class="content-section">
        <div class="section-header">
          <div>
            <h2 class="section-title">Productos <span>recientes</span></h2>
          </div>
          <div style="display:flex;gap:10px;align-items:center;">
            <span style="font-size:.82rem;color:var(--text-soft);">Ordenar por:</span>
            <select id="sortSelect" style="border:1.5px solid #ddd;border-radius:8px;padding:6px 12px;font-family:'DM Sans',sans-serif;font-size:.84rem;outline:none;color:var(--text-dark);cursor:pointer;">
              <option value="recent">Más recientes</option>
              <option value="price_asc">Menor precio</option>
              <option value="price_desc">Mayor precio</option>
            </select>
            <a class="section-link" href="products/all.php">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="divider-line"></div>
        <!--  -->

        <div class="product-grid animate__animated animate__fadeIn">
          <?php if ($resultProducts && $resultProducts->num_rows > 0): ?>
            <?php while ($product = $resultProducts->fetch_assoc()):
              $pCond     = strtolower($product['condition_type']);
              $isBadge   = $pCond === 'nuevo' ? 'badge-new' : ($pCond === 'reacondicionado' ? 'badge-refurbished' : 'badge-used');
              $badgeText = ucfirst($product['condition_type']);
              $price     = number_format($product['price'], 0, ',', '.');
              $initials  = getInitials($product['full_name']);
              $timeString = timeAgo($product['created_at'] ?? null);
              $location  = $product['city'] ?? 'Colombia';
              $rp_promo  = $product['promotion_type'];
              $rp_badge  = $rp_promo ? ($staticPlanBadge[$rp_promo] ?? null) : null;
              $img_st2 = mysqli_prepare($conn, "SELECT image_url FROM product_images WHERE product_id = ? ORDER BY id ASC LIMIT 1");
              mysqli_stmt_bind_param($img_st2, 'i', $product['id']);
              mysqli_stmt_execute($img_st2);
              $img_res2 = mysqli_stmt_get_result($img_st2);
              $img_row2 = mysqli_fetch_assoc($img_res2);
            ?>
              <a href="products/detalle.php?id=<?= (int)$product['id'] ?>" class="product-link">
                <div class="product-card">
                  <div class="<?= $isBadge ?>"><?= $badgeText ?></div>
                  <button class="btn-fav" data-product-id="<?= (int)$product['id'] ?>"><i class="bi bi-heart"></i></button>
                  <div class="product-img-placeholder promo-pill-wrap">
                    <?php if ($rp_badge): ?>
                      <div class="promo-pill <?= $rp_badge['css'] ?>"><?= $rp_badge['icon'] ?> <?= $rp_badge['label'] ?></div>
                    <?php endif; ?>
                    <?php if ($img_row2): ?>
                      <img src="../../public/uploads/products/<?= htmlspecialchars($img_row2['image_url']) ?>" alt="">
                    <?php else: ?>
                      <i class="bi bi-box-seam"></i>
                    <?php endif; ?>
                  </div>
                  <div class="product-body">
                    <div class="product-price">$<?= $price ?></div>
                    <div class="product-title"><?= htmlspecialchars($product['title']) ?></div>
                    <div class="product-meta">
                      <div class="product-meta-row"><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($location) ?></div>
                      <div class="product-meta-row"><i class="bi bi-clock"></i> <?= $timeString ?></div>
                    </div>
                    <div class="product-seller">
                      <div class="seller-avatar"><?= $initials ?></div>
                      <div class="seller-info">
                        <div class="seller-name"><?= htmlspecialchars($product['full_name'] ?? 'Usuario') ?><?php if (!empty($product['seller_verified'])): ?> <i class="bi bi-patch-check-fill" style="color:#25883f;font-size:.75rem;" title="Verificado"></i><?php endif; ?></div>
                        <div class="seller-rating">
                          <?php 
                            $rating = isset($product['avg_rating']) ? (float)$product['avg_rating'] : 0;
                            $fullStars = floor($rating);
                            $halfStar = ($rating - $fullStars) >= 0.5;
                            $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                            for ($i = 0; $i < $fullStars; $i++) echo '<i class="bi bi-star-fill"></i>';
                            if ($halfStar) echo '<i class="bi bi-star-half"></i>';
                            for ($i = 0; $i < $emptyStars; $i++) echo '<i class="bi bi-star"></i>';
                          ?>
                          <span><?= number_format($rating, 1) ?> (<?= isset($product['total_reviews']) ? $product['total_reviews'] : 0 ?>)</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </a>

            <?php endwhile; ?>
          <?php else: ?>
            <p style="text-align: center; padding: 20px; color: var(--text-soft);">No hay productos disponibles.</p>
          <?php endif; ?>
        </div>
        <!-- xxxxxxxxxxxxxxx -->
      </div>
    </div>
  </div>

  <!-- ======= ¿CÓMO FUNCIONA? ======= -->
  <section class="how-section">
    <div class="how-inner">
      <div class="how-header">
        <div class="how-eyebrow"><i class="bi bi-question-circle-fill"></i> Simple y rápido</div>
        <h2 class="how-title">¿Cómo funciona <span>ComercioLocal</span>?</h2>
        <p class="how-subtitle">En tres pasos ya estás comprando o vendiendo en tu ciudad</p>
      </div>
      <div class="how-steps">
        <div class="how-step">
          <div class="how-step-num">01</div>
          <div class="how-step-icon"><i class="bi bi-person-plus-fill"></i></div>
          <h3>Crea tu cuenta</h3>
          <p>Regístrate gratis en menos de 1 minuto. Solo necesitas tu correo y ya estás listo para empezar.</p>
          <a href="auth/register.php" class="how-step-link">Registrarse <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="how-step-connector"><i class="bi bi-arrow-right"></i></div>
        <div class="how-step">
          <div class="how-step-num">02</div>
          <div class="how-step-icon"><i class="bi bi-camera-fill"></i></div>
          <h3>Publica tu producto</h3>
          <p>Sube fotos, describe el producto, pon tu precio y elige la categoría. ¡En segundos está publicado!</p>
          <a href="products/crear.php" class="how-step-link">Publicar ahora <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="how-step-connector"><i class="bi bi-arrow-right"></i></div>
        <div class="how-step">
          <div class="how-step-num">03</div>
          <div class="how-step-icon"><i class="bi bi-chat-dots-fill"></i></div>
          <h3>Conecta y vende</h3>
          <p>Los compradores te contactan por chat directo. Acordá el precio y concretá la venta en tu barrio.</p>
          <a href="products/all.php" class="how-step-link">Ver productos <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CTA BANNER ======= -->
  <section class="cta-banner">
    <div class="cta-banner-bg-dots"></div>
    <div class="cta-banner-content">
      <div class="cta-banner-left">
        <div class="cta-banner-badge">
          <i class="bi bi-lightning-charge-fill"></i> ¡Es gratis!
        </div>
        <h2>¿Tienes algo para vender?<br><em>Publícalo hoy mismo</em></h2>
        <p>Miles de compradores en tu ciudad ya están buscando lo que tienes. Publica en segundos y empieza a vender.</p>
        <div class="cta-banner-actions">
          <a href="products/crear.php" class="btn-cta-primary">
            <i class="bi bi-plus-circle-fill"></i> Publicar gratis
          </a>
          <?php if (!$isLoggedIn): ?>
            <a href="auth/register.php" class="btn-cta-ghost">
              Crear cuenta <i class="bi bi-arrow-right"></i>
            </a>
          <?php endif; ?>
        </div>
        <div class="cta-trust-row">
          <span><i class="bi bi-shield-check-fill"></i> 100% seguro</span>
          <span><i class="bi bi-clock-fill"></i> Publica en 2 min</span>
          <span><i class="bi bi-people-fill"></i> +120K usuarios</span>
        </div>
      </div>
      <div class="cta-banner-right">
        <div class="cta-stat-card">
          <div class="cta-stat-num">48K+</div>
          <div class="cta-stat-lbl">Anuncios activos</div>
        </div>
        <div class="cta-stat-card cta-stat-yellow">
          <div class="cta-stat-num">120K</div>
          <div class="cta-stat-lbl">Usuarios registrados</div>
        </div>
        <div class="cta-stat-card">
          <div class="cta-stat-num">32</div>
          <div class="lbl">Ciudades</div>
        </div>
        <div class="cta-stat-card cta-stat-yellow">
          <div class="cta-stat-num">4.9★</div>
          <div class="cta-stat-lbl">Valoración media</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= FOOTER ======= -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="brand-name">
          <div class="brand-icon"><i class="bi bi-shop"></i></div>ComercioLocal
        </div>
        <p>Conectamos compradores y vendedores dentro de la misma ciudad. Más local, más rápido, más seguro.</p>
        <div class="social-links">
          <div class="social-btn"><i class="bi bi-facebook"></i></div>
          <div class="social-btn"><i class="bi bi-instagram"></i></div>
          <div class="social-btn"><i class="bi bi-twitter-x"></i></div>
          <div class="social-btn"><i class="bi bi-whatsapp"></i></div>
          <div class="social-btn"><i class="bi bi-youtube"></i></div>
          <div class="social-btn"><i class="bi bi-tiktok"></i></div>
        </div>
      </div>
      <div class="footer-col">
        <h5>Explorar</h5>
        <ul>
          <li><a href="#">Tecnología</a></li>
          <li><a href="#">Vehículos</a></li>
          <li><a href="#">Hogar</a></li>
          <li><a href="#">Ropa y Moda</a></li>
          <li><a href="#">Deportes</a></li>
          <li><a href="#">Servicios</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Cuenta</h5>
        <ul>
          <?php if ($isLoggedIn): ?>
            <li><a href="admin/dashboard.php">Mi panel</a></li>
            <li><a href="#">Mis anuncios</a></li>
            <li><a href="#">Mensajes</a></li>
            <li><a href="#">Favoritos</a></li>
            <li><a href="<?= $ctrlPath ?>auth_logout.php">Cerrar sesión</a></li>
          <?php else: ?>
            <li><a href="auth/login.php">Iniciar sesión</a></li>
            <li><a href="auth/register.php">Registrarse</a></li>
            <li><a href="#">Mis anuncios</a></li>
            <li><a href="#">Mensajes</a></li>
            <li><a href="#">Favoritos</a></li>
          <?php endif; ?>
          <li><a href="#">Centro de ayuda</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Descarga la app</h5>
        <div class="app-btns">
          <div class="app-btn"><i class="bi bi-apple"></i>
            <div class="app-btn-text">Disponible en<br><strong>App Store</strong></div>
          </div>
          <div class="app-btn"><i class="bi bi-google-play"></i>
            <div class="app-btn-text">Disponible en<br><strong>Google Play</strong></div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 ComercioLocal. Hecho con <span style="color:var(--yellow-400);">♥</span> en Colombia. Todos los derechos reservados.</p>
      <div class="footer-legal">
        <a href="#">Términos de uso</a>
        <a href="#">Privacidad</a>
        <a href="#">Cookies</a>
        <a href="#">Contacto</a>
      </div>
    </div>
  </footer>

  <script src="../../public/js/favorites.js"></script>
  <script>
    // Cargar favoritos ANTES de home.js para que renderCard pueda usar Favorites.has()
    (async () => {
      const base = '../../';
      await Favorites.load(base);
      Favorites.applyToButtons('.btn-fav');
      Favorites.bindButtons('.btn-fav', base);
    })();
  </script>
  <script src="../../public/js/home.js"></script>

</body>

</html>
