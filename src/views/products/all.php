<?php include('../../config/conexion.php'); ?>
<?php
session_start();
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';

/* ══════════════════════════════════════════
   LOGIC PRESERVED — original query + extras
══════════════════════════════════════════ */
$where_parts = ["p.status = 'disponible'", "p.admin_status = 'active'"];

// Excluir productos del usuario logueado
if ($isLoggedIn) {
    $where_parts[] = "p.user_id != " . (int)$user['id'];
}

// Búsqueda inteligente: matchea título, descripción, categoría y ciudad
$search = '';
if (!empty($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, trim($_GET['search']));
    $where_parts[] = "(
        p.title       LIKE '%$search%'
        OR p.description LIKE '%$search%'
        OR cat.name   LIKE '%$search%'
        OR u.city     LIKE '%$search%'
    )";

    // Guardar en historial si hay sesión y el término no es duplicado del último
    if ($isLoggedIn && $search !== '') {
        $uid = (int)$user['id'];
        $last = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT query FROM search_history WHERE user_id = $uid ORDER BY id DESC LIMIT 1"));
        if (!$last || strcasecmp($last['query'], $_GET['search']) !== 0) {
            $qInsert = mysqli_real_escape_string($conn, mb_substr(trim($_GET['search']), 0, 150));
            mysqli_query($conn, "INSERT INTO search_history (user_id, query) VALUES ($uid, '$qInsert')");
        }
    }
}

// Categoría única (desde slider)
if (!empty($_GET['category'])) {
    $cat_id = (int)$_GET['category'];
    $where_parts[] = "p.category_id = $cat_id";
}

// Categorías múltiples (desde sidebar)
if (!empty($_GET['categories']) && is_array($_GET['categories'])) {
    $cats = array_map('intval', $_GET['categories']);
    $where_parts[] = "p.category_id IN (" . implode(',', $cats) . ")";
}

// Precios
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $pmin = (int)$_GET['price_min'];
    $where_parts[] = "p.price >= $pmin";
}
if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $pmax = (int)$_GET['price_max'];
    $where_parts[] = "p.price <= $pmax";
}

// Condición
if (!empty($_GET['condition']) && $_GET['condition'] !== 'all') {
    $cond = mysqli_real_escape_string($conn, $_GET['condition']);
    $where_parts[] = "LOWER(p.condition_type) = LOWER('$cond')";
}

// Ubicación
if (!empty($_GET['location'])) {
    $loc = mysqli_real_escape_string($conn, $_GET['location']);
    $where_parts[] = "u.city = '$loc'";
}

$where_sql = "WHERE " . implode(" AND ", $where_parts);

