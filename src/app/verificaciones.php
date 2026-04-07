<?php
/**
 * Solicitudes de Verificación — ComercioLocal Admin
 */
require_once("../config/conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php"); exit();
}
if (($_SESSION['user']['role'] ?? '') !== 'admin') {
    header("Location: ./inde.php"); exit();
}

$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

/* ── Mock verification requests ── */
$requests = [
  [
    'id'       => 1,
    'initials' => 'TC',
    'color'    => 'linear-gradient(135deg,#16a34a,#22c55e)',
    'name'     => 'TechColombia SAS',
    'email'    => 'ventas@techcolombia.co',
    'phone'    => '+57 310 842 1100',
    'city'     => 'Bogotá, Colombia',
    'category' => 'Electrónica',
    'date'     => '2026-03-30',
    'status'   => 'pending',
    'urgent'   => true,
    'doc_name' => 'camara_comercio_techcolombia.jpg',
    'doc_type' => 'Cámara de Comercio',
    'doc_size' => '2.4 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1568602471122-7832951cc4c5?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'30 mar 2026, 09:14'],
      ['dot'=>'view',  'text'=>'Documento revisado por sistema automático','time'=>'30 mar 2026, 09:15'],
    ],
  ],
  [
    'id'       => 2,
    'initials' => 'MF',
    'color'    => 'linear-gradient(135deg,#ca8a04,#facc15)',
    'name'     => 'Moda Femenina Cali',
    'email'    => 'info@modafemenina.co',
    'phone'    => '+57 315 203 4499',
    'city'     => 'Cali, Colombia',
    'category' => 'Ropa y moda',
    'date'     => '2026-03-29',
    'status'   => 'pending',
    'urgent'   => false,
    'doc_name' => 'rut_moda_femenina.jpg',
    'doc_type' => 'RUT',
    'doc_size' => '1.8 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1512436991641-6745cdb1723f?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'29 mar 2026, 14:32'],
    ],
  ],
  [
    'id'       => 3,
    'initials' => 'HV',
    'color'    => 'linear-gradient(135deg,#3b82f6,#60a5fa)',
    'name'     => 'HogarVerde',
    'email'    => 'contacto@hogarverde.co',
    'phone'    => '+57 301 779 5500',
    'city'     => 'Medellín, Colombia',
    'category' => 'Hogar y jardín',
    'date'     => '2026-03-28',
    'status'   => 'pending',
    'urgent'   => true,
    'doc_name' => 'cedula_hogarverde.jpg',
    'doc_type' => 'Cédula de ciudadanía',
    'doc_size' => '900 KB',
    'doc_img'  => 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'28 mar 2026, 08:55'],
      ['dot'=>'view',  'text'=>'Revisión manual solicitada','time'=>'29 mar 2026, 10:00'],
    ],
  ],
  [
    'id'       => 4,
    'initials' => 'JS',
    'color'    => 'linear-gradient(135deg,#8b5cf6,#a78bfa)',
    'name'     => 'Joyería Stella',
    'email'    => 'stella@joyeria.com',
    'phone'    => '+57 320 456 7890',
    'city'     => 'Barranquilla, Colombia',
    'category' => 'Joyería y relojes',
    'date'     => '2026-03-27',
    'status'   => 'approved',
    'urgent'   => false,
    'doc_name' => 'registro_mercantil_stella.jpg',
    'doc_type' => 'Registro mercantil',
    'doc_size' => '3.1 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1515562141207-7a88fb7ce338?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'27 mar 2026, 11:20'],
      ['dot'=>'action','text'=>'Aprobado por Admin — ComercioLocal','time'=>'28 mar 2026, 16:45'],
    ],
  ],
  [
    'id'       => 5,
    'initials' => 'AP',
    'color'    => 'linear-gradient(135deg,#f97316,#fb923c)',
    'name'     => 'Autos Pereira',
    'email'    => 'ventas@autospereira.co',
    'phone'    => '+57 316 890 1234',
    'city'     => 'Pereira, Colombia',
    'category' => 'Automóviles',
    'date'     => '2026-03-26',
    'status'   => 'rejected',
    'urgent'   => false,
    'doc_name' => 'nit_autos_pereira.jpg',
    'doc_type' => 'NIT',
    'doc_size' => '1.2 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=600&q=80',
    'rejection_reason' => 'El documento NIT presentado está vencido. Por favor actualice su documento y reenvíe la solicitud.',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'26 mar 2026, 17:00'],
      ['dot'=>'view',  'text'=>'Revisión iniciada por admin','time'=>'27 mar 2026, 09:30'],
      ['dot'=>'action','text'=>'Rechazado: documento vencido','time'=>'27 mar 2026, 09:45'],
    ],
  ],
  [
    'id'       => 6,
    'initials' => 'LB',
    'color'    => 'linear-gradient(135deg,#0ea5e9,#38bdf8)',
    'name'     => 'LibreríaBogotá',
    'email'    => 'libreria@bogota.co',
    'phone'    => '+57 312 345 6789',
    'city'     => 'Bogotá, Colombia',
    'category' => 'Libros y cultura',
    'date'     => '2026-04-01',
    'status'   => 'pending',
    'urgent'   => false,
    'doc_name' => 'camara_comercio_libreria.jpg',
    'doc_type' => 'Cámara de Comercio',
    'doc_size' => '2.0 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1521056787327-165eb2b76cbb?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada por el vendedor','time'=>'01 abr 2026, 08:10'],
    ],
  ],
  [
    'id'       => 7,
    'initials' => 'FP',
    'color'    => 'linear-gradient(135deg,#ec4899,#f472b6)',
    'name'     => 'FotoPlus Studio',
    'email'    => 'hola@fotoplus.co',
    'phone'    => '+57 305 678 9001',
    'city'     => 'Medellín, Colombia',
    'category' => 'Fotografía',
    'date'     => '2026-03-31',
    'status'   => 'approved',
    'urgent'   => false,
    'doc_name' => 'rut_fotoplus.jpg',
    'doc_type' => 'RUT',
    'doc_size' => '1.5 MB',
    'doc_img'  => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=600&q=80',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada','time'=>'31 mar 2026, 15:00'],
      ['dot'=>'action','text'=>'Aprobado por Admin','time'=>'01 abr 2026, 10:20'],
    ],
  ],
  [
    'id'       => 8,
    'initials' => 'MS',
    'color'    => 'linear-gradient(135deg,#14b8a6,#2dd4bf)',
    'name'     => 'MueblesSur',
    'email'    => 'ventas@muebles-sur.co',
    'phone'    => '+57 318 112 2233',
    'city'     => 'Cúcuta, Colombia',
    'category' => 'Muebles y decoración',
    'date'     => '2026-03-25',
    'status'   => 'rejected',
    'urgent'   => false,
    'doc_name' => 'cedula_mueblessur.jpg',
    'doc_type' => 'Cédula de ciudadanía',
    'doc_size' => '780 KB',
    'doc_img'  => 'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=600&q=80',
    'rejection_reason' => 'Las imágenes del documento son ilegibles. Favor reenviar con mejor resolución.',
    'timeline' => [
      ['dot'=>'submit','text'=>'Solicitud enviada','time'=>'25 mar 2026, 13:00'],
      ['dot'=>'action','text'=>'Rechazado: documento ilegible','time'=>'26 mar 2026, 11:00'],
    ],
  ],
];

