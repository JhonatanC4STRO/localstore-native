<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: ./auth/login.php');
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
<link rel="stylesheet" href="../style/misProductos.css">
<link rel="stylesheet" href="../style/dashborad.css">
</head>
<body>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<?php $basePath = '../';
include __DIR__ . '/../components/header.php'; ?>

<!-- ═══════════════ LAYOUT ═══════════════ -->
<div class="page-shell">

        <!-- ══ SIDEBAR ══ -->
        <aside class="sidebar">
            <div class="sb-section-label">Principal</div>
            <a class="sb-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
            <a class="sb-link active" href="./misProductos.php">
                <i class="bi bi-box-seam"></i> Mis productos
                <span class="sb-badge"><?php echo $totalProducts; ?></span>
            </a>
            <a class="sb-link" href="./chat.php">
                <i class="bi bi-chat-dots"></i> Mensajes
                <span class="sb-badge yellow">5</span>
            </a>
            <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>
            <div class="sb-divider"></div>
            <div class="sb-section-label">Cuenta</div>
            <a class="sb-link" href="./perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
            <a class="sb-link" href="#"><i class="bi bi-star"></i> Reseñas</a>
            <a class="sb-link" href="#"><i class="bi bi-gear"></i> Configuración</a>
            <a class="sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
            </a>
            <div class="sb-divider"></div>
            <div class="sb-promo">
                <div class="sb-promo-icon">⭐</div>
                <p>Destaca tu anuncio y llega a 10× más compradores hoy.</p>
                <a href="#">Ver planes</a>
            </div>
        </aside>

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
$sql = "SELECT p.*, c.name as category_name
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
    $card_i++; ?>

          <div class="product-card" style="animation-delay:<?php echo $card_i * 0.07; ?>s;">
            <a href="./actions/detalleProducto.php?id=<?php echo $row['id']; ?>" target="_top">

              <!-- Image slot -->
              <div class="pc-img-slot">
                <?php
    $pid = $row['id'];
    $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
    $img_r = mysqli_query($conn, $img_q);
    $img_row = mysqli_fetch_assoc($img_r);
    if ($img_row): ?>
                  <img src="./productos/uploads/<?php echo htmlspecialchars($img_row['image_url']); ?>" alt="">
                <?php
    else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php
    endif; ?>

                <!-- Status badge -->
                <div class="pc-status-badge <?php echo($row['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
                  <?php echo($row['status'] == 1) ? '● Activo' : '○ Inactivo'; ?>
                </div>

                <!-- Quick action buttons on image -->
                <div class="pc-actions-top">
                  <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn edit" title="Editar" target="_top">
                    <i class="bi bi-pencil-fill"></i>
                  </a>
                  <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn del" title="Eliminar"
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

                <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                <div class="pc-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>

                <div class="pc-meta">
                  <?php if (!empty($row['condition_type'])): ?>
                    <div class="pc-meta-row">
                      <i class="bi bi-award-fill"></i>
                      <?php echo $row['condition_type'] === 'new' ? 'Nuevo' : 'Usado'; ?>
                    </div>
                  <?php
    endif; ?>
                  <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                    <div class="pc-meta-row">
                      <i class="bi bi-geo-alt-fill"></i>
                      <?php echo round($row['latitude'], 4); ?>, <?php echo round($row['longitude'], 4); ?>
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
                  <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-edit" target="_top">
                    <i class="bi bi-pencil"></i> Editar
                  </a>
                  <a href="./promociones.php?product_id=<?php echo $row['id']; ?>" class="pc-btn"
                    style="background:#fef9c3;color:#ca8a04;border:1.5px solid #fde68a;" target="_top">
                    <i class="bi bi-rocket-takeoff"></i> Promover
                  </a>
                  <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-del"
                    onclick="return confirm('¿Seguro que deseas eliminar este producto?')" target="_top">
                    <i class="bi bi-trash"></i> Eliminar
                  </a>
                </div>
              </div>
            </a>
          </div>

        <?php
  endwhile; ?>

      <?php
endif; ?>

    </div>

  </main>
</div>

<!-- Toast notification -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Acción realizada</span>
</div>

<script src="../js/misProductos.js"></script>
</body>
</html>