$sql = "SELECT
    p.id,
    p.user_id,
    p.title,
    p.description,
    p.price,
    p.condition_type,
    p.status,
    p.created_at,
    u.full_name AS seller_name,
    cat.name AS category_name,
    (SELECT pi.image_url
     FROM product_images pi
     WHERE pi.product_id = p.id
     ORDER BY pi.id ASC
     LIMIT 1) AS image_url,
    COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
    (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS total_reviews,
    (SELECT sv.id FROM seller_verifications sv WHERE sv.user_id = p.user_id AND sv.status = 'approved' LIMIT 1) AS seller_verified
FROM products p
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN categories cat ON p.category_id = cat.id
$where_sql";

// Cuando hay search, ordenar por relevancia (título prefix > título contains > categoría > ciudad > descripción)
if ($search !== '') {
    $sql .= "
ORDER BY
    (CASE
        WHEN p.title    LIKE '$search%'  THEN 5
        WHEN p.title    LIKE '%$search%' THEN 4
        WHEN cat.name   LIKE '%$search%' THEN 3
        WHEN u.city     LIKE '%$search%' THEN 2
        WHEN p.description LIKE '%$search%' THEN 1
        ELSE 0
     END) DESC,
    p.id DESC";
} else {
    $sql .= " ORDER BY p.id DESC";
}

$result     = mysqli_query($conn, $sql);
$totalCount = mysqli_num_rows($result);

/* Load categories for quick-bar chips */
$catResult = mysqli_query($conn, "SELECT id, name FROM categories ORDER BY name ASC");
$allCategories = [];
if ($catResult) {
    while ($c = mysqli_fetch_assoc($catResult)) {
        $allCategories[] = $c;
    }
}

$_qfIconMap = [
    'vehículo' => 'bi-car-front-fill', 'vehiculo' => 'bi-car-front-fill',
    'electrónica' => 'bi-lightning-charge-fill', 'electronica' => 'bi-lightning-charge-fill',
    'celular' => 'bi-phone-fill', 'computador' => 'bi-laptop-fill',
    'ropa' => 'bi-bag-heart-fill', 'calzado' => 'bi-bootstrap-fill',
    'hogar' => 'bi-house-door-fill', 'mueble' => 'bi-house-fill',
    'deporte' => 'bi-trophy-fill', 'herramienta' => 'bi-tools',
    'libro' => 'bi-book-fill', 'mascota' => 'bi-heart-fill',
    'juguete' => 'bi-controller', 'videojuego' => 'bi-controller',
    'bicicleta' => 'bi-bicycle', 'instrumento' => 'bi-music-note-beamed',
    'belleza' => 'bi-stars', 'jardín' => 'bi-leaf-fill', 'jardin' => 'bi-leaf-fill',
    'arte' => 'bi-palette-fill', 'servicio' => 'bi-briefcase-fill',
    'propiedad' => 'bi-house-door-fill', 'electrodoméstico' => 'bi-plug-fill',
];

function getQfIcon($name, $map) {
    $k = strtolower(trim($name));
    foreach ($map as $keyword => $icon) {
        if (str_contains($k, $keyword)) return $icon;
    }
    return 'bi-grid-fill';
}

function getInitials($name)
{
    if (empty($name)) return 'U';
    $parts = explode(' ', trim($name));
    $out   = '';
    foreach ($parts as $p) if (!empty($p)) $out .= strtoupper($p[0]);
    return substr($out, 0, 2);
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los productos – ComercioLocal</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../../../public/css/output.css">
    <link rel="stylesheet" href="../../../public/css/allProduct.css">
</head>

<body>

    <!-- ══ NAVBAR ══ -->
    <?php $basePath = "../../../"; include __DIR__ . '/../../components/header.php'; ?>

    <!-- ══ BREADCRUMB ══ -->
    <div class="breadcrumb-bar">
        <div class="breadcrumb">
            <a href="../home.php">Inicio</a>
            <i class="bi bi-chevron-right"></i>
            <span>Todos los productos</span>
        </div>
    </div>
    <!-- ══ QUICK FILTER BAR ══ -->
    <div class="quick-bar-wrap">
      <div class="quick-bar">
          <div class="qf-chip active" data-cat-id="all"><i class="bi bi-grid-fill"></i> Todos</div>
          <?php foreach (array_slice($allCategories, 0, 8) as $qCat): ?>
              <div class="qf-chip" data-cat-id="<?= (int)$qCat['id'] ?>">
                  <i class="bi <?= getQfIcon($qCat['name'], $_qfIconMap) ?>"></i>
                  <?= htmlspecialchars($qCat['name']) ?>
              </div>
          <?php endforeach; ?>
          <div class="qf-sep"></div>
          <div class="qf-chip" data-special="featured"><i class="bi bi-star-fill" style="color:var(--y400);"></i> Destacados</div>
          <div class="qf-chip" data-special="recent"><i class="bi bi-clock-fill"></i> Recientes</div>
      </div>
    </div>

    <!-- ══ PAGE SHELL ══ -->
    <div class="page-shell">

        <!-- ── SIDEBAR (unified component) ── -->
        <?php
        $showPromo = true;
        $promoHref = '../app/crear.php';
        include __DIR__ . '/../../components/filter_sidebar.php';
        ?>

        <!-- ── MAIN CONTENT ── -->
        <div class="main-content">

            <!-- ══ SEARCH BAR ══ -->
            <div class="ap-search-wrap">
              <div class="ap-search-field">
                <i class="bi bi-search ap-search-ico"></i>
                <input type="text" id="searchInput" class="ap-search-input"
                       placeholder="Buscar productos, marcas, categorías..."
                       value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <button class="ap-search-clear" id="searchClear"
                  <?= empty($_GET['search']) ? 'style="display:none"' : '' ?>>
                  <i class="bi bi-x-lg"></i>
                </button>
              </div>
              <button class="ap-search-btn" id="searchBtn">
                <i class="bi bi-search"></i> Buscar
              </button>
            </div>

            <!-- Results header -->
            <div class="results-header">
                <div class="results-left">
                    <h1>Todos los productos en <span>tu ciudad</span></h1>
                    <div class="results-count">
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                        Mostrando <?php echo $totalCount; ?> producto<?php echo $totalCount !== 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="results-right">
                    <div class="view-toggle">
                        <button class="vt-btn active" id="gridBtn" title="Cuadrícula"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                        <button class="vt-btn" id="listBtn" title="Lista"><i class="bi bi-list-ul"></i></button>
                    </div>
                    <select class="sort-inline" id="sortInline">
                        <option value="recent">Más recientes</option>
                        <option value="price_asc">Precio ↑</option>
                        <option value="price_desc">Precio ↓</option>
                    </select>
                </div>
            </div>

            <!-- ══ PRODUCT GRID — LOGIC PRESERVED ══ -->
            <div class="product-grid" id="productGrid">
                <?php
                $i = 0;
                if (mysqli_num_rows($result) > 0):
                    while ($row = mysqli_fetch_assoc($result)):
                        $i++;
                        $image      = $row['image_url']
                            ? "../../../public/uploads/products/" . $row['image_url']
                            : null;
                        $isNew      = ($i % 3 !== 0); // alternate for demo; real: use condition_type
                        $isFeatured = ($i % 5 === 0); // every 5th card gets "Destacado"
                        $condType   = strtolower($row['condition_type'] ?? '');
                        $isNewCond  = ($condType === 'nuevo');
                        $initials   = getInitials($row['seller_name'] ?? '');
                        $price      = number_format($row['price'], 0, ',', '.');
                ?>
                        <a href="./detalle.php?id=<?php echo $row['id']; ?>"
                            style="display:contents;">
                            <div class="product-card">

                                <!-- Image -->
                                <div class="pc-img">
                                    <?php if ($image): ?>
                                        <img src="<?php echo htmlspecialchars($image); ?>"
                                            alt="<?php echo htmlspecialchars($row['title']); ?>">
                                    <?php else: ?>
                                        <div class="pc-img-ph"><i class="bi bi-box-seam"></i></div>
                                    <?php endif; ?>

                                    <!-- Condition badge -->
                                    <?php if ($isFeatured): ?>
                                        <div class="badge badge-featured"><i class="bi bi-star-fill"></i> Destacado</div>
                                    <?php elseif ($isNewCond): ?>
                                        <div class="badge badge-new">Nuevo</div>
                                    <?php elseif ($condType === 'reacondicionado'): ?>
                                        <div class="badge badge-refurbished">Reacondicionado</div>
                                    <?php else: ?>
                                        <div class="badge badge-used">Usado</div>
                                    <?php endif; ?>

                                    <!-- Fav -->
                                    <button class="fav-btn" data-product-id="<?php echo (int)$row['id']; ?>">
                                        <i class="bi bi-heart"></i>
                                    </button>
                                </div>

                                <!-- Body -->
                                <div class="pc-body">
                                    <div class="pc-cat"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($row['category_name'] ?? 'General'); ?></div>
                                    <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="pc-price">$<?php echo $price; ?></div>
                                    <div class="pc-meta">
                                        <div class="pc-meta-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
                                        <div class="pc-meta-row"><i class="bi bi-clock-fill"></i>
                                            <?php
                                            if (!empty($row['created_at'])) {
                                                $diff = time() - strtotime($row['created_at']);
                                                if      ($diff < 60)     echo 'Hace unos segundos';
                                                elseif  ($diff < 3600)   echo 'Hace ' . floor($diff/60) . ' min';
                                                elseif  ($diff < 86400)  echo 'Hace ' . floor($diff/3600) . ' h';
                                                elseif  ($diff < 604800) echo 'Hace ' . floor($diff/86400) . ' días';
                                                else                     echo date('d/m/Y', strtotime($row['created_at']));
                                            } else {
                                                echo 'Recientemente';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <div class="pc-seller">
                                        <div class="seller-av"><?php echo $initials; ?></div>
                                        <a href="./seller_profile.php?seller_id=<?php echo $row['user_id']; ?>" class="seller-name-txt hover:underline"><?php echo htmlspecialchars($row['seller_name'] ?? 'Usuario'); ?></a>
                                        <?php if (!empty($row['seller_verified'])): ?>
                                          <i class="bi bi-patch-check-fill" style="color:#25883f;font-size:.8rem;" title="Verificado"></i>
                                        <?php endif; ?>
                                        <div class="seller-stars">
                                            <?php
                                            $avgRating    = (float)($row['avg_rating'] ?? 0);
                                            $totalReviews = (int)($row['total_reviews'] ?? 0);
                                            $fullStars    = floor($avgRating);
                                            $halfStar     = ($avgRating - $fullStars) >= 0.5;
                                            $emptyStars   = 5 - $fullStars - ($halfStar ? 1 : 0);
                                            for ($s = 0; $s < $fullStars; $s++) echo '<i class="bi bi-star-fill"></i>';
                                            if ($halfStar) echo '<i class="bi bi-star-half"></i>';
                                            for ($s = 0; $s < $emptyStars; $s++) echo '<i class="bi bi-star"></i>';
                                            ?>
                                            <span><?= number_format($avgRating, 1) ?> (<?= $totalReviews ?>)</span>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </a>
                    <?php endwhile;
                else: ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="bi bi-search"></i></div>
                        <div class="empty-title">No hay productos disponibles</div>
                        <div class="empty-sub">Sé el primero en publicar un producto en tu ciudad.</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ══ LOAD MORE ══ -->
            <?php if ($totalCount > 0): ?>
                <div class="load-more-wrap">
                    <button class="btn-load-more">
                        <i class="bi bi-arrow-repeat"></i>
                        Cargar más productos
                    </button>
                    <div class="pagination" style="margin-top:20px;">
                        <button class="pg-btn"><i class="bi bi-chevron-left"></i></button>
                        <button class="pg-btn active">1</button>
                        <button class="pg-btn">2</button>
                        <button class="pg-btn">3</button>
                        <span class="pg-dots">...</span>
                        <button class="pg-btn">12</button>
                        <button class="pg-btn"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
            <?php endif; ?>

        </div><!-- /main-content -->
    </div><!-- /page-shell -->

  <script>
    let currentOffset  = <?php echo (int)mysqli_num_rows($result); ?>;
    let totalProducts  = <?php echo (int)$totalCount; ?>;
    const initialSearch = <?= json_encode(trim($_GET['search'] ?? '')) ?>;
    const initialCategory = <?= json_encode(trim($_GET['category'] ?? '')) ?>;
    const initialCategories = <?= json_encode($_GET['categories'] ?? []) ?>;
    const initialPriceMin = <?= json_encode($_GET['price_min'] ?? '') ?>;
    const initialPriceMax = <?= json_encode($_GET['price_max'] ?? '') ?>;
    const initialCondition = <?= json_encode($_GET['condition'] ?? 'all') ?>;
    const initialLocation = <?= json_encode(trim($_GET['location'] ?? '')) ?>;
  </script>
    <script src="../../../public/js/favorites.js"></script>
    <script>
      (async () => {
        const base = '../../../';
        await Favorites.load(base);
        Favorites.applyToButtons('.fav-btn');
        Favorites.bindButtons('.fav-btn', base);
      })();
    </script>
    <script src="../../../public/js/all.js"></script>
</body>

</html>