$total    = count($requests);
$pending  = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitudes de Verificación — ComercioLocal Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style/verificaciones.css">
</head>
<body class="admin-body">

<!-- ══════════════════════════════════════
     TOP NAVIGATION BAR
══════════════════════════════════════ -->
<header class="adm-topbar">
  <a href="./admin_dashboard.php" class="adm-topbar__logo">
    <div class="adm-topbar__logo-icon">🏪</div>
    <div class="adm-topbar__logo-text">
      <strong>ComercioLocal</strong>
      <span>Admin Panel</span>
    </div>
  </a>

  <div class="adm-topbar__divider"></div>

  <div class="adm-topbar__search">
    <i class="bi bi-search"></i>
    <input type="text" placeholder="Buscar vendedor, correo, ciudad…" id="topbarSearch">
  </div>

  <div class="adm-topbar__actions">
    <span style="font-size:.8rem;color:#64748b;display:flex;align-items:center;">
      <span class="adm-live-dot"></span> En vivo
    </span>

    <!-- Pending alert pill -->
    <div class="adm-topbar__alert-pill">
      <i class="bi bi-hourglass-split"></i>
      <?= $pending ?> pendientes
    </div>

    <button class="adm-topbar__icon-btn" title="Notificaciones">
      <i class="bi bi-bell-fill"></i>
      <span class="badge"></span>
    </button>

    <button class="adm-topbar__icon-btn" title="Ir al dashboard">
      <i class="bi bi-speedometer2"></i>
    </button>

    <div class="adm-avatar-btn">
      <div class="adm-avatar-circle"><?= htmlspecialchars($userInitial) ?></div>
      <div class="adm-avatar-btn-info">
        <strong><?= htmlspecialchars($userName) ?></strong>
        <span>Super Admin</span>
      </div>
    </div>
  </div>
</header>

<!-- ══════════════════════════════════════
     SHELL
