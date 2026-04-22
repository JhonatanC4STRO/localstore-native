<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: auth/login.php');
  exit();
}
$user    = $_SESSION['user'];
$user_id = (int) $user['id'];
$isLoggedIn  = true;
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

// Contar productos para sidebar badge
$totalProducts = 0;
$rc = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'");
if ($rc) $totalProducts = mysqli_fetch_assoc($rc)['total'];

// Asegurar tabla
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_product` (`user_id`, `product_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Obtener favoritos con info de producto
$sql = "SELECT p.*, c.name AS category_name, f.created_at AS fav_date,
               (SELECT pi.image_url FROM product_images pi WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS image_url
        FROM favorites f
        JOIN products p ON p.id = f.product_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$favCount = mysqli_num_rows($result);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mis Favoritos – ComercioLocal</title>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/dashborad.css">
  <link rel="stylesheet" href="../../public/css/output.css">
  <link rel="stylesheet" href="../../public/css/sidebar.css">
  <style>
    .fav-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 20px;
    }
    .fav-card {
      background: #fff;
      border-radius: 16px;
      border: 1.5px solid var(--border, #e5e7eb);
      overflow: hidden;
      transition: all .25s;
      position: relative;
    }
    .fav-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(0,0,0,.08);
      border-color: var(--g200, #a8e6bf);
    }
    .fav-card a { display: block; text-decoration: none; color: inherit; }
    .fav-img {
      position: relative;
      height: 180px;
      background: #f9fafb;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }
    .fav-img img { width: 100%; height: 100%; object-fit: cover; }
    .fav-img .placeholder-icon { font-size: 2.5rem; color: #ddd; }
    .fav-remove {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 2;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      border: none;
      background: rgba(255,255,255,.9);
      color: #ef4444;
      font-size: 1rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all .2s;
      backdrop-filter: blur(4px);
    }
    .fav-remove:hover { background: #fee2e2; transform: scale(1.1); }
    .fav-cond {
      position: absolute;
      top: 10px;
      left: 10px;
      font-size: .65rem;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 20px;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .fav-body { padding: 14px 16px 16px; }
    .fav-cat { font-size: .72rem; color: var(--ink3, #94a3b8); margin-bottom: 4px; }
    .fav-cat i { margin-right: 3px; }
    .fav-title { font-size: .88rem; font-weight: 700; color: var(--ink, #0f172a); margin-bottom: 4px; line-height: 1.3; }
    .fav-price { font-size: 1.05rem; font-weight: 800; color: var(--g700, #15803d); margin-bottom: 8px; }
    .fav-meta { display: flex; gap: 12px; font-size: .72rem; color: var(--ink3, #94a3b8); }
    .fav-meta i { margin-right: 3px; }
    .fav-status {
      display: inline-block;
      font-size: .65rem;
      font-weight: 600;
      padding: 2px 8px;
      border-radius: 10px;
      margin-top: 8px;
    }
    .fav-status.available { background: var(--g50, #f0fdf4); color: var(--g700, #15803d); }
    .fav-status.sold { background: #eef2ff; color: #6366f1; }
    .fav-status.inactive { background: #f3f4f6; color: #6b7280; }

    .empty-favs {
      text-align: center;
      padding: 60px 20px;
      grid-column: 1 / -1;
    }
    .empty-favs i { font-size: 3rem; color: #e5e7eb; display: block; margin-bottom: 16px; }
    .empty-favs h3 { font-size: 1.1rem; font-weight: 700; color: var(--ink2, #334155); margin-bottom: 6px; }
    .empty-favs p { font-size: .85rem; color: var(--ink3, #94a3b8); margin-bottom: 20px; }
    .empty-favs a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--g500, #22c55e);
      color: #fff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 700;
      font-size: .85rem;
      text-decoration: none;
      transition: background .2s;
    }
    .empty-favs a:hover { background: var(--g600, #16a34a); }
  </style>
</head>
<body>

  <?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

  <div class="page-shell">
    <?php $activeTab = 'favoritos'; include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

      <div class="pg-header">
        <div>
          <div class="pg-breadcrumb">
            <a href="./home.php">Inicio</a>
            <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
            <span>Favoritos</span>
          </div>
          <h1 class="pg-title">
            Mis <span>favoritos</span>
          </h1>
          <p class="pg-subtitle">
            <?php if ($favCount > 0): ?>
              Tienes <strong><?php echo $favCount; ?></strong> producto<?php echo $favCount !== 1 ? 's' : ''; ?> guardado<?php echo $favCount !== 1 ? 's' : ''; ?>.
            <?php else: ?>
              Guarda productos que te interesen para verlos luego.
            <?php endif; ?>
          </p>
        </div>
      </div>

      <div class="fav-grid">
        <?php if ($favCount === 0): ?>
          <div class="empty-favs">
            <i class="bi bi-heart"></i>
            <h3>No tienes favoritos aun</h3>
            <p>Explora productos y toca el corazon para guardarlos aqui.</p>
            <a href="./home.php"><i class="bi bi-search"></i> Explorar productos</a>
          </div>
        <?php else: ?>
          <?php while ($row = mysqli_fetch_assoc($result)):
            $ct = strtolower($row['condition_type'] ?? '');
            $condClass = $ct === 'nuevo' ? 'badge-new' : ($ct === 'reacondicionado' ? 'badge-refurbished' : 'badge-used');
            $condText  = $ct === 'nuevo' ? 'Nuevo' : ($ct === 'reacondicionado' ? 'Reacondicionado' : 'Usado');
            $st = strtolower($row['status'] ?? '');
            $stClass = $st === 'disponible' ? 'available' : ($st === 'vendido' ? 'sold' : 'inactive');
            $stText  = $st === 'disponible' ? 'Disponible' : ($st === 'vendido' ? 'Vendido' : 'Inactivo');
            $price = number_format($row['price'], 0, ',', '.');
          ?>
            <div class="fav-card" data-product-id="<?php echo $row['id']; ?>">
              <button class="fav-remove" onclick="removeFav(this, <?php echo $row['id']; ?>)" title="Quitar de favoritos">
                <i class="bi bi-heart-fill"></i>
              </button>
              <a href="./products/detalle.php?id=<?php echo $row['id']; ?>">
                <div class="fav-img">
                  <?php if (!empty($row['image_url'])): ?>
                    <img src="../../public/uploads/products/<?php echo htmlspecialchars($row['image_url']); ?>" alt="">
                  <?php else: ?>
                    <i class="bi bi-box-seam placeholder-icon"></i>
                  <?php endif; ?>
                  <div class="fav-cond <?php echo $condClass; ?>"><?php echo $condText; ?></div>
                </div>
                <div class="fav-body">
                  <?php if (!empty($row['category_name'])): ?>
                    <div class="fav-cat"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars($row['category_name']); ?></div>
                  <?php endif; ?>
                  <div class="fav-title"><?php echo htmlspecialchars($row['title']); ?></div>
                  <div class="fav-price">$<?php echo $price; ?></div>
                  <div class="fav-meta">
                    <span><i class="bi bi-clock-fill"></i> <?php echo date('d M Y', strtotime($row['fav_date'])); ?></span>
                  </div>
                  <div class="fav-status <?php echo $stClass; ?>"><?php echo $stText; ?></div>
                </div>
              </a>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>

    </main>
  </div>

  <script>
    async function removeFav(btn, productId) {
      btn.disabled = true;
      try {
        const res = await fetch('../../src/api/favorites/toggle.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ product_id: productId })
        });
        const data = await res.json();
        if (data.ok && !data.favorited) {
          const card = btn.closest('.fav-card');
          card.style.transition = 'opacity .3s, transform .3s';
          card.style.opacity = '0';
          card.style.transform = 'scale(.95)';
          setTimeout(() => {
            card.remove();
            // Verificar si queda vacio
            const grid = document.querySelector('.fav-grid');
            if (!grid.querySelector('.fav-card')) {
              grid.innerHTML = `
                <div class="empty-favs">
                  <i class="bi bi-heart"></i>
                  <h3>No tienes favoritos aun</h3>
                  <p>Explora productos y toca el corazon para guardarlos aqui.</p>
                  <a href="./home.php"><i class="bi bi-search"></i> Explorar productos</a>
                </div>`;
            }
          }, 300);
        }
      } catch (e) {
        btn.disabled = false;
      }
    }
  </script>

</body>
</html>
