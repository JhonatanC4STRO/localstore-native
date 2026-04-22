<?php require_once('../config/conexion.php'); ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
  header("Location: auth/login.php");
  exit();
}

$isLoggedIn = isset($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
$user_id = $_SESSION['user']['id'];

// contar productos totales
$totalProducts = 0;
$sql_count = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'";
$r = mysqli_query($conn, $sql_count);
if ($r) {
  $row_c = mysqli_fetch_assoc($r);
  $totalProducts = $row_c['total'];
}

// contar anuncios activos
$activeListings = 0;
$sql_active = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id' AND status = 'disponible' AND admin_status = 'active'";
$r2 = mysqli_query($conn, $sql_active);
if ($r2) {
  $row_a = mysqli_fetch_assoc($r2);
  $activeListings = $row_a['total'];
}

// contar mensajes sin leer
$unreadMessages = 0;
$sql_unread = "SELECT COUNT(*) as total
  FROM messages m
  JOIN conversations c ON m.conversation_id = c.id
  WHERE (c.buyer_id = '$user_id' OR c.seller_id = '$user_id')
    AND m.sender_id != '$user_id'
    AND m.is_read = 0";
$r3 = mysqli_query($conn, $sql_unread);
if ($r3) {
  $row_u = mysqli_fetch_assoc($r3);
  $unreadMessages = $row_u['total'];
}

// contar productos vendidos
$soldProducts = 0;
$sql_sold = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id' AND status = 'vendido'";
$r4 = mysqli_query($conn, $sql_sold);
if ($r4) {
  $row_s = mysqli_fetch_assoc($r4);
  $soldProducts = $row_s['total'];
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/dashborad.css">
  <link rel="stylesheet" href="../../public/css/output.css">
  <link rel="stylesheet" href="../../public/css/sidebar.css">

</head>

<body>

  <!-- header -->
  <?php $basePath = "../../";
  include __DIR__ . '/../components/header.php'; ?>

  <div class="page-shell">

    <!-- ══════════════════════════════════
       SIDEBAR
    ══════════════════════════════════ -->
    <?php $activeTab = 'dashboard';
    include __DIR__ . '/../components/sidebar.php'; ?>

    <!-- ══════════════════════════════════
       MAIN CONTENT
    ══════════════════════════════════ -->
    <main class="main-content">

      <!-- Page header -->
      <div class="pg-header">
        <div>
          <div class="pg-breadcrumb">
            <a href="./home.php">Inicio</a>
            <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
            <span>Dashboard</span>
          </div>
          <h1 class="pg-title">
            Panel de <span>vendedor</span>
          </h1>
          <p class="pg-subtitle">
            Bienvenido, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> — aquí está el resumen de
            tu actividad.
          </p>
        </div>
        <a href="./products/crear.php" class="btn-publish-new">
          <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
          Publicar producto
        </a>
      </div>

      <!-- ── STATS CARDS ── -->
      <div class="stats-grid">

        <div class="stat-card green-card">
          <div class="stat-top">
            <div class="stat-icon si-green"><i class="bi bi-box-seam-fill"></i></div>
          </div>
          <div class="stat-value"><?php echo $totalProducts; ?></div>
          <div class="stat-label">Total de productos</div>
          <div class="stat-bar-wrap">
            <div class="stat-bar bar-green" style="width:<?php echo min($totalProducts * 10, 100); ?>%"></div>
          </div>
        </div>

        <div class="stat-card yellow-card">
          <div class="stat-top">
            <div class="stat-icon si-yellow"><i class="bi bi-chat-dots-fill"></i></div>
            <?php if ($unreadMessages > 0): ?>
              <div class="stat-trend trend-up"><i class="bi bi-envelope-fill"></i> <?php echo $unreadMessages; ?> sin leer</div>
            <?php endif; ?>
          </div>
          <div class="stat-value"><?php echo $unreadMessages; ?></div>
          <div class="stat-label">Mensajes sin leer</div>
          <div class="stat-bar-wrap">
            <div class="stat-bar bar-yellow" style="width:<?php echo min($unreadMessages * 10, 100); ?>%"></div>
          </div>
        </div>

        <div class="stat-card blue-card">
          <div class="stat-top">
            <div class="stat-icon si-blue"><i class="bi bi-bag-check-fill"></i></div>
          </div>
          <div class="stat-value"><?php echo $soldProducts; ?></div>
          <div class="stat-label">Productos vendidos</div>
          <div class="stat-bar-wrap">
            <div class="stat-bar bar-blue" style="width:<?php echo $totalProducts > 0 ? round($soldProducts / $totalProducts * 100) : 0; ?>%"></div>
          </div>
        </div>

        <div class="stat-card green-card">
          <div class="stat-top">
            <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
          </div>
          <div class="stat-value"><?php echo $activeListings; ?></div>
          <div class="stat-label">Anuncios activos</div>
          <div class="stat-bar-wrap">
            <div class="stat-bar bar-green"
              style="width:<?php echo $totalProducts > 0 ? round($activeListings / $totalProducts * 100) : 0; ?>%">
            </div>
          </div>
        </div>

      </div>

      <!-- ── QUICK ACTIONS ── -->
      <div class="quick-row">
        <a href="./products/crear.php" class="quick-card">
          <div class="qc-icon" style="background:var(--g100);color:var(--g700);"><i class="bi bi-plus-square-fill"></i>
          </div>
          <div>
            <div class="qc-label">Nuevo anuncio</div>
            <div class="qc-sub">Publicar producto</div>
          </div>
        </a>
        <a href="./chat/chat.php" class="quick-card">
          <div class="qc-icon" style="background:var(--y100);color:var(--y600);"><i class="bi bi-chat-dots-fill"></i>
          </div>
          <div>
            <div class="qc-label">Ver mensajes</div>
            <div class="qc-sub"><?php echo $unreadMessages > 0 ? $unreadMessages . ' sin leer' : 'Sin mensajes nuevos'; ?></div>
          </div>
        </a>
        <a href="./promociones.php" class="quick-card">
          <div class="qc-icon" style="background:#fef9c3;color:#ca8a04;"><i class="bi bi-rocket-takeoff-fill"></i></div>
          <div>
            <div class="qc-label">Promociones</div>
            <div class="qc-sub">Ver planes</div>
          </div>
        </a>
      </div>

      <!-- ── MY PRODUCTS ── -->
      <div class="section-block">
        <div class="section-head">
          <div class="section-head-left">
            <div class="section-title-main">Mis <span>productos</span></div>
            <div class="section-sub-txt">Administra y edita tus anuncios publicados</div>
            <div class="section-accent-line"></div>
          </div>
          <div class="section-head-actions">
            <div class="view-toggle">
              <button class="vt-btn active" title="Cuadrícula"><i class="bi bi-grid-3x3-gap-fill"></i></button>
              <button class="vt-btn" title="Lista"><i class="bi bi-list-ul"></i></button>
            </div>
            <a href="./products/crear.php" class="btn-outline"><i class="bi bi-plus-lg"></i> Nuevo</a>
          </div>
        </div>

        <div class="products-grid" id="productsGrid">

          <?php
          $sql = "SELECT p.*, c.name as category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE p.user_id = '$user_id'
                ORDER BY p.id DESC";
          $result = mysqli_query($conn, $sql);

          if (mysqli_num_rows($result) === 0): ?>

            <div class="empty-state" style="grid-column:1/-1;">
              <div class="empty-icon"><i class="bi bi-box-seam"></i></div>
              <div class="empty-title">No tienes productos publicados aún</div>
              <div class="empty-sub">Publica tu primer anuncio y empieza a vender<br>en tu ciudad hoy mismo.</div>
              <a href="./products/crear.php" class="btn-publish-new" style="display:inline-flex;">
                <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
                Publicar primer producto
              </a>
            </div>

          <?php else: ?>

            <?php $card_i = 0;
            while ($row = mysqli_fetch_assoc($result)):
              $card_i++; ?>

              <div class="product-card" style="animation-delay:<?php echo $card_i * 0.07; ?>s;">
                <a href="./products/detalle.php?id=<?php echo $row['id']; ?>">

                  <!-- Image slot -->
                  <div class="pc-img-slot">
                    <?php
                    $pid = $row['id'];
                    $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
                    $img_r = mysqli_query($conn, $img_q);
                    $img_row = mysqli_fetch_assoc($img_r);
                    if ($img_row): ?>
                      <img src="../../public/uploads/products/<?php echo htmlspecialchars($img_row['image_url']); ?>" alt="">
                    <?php else: ?>
                      <i class="bi bi-box-seam"></i>
                    <?php endif; ?>

                    <?php
                      $st    = strtolower($row['status'] ?? '');
                      $adm   = strtolower($row['admin_status'] ?? 'active');
                      if ($adm === 'inactive') {
                        $stClass = 'status-inactive'; $stText = '○ Inactivo';
                      } elseif ($st === 'vendido') {
                        $stClass = 'status-sold'; $stText = '● Vendido';
                      } else {
                        $stClass = 'status-active'; $stText = '● Activo';
                      }
                    ?>
                    <div class="pc-status-badge <?php echo $stClass; ?>">
                      <?php echo $stText; ?>
                    </div>

                    <div class="pc-actions-top">
                      <a href="./products/edit.php?id=<?php echo $row['id']; ?>" class="pc-action-btn edit" title="Editar">
                        <i class="bi bi-pencil-fill"></i>
                      </a>
                      <a href="../controllers/product_delete_action.php?id=<?php echo $row['id']; ?>"
                        class="pc-action-btn del" title="Eliminar" onclick="return confirm('¿Eliminar este producto?')">
                        <i class="bi bi-trash-fill"></i>
                      </a>
                    </div>
                  </div>

                  <!-- Card body -->
                  <div class="pc-body">
                    <?php if (!empty($row['category_name'])): ?>
                      <div class="pc-category"><i
                          class="bi bi-tag-fill"></i><?php echo htmlspecialchars($row['category_name']); ?></div>
                    <?php endif; ?>

                    <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                    <div class="pc-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>

                    <div class="pc-meta">
                      <?php if (!empty($row['condition_type'])): ?>
                        <div class="pc-meta-row">
                          <i class="bi bi-award-fill"></i>
                          <?php
                            $ct = strtolower($row['condition_type']);
                            echo $ct === 'nuevo' ? 'Nuevo' : ($ct === 'reacondicionado' ? 'Reacondicionado' : 'Usado');
                          ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($row['latitude']) && !empty($row['longitude'])): ?>
                        <div class="pc-meta-row">
                          <i class="bi bi-geo-alt-fill"></i>
                          <?php echo round($row['latitude'], 4); ?>, <?php echo round($row['longitude'], 4); ?>
                        </div>
                      <?php endif; ?>
                      <div class="pc-meta-row">
                        <i class="bi bi-clock-fill"></i>
                        <?php echo !empty($row['created_at']) ? date('d M Y', strtotime($row['created_at'])) : 'Publicado'; ?>
                      </div>
                    </div>

                    <!-- Footer action buttons -->
                    <div class="pc-footer">
                      <a href="./products/edit.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-edit">
                        <i class="bi bi-pencil"></i> Editar
                      </a>
                      <a href="../controllers/product_delete_action.php?id=<?php echo $row['id']; ?>"
                        class="pc-btn pc-btn-del" onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                        <i class="bi bi-trash"></i> Eliminar
                      </a>
                    </div>
                  </div>
                </a>
              </div>

            <?php endwhile; ?>
          <?php endif; ?>

        </div>
      </div>

      <script src="../../public/js/dashboard.js"></script>

</body>

</html>