══════════════════════════════════════ -->
<div class="adm-shell">

  <!-- ── SIDEBAR ── -->
  <aside class="adm-sidebar">
    <div class="adm-sb-label">Principal</div>
    <a class="adm-sb-link" href="./admin_dashboard.php"><i class="bi bi-speedometer2"></i>Dashboard</a>
    <a class="adm-sb-link active" href="./verificaciones.php">
      <i class="bi bi-patch-check"></i>Solicitudes de verificación
      <span class="adm-sb-badge red"><?= $pending ?></span>
    </a>
    <a class="adm-sb-link" href="#"><i class="bi bi-people"></i>Gestión de usuarios</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-box-seam"></i>Gestión de productos</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-flag"></i>Productos reportados</a>
    <hr class="adm-sb-divider">
    <div class="adm-sb-label">Administración</div>
    <a class="adm-sb-link" href="./gestion_admins.php"><i class="bi bi-shield-check"></i>Gestión de admins</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-journal-text"></i>Registros de actividad</a>
    <a class="adm-sb-link" href="#"><i class="bi bi-gear"></i>Configuración</a>
    <hr class="adm-sb-divider">
    <a class="adm-sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
      <i class="bi bi-box-arrow-right"></i>Cerrar sesión
    </a>
    <div class="adm-sb-footer">
      <strong>🔐 Módulo seguro</strong>
      <p>Todas las acciones quedan registradas en el log de actividad.</p>
    </div>
  </aside>

  <!-- ══════════════════════════════════════
       MAIN CONTENT
  ══════════════════════════════════════ -->
  <main class="adm-main">

    <!-- Page header -->
    <div class="adm-page-header">
      <div class="adm-page-header__title">
        <div class="adm-breadcrumb">
          <a href="./admin_dashboard.php">Dashboard</a>
          <i class="bi bi-chevron-right"></i>
          <span>Solicitudes de verificación</span>
        </div>
        <h1>Solicitudes de verificación</h1>
        <p>Revisa, aprueba o rechaza las solicitudes de vendedores. Cada acción queda registrada.</p>
      </div>
    </div>

    <!-- Stats summary -->
    <div class="vf-stats-row">
      <div class="vf-stat vf-stat--total">
        <div class="vf-stat__top">
          <div class="vf-stat__icon"><i class="bi bi-folder2-open"></i></div>
          <span class="vf-stat__pct">Total</span>
        </div>
        <div class="vf-stat__num"><?= $total ?></div>
        <div class="vf-stat__lbl">Solicitudes totales</div>
        <div class="vf-stat__bar"><div class="vf-stat__fill" style="width:100%;"></div></div>
      </div>
      <div class="vf-stat vf-stat--pending">
        <div class="vf-stat__top">
          <div class="vf-stat__icon"><i class="bi bi-hourglass-split"></i></div>
          <span class="vf-stat__pct"><?= round($pending/$total*100) ?>%</span>
        </div>
        <div class="vf-stat__num"><?= $pending ?></div>
        <div class="vf-stat__lbl">Pendientes de revisión</div>
        <div class="vf-stat__bar"><div class="vf-stat__fill" style="width:<?= round($pending/$total*100) ?>%;"></div></div>
      </div>
      <div class="vf-stat vf-stat--approved">
        <div class="vf-stat__top">
          <div class="vf-stat__icon"><i class="bi bi-patch-check-fill"></i></div>
          <span class="vf-stat__pct"><?= round($approved/$total*100) ?>%</span>
        </div>
        <div class="vf-stat__num"><?= $approved ?></div>
        <div class="vf-stat__lbl">Aprobadas</div>
        <div class="vf-stat__bar"><div class="vf-stat__fill" style="width:<?= round($approved/$total*100) ?>%;"></div></div>
      </div>
      <div class="vf-stat vf-stat--rejected">
        <div class="vf-stat__top">
          <div class="vf-stat__icon"><i class="bi bi-x-circle-fill"></i></div>
          <span class="vf-stat__pct"><?= round($rejected/$total*100) ?>%</span>
        </div>
        <div class="vf-stat__num"><?= $rejected ?></div>
        <div class="vf-stat__lbl">Rechazadas</div>
        <div class="vf-stat__bar"><div class="vf-stat__fill" style="width:<?= round($rejected/$total*100) ?>%;"></div></div>
      </div>
    </div>

    <!-- Tabs + Filters toolbar -->
    <div class="vf-toolbar">
      <!-- Tabs -->
      <div class="vf-tabs" role="tablist">
        <button class="vf-tab active--pending" data-tab="pending" onclick="switchTab('pending',this)">
          <i class="bi bi-hourglass-split"></i>
          Pendientes
          <span class="vf-tab-count" id="tab-count-pending"><?= $pending ?></span>
        </button>
        <button class="vf-tab" data-tab="approved" onclick="switchTab('approved',this)">
          <i class="bi bi-patch-check-fill"></i>
          Aprobadas
          <span class="vf-tab-count" id="tab-count-approved"><?= $approved ?></span>
        </button>
        <button class="vf-tab" data-tab="rejected" onclick="switchTab('rejected',this)">
          <i class="bi bi-x-circle-fill"></i>
          Rechazadas
          <span class="vf-tab-count" id="tab-count-rejected"><?= $rejected ?></span>
        </button>
      </div>

      <div class="vf-toolbar__sep"></div>

      <!-- Search -->
      <div class="vf-search">
        <i class="bi bi-search"></i>
        <input type="text" placeholder="Nombre, correo o ciudad…" id="mainSearch" oninput="filterList()">
      </div>

      <!-- Date filter -->
      <input type="date" class="vf-filter-date" id="dateFilter" onchange="filterList()" title="Filtrar por fecha">

      <!-- Urgency filter -->
      <select class="vf-filter-select" id="urgencyFilter" onchange="filterList()">
        <option value="">Todas las prioridades</option>
        <option value="urgent">Solo urgentes</option>
        <option value="normal">Solo normales</option>
      </select>

      <!-- Category filter -->
      <select class="vf-filter-select" id="categoryFilter" onchange="filterList()">
        <option value="">Todas las categorías</option>
        <option value="Electrónica">Electrónica</option>
        <option value="Ropa y moda">Ropa y moda</option>
        <option value="Hogar y jardín">Hogar y jardín</option>
        <option value="Joyería y relojes">Joyería y relojes</option>
        <option value="Automóviles">Automóviles</option>
        <option value="Libros y cultura">Libros y cultura</option>
        <option value="Fotografía">Fotografía</option>
        <option value="Muebles y decoración">Muebles y decoración</option>
      </select>

      <button class="vf-toolbar__export" title="Exportar lista">
        <i class="bi bi-download"></i> Exportar
      </button>
    </div>

    <!-- TWO-COLUMN LAYOUT -->
    <div class="vf-layout">

      <!-- ── LEFT: Request list ── -->
      <div class="vf-list-panel">
        <div class="vf-list-header">
          <h3>
            <i class="bi bi-list-ul" style="color:var(--g600);"></i>
            <span id="list-heading">Solicitudes pendientes</span>
          </h3>
          <div style="display:flex;gap:8px;align-items:center;">
            <span class="vf-list-count" id="visible-count"><?= $pending ?></span>
            <div class="vf-list-sort" id="sortBtn" onclick="toggleSort()">
              <i class="bi bi-sort-down" id="sortIcon"></i>
              <span id="sortLabel">Más reciente</span>
            </div>
          </div>
        </div>

        <div class="vf-list-scroll" id="requestList">
          <?php foreach ($requests as $req): ?>
          <div class="vf-req-card <?= $req['urgent'] ? 'urgent' : '' ?> status-<?= $req['status'] ?>"
               data-id="<?= $req['id'] ?>"
               data-status="<?= $req['status'] ?>"
               data-name="<?= strtolower($req['name']) ?>"
               data-email="<?= strtolower($req['email']) ?>"
               data-city="<?= strtolower($req['city']) ?>"
               data-date="<?= $req['date'] ?>"
               data-urgent="<?= $req['urgent'] ? 'urgent' : 'normal' ?>"
               data-category="<?= $req['category'] ?>"
               onclick="selectRequest(<?= $req['id'] ?>)">

            <div class="vf-req-avatar" style="background:<?= $req['color'] ?>">
              <?= $req['initials'] ?>
              <span class="status-dot <?= $req['status'] ?>"></span>
            </div>

            <div class="vf-req-info">
              <div class="vf-req-name"><?= htmlspecialchars($req['name']) ?></div>
              <div class="vf-req-email"><?= htmlspecialchars($req['email']) ?></div>
              <div class="vf-req-meta">
                <div class="vf-req-meta-item"><i class="bi bi-geo-alt"></i><?= htmlspecialchars($req['city']) ?></div>
                <div class="vf-req-meta-item"><i class="bi bi-tag"></i><?= htmlspecialchars($req['category']) ?></div>
              </div>
            </div>

            <div class="vf-req-right">
              <div class="vf-req-date"><?= date('d/m/Y', strtotime($req['date'])) ?></div>
              <?php if ($req['status'] === 'pending'): ?>
                <span class="vf-badge pending"><i class="bi bi-clock"></i>Pendiente</span>
              <?php elseif ($req['status'] === 'approved'): ?>
                <span class="vf-badge approved"><i class="bi bi-check-circle"></i>Aprobado</span>
              <?php else: ?>
                <span class="vf-badge rejected"><i class="bi bi-x-circle"></i>Rechazado</span>
              <?php endif; ?>
            </div>

          </div>
          <?php endforeach; ?>

          <div class="vf-empty hidden" id="emptyState">
            <i class="bi bi-search"></i>
            <p>No se encontraron solicitudes con esos filtros.</p>
          </div>
        </div>
      </div><!-- /list panel -->

      <!-- ── RIGHT: Detail panel ── -->
      <div class="vf-detail-panel" id="detailPanel">

        <!-- Empty placeholder -->
        <div class="vf-detail-empty" id="detailEmpty">
          <div class="icon-wrap">📋</div>
          <h3>Selecciona una solicitud</h3>
          <p>Haz clic en cualquier solicitud de la lista para ver los documentos y tomar una decisión.</p>
        </div>

        <!-- Loaded detail (hidden until a card is selected) -->
        <div id="detailContent" class="hidden">

          <!-- Header -->
          <div class="vf-detail-header">
            <div class="vf-detail-avatar" id="detailAvatar"></div>
            <div class="vf-detail-name-block">
              <h2 id="detailName"></h2>
              <span id="detailCategory"></span>
            </div>
            <span id="detailBadge" class="vf-badge" style="margin-right:8px;"></span>
            <button class="vf-detail-close" onclick="closeDetail()" title="Cerrar"><i class="bi bi-x-lg"></i></button>
          </div>

          <!-- Body -->
          <div class="vf-detail-body">

            <!-- Info grid -->
            <div class="vf-info-grid">
              <div class="vf-info-item">
                <div class="lbl"><i class="bi bi-envelope"></i> Correo</div>
                <div class="val" id="detailEmail"></div>
              </div>
              <div class="vf-info-item">
                <div class="lbl"><i class="bi bi-telephone"></i> Teléfono</div>
                <div class="val" id="detailPhone"></div>
              </div>
              <div class="vf-info-item">
                <div class="lbl"><i class="bi bi-geo-alt"></i> Ciudad</div>
                <div class="val" id="detailCity"></div>
              </div>
              <div class="vf-info-item">
                <div class="lbl"><i class="bi bi-calendar3"></i> Fecha solicitud</div>
                <div class="val" id="detailDate"></div>
              </div>
              <div class="vf-info-item" style="grid-column:1/-1;">
                <div class="lbl"><i class="bi bi-tag"></i> Categoría de negocio</div>
                <div class="val" id="detailCat2"></div>
              </div>
            </div>

            <!-- Document preview -->
            <div class="vf-doc-section">
              <div class="vf-doc-title">
                <i class="bi bi-file-earmark-check-fill"></i>
                Documento adjunto
              </div>
              <div class="vf-doc-preview" id="docPreview">
                <img id="docImg" src="" alt="Documento de verificación">
                <div class="vf-doc-overlay">
                  <button class="vf-doc-overlay-btn" onclick="openZoom()" title="Ampliar"><i class="bi bi-zoom-in"></i></button>
                  <button class="vf-doc-overlay-btn" title="Descargar"><i class="bi bi-download"></i></button>
                </div>
              </div>
              <div class="vf-doc-meta">
                <div class="vf-doc-meta-tag"><i class="bi bi-file-earmark"></i> <span id="docName"></span></div>
                <div class="vf-doc-meta-tag"><i class="bi bi-hdd"></i> <span id="docSize"></span></div>
                <div class="vf-doc-meta-tag"><i class="bi bi-shield-check"></i> <span id="docType"></span></div>
              </div>
            </div>

            <!-- Timeline -->
            <div>
              <div class="vf-doc-title"><i class="bi bi-clock-history" style="color:var(--blue500);"></i> Historial de la solicitud</div>
              <div class="vf-timeline" id="detailTimeline"></div>
            </div>

          </div><!-- /body -->

          <!-- Action panel -->
          <div class="vf-action-panel" id="actionPanel">
            <div class="vf-action-panel__title">
              <i class="bi bi-shield-fill-check"></i> Decisión del moderador
            </div>

            <!-- Buttons (shown when pending) -->
            <div id="actionButtons" class="vf-action-btns">
              <button class="vf-btn-approve" id="approveBtn" onclick="handleApprove()">
                <i class="bi bi-patch-check-fill"></i> Aprobar solicitud
              </button>
              <button class="vf-btn-reject" id="rejectBtn" onclick="toggleRejectBox()">
                <i class="bi bi-x-circle-fill"></i> Rechazar
              </button>
              <button class="vf-btn-hold" title="Poner en espera"><i class="bi bi-pause-circle"></i></button>
            </div>

            <!-- Rejection reason box -->
            <div class="vf-rejection-box" id="rejectionBox">
              <label><i class="bi bi-exclamation-triangle-fill"></i> Motivo del rechazo <span style="color:var(--red400);">*</span></label>
              <div class="vf-rejection-reason-presets">
                <button class="vf-preset-btn" onclick="setPreset('Documento vencido o inválido.')">Documento vencido</button>
                <button class="vf-preset-btn" onclick="setPreset('La imagen del documento es ilegible.')">Ilegible</button>
                <button class="vf-preset-btn" onclick="setPreset('Documento incorrecto para esta categoría.')">Tipo incorrecto</button>
                <button class="vf-preset-btn" onclick="setPreset('Los datos no coinciden con el registro.')">Datos no coinciden</button>
              </div>
              <textarea id="rejectionReason" placeholder="Escribe el motivo del rechazo para notificar al vendedor…"></textarea>
              <button class="vf-btn-confirm-reject" onclick="handleReject()">
                <i class="bi bi-x-circle-fill"></i> Confirmar rechazo
              </button>
            </div>

            <!-- Already decided banner (shown when approved/rejected) -->
            <div class="vf-decided-banner approved hidden" id="approvedBanner">
              <i class="bi bi-patch-check-fill"></i>
              Esta solicitud ya fue <strong>aprobada</strong>. El vendedor ha sido notificado.
            </div>
            <div class="vf-decided-banner rejected hidden" id="rejectedBanner">
              <i class="bi bi-x-circle-fill"></i>
              <div>
                Esta solicitud fue <strong>rechazada</strong>.
                <div id="rejectedReason" style="font-size:.8rem;opacity:.8;margin-top:3px;font-weight:400;"></div>
              </div>
            </div>

          </div><!-- /action panel -->
        </div><!-- /detailContent -->
      </div><!-- /detail panel -->

    </div><!-- /vf-layout -->
  </main>
