<?php
require_once(__DIR__ . '/../../config/conexion.php');
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: ../auth/login.php');
  exit();
}
$user = $_SESSION['user'];
$user_id = $user['id'];
$isLoggedIn = true;
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName = explode(' ', $user['full_name'])[0];

// Count products
$totalProducts = 0;
$sql_count = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'";
$rc = mysqli_query($conn, $sql_count);
if ($rc)
  $totalProducts = mysqli_fetch_assoc($rc)['total'];

$isEmbed = false;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ComercioLocal — Mis Productos</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="../../../public/css/misProductos.css">
<link rel="stylesheet" href="../../../public/css/dashborad.css">
<link rel="stylesheet" href="../../../public/css/sidebar.css">
</head>
<body>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<?php $basePath = "../../../";
include __DIR__ . '/../../components/header.php'; ?>

<!-- ═══════════════ LAYOUT ═══════════════ -->
<div class="page-shell">

        <!-- ══ SIDEBAR ══ -->
        <?php $activeTab = 'productos'; include __DIR__ . '/../../components/sidebar.php'; ?>

  <!-- ═══════════════ MAIN ═══════════════ -->
  <main class="main-content">

    <!-- Page header -->
    <div class="page-header">
      <div class="page-title-group">
        <div class="page-breadcrumb">
          <i class="fas fa-home"></i>
          <i class="fas fa-chevron-right" style="font-size:9px"></i>
          <span>Mis Productos</span>
        </div>
        <h1 class="page-title">
          Mis productos
          <span class="page-title-badge"><?php echo $totalProducts; ?> publicaciones</span>
        </h1>
        <p class="page-subtitle">Administra, edita y controla todas tus publicaciones</p>
      </div>
      <a class="publish-btn publish-btn-alt" href="./crear.php" onclick="if(window.parent!==window){event.preventDefault();window.parent.document.querySelector('[data-tab=crear]')?.click();}">
        <i class="fas fa-plus"></i>
        Publicar nuevo producto
      </a>
    </div>

    <!-- Stats removed — now in dashboard -->

    <!-- Controls -->
    <div class="controls-bar">
      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Buscar mis productos..." id="searchInput" oninput="filterProducts()">
      </div>
      <select class="filter-select" id="statusFilter" onchange="filterProducts()">
        <option value="all">Todos los estados</option>
        <option value="active">Activos</option>
        <option value="inactive">Inactivos</option>
        <option value="sold">Vendidos</option>
      </select>
      <div class="view-toggle">
        <button class="view-btn active" onclick="setView('grid',this)" title="Cuadrícula"><i class="fas fa-th"></i></button>
        <button class="view-btn" onclick="setView('list',this)" title="Lista"><i class="fas fa-list"></i></button>
      </div>
    </div>

    <!-- Section header -->
    <div class="section-header">
      <div class="section-title">
        Publicaciones
        <span class="section-count" id="productCount"><?php echo $totalProducts; ?> productos</span>
      </div>
    </div>

    <!-- Products Grid -->
    <div class="products-grid" id="productsGrid">

      <?php
$sql = "SELECT p.*, c.name as category_name,
              COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r WHERE r.product_id = p.id), 0) AS avg_rating,
              (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) AS total_reviews
              FROM products p
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE p.user_id = '$user_id'
              ORDER BY p.id DESC";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0): ?>

        <!-- Empty State -->
        <div class="empty-state" style="grid-column:1/-1;display:flex;">
          <div class="empty-illustration">📦</div>
          <div class="empty-title">No tienes productos aún</div>
          <div class="empty-sub">¡Empieza a vender hoy! Publica tu primer producto y llega a miles de compradores locales.</div>
          <a class="publish-btn" style="margin:0 auto" href="./crear.php" onclick="if(window.parent!==window){event.preventDefault();window.parent.document.querySelector('[data-tab=crear]')?.click();}">
            <i class="fas fa-plus"></i> Publicar primer producto
          </a>
        </div>

      <?php
