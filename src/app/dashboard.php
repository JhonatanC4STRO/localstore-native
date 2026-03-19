<?php require_once("../config/conexion.php"); ?>
<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ./auth/register.php");
    exit();
}

$isLoggedIn = isset($_SESSION['user']);
$user       = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName   = $isLoggedIn ? explode(' ', $user['full_name'])[0] : '';
$user_id    = $_SESSION['user']['id'];

// contar productos totales y activos para mostrar en el dashboard
$totalProducts = 0;
$activeListings = 0;
$sql_count = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'";
$r = mysqli_query($conn, $sql_count);
if ($r) {
    $row_c = mysqli_fetch_assoc($r);
    $totalProducts = $row_c['total'];
}

$sql_active = "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id' AND status = 1";
$r2 = mysqli_query($conn, $sql_active);
if ($r2) {
    $row_a = mysqli_fetch_assoc($r2);
    $activeListings = $row_a['total'];
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
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../style/dashborad.css">
    <link rel="stylesheet" href="../output.css">
</head>

<body>

    <!-- header -->
    <header class="topbar">
        <a href="./inde.php" class=""><img class="h-28 w-28" src="./Logo de Comercio Local.png" alt=""></a>


        <div class="tb-search">
            <input type="text" placeholder="Buscar productos, anuncios...">
            <button><i class="bi bi-search"></i></button>
        </div>

        <div class="tb-spacer"></div>

        <div class="tb-actions">

            <div class="tb-icon-btn">
                <i class="bi bi-bell"></i>
                <div class="notif-dot"></div>
            </div>
            <div class="tb-icon-btn">
                <i class="bi bi-chat-dots"></i>
            </div>
            <div class="tb-icon-btn">
                <i class="bi bi-question-circle"></i>
            </div>

            <!-- User chip + dropdown — LOGIC PRESERVED -->
            <?php if ($isLoggedIn): ?>
                <div class="user-menu-wrap" id="userMenuWrap">
                    <div class="user-chip" id="userChip">
                        <div style="position:relative;">
                            <div class="user-avatar"><?php echo $userInitial; ?></div>
                            <div class="online-dot"></div>
                        </div>
                        <div class="uc-info">
                            <div class="uc-greeting">Hola,</div>
                            <div class="uc-name"><?php echo htmlspecialchars($userName); ?></div>
                        </div>
                        <i class="bi bi-chevron-down uc-arrow"></i>
                    </div>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="ud-head">
                            <div class="ud-av"><?php echo $userInitial; ?></div>
                            <div>
                                <div class="ud-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="ud-email"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                        <div class="ud-body">
                            <a class="ud-lnk" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
                            <a class="ud-lnk" href="./crear.php"><i class="bi bi-plus-square"></i> Publicar producto</a>
                            <a class="ud-lnk" href="#"><i class="bi bi-box-seam"></i> Mis anuncios</a>
                            <a class="ud-lnk" href="#"><i class="bi bi-person-circle"></i> Mi perfil</a>
                            <div class="ud-sep"></div>
                            <a class="ud-logout" href="../controller/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </header>

    <div class="page-shell">

        <!-- ══════════════════════════════════
       SIDEBAR
  ══════════════════════════════════ -->
        <aside class="sidebar">
            <div class="sb-section-label">Principal</div>

            <a class="sb-link active" href="#"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
            <a class="sb-link" href="#">
                <i class="bi bi-box-seam"></i> Mis productos
                <span class="sb-badge"><?php echo $totalProducts; ?></span>
            </a>
            <a class="sb-link" href="#">
                <i class="bi bi-chat-dots"></i> Mensajes
                <span class="sb-badge yellow">5</span>
            </a>
            <a class="sb-link" href="#"><i class="bi bi-graph-up-arrow"></i> Ventas</a>
            <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>

            <div class="sb-divider"></div>
            <div class="sb-section-label">Cuenta</div>

            <a class="sb-link" href="#"><i class="bi bi-person-circle"></i> Mi perfil</a>
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

        <!-- ══════════════════════════════════
       MAIN
  ══════════════════════════════════ -->
        <main class="main-content">

            <!-- Page header -->
            <div class="pg-header">
                <div>
                    <div class="pg-breadcrumb">
                        <a href="./inde.php">Inicio</a>
                        <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
                        <span>Dashboard</span>
                    </div>
                    <h1 class="pg-title">
                        Panel de <span>vendedor</span>
                    </h1>
                    <p class="pg-subtitle">
                        Bienvenido, <strong><?php echo htmlspecialchars($user['full_name']); ?></strong> — aquí está el resumen de tu actividad.
                    </p>
                </div>
                <a href="./crear.php" class="btn-publish-new">
                    <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
                    Publicar producto
                </a>
            </div>

            <!-- ── STATS CARDS ── -->
            <div class="stats-grid">

                <div class="stat-card green-card">
                    <div class="stat-top">
                        <div class="stat-icon si-green"><i class="bi bi-box-seam-fill"></i></div>
                        <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +2</div>
                    </div>
                    <!-- LOGIC PRESERVED: PHP count query -->
                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                    <div class="stat-label">Total de productos</div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar bar-green" style="width:<?php echo min($totalProducts * 10, 100); ?>%"></div>
                    </div>
                </div>

                <div class="stat-card yellow-card">
                    <div class="stat-top">
                        <div class="stat-icon si-yellow"><i class="bi bi-chat-dots-fill"></i></div>
                        <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +3</div>
                    </div>
                    <div class="stat-value">5</div>
                    <div class="stat-label">Mensajes recibidos</div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar bar-yellow" style="width:50%"></div>
                    </div>
                </div>

                <div class="stat-card blue-card">
                    <div class="stat-top">
                        <div class="stat-icon si-blue"><i class="bi bi-eye-fill"></i></div>
                        <div class="stat-trend trend-up"><i class="bi bi-arrow-up"></i> +12%</div>
                    </div>
                    <div class="stat-value">248</div>
                    <div class="stat-label">Vistas en productos</div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar bar-blue" style="width:68%"></div>
                    </div>
                </div>

                <div class="stat-card green-card">
                    <div class="stat-top">
                        <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
                        <!-- LOGIC PRESERVED: active listings count -->
                        <div class="stat-trend trend-flat"><i class="bi bi-dash"></i> estable</div>
                    </div>
                    <div class="stat-value"><?php echo $activeListings; ?></div>
                    <div class="stat-label">Anuncios activos</div>
                    <div class="stat-bar-wrap">
                        <div class="stat-bar bar-green" style="width:<?php echo $totalProducts > 0 ? round($activeListings / $totalProducts * 100) : 0; ?>%"></div>
                    </div>
                </div>

            </div>

            <!-- ── QUICK ACTIONS ── -->
            <div class="quick-row">
                <a href="./crear.php" class="quick-card">
                    <div class="qc-icon" style="background:var(--g100);color:var(--g700);"><i class="bi bi-plus-square-fill"></i></div>
                    <div>
                        <div class="qc-label">Nuevo anuncio</div>
                        <div class="qc-sub">Publicar producto</div>
                    </div>
                </a>
                <a href="#" class="quick-card">
                    <div class="qc-icon" style="background:var(--y100);color:var(--y600);"><i class="bi bi-chat-dots-fill"></i></div>
                    <div>
                        <div class="qc-label">Ver mensajes</div>
                        <div class="qc-sub">5 sin leer</div>
                    </div>
                </a>
                <a href="#" class="quick-card">
                    <div class="qc-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div class="qc-label">Estadísticas</div>
                        <div class="qc-sub">Ver rendimiento</div>
                    </div>
                </a>
                <a href="#" class="quick-card">
                    <div class="qc-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="bi bi-star-fill"></i></div>
                    <div>
                        <div class="qc-label">Destacar</div>
                        <div class="qc-sub">Planes premium</div>
                    </div>
                </a>
            </div>

            <!-- ── MY PRODUCTS — LOGIC PRESERVED ── -->
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
                        <a href="./crear.php" class="btn-outline"><i class="bi bi-plus-lg"></i> Nuevo</a>
                    </div>
                </div>

                <div class="products-grid" id="productsGrid">

                    <?php
                    /* ── LOGIC 100% PRESERVED: original query ── */
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
                            <a href="./crear.php" class="btn-publish-new" style="display:inline-flex;">
                                <div class="btn-icon"><i class="bi bi-plus-lg"></i></div>
                                Publicar primer producto
                            </a>
                        </div>

                    <?php else: ?>

                        <?php $card_i = 0;
                        while ($row = mysqli_fetch_assoc($result)): $card_i++; ?>

                            <div class="product-card" style="animation-delay:<?php echo $card_i * 0.07; ?>s;">

                                <!-- Image slot -->
                                <div class="pc-img-slot">
                                    <?php
                                    /* LOGIC PRESERVED: fetch product images */
                                    $pid = $row['id'];
                                    $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
                                    $img_r = mysqli_query($conn, $img_q);
                                    $img_row = mysqli_fetch_assoc($img_r);
                                    if ($img_row): ?>
                                        <img src="./productos/uploads/<?php echo htmlspecialchars($img_row['image_url']); ?>" alt="">
                                    <?php else: ?>
                                        <i class="bi bi-box-seam"></i>
                                    <?php endif; ?>

                                    <!-- Status badge -->
                                    <div class="pc-status-badge <?php echo ($row['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ($row['status'] == 1) ? '● Activo' : '○ Inactivo'; ?>
                                    </div>

                                    <!-- Quick action buttons on image -->
                                    <div class="pc-actions-top">
                                        <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn edit" title="Editar">
                                            <i class="bi bi-pencil-fill"></i>
                                        </a>
                                        <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-action-btn del" title="Eliminar"
                                            onclick="return confirm('¿Eliminar este producto?')">
                                            <i class="bi bi-trash-fill"></i>
                                        </a>
                                    </div>
                                </div>

                                <!-- Card body -->
                                <div class="pc-body">
                                    <?php if (!empty($row['category_name'])): ?>
                                        <div class="pc-category"><i class="bi bi-tag-fill"></i><?php echo htmlspecialchars($row['category_name']); ?></div>
                                    <?php endif; ?>

                                    <div class="pc-title"><?php echo htmlspecialchars($row['title']); ?></div>
                                    <div class="pc-price">$<?php echo number_format($row['price'], 0, ',', '.'); ?></div>

                                    <div class="pc-meta">
                                        <?php if (!empty($row['condition_type'])): ?>
                                            <div class="pc-meta-row">
                                                <i class="bi bi-award-fill"></i>
                                                <?php echo $row['condition_type'] === 'new' ? 'Nuevo' : 'Usado'; ?>
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
                                        <a href="./edit_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-edit">
                                            <i class="bi bi-pencil"></i> Editar
                                        </a>
                                        <a href="./delete_product.php?id=<?php echo $row['id']; ?>" class="pc-btn pc-btn-del"
                                            onclick="return confirm('¿Seguro que deseas eliminar este producto?')">
                                            <i class="bi bi-trash"></i> Eliminar
                                        </a>
                                    </div>
                                </div>

                            </div>

                        <?php endwhile; ?>
                    <?php endif; ?>

                </div>
            </div>

            <!-- ── RECENT ACTIVITY ──
            <div class="activity-strip">
                <div class="as-header">
                    <div class="as-title">Actividad reciente</div>
                    <a href="#" class="as-see-all">Ver todo <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="activity-list">
                    <div class="act-item">
                        <div class="act-icon ai-blue"><i class="bi bi-eye-fill"></i></div>
                        <div class="act-text">
                            <div class="act-title">Alguien vio tu anuncio "iPhone 13"</div>
                            <div class="act-sub">Chapinero, Bogotá</div>
                        </div>
                        <div class="act-time">Hace 5 min</div>
                    </div>
                    <div class="act-item">
                        <div class="act-icon ai-yellow"><i class="bi bi-chat-dots-fill"></i></div>
                        <div class="act-text">
                            <div class="act-title">Nuevo mensaje de Carlos A.</div>
                            <div class="act-sub">"¿Está disponible el viernes?"</div>
                        </div>
                        <div class="act-time">Hace 23 min</div>
                    </div>
                    <div class="act-item">
                        <div class="act-icon ai-green"><i class="bi bi-check-circle-fill"></i></div>
                        <div class="act-text">
                            <div class="act-title">Tu anuncio fue aprobado</div>
                            <div class="act-sub">Bicicleta MTB Trek 29"</div>
                        </div>
                        <div class="act-time">Hace 1 hora</div>
                    </div>
                </div>
            </div> -->

        </main>
    </div>

    <script>
        /* ── User dropdown toggle ── */
        const wrap = document.getElementById('userMenuWrap');
        const chip = document.getElementById('userChip');
        if (chip) {
            chip.addEventListener('click', e => {
                e.stopPropagation();
                wrap.classList.toggle('open');
            });
            document.addEventListener('click', e => {
                if (!wrap.contains(e.target)) wrap.classList.remove('open');
            });
            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') wrap.classList.remove('open');
            });
        }

        /* ── View toggle ── */
        document.querySelectorAll('.vt-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.vt-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const grid = document.getElementById('productsGrid');
                if (btn.querySelector('.bi-list-ul')) {
                    grid.style.gridTemplateColumns = '1fr';
                    grid.querySelectorAll('.product-card').forEach(c => {
                        c.style.display = 'flex';
                    });
                } else {
                    grid.style.gridTemplateColumns = '';
                    grid.querySelectorAll('.product-card').forEach(c => {
                        c.style.display = '';
                    });
                }
            });
        });
    </script>

</body>

</html>