</div><!-- /shell -->

<!-- ══════════════════════════════════════
     ZOOM MODAL
══════════════════════════════════════ -->
<div class="vf-modal-backdrop" id="zoomBackdrop" onclick="closeZoomOutside(event)">
  <div class="vf-modal">
    <div class="vf-modal-header">
      <h3 id="zoomTitle">Vista del documento</h3>
      <button class="vf-modal-close" onclick="closeZoom()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="vf-modal-body">
      <img id="zoomImg" src="" alt="Documento ampliado">
    </div>
    <div class="vf-modal-footer">
      <div class="vf-modal-tag"><i class="bi bi-shield-check" style="color:var(--g600);"></i> Documento oficial — ComercioLocal</div>
      <a href="#" class="vf-modal-dl" id="zoomDl"><i class="bi bi-download"></i> Descargar</a>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     CONFIRM MODAL
══════════════════════════════════════ -->
<div class="vf-confirm-backdrop" id="confirmBackdrop">
  <div class="vf-confirm-modal">
    <div class="vf-confirm-icon" id="confirmIcon">✅</div>
    <h3 id="confirmTitle">¿Aprobar esta solicitud?</h3>
    <p id="confirmMsg">Al aprobar, el vendedor recibirá la insignia de verificado y podrá vender en ComercioLocal.</p>
    <div class="vf-confirm-btns">
      <button class="vf-confirm-cancel" onclick="closeConfirm()">Cancelar</button>
      <button id="confirmOkBtn" class="vf-confirm-ok-approve" onclick="executeAction()">Confirmar</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════
     TOAST CONTAINER
