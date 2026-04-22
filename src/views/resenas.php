<?php
require_once(__DIR__ . '/../config/conexion.php');
session_start();
if (!isset($_SESSION['user'])) {
  header('Location: auth/login.php');
  exit();
}
$user        = $_SESSION['user'];
$user_id     = (int) $user['id'];
$isLoggedIn  = true;
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

// Sidebar badge: total productos del vendedor
$totalProducts = 0;
$rc = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'");
if ($rc) $totalProducts = (int) mysqli_fetch_assoc($rc)['total'];

/* ─────────────────────────────────────────────
   RESEÑAS: solo las recibidas en productos del
   vendedor actual. Nunca de otros vendedores.
───────────────────────────────────────────── */
$reviews_sql = "SELECT r.id, r.rating, r.comment, r.created_at,
                       u.full_name, u.profile_photo,
                       p.id AS product_id, p.title AS product_title,
                       (SELECT pi.image_url FROM product_images pi
                         WHERE pi.product_id = p.id ORDER BY pi.id ASC LIMIT 1) AS product_thumb
                FROM reviews r
                JOIN products p ON p.id = r.product_id
                JOIN users u    ON u.id = r.user_id
                WHERE p.user_id = ?
                ORDER BY r.created_at DESC";
$stmt = mysqli_prepare($conn, $reviews_sql);
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$reviews_result = mysqli_stmt_get_result($stmt);

$reviews = [];
while ($row = mysqli_fetch_assoc($reviews_result)) {
  $reviews[] = $row;
}

$total_reviews = count($reviews);

