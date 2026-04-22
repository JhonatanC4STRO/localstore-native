<?php
require_once __DIR__ . '/../config/conexion.php';
session_start();

if (!isset($_SESSION['user'])) {
  header('Location: auth/login.php');
  exit;
}

$user        = $_SESSION['user'];
$user_id     = (int) $user['id'];
$isLoggedIn  = true;
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

// Sidebar badge
$rc = mysqli_query($conn, "SELECT COUNT(*) as t FROM products WHERE user_id=$user_id");
$totalProducts = (int) mysqli_fetch_assoc($rc)['t'];

// ── Specific product requested ─────────────────────────────
$product_id   = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$product      = null;
$productImage = null;
$activePromo  = null;

if ($product_id > 0) {
  $sp = mysqli_prepare($conn,
    "SELECT p.*, c.name AS category_name FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     WHERE p.id=? AND p.user_id=?");
  mysqli_stmt_bind_param($sp, 'ii', $product_id, $user_id);
  mysqli_stmt_execute($sp);
  $product = mysqli_fetch_assoc(mysqli_stmt_get_result($sp));

  if ($product) {
    $si = mysqli_prepare($conn, "SELECT image_url FROM product_images WHERE product_id=? LIMIT 1");
    mysqli_stmt_bind_param($si, 'i', $product_id);
    mysqli_stmt_execute($si);
    if ($ir = mysqli_fetch_assoc(mysqli_stmt_get_result($si))) $productImage = $ir['image_url'];

    mysqli_query($conn,
      "UPDATE product_promotions SET status='expired' WHERE status='active' AND end_date < NOW()");

    $sap = mysqli_prepare($conn,
      "SELECT * FROM product_promotions WHERE product_id=? AND status='active' LIMIT 1");
    mysqli_stmt_bind_param($sap, 'i', $product_id);
    mysqli_stmt_execute($sap);
    $activePromo = mysqli_fetch_assoc(mysqli_stmt_get_result($sap));
  }
}