══════════════════════════════════════ -->
<div class="vf-toast-container" id="toastContainer"></div>

<!-- ══════════════════════════════════════
     JAVASCRIPT
══════════════════════════════════════ -->
<script>
/* ═══════════════════════════════
   DATA (from PHP)
═══════════════════════════════ */
const REQUESTS = <?= json_encode($requests) ?>;
let currentId    = null;
let currentTab   = 'pending';
let sortAsc      = false;
let pendingAction= null; // 'approve' | 'reject'

/* ═══════════════════════════════
   TAB SWITCHING
═══════════════════════════════ */
function switchTab(tab, btn) {
  currentTab = tab;
  // Update tab styles
  document.querySelectorAll('.vf-tab').forEach(t => {
    t.classList.remove('active--pending','active--approved','active--rejected');
  });
  btn.classList.add('active--' + tab);

  // Update heading
  const headings = { pending:'Solicitudes pendientes', approved:'Solicitudes aprobadas', rejected:'Solicitudes rechazadas' };
  document.getElementById('list-heading').textContent = headings[tab];

  closeDetail();
  filterList();
}

/* ═══════════════════════════════
   FILTER / SEARCH
═══════════════════════════════ */
function filterList() {
  const q         = document.getElementById('mainSearch').value.toLowerCase().trim();
  const dateVal   = document.getElementById('dateFilter').value;
  const urgency   = document.getElementById('urgencyFilter').value;
  const category  = document.getElementById('categoryFilter').value;

  const cards = document.querySelectorAll('.vf-req-card');
  let visible = 0;

  cards.forEach(card => {
    const status   = card.dataset.status;
    const name     = card.dataset.name;
    const email    = card.dataset.email;
    const city     = card.dataset.city;
    const date     = card.dataset.date;
    const urg      = card.dataset.urgent;
    const cat      = card.dataset.category;

    const matchTab      = status === currentTab;
    const matchSearch   = !q || name.includes(q) || email.includes(q) || city.includes(q);
    const matchDate     = !dateVal || date === dateVal;
    const matchUrgency  = !urgency || urg === urgency;
    const matchCategory = !category || cat === category;

    const show = matchTab && matchSearch && matchDate && matchUrgency && matchCategory;
    card.classList.toggle('hidden', !show);
    if (show) visible++;
  });

  document.getElementById('visible-count').textContent = visible;
  document.getElementById('emptyState').classList.toggle('hidden', visible > 0);
}