// Promedio y distribución
$sum_rating = 0;
$breakdown  = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
foreach ($reviews as $rv) {
  $rt = (int) $rv['rating'];
  $sum_rating += $rt;
  if (isset($breakdown[$rt])) $breakdown[$rt]++;
}
$avg_rating = $total_reviews > 0 ? round($sum_rating / $total_reviews, 1) : 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reseñas recibidas – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/dashborad.css">
  <link rel="stylesheet" href="../../public/css/output.css">
  <link rel="stylesheet" href="../../public/css/sidebar.css">
  <style>
    :root {
      --ink:   #0f172a;
      --ink2:  #334155;
      --ink3:  #94a3b8;
      --bord:  #e5e7eb;
      --g50:   #f0fdf4;
      --g200:  #a8e6bf;
      --g500:  #22c55e;
      --g600:  #16a34a;
      --g700:  #15803d;
      --y500:  #eab308;
    }

    /* ══ SUMMARY ══ */
    .rev-summary {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 28px;
      background: #fff;
      border: 1.5px solid var(--bord);
      border-radius: 18px;
      padding: 28px;
      margin-bottom: 24px;
    }
    .rev-summary-left {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      border-right: 1px solid var(--bord);
      padding-right: 24px;
    }
    .big-score {
      font-size: 3.6rem;
      font-weight: 800;
      color: var(--ink);
      line-height: 1;
    }
    .big-score .of { font-size: 1rem; color: var(--ink3); font-weight: 600; }
    .big-stars {
      font-size: 1.1rem;
      color: var(--y500);
      margin: 8px 0 6px;
      letter-spacing: 2px;
    }
    .big-stars .bi-star { color: #e5e7eb; }
    .big-total {
      font-size: .85rem;
      color: var(--ink3);
      font-weight: 600;
    }
    .big-trust {
      margin-top: 10px;
      font-size: .7rem;
      background: var(--g50);
      color: var(--g700);
      padding: 4px 10px;
      border-radius: 999px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .rev-summary-right { display: flex; flex-direction: column; gap: 8px; justify-content: center; }
    .bar-row {
      display: grid;
      grid-template-columns: 54px 1fr 36px;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      padding: 3px 6px;
      border-radius: 8px;
      transition: background .15s;
    }
    .bar-row:hover { background: #f9fafb; }
    .bar-row.active { background: var(--g50); }
    .bar-label {
      font-size: .82rem;
      font-weight: 700;
      color: var(--ink2);
      display: flex;
      align-items: center;
      gap: 3px;
    }
    .bar-label .bi { color: var(--y500); font-size: .78rem; }
    .bar-track {
      height: 8px;
      background: #f1f5f9;
      border-radius: 999px;
      overflow: hidden;
    }
    .bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--y500), #f59e0b);
      border-radius: 999px;
      transition: width .4s;
    }
    .bar-count { font-size: .78rem; color: var(--ink3); text-align: right; font-weight: 600; }

    /* ══ FILTERS ══ */
    .rev-filters {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 18px;
    }
    .chip {
      border: 1.5px solid var(--bord);
      background: #fff;
      color: var(--ink2);
      padding: 8px 14px;
      border-radius: 999px;
      font-size: .8rem;
      font-weight: 600;
      cursor: pointer;
      transition: all .15s;
      display: inline-flex;
      align-items: center;
      gap: 5px;
    }
    .chip:hover { border-color: var(--g200); color: var(--g700); }
    .chip.active {
      background: var(--g500);
      border-color: var(--g500);
      color: #fff;
    }
    .chip .bi-star-fill { color: var(--y500); font-size: .75rem; }
    .chip.active .bi-star-fill { color: #fff; }

    /* ══ LIST ══ */
    .rev-list { display: flex; flex-direction: column; gap: 14px; }
    .rev-card {
      background: #fff;
      border: 1.5px solid var(--bord);
      border-radius: 14px;
      padding: 18px 20px;
      transition: all .2s;
    }
    .rev-card:hover {
      border-color: var(--g200);
      box-shadow: 0 8px 24px rgba(0,0,0,.05);
    }
    .rev-card.hidden { display: none; }
    .rev-top {
      display: flex;
      gap: 14px;
      align-items: flex-start;
    }
    .rev-av {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--g500), var(--g700));
      color: #fff;
      font-weight: 700;
      font-size: 1.05rem;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      overflow: hidden;
    }
    .rev-av img { width: 100%; height: 100%; object-fit: cover; }
    .rev-main { flex: 1; min-width: 0; }
    .rev-head-line {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
    }
    .rev-name {
      font-size: .95rem;
      font-weight: 700;
      color: var(--ink);
    }
    .rev-date {
      font-size: .75rem;
      color: var(--ink3);
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }
    .rev-stars {
      color: var(--y500);
      font-size: .88rem;
      margin-top: 4px;
      letter-spacing: 2px;
    }
    .rev-stars .bi-star { color: #e5e7eb; }
    .rev-comment {
      margin-top: 8px;
      font-size: .88rem;
      color: var(--ink2);
      line-height: 1.55;
    }
    .rev-comment--empty {
      color: var(--ink3);
      font-style: italic;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .rev-product {
      margin-top: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px;
      background: #f9fafb;
      border-radius: 10px;
      text-decoration: none;
      color: inherit;
      transition: background .15s;
      border: 1px solid transparent;
    }
    .rev-product:hover {
      background: var(--g50);
      border-color: var(--g200);
    }
    .rev-product-img {
      width: 42px;
      height: 42px;
      border-radius: 8px;
      background: #fff;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      flex-shrink: 0;
      border: 1px solid var(--bord);
    }
    .rev-product-img img { width: 100%; height: 100%; object-fit: cover; }
    .rev-product-img .bi { color: #cbd5e1; font-size: 1.1rem; }
    .rev-product-info { min-width: 0; }
    .rev-product-label {
      font-size: .68rem;
      color: var(--ink3);
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: .04em;
    }
    .rev-product-title {
      font-size: .85rem;
      font-weight: 700;
      color: var(--ink);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .rev-product-link {
      margin-left: auto;
      color: var(--g600);
      font-size: .85rem;
    }

    /* ══ EMPTY ══ */
    .rev-empty {
      text-align: center;
      padding: 60px 20px;
      background: #fff;
      border: 1.5px dashed var(--bord);
      border-radius: 16px;
    }
    .rev-empty i {
      font-size: 3rem;
      color: #e5e7eb;
      display: block;
      margin-bottom: 12px;
    }
    .rev-empty h3 {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--ink2);
      margin-bottom: 6px;
    }
    .rev-empty p {
      font-size: .85rem;
      color: var(--ink3);
      max-width: 380px;
      margin: 0 auto 18px;
    }
    .rev-empty a {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--g500);
      color: #fff;
      padding: 10px 20px;
      border-radius: 10px;
      font-weight: 700;
      font-size: .85rem;
      text-decoration: none;
    }
    .rev-empty a:hover { background: var(--g600); }

    .rev-no-match {
      text-align: center;
      padding: 40px 20px;
      font-size: .9rem;
      color: var(--ink3);
    }

    @media (max-width: 768px) {
      .rev-summary { grid-template-columns: 1fr; padding: 22px; }
      .rev-summary-left {
        border-right: none;
        border-bottom: 1px solid var(--bord);
        padding: 0 0 20px;
      }
    }
  </style>
</head>
<body>

  <?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

  <div class="page-shell">
    <?php $activeTab = 'resenas'; include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

      <div class="pg-header">
        <div>
          <div class="pg-breadcrumb">
            <a href="./dashboard.php">Dashboard</a>
            <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
            <span>Reseñas</span>
          </div>
          <h1 class="pg-title">Reseñas <span>recibidas</span></h1>
          <p class="pg-subtitle">
            <?php if ($total_reviews > 0): ?>
              Tienes <strong><?= $total_reviews ?></strong> reseña<?= $total_reviews !== 1 ? 's' : '' ?> en tus productos.
            <?php else: ?>
              Aquí verás las reseñas que los compradores dejen en tus productos.
            <?php endif; ?>
          </p>
        </div>
      </div>

      <?php if ($total_reviews === 0): ?>
        <div class="rev-empty">
          <i class="bi bi-chat-heart"></i>
          <h3>Aún no has recibido reseñas</h3>
          <p>Cuando un comprador deje una reseña en alguno de tus productos, aparecerá aquí.</p>
          <a href="./products/mis_productos.php"><i class="bi bi-box-seam"></i> Ver mis productos</a>
        </div>
      <?php else: ?>

        <!-- ══ RESUMEN ══ -->
        <div class="rev-summary">
          <div class="rev-summary-left">
            <div class="big-score"><?= $avg_rating ?><span class="of"> / 5</span></div>
            <div class="big-stars">
              <?php
              $full  = floor($avg_rating);
              $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
              $empty = 5 - $full - $half;
              for ($s = 0; $s < $full;  $s++) echo '<i class="bi bi-star-fill"></i>';
              if ($half)                      echo '<i class="bi bi-star-half"></i>';
              for ($s = 0; $s < $empty; $s++) echo '<i class="bi bi-star"></i>';
              ?>
            </div>
            <div class="big-total">
              Basado en <?= $total_reviews ?> reseña<?= $total_reviews !== 1 ? 's' : '' ?>
            </div>
            <span class="big-trust">
              <i class="bi bi-shield-check-fill"></i> Reseñas verificadas
            </span>
          </div>
          <div class="rev-summary-right">
            <?php for ($star = 5; $star >= 1; $star--):
              $cnt = $breakdown[$star];
              $pct = $total_reviews > 0 ? round(($cnt / $total_reviews) * 100) : 0;
            ?>
              <div class="bar-row" data-filter="<?= $star ?>">
                <span class="bar-label"><?= $star ?> <i class="bi bi-star-fill"></i></span>
                <div class="bar-track"><div class="bar-fill" style="width: <?= $pct ?>%"></div></div>
                <span class="bar-count"><?= $cnt ?></span>
              </div>
            <?php endfor; ?>
          </div>
        </div>

        <!-- ══ FILTROS ══ -->
        <div class="rev-filters">
          <button class="chip active" data-filter="all">Todas (<?= $total_reviews ?>)</button>
          <?php for ($star = 5; $star >= 1; $star--):
            if ($breakdown[$star] === 0) continue;
          ?>
            <button class="chip" data-filter="<?= $star ?>">
              <?= $star ?> <i class="bi bi-star-fill"></i> (<?= $breakdown[$star] ?>)
            </button>
          <?php endfor; ?>
        </div>

        <!-- ══ LISTA ══ -->
        <div class="rev-list" id="revList">
          <?php foreach ($reviews as $rev):
            $initial = strtoupper(mb_substr($rev['full_name'], 0, 1));
            $rating  = (int) $rev['rating'];
            $pt = $rev['product_thumb'] ?? '';
            $pp = $rev['profile_photo'] ?? '';
          ?>
            <div class="rev-card" data-rating="<?= $rating ?>">
              <div class="rev-top">
                <div class="rev-av">
                  <?php if (!empty($pp)): ?>
                    <img src="../../public/uploads/avatars/<?= htmlspecialchars($pp) ?>" alt="">
                  <?php else: ?>
                    <?= $initial ?>
                  <?php endif; ?>
                </div>
                <div class="rev-main">
                  <div class="rev-head-line">
                    <div class="rev-name"><?= htmlspecialchars($rev['full_name']) ?></div>
                    <div class="rev-date">
                      <i class="bi bi-calendar3"></i>
                      <?= date('d M Y', strtotime($rev['created_at'])) ?>
                    </div>
                  </div>
                  <div class="rev-stars">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                      <i class="bi bi-star<?= $s <= $rating ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                  </div>
                  <?php if (!empty(trim($rev['comment']))): ?>
                    <p class="rev-comment"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                  <?php else: ?>
                    <p class="rev-comment rev-comment--empty">
                      <i class="bi bi-chat-dots"></i> Sin comentario adicional
                    </p>
                  <?php endif; ?>

                  <a class="rev-product" href="./products/detalle.php?id=<?= (int) $rev['product_id'] ?>">
                    <div class="rev-product-img">
                      <?php if (!empty($pt)): ?>
                        <img src="../../public/uploads/products/<?= htmlspecialchars($pt) ?>" alt="">
                      <?php else: ?>
                        <i class="bi bi-box-seam"></i>
                      <?php endif; ?>
                    </div>
                    <div class="rev-product-info">
                      <div class="rev-product-label">Producto reseñado</div>
                      <div class="rev-product-title"><?= htmlspecialchars($rev['product_title']) ?></div>
                    </div>
                    <i class="bi bi-arrow-right rev-product-link"></i>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="rev-no-match" id="noMatch" style="display:none;">
          <i class="bi bi-funnel" style="font-size:1.5rem;display:block;margin-bottom:6px;"></i>
          No hay reseñas con ese filtro.
        </div>

      <?php endif; ?>

    </main>
  </div>

  <script>
    const chips    = document.querySelectorAll('.chip');
    const barRows  = document.querySelectorAll('.bar-row');
    const cards    = document.querySelectorAll('.rev-card');
    const noMatch  = document.getElementById('noMatch');
    const list     = document.getElementById('revList');

    function applyFilter(filter) {
      chips.forEach(c => c.classList.toggle('active', c.dataset.filter === filter));
      barRows.forEach(b => b.classList.toggle('active', filter !== 'all' && b.dataset.filter === filter));
      let shown = 0;
      cards.forEach(card => {
        const show = filter === 'all' || card.dataset.rating === filter;
        card.classList.toggle('hidden', !show);
        if (show) shown++;
      });
      if (noMatch) noMatch.style.display = shown === 0 ? 'block' : 'none';
    }

    chips.forEach(chip => chip.addEventListener('click', () => applyFilter(chip.dataset.filter)));
    barRows.forEach(row => row.addEventListener('click', () => applyFilter(row.dataset.filter)));
  </script>

</body>
</html>