// ── User products for selector screen ─────────────────────
$userProducts = [];
if (!$product_id || !$product) {
  mysqli_query($conn,
    "UPDATE product_promotions SET status='expired' WHERE status='active' AND end_date < NOW()");
  $rp = mysqli_query($conn,
    "SELECT p.id, p.title, p.price, c.name AS category_name,
            (SELECT pi.image_url FROM product_images pi WHERE pi.product_id=p.id LIMIT 1) AS image_url,
            (SELECT pp.plan_type FROM product_promotions pp
             WHERE pp.product_id=p.id AND pp.status='active' LIMIT 1) AS active_plan
     FROM products p LEFT JOIN categories c ON c.id=p.category_id
     WHERE p.user_id=$user_id ORDER BY p.id DESC");
  while ($row = mysqli_fetch_assoc($rp)) $userProducts[] = $row;
}

$initialScreen = ($product_id > 0 && $product) ? 'plans' : 'selector';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Promocionar Producto – ComercioLocal</title>
  <meta name="description" content="Aumenta la visibilidad de tu producto con los planes de promoción de ComercioLocal.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../public/css/dashborad.css">
  <link rel="stylesheet" href="../../public/css/promociones.css">
  <link rel="stylesheet" href="../../public/css/sidebar.css">
  <link rel="stylesheet" href="../../public/css/output.css">
</head>
<body>

<?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

<div class="page-shell">

  <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
  <?php $activeTab = "promociones"; include __DIR__ . "/../components/sidebar.php"; ?>

  <!-- ══ MAIN ═════════════════════════════════════════════ -->
  <main class="main-content promo-main">

    <!-- Breadcrumb + title -->
    <div class="promo-breadcrumb">
      <a href="./dashboard.php">Inicio</a>
      <i class="bi bi-chevron-right" style="font-size:.6rem"></i>
      <span>Promocionar producto</span>
    </div>
    <h1 class="promo-page-title">
      Promocionar <span>producto</span>
    </h1>
    <p class="promo-page-sub">Aumenta la visibilidad de tus anuncios y llega a más compradores.</p>

    <!-- ── Step Indicator (hidden on selector screen) ───── -->
    <div class="step-indicator" id="stepIndicator" style="display:none">
      <div class="step-item" id="si1">
        <div class="step-circle">1</div>
        <div class="step-label">Elegir plan</div>
      </div>
      <div class="step-line" id="sl1"></div>
      <div class="step-item" id="si2">
        <div class="step-circle">2</div>
        <div class="step-label">Resumen</div>
      </div>
      <div class="step-line" id="sl2"></div>
      <div class="step-item" id="si3">
        <div class="step-circle">3</div>
        <div class="step-label">Método de pago</div>
      </div>
      <div class="step-line" id="sl3"></div>
      <div class="step-item" id="si4">
        <div class="step-circle">4</div>
        <div class="step-label">Confirmar</div>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════ -->
    <!-- WIZARD                                             -->
    <!-- ═══════════════════════════════════════════════════ -->
    <div id="promoWizard">

      <!-- ── SCREEN: PRODUCT SELECTOR ─────────────────── -->
      <div class="wizard-screen" id="screenSelector">
        <div class="selector-intro">
          <h2>Elige el producto que deseas promocionar</h2>
          <p>Selecciona uno de tus anuncios activos para destacarlo.</p>
        </div>
        <div class="selector-grid">
          <?php if (empty($userProducts)): ?>
            <div class="empty-products">
              <div class="ep-icon">📦</div>
              <h3>No tienes productos publicados</h3>
              <p>Primero publica un producto para poder promocionarlo.</p>
              <a href="./crear.php" style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;padding:10px 20px;background:#16a34a;color:#fff;border-radius:8px;font-weight:700;text-decoration:none;">
                <i class="bi bi-plus-lg"></i> Publicar producto
              </a>
            </div>
          <?php else: ?>
            <?php foreach ($userProducts as $up): ?>
              <div class="selector-card" onclick="window.location.href='?product_id=<?= $up['id'] ?>'">
                <div class="sc-img">
                  <?php if ($up['image_url']): ?>
                    <img src="../../public/uploads/products/<?= htmlspecialchars($up['image_url']) ?>" alt="">
                  <?php else: ?>
                    <i class="bi bi-image" style="color:#cbd5e1; font-size:2rem;"></i>
                  <?php endif; ?>
                </div>
                <div class="sc-body">
                  <div class="sc-title"><?= htmlspecialchars($up['title']) ?></div>
                  <div class="sc-price">$<?= number_format($up['price'], 0, ',', '.') ?> COP</div>
                  <?php if ($up['category_name']): ?>
                    <div class="sc-cat"><i class="bi bi-tag-fill"></i> <?= htmlspecialchars($up['category_name']) ?></div>
                  <?php endif; ?>
                </div>
                <div class="sc-footer">
                  <?php if ($up['active_plan']): ?>
                    <div class="sc-promo-active">
                      <i class="bi bi-lightning-charge-fill"></i> Promoción activa
                    </div>
                  <?php else: ?>
                    <button class="btn-select-product">Promocionar</button>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ── SCREEN: PLANS ───────────────────────────── -->
      <div class="wizard-screen" id="screenPlans">
        
        <!-- Product summary card -->
        <div class="product-summary-card" id="productSummaryCard">
          <div class="ps-img" id="psSummaryImg"></div>
          <div class="ps-info">
            <h3 id="psSummaryTitle"></h3>
            <div class="ps-price" id="psSummaryPrice"></div>
            <div class="ps-meta">
              <span id="psSummaryCat"></span>
            </div>
          </div>
          <a href="./promociones.php" style="margin-left:auto;font-size:.8rem;color:#64748b;">
            <i class="bi bi-arrow-left-circle"></i> Cambiar producto
          </a>
        </div>

        <!-- Active promo warning -->
        <div class="active-promo-alert" id="activePromoAlert" style="display:none">
          <span class="apa-icon">⚠️</span>
          <div>
            <strong>Ya tienes una promoción activa.</strong>
            <span id="activePromoText"></span>
            Espera a que expire antes de contratar un nuevo plan.
          </div>
        </div>

        <!-- Plans section -->
        <div class="plans-section-title">
          <h2>Elige tu plan de promoción</h2>
          <p>Selecciona el plan que mejor se adapte a tus objetivos de venta</p>
        </div>

        <div class="plans-grid" id="plansGrid">

          <!-- ── Basic ──────────────────────────────── -->
          <div class="plan-card plan-basic" id="cardBasic" onclick="selectPlan('basic')">
            <div class="plan-badge">Destacado</div>
            <div class="plan-icon-wrap"><span>⭐</span></div>
            <div class="plan-name">Plan Básico</div>
            <div class="plan-price-wrap">
              <span class="plan-price-amount">$3.000</span>
              <span class="plan-price-cur">COP</span>
            </div>
            <div class="plan-duration"><i class="bi bi-clock"></i> 3 días de visibilidad</div>
            <ul class="plan-features">
              <li><i class="bi bi-check-circle-fill fi"></i> Aparece por encima de anuncios normales</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Insignia "Destacado" visible</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Mayor prioridad en búsquedas</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Activación inmediata</li>
            </ul>
            <button class="plan-btn plan-btn-basic">
              <i class="bi bi-rocket-takeoff"></i> Seleccionar plan
            </button>
          </div>

          <!-- ── Recommended (Popular) ──────────────── -->
          <div class="plan-card plan-recommended" id="cardRecommended" onclick="selectPlan('recommended')">
            <div class="popular-ribbon">⭐ Más popular</div>
            <div class="plan-badge">Más popular</div>
            <div class="plan-icon-wrap"><span>🚀</span></div>
            <div class="plan-name">Plan Recomendado</div>
            <div class="plan-price-wrap">
              <span class="plan-price-amount">$5.000</span>
              <span class="plan-price-cur">COP</span>
            </div>
            <div class="plan-duration"><i class="bi bi-clock"></i> 7 días de visibilidad</div>
            <ul class="plan-features">
              <li><i class="bi bi-check-circle-fill fi"></i> Aparece en "Recomendados" del inicio</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Insignia de recomendado visible</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Alta prioridad en búsquedas</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Mayor exposición que el plan básico</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Activación inmediata</li>
            </ul>
            <button class="plan-btn plan-btn-recommended">
              <i class="bi bi-rocket-takeoff"></i> Seleccionar plan
            </button>
          </div>

          <!-- ── Premium ────────────────────────────── -->
          <div class="plan-card plan-premium" id="cardPremium" onclick="selectPlan('premium')">
            <div class="plan-badge">Premium</div>
            <div class="plan-icon-wrap"><span>💎</span></div>
            <div class="plan-name">Plan Premium</div>
            <div class="plan-price-wrap">
              <span class="plan-price-amount">$10.000</span>
              <span class="plan-price-cur">COP</span>
            </div>
            <div class="plan-duration"><i class="bi bi-clock"></i> 7 días de máxima visibilidad</div>
            <ul class="plan-features">
              <li><i class="bi bi-check-circle-fill fi"></i> Slot destacado en la portada del sitio</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Top de resultados en búsqueda por categoría</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Insignia "Premium" exclusiva</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Cupos limitados por categoría</li>
              <li><i class="bi bi-check-circle-fill fi"></i> Máxima visibilidad garantizada</li>
            </ul>
            <button class="plan-btn plan-btn-premium">
              <i class="bi bi-gem"></i> Seleccionar plan
            </button>
          </div>

        </div>
      </div>

      <!-- ── SCREEN: SUMMARY ───────────────────────────── -->
      <div class="wizard-screen" id="screenSummary">
        <div class="summary-wrapper">
          <div class="summary-card">
            <div class="summary-header">
              <h2><i class="bi bi-receipt"></i> Resumen del pedido</h2>
            </div>
            <div class="summary-body">
              <div class="summary-row">
                <span class="label">Producto</span>
                <span class="value" id="sumProductTitle" style="max-width:60%;text-align:right;"></span>
              </div>
              <div class="summary-row">
                <span class="label">Plan seleccionado</span>
                <span class="value" id="sumPlanName"></span>
              </div>
              <div class="summary-row">
                <span class="label">Duración</span>
                <span class="value" id="sumDuration"></span>
              </div>
              <div class="summary-row">
                <span class="label">Fecha de inicio</span>
                <span class="value" id="sumStartDate"></span>
              </div>
              <div class="summary-row">
                <span class="label">Fecha de fin estimada</span>
                <span class="value" id="sumEndDate"></span>
              </div>
              <hr class="summary-divider">
              <div class="summary-total-row">
                <span class="total-label">Total a pagar</span>
                <span class="total-amount" id="sumTotal"></span>
              </div>
            </div>
          </div>
          <div class="wizard-actions">
            <button class="btn-back" onclick="goTo('plans')">
              <i class="bi bi-arrow-left"></i> Volver
            </button>
            <button class="btn-next" onclick="goTo('payment')">
              Continuar al pago <i class="bi bi-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ── SCREEN: PAYMENT METHOD ────────────────────── -->
      <div class="wizard-screen" id="screenPayment">
        <div class="summary-wrapper">
          <p class="payment-section-title">Selecciona tu método de pago</p>
          <div class="payment-methods-grid">
            <div class="payment-method-card" id="pmNequi" onclick="selectMethod('nequi')">
              <div class="pm-logo pm-logo-nequi"><i class="bi bi-phone-fill"></i></div>
              <div class="pm-name">Nequi</div>
              <div class="pm-sub">Pago desde tu celular</div>
            </div>
            <div class="payment-method-card" id="pmCard" onclick="selectMethod('card')">
              <div class="pm-logo pm-logo-card"><i class="bi bi-credit-card-fill"></i></div>
              <div class="pm-name">Tarjeta</div>
              <div class="pm-sub">Débito / Crédito</div>
            </div>
            <div class="payment-method-card" id="pmCash" onclick="selectMethod('cash')">
              <div class="pm-logo pm-logo-cash"><i class="bi bi-cash-stack"></i></div>
              <div class="pm-name">Efectivo</div>
              <div class="pm-sub">Pago en punto de venta</div>
            </div>
          </div>
          <div class="wizard-actions">
            <button class="btn-back" onclick="goTo('summary')">
              <i class="bi bi-arrow-left"></i> Volver
            </button>
            <button class="btn-next" id="btnToConfirm" onclick="goTo('confirm')" disabled>
              Revisar y confirmar <i class="bi bi-arrow-right"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- ── SCREEN: CONFIRM ───────────────────────────── -->
      <div class="wizard-screen" id="screenConfirm">
        <div class="confirm-wrapper">
          <div class="confirm-card">
            <div class="confirm-header">
              <h2><i class="bi bi-shield-check"></i> Confirma tu pago</h2>
              <p>Revisa los detalles antes de proceder</p>
            </div>
            <div class="confirm-body">
              <div id="confirmPlanBadge"></div>
              <div class="summary-row">
                <span class="label">Producto</span>
                <span class="value" id="confProduct" style="max-width:55%;text-align:right;"></span>
              </div>
              <div class="summary-row">
                <span class="label">Plan</span>
                <span class="value" id="confPlan"></span>
              </div>
              <div class="summary-row">
                <span class="label">Duración</span>
                <span class="value" id="confDuration"></span>
              </div>
              <div class="summary-row">
                <span class="label">Método de pago</span>
                <span class="value" id="confMethod"></span>
              </div>
              <hr class="summary-divider">
              <div class="summary-total-row">
                <span class="total-label">Total</span>
                <span class="total-amount" id="confTotal"></span>
              </div>
              <div class="confirm-security">
                <i class="bi bi-lock-fill" style="color:#22c55e"></i>
                Pago 100% seguro y cifrado. Tu información está protegida.
              </div>
            </div>
          </div>
          <div class="wizard-actions" style="margin-top:20px">
            <button class="btn-back" onclick="goTo('payment')">
              <i class="bi bi-arrow-left"></i> Volver
            </button>
            <button class="btn-confirm-pay" id="btnConfirmPay" onclick="confirmPayment()">
              <i class="bi bi-lock-fill"></i> Confirmar pago
            </button>
          </div>
        </div>
      </div>

      <!-- ── SCREEN: PROCESSING ────────────────────────── -->
      <div class="wizard-screen" id="screenProcessing">
        <div class="processing-screen">
          <div class="spinner-ring"></div>
          <h2>Procesando tu pago<span class="dots-anim"></span></h2>
          <p>Por favor espera. No cierres esta ventana.</p>
          <p style="font-size:.78rem;color:#94a3b8;margin-top:8px;">Esto toma solo unos segundos.</p>
        </div>
      </div>

      <!-- ── SCREEN: SUCCESS ───────────────────────────── -->
      <div class="wizard-screen" id="screenSuccess">
        <div class="result-screen">
          <div class="result-icon">✅</div>
          <div class="result-title success-title">¡Pago realizado con éxito!</div>
          <p style="color:#64748b;font-size:.9rem">Tu promoción ya está activa. ¡Tu producto ahora tiene mayor visibilidad!</p>
          <div class="txn-badge">
            <i class="bi bi-receipt"></i> ID: <span id="txnId"></span>
          </div>
          <div class="promo-result-grid">
            <div class="prg-header"><i class="bi bi-star-fill"></i> Detalles de tu promoción</div>
            <div class="prg-row"><span class="prg-label">Producto</span><span class="prg-val" id="resProduct"></span></div>
            <div class="prg-row"><span class="prg-label">Plan</span><span class="prg-val" id="resPlan"></span></div>
            <div class="prg-row"><span class="prg-label">Inicio</span><span class="prg-val" id="resStart"></span></div>
            <div class="prg-row"><span class="prg-label">Vence</span><span class="prg-val" id="resEnd"></span></div>
            <div class="prg-row"><span class="prg-label">Estado</span><span class="prg-val" style="color:#16a34a;"><i class="bi bi-circle-fill" style="font-size:.5rem"></i> Activo</span></div>
          </div>
          <div class="result-actions">
            <a href="./mis_productos.php" class="btn-result btn-result-primary">
              <i class="bi bi-box-seam"></i> Ver mis productos
            </a>
            <a href="./dashboard.php" class="btn-result btn-result-ghost">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </div>
        </div>
      </div>

      <!-- ── SCREEN: ERROR ─────────────────────────────── -->
      <div class="wizard-screen" id="screenError">
        <div class="result-screen">
          <div class="result-icon">❌</div>
          <div class="result-title error-title">Error en el pago</div>
          <p id="errorMessage" style="color:#64748b;font-size:.9rem;text-align:center;"></p>
          <div class="result-actions">
            <button class="btn-result btn-result-retry" onclick="goTo('plans')">
              <i class="bi bi-arrow-counterclockwise"></i> Intentar de nuevo
            </button>
            <a href="./dashboard.php" class="btn-result btn-result-ghost">
              <i class="bi bi-house"></i> Ir al dashboard
            </a>
          </div>
        </div>
      </div>

    </div><!-- /#promoWizard -->
  </main>
</div>

<!-- PHP → JS data bridge -->
<script>
  const PRODUCT_DATA   = <?= json_encode($product) ?>;
  const PRODUCT_IMAGE  = <?= json_encode($productImage) ?>;
  const ACTIVE_PROMO   = <?= json_encode($activePromo) ?>;
  const PRODUCT_ID     = <?= $product_id ?>;
  const INITIAL_SCREEN = <?= json_encode($initialScreen) ?>;
  const BASE_URL       = '../';
</script>
<script src="../../public/js/promociones.js"></script>
</body>
</html>