/* ═══════════════════════════════
   SORT
═══════════════════════════════ */
function toggleSort() {
  sortAsc = !sortAsc;
  document.getElementById('sortLabel').textContent = sortAsc ? 'Más antigua' : 'Más reciente';
  document.getElementById('sortIcon').className = sortAsc ? 'bi bi-sort-up' : 'bi bi-sort-down';

  const list = document.getElementById('requestList');
  const cards = [...list.querySelectorAll('.vf-req-card')];
  cards.sort((a, b) => {
    const da = new Date(a.dataset.date), db = new Date(b.dataset.date);
    return sortAsc ? da - db : db - da;
  });
  cards.forEach(c => list.appendChild(c));
}

/* ═══════════════════════════════
   SELECT REQUEST
═══════════════════════════════ */
function selectRequest(id) {
  const req = REQUESTS.find(r => r.id === id);
  if (!req) return;
  currentId = id;

  // Highlight card
  document.querySelectorAll('.vf-req-card').forEach(c => c.classList.remove('selected'));
  document.querySelector(`.vf-req-card[data-id="${id}"]`)?.classList.add('selected');

  // Show detail
  document.getElementById('detailEmpty').classList.add('hidden');
  document.getElementById('detailContent').classList.remove('hidden');

  // Avatar
  const av = document.getElementById('detailAvatar');
  av.style.background = req.color;
  av.textContent = req.initials;

  // Header info
  document.getElementById('detailName').textContent = req.name;
  document.getElementById('detailCategory').textContent = req.category;

  // Badge
  const badge = document.getElementById('detailBadge');
  badge.className = 'vf-badge ' + req.status;
  const badgeMap = {
    pending:  '<i class="bi bi-clock"></i> Pendiente',
    approved: '<i class="bi bi-check-circle"></i> Aprobado',
    rejected: '<i class="bi bi-x-circle"></i> Rechazado',
  };
  badge.innerHTML = badgeMap[req.status];

  // Info fields
  document.getElementById('detailEmail').innerHTML = `<a href="mailto:${req.email}">${req.email}</a>`;
  document.getElementById('detailPhone').textContent = req.phone;
  document.getElementById('detailCity').textContent = req.city;
  document.getElementById('detailDate').textContent = formatDate(req.date);
  document.getElementById('detailCat2').textContent = req.category;

  // Doc
  document.getElementById('docImg').src = req.doc_img;
  document.getElementById('docName').textContent = req.doc_name;
  document.getElementById('docSize').textContent = req.doc_size;
  document.getElementById('docType').textContent = req.doc_type;

  // Timeline
  const tl = document.getElementById('detailTimeline');
  tl.innerHTML = req.timeline.map(t => `
    <div class="vf-timeline-item">
      <div class="vf-tl-dot ${t.dot}"></div>
      <div class="vf-tl-content"><p>${t.text}</p><time>${t.time}</time></div>
    </div>`).join('');

  // Action panel
  const actionBtns   = document.getElementById('actionButtons');
  const rejectBox    = document.getElementById('rejectionBox');
  const approvedBnr  = document.getElementById('approvedBanner');
  const rejectedBnr  = document.getElementById('rejectedBanner');

  actionBtns.classList.add('hidden');
  rejectBox.classList.remove('open');
  approvedBnr.classList.add('hidden');
  rejectedBnr.classList.add('hidden');

  if (req.status === 'pending') {
    actionBtns.classList.remove('hidden');
  } else if (req.status === 'approved') {
    approvedBnr.classList.remove('hidden');
  } else {
    rejectedBnr.classList.remove('hidden');
    document.getElementById('rejectedReason').textContent = req.rejection_reason || '';
  }
}

