<?php include('../config/conexion.php'); ?>
<?php
session_start();
$isLoggedIn  = isset($_SESSION['user']);
$user        = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';

/* ══════════════════════════════════════════
   LOGIC PRESERVED — original query + extras
══════════════════════════════════════════ */
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
     LIMIT 1) AS image_url
  FROM products p
  LEFT JOIN users u ON p.user_id = u.id
  LEFT JOIN categories cat ON p.category_id = cat.id
  ORDER BY p.id DESC";

$result     = mysqli_query($conn, $sql);
$totalCount = mysqli_num_rows($result);

/* categories for sidebar */
$cat_sql    = "SELECT id, name FROM categories ORDER BY name ASC";
$cat_result = mysqli_query($conn, $cat_sql);
$categories = [];
while ($c = mysqli_fetch_assoc($cat_result)) $categories[] = $c;

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

    <link rel="stylesheet" href="../output.css">
    <link rel="stylesheet" href="../style/allProduct.css">
</head>

<body>

    <!-- ══ NAVBAR ══ -->
    <?php $basePath = '../'; include __DIR__ . '/../components/header.php'; ?>

    <!-- ══ BREADCRUMB ══ -->
    <div class="breadcrumb-bar">
        <div class="breadcrumb">
            <a href="../index.php">Inicio</a>
            <i class="bi bi-chevron-right"></i>
            <span>Todos los productos</span>
        </div>
    </div>

    <!-- ══ QUICK FILTER BAR ══ -->
    <div class="quick-bar">
        <div class="qf-chip active"><i class="bi bi-grid-fill"></i> Todos</div>
        <div class="qf-chip"><i class="bi bi-cpu-fill"></i> Tecnología</div>
        <div class="qf-chip"><i class="bi bi-car-front-fill"></i> Vehículos</div>
        <div class="qf-chip"><i class="bi bi-bag-heart-fill"></i> Ropa</div>
        <div class="qf-chip"><i class="bi bi-house-door-fill"></i> Hogar</div>
        <div class="qf-chip"><i class="bi bi-trophy-fill"></i> Deportes</div>
        <div class="qf-chip"><i class="bi bi-tools"></i> Herramientas</div>
        <div class="qf-chip"><i class="bi bi-book-fill"></i> Libros</div>
        <div class="qf-sep"></div>
        <div class="qf-chip"><i class="bi bi-star-fill" style="color:var(--y400);"></i> Destacados</div>
        <div class="qf-chip"><i class="bi bi-fire" style="color:#ef4444;"></i> Más vistos</div>
        <div class="qf-chip"><i class="bi bi-clock-fill"></i> Recientes</div>
    </div>

    <!-- ══ PAGE SHELL ══ -->
    <div class="page-shell">

        <!-- ── SIDEBAR ── -->
        <aside class="sidebar">
            <div class="filter-card">
                <div class="filter-title"><i class="bi bi-sliders"></i> Filtros</div>

                <?php
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
                    'Jardín y exterior' => 'bi-leaf-fill',
                    'Instrumentos musicales' => 'bi-music-note-beamed',
                    'Arte y coleccionables' => 'bi-palette-fill',
                    'Libros y revistas' => 'bi-book-fill',
                    'Videojuegos y consolas' => 'bi-controller',
                    'Bicicletas' => 'bi-bicycle',
                    'Motocicletas' => 'bi-bicycle',
                    'Servicios' => 'bi-briefcase-fill',
                ];
                function getIcon($name, $map) { return $map[$name] ?? 'bi-grid-fill'; }
                ?>

                <!-- Categorías -->
                <div class="filter-group">
                    <div class="filter-group-label">Categoría</div>
                    <div class="filter-options" id="filterCategories">
                        <?php if (!empty($categories)): foreach ($categories as $cat): ?>
                            <label class="filter-opt">
                                <input type="checkbox" value="<?= (int)$cat['id'] ?>">
                                <span class="filter-opt-label">
                                    <i class="bi <?= getIcon($cat['name'], $iconMap) ?>" style="color:var(--green-500,#25883f);margin-right:4px;font-size:.8rem;"></i>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </span>
                            </label>
                        <?php endforeach; else: ?>
                            <p style="font-size:.82rem;color:var(--text-soft);">Sin categorías</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Precio -->
                <div class="filter-group">
                    <div class="filter-group-label">Rango de precio</div>
                    <div class="price-range">
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">$</span>
                            <input type="text" class="price-input price-min" id="priceMinInput" placeholder="Mín" value="0">
                        </div>
                        <span class="price-sep">–</span>
                        <div class="price-input-wrapper">
                            <span class="currency-symbol">$</span>
                            <input type="text" class="price-input price-max" id="priceMaxInput" placeholder="Máx" value="10.000.000">
                        </div>
                    </div>
                    <input type="range" class="range-slider" id="rangeSlider" min="0" max="10000000" value="10000000">
                </div>

                <!-- Condición -->
                <div class="filter-group">
                    <div class="filter-group-label">Estado del producto</div>
                    <div class="condition-tags" id="filterCondition">
                        <div class="cond-tag active" data-condition="all">Todos</div>
                        <div class="cond-tag" data-condition="Nuevo">Nuevo</div>
                        <div class="cond-tag" data-condition="Usado">Usado</div>
                        <div class="cond-tag" data-condition="Reacondicionado">Reacondicionado</div>
                    </div>
                </div>

                <button class="btn-apply-filter" id="applyFiltersBtn"><i class="bi bi-funnel-fill"></i> Aplicar filtros</button>
                <button class="btn-apply-filter" id="resetFiltersBtn" style="background:transparent;color:var(--text-soft,#888);border:1.5px solid #ddd;margin-top:6px;">Limpiar filtros</button>
            </div>

            <div class="sb-promo">
                <div class="pt">🚀 Premium</div>
                <h4>¿Vendes algo?</h4>
                <p>Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.</p>
                <a href="../app/crear.php">Publicar ahora <i class="bi bi-arrow-right"></i></a>
            </div>
        </aside>

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
                            ? "./productos/uploads/" . $row['image_url']
                            : null;
                        $isNew      = ($i % 3 !== 0); // alternate for demo; real: use condition_type
                        $isFeatured = ($i % 5 === 0); // every 5th card gets "Destacado"
                        $condType   = strtolower($row['condition_type'] ?? '');
                        $isNewCond  = ($condType === 'nuevo');
                        $initials   = getInitials($row['seller_name'] ?? '');
                        $price      = number_format($row['price'], 0, ',', '.');
                ?>
                        <a href="./actions/detalleProducto.php?id=<?php echo $row['id']; ?>"
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
                                    <?php else: ?>
                                        <div class="badge badge-used">Usado</div>
                                    <?php endif; ?>

                                    <!-- Fav -->
                                    <button class="fav-btn" onclick="event.preventDefault();this.classList.toggle('active');this.querySelector('i').className=this.classList.contains('active')?'bi bi-heart-fill':'bi bi-heart';">
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
                                        <div class="seller-stars">
                                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                                            <i class="bi bi-star-half"></i>
                                            <span>4.5</span>
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
  </script>
    <script src="../js/allProduct.js"></script>
</body>

</html>