else: ?>

        <?php $card_i = 0;
  while ($row = mysqli_fetch_assoc($result)):
    $card_i++;
    $pcIsSold    = (($row['status'] ?? '') === 'vendido');
    $pcIsActive  = !$pcIsSold && (($row['admin_status'] ?? 'active') !== 'inactive');
    if ($pcIsSold)      { $pcStatusKey = 'sold'; }
    elseif ($pcIsActive){ $pcStatusKey = 'active'; }
    else                { $pcStatusKey = 'inactive'; }
  ?>

          <div class="product-card" id="pcard-<?php echo (int)$row['id']; ?>"
               data-title="<?php echo htmlspecialchars($row['title'] ?? '', ENT_QUOTES); ?>"
               data-status="<?php echo $pcStatusKey; ?>"
               style="animation-delay:<?php echo $card_i * 0.07; ?>s;">

              <!-- Image slot -->
              <div class="pc-img-slot">
                <a href="./detalle.php?id=<?php echo $row['id']; ?>" target="_top">
                <?php
    $pid = $row['id'];
    $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
    $img_r = mysqli_query($conn, $img_q);
    $img_row = mysqli_fetch_assoc($img_r);
    if ($img_row): ?>
                  <img src="../../../public/uploads/products/<?php echo $img_row['image_url']; ?>" alt="">
                <?php
    else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php
    endif; ?>
                </a>

                <!-- Status badge -->
                <?php
                  if ($pcIsSold)       { $badgeCls = 'status-sold';     $badgeTxt = '● Vendido'; }
                  elseif ($pcIsActive) { $badgeCls = 'status-active';   $badgeTxt = '● Activo';  }
                  else                 { $badgeCls = 'status-inactive'; $badgeTxt = '○ Inactivo'; }
                ?>
                <div class="pc-status-badge <?php echo $badgeCls; ?>" data-badge>
                  <?php echo $badgeTxt; ?>
                </div>

                <!-- Quick action buttons on image -->
                <div class="pc-actions-top">
                  <a href="./edit.php?id=<?php echo $row['id']; ?>" class="pc-action-btn edit" title="Editar" target="_top">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="../../controllers/product_delete_action.php?id=<?php echo $row['id']; ?>" class="pc-action-btn del" title="Eliminar"
                    onclick="return confirm('¿Eliminar este producto?')" target="_top">
                    <i class="bi bi-trash-fill"></i>
                  </a>
                </div>
              </div>

              <!-- Card body -->
              <div class="pc-body">
                <?php if (!empty($row['category_name'])): ?>
                  <div class="pc-category"><i class="bi bi-tag-fill"></i><?php echo htmlspecialchars($row['category_name']); ?></div>
                <?php
    endif; ?>

                <a href="./detalle.php?id=<?php echo $row['id']; ?>" target="_top" style="text-decoration:none;">
                  <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                </a>
                <div class="pc-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>

                <!-- Rating stars -->
                <div class="pc-rating">
                  <?php
                    $rating = (float)($row['avg_rating'] ?? 0);
                    $totalReviews = (int)($row['total_reviews'] ?? 0);
                    $fullStars  = floor($rating);
                    $halfStar   = ($rating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    for ($s = 0; $s < $fullStars; $s++) echo '<i class="bi bi-star-fill"></i>';
                    if ($halfStar) echo '<i class="bi bi-star-half"></i>';
                    for ($s = 0; $s < $emptyStars; $s++) echo '<i class="bi bi-star"></i>';
                  ?>
                  <span class="pc-rating-num"><?php echo number_format($rating, 1); ?></span>
                  <span class="pc-rating-count">(<?php echo $totalReviews; ?>)</span>
                </div>

                <div class="pc-meta">
                  <?php if (!empty($row['condition_type'])): ?>
                    <div class="pc-meta-row">
                      <i class="bi bi-award-fill"></i>
                      <?php
                        $ct = strtolower($row['condition_type'] ?? '');
                        echo $ct === 'nuevo' ? 'Nuevo' : ($ct === 'reacondicionado' ? 'Reacondicionado' : 'Usado');
                      ?>
                    </div>
                  <?php
    endif; ?>
                  <div class="pc-meta-row">
                    <i class="bi bi-clock-fill"></i>
                    <?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : 'Publicado'; ?>
                  </div>
                </div>

                <!-- Footer action buttons -->
                <div class="pc-footer">
                  <a href="./detalle.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-preview" target="_top">
                    <i class="bi bi-eye"></i> Ver como cliente
                  </a>
                  <a href="./edit.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-edit" target="_top">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                  <button type="button"
                    class="pc-btn <?php echo $pcIsSold ? 'pc-btn-undo' : 'pc-btn-sold'; ?>"
                    data-sold-btn
                    onclick="toggleProductSold(<?php echo (int)$row['id']; ?>, this)">
                    <i class="bi <?php echo $pcIsSold ? 'bi-arrow-counterclockwise' : 'bi-check2-circle'; ?>" data-sold-icon></i>
                    <span data-sold-label><?php echo $pcIsSold ? 'Deshacer venta' : 'Marcar vendido'; ?></span>
                  </button>
                  <a href="../promociones.php?product_id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-promote" target="_top">
                    <i class="bi bi-rocket-takeoff"></i> Promover
                  </a>
                  <a href="../../controllers/product_delete_action.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-del pc-btn-full"
                    onclick="return confirm('¿Seguro que deseas eliminar este producto?')" target="_top">
                    <i class="bi bi-trash"></i> Eliminar
                  </a>
                </div>
              </div>
          </div>

        <?php
  endwhile; ?>

      <?php
endif; ?>

    </div>

    <!-- Empty state para filtro sin resultados -->
    <div class="empty-state" id="emptyState" style="display:none;grid-column:1/-1;">
      <div class="empty-illustration">🔎</div>
      <div class="empty-title">Sin resultados</div>
      <div class="empty-sub">No encontramos productos que coincidan con tu búsqueda o filtro.</div>
    </div>

  </main>
</div>

<!-- Toast notification -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Acción realizada</span>
</div>

<script src="../../../public/js/mis_productos.js"></script>
</body>
</html>