function closeDetail() {
  currentId = null;
  document.querySelectorAll('.vf-req-card').forEach(c => c.classList.remove('selected'));
  document.getElementById('detailEmpty').classList.remove('hidden');
  document.getElementById('detailContent').classList.add('hidden');
}

/* ═══════════════════════════════
   REJECT BOX TOGGLE
═══════════════════════════════ */
function toggleRejectBox() {
  const box = document.getElementById('rejectionBox');
  box.classList.toggle('open');
}

function setPreset(text) {
  document.getElementById('rejectionReason').value = text;
}

/* ═══════════════════════════════
   APPROVE / REJECT FLOW
═══════════════════════════════ */
function handleApprove() {
  if (!currentId) return;
  const req = REQUESTS.find(r => r.id === currentId);
  pendingAction = 'approve';
  document.getElementById('confirmIcon').textContent = '✅';
  document.getElementById('confirmTitle').textContent = `¿Aprobar a "${req.name}"?`;
  document.getElementById('confirmMsg').textContent = 'Al aprobar, el vendedor recibirá la insignia de verificado y podrá vender en ComercioLocal. Esta acción queda registrada.';
  document.getElementById('confirmOkBtn').className = 'vf-confirm-ok-approve';
  document.getElementById('confirmOkBtn').textContent = 'Aprobar';
  document.getElementById('confirmBackdrop').classList.add('open');
}

function handleReject() {
  const reason = document.getElementById('rejectionReason').value.trim();
  if (!reason) {
    showToast('warning', 'Motivo requerido', 'Escribe el motivo del rechazo antes de continuar.');
    document.getElementById('rejectionReason').focus();
    return;
  }
  if (!currentId) return;
  const req = REQUESTS.find(r => r.id === currentId);
  pendingAction = 'reject';
  document.getElementById('confirmIcon').textContent = '❌';
  document.getElementById('confirmTitle').textContent = `¿Rechazar a "${req.name}"?`;
  document.getElementById('confirmMsg').textContent = `Se notificará al vendedor con el siguiente motivo: "${reason}"`;
  document.getElementById('confirmOkBtn').className = 'vf-confirm-ok-reject';
  document.getElementById('confirmOkBtn').textContent = 'Confirmar rechazo';
  document.getElementById('confirmBackdrop').classList.add('open');
}

function executeAction() {
  closeConfirm();
  if (!currentId) return;

  const req = REQUESTS.find(r => r.id === currentId);
  const card = document.querySelector(`.vf-req-card[data-id="${currentId}"]`);

  if (pendingAction === 'approve') {
    req.status = 'approved';
    card.dataset.status = 'approved';
    card.querySelector('.status-dot').className = 'status-dot approved';
    card.querySelector('.vf-badge').className = 'vf-badge approved';
    card.querySelector('.vf-badge').innerHTML = '<i class="bi bi-check-circle"></i>Aprobado';
    req.timeline.push({ dot:'action', text:'Aprobado por Admin — ComercioLocal', time: 'Ahora' });
    showToast('success', 'Solicitud aprobada', `"${req.name}" ahora es un vendedor verificado.`);
  } else {
    const reason = document.getElementById('rejectionReason').value.trim();
    req.status = 'rejected';
    req.rejection_reason = reason;
    card.dataset.status = 'rejected';
    card.querySelector('.status-dot').className = 'status-dot rejected';
    card.querySelector('.vf-badge').className = 'vf-badge rejected';
    card.querySelector('.vf-badge').innerHTML = '<i class="bi bi-x-circle"></i>Rechazado';
    req.timeline.push({ dot:'action', text:`Rechazado: ${reason}`, time: 'Ahora' });
    showToast('error', 'Solicitud rechazada', `"${req.name}" ha sido notificado con el motivo.`);
  }

  updateCounts();
  // Re-render detail
  selectRequest(currentId);
  // Hide card from current tab (it no longer belongs here)
  setTimeout(() => {
    filterList();
    closeDetail();
  }, 1200);
}

/* ═══════════════════════════════
   UPDATE COUNTS
═══════════════════════════════ */
function updateCounts() {
  const p = REQUESTS.filter(r => r.status === 'pending').length;
  const a = REQUESTS.filter(r => r.status === 'approved').length;
  const r = REQUESTS.filter(r => r.status === 'rejected').length;
  document.getElementById('tab-count-pending').textContent  = p;
  document.getElementById('tab-count-approved').textContent = a;
  document.getElementById('tab-count-rejected').textContent = r;
}

/* ═══════════════════════════════
   CONFIRM MODAL
═══════════════════════════════ */
function closeConfirm() {
  document.getElementById('confirmBackdrop').classList.remove('open');
}
document.getElementById('confirmBackdrop').addEventListener('click', e => {
  if (e.target === document.getElementById('confirmBackdrop')) closeConfirm();
});

/* ═══════════════════════════════
   ZOOM MODAL
═══════════════════════════════ */
function openZoom() {
  if (!currentId) return;
  const req = REQUESTS.find(r => r.id === currentId);
  document.getElementById('zoomImg').src = req.doc_img;
  document.getElementById('zoomTitle').textContent = `📄 ${req.doc_name}`;
  document.getElementById('zoomDl').href = req.doc_img;
  document.getElementById('zoomBackdrop').classList.add('open');
}
function closeZoom() { document.getElementById('zoomBackdrop').classList.remove('open'); }
function closeZoomOutside(e) { if (e.target === document.getElementById('zoomBackdrop')) closeZoom(); }

/* ═══════════════════════════════
   TOAST
═══════════════════════════════ */
function showToast(type, title, msg) {
  const icons = { success:'bi-check-circle-fill', error:'bi-x-circle-fill', warning:'bi-exclamation-triangle-fill' };
  const t = document.createElement('div');
  t.className = `vf-toast ${type}`;
  t.innerHTML = `
    <i class="bi ${icons[type]} vf-toast__icon"></i>
    <div><strong>${title}</strong><br><span style="font-weight:400;font-size:.82rem;">${msg}</span></div>
    <button class="vf-toast__close" onclick="this.parentElement.remove()"><i class="bi bi-x-lg"></i></button>`;
  document.getElementById('toastContainer').appendChild(t);
  setTimeout(() => { t.classList.add('fade-out'); setTimeout(() => t.remove(), 300); }, 4500);
}

/* ═══════════════════════════════
   HELPERS
═══════════════════════════════ */
function formatDate(d) {
  const months = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const dt = new Date(d + 'T00:00:00');
  return `${dt.getDate()} ${months[dt.getMonth()]} ${dt.getFullYear()}`;
}

// Sync topbar search with main search
document.getElementById('topbarSearch').addEventListener('input', function() {
  document.getElementById('mainSearch').value = this.value;
  filterList();
});

// Init: show pending tab
filterList();
</script>
</body>
</html>
