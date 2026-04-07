<?php
/**
 * Solicitar Verificación — ComercioLocal (user-facing)
 * Allows users/sellers to submit a seller verification request.
 */
require_once("../config/conexion.php");
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: ./auth/login.php"); exit();
}

$user       = $_SESSION['user'];
$user_id    = (int)$user['id'];
$userInitial= strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1));
$userName   = explode(' ', $user['full_name'] ?? 'Usuario')[0];

/* ── Bootstrap table ── */
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS seller_verifications (
        id               INT AUTO_INCREMENT PRIMARY KEY,
        user_id          INT NOT NULL,
        store_name       VARCHAR(255) NOT NULL,
        category         VARCHAR(100) DEFAULT NULL,
        city             VARCHAR(100) DEFAULT NULL,
        phone            VARCHAR(30)  DEFAULT NULL,
        document_type    VARCHAR(100) DEFAULT NULL,
        document_path    VARCHAR(255) DEFAULT NULL,
        description      TEXT         DEFAULT NULL,
        status           ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        rejection_reason TEXT         DEFAULT NULL,
        reviewed_by      INT          DEFAULT NULL,
        reviewed_at      TIMESTAMP    DEFAULT NULL,
        created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user   (user_id),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ── Fetch current user's latest verification ── */
$stmt = mysqli_prepare($conn, "
    SELECT id, store_name, category, city, phone, document_type,
           status, rejection_reason, created_at, reviewed_at
    FROM seller_verifications
    WHERE user_id = ?
    ORDER BY created_at DESC LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
$currentStatus = $existing['status'] ?? null;

/* ── Handle form submit ── */
$formError   = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sv_submit'])) {
    $store_name   = trim($_POST['store_name']   ?? '');
    $category     = trim($_POST['category']     ?? '');
    $city         = trim($_POST['city']         ?? '');
    $phone        = trim($_POST['phone']        ?? '');
    $doc_type     = trim($_POST['document_type'] ?? '');
    $description  = trim($_POST['description']  ?? '');

    /* Validation */
    if (mb_strlen($store_name) < 2)      $formError = 'El nombre del negocio debe tener al menos 2 caracteres.';
    elseif (empty($category))            $formError = 'Selecciona una categoría.';
    elseif (empty($city))                $formError = 'Indica tu ciudad.';
    elseif (empty($doc_type))            $formError = 'Selecciona el tipo de documento.';

    /* File upload */
    $doc_path = null;
    if (empty($formError) && !empty($_FILES['document']['name'])) {
        $file     = $_FILES['document'];
        $allowed  = ['image/jpeg','image/png','image/webp','application/pdf'];
        $maxSize  = 5 * 1024 * 1024; // 5 MB

        if (!in_array($file['type'], $allowed)) {
            $formError = 'El documento debe ser JPG, PNG, WEBP o PDF (máx. 5 MB).';
        } elseif ($file['size'] > $maxSize) {
            $formError = 'El archivo supera el límite de 5 MB.';
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'verif_' . $user_id . '_' . time() . '.' . $ext;
            $dir      = __DIR__ . '/../uploads/verificaciones/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $doc_path = 'uploads/verificaciones/' . $filename;
            } else {
                $formError = 'No se pudo guardar el documento. Intenta de nuevo.';
            }
        }
    }

    if (empty($formError)) {
        /* Cancel any previous rejected request before inserting */
        if ($currentStatus === 'rejected') {
            $del = mysqli_prepare($conn, "DELETE FROM seller_verifications WHERE user_id=? AND status='rejected'");
            mysqli_stmt_bind_param($del, 'i', $user_id);
            mysqli_stmt_execute($del);
        }

        $ins = mysqli_prepare($conn, "
            INSERT INTO seller_verifications
                (user_id, store_name, category, city, phone, document_type, document_path, description, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");
        mysqli_stmt_bind_param($ins, 'issssss s', $user_id, $store_name, $category, $city, $phone, $doc_type, $doc_path, $description);
        mysqli_stmt_execute($ins);

        if (mysqli_stmt_affected_rows($ins) > 0) {
            /* Log to activity_log silently */
            @mysqli_query($conn, "INSERT INTO activity_log (admin_id, action, created_at) VALUES ($user_id, 'Nueva solicitud de verificación enviada por usuario #{$user_id}', NOW())");

            header("Location: ./solicitar_verificacion.php?sent=1");
            exit();
        } else {
            $formError = 'No se pudo enviar la solicitud. Intenta de nuevo.';
        }
    }

    /* Re-fetch existing after possible insert */
    mysqli_stmt_execute($stmt);
    $existing      = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $currentStatus = $existing['status'] ?? null;
}

/* Flash message */
$sent = isset($_GET['sent']);

/* Product count for sidebar badge */
$totalProducts = 0;
$r = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM products WHERE user_id = $user_id");
if ($r) $totalProducts = (int)(mysqli_fetch_assoc($r)['c'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Solicitar verificación — ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../style/solicitar_verificacion.css">
</head>
<body>

  <!-- Header -->
  <?php
    $basePath    = '../';
    $isLoggedIn  = true;
    $userInitial = strtoupper(mb_substr($user['full_name'] ?? 'U', 0, 1));
    $userName    = explode(' ', $user['full_name'] ?? 'Usuario')[0];
    include __DIR__ . '/../components/header.php';
  ?>

  <div class="page-shell">

    <!-- ══════════════════════════════════
         SIDEBAR
    ══════════════════════════════════ -->
    <aside class="sidebar">
      <div class="sb-section-label">Principal</div>

      <a class="sb-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
      <a class="sb-link" href="./misProductos.php">
        <i class="bi bi-box-seam"></i> Mis productos
        <span class="sb-badge"><?= $totalProducts ?></span>
      </a>
      <a class="sb-link" href="./chat.php"><i class="bi bi-chat-dots"></i> Mensajes</a>
      <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>

      <div class="sb-divider"></div>
      <div class="sb-section-label">Cuenta</div>

      <a class="sb-link active" href="./solicitar_verificacion.php">
        <i class="bi bi-patch-check-fill"></i> Verificación
        <?php if ($currentStatus === 'pending'): ?>
          <span class="sb-badge yellow">En revisión</span>
        <?php elseif ($currentStatus === 'approved'): ?>
          <span class="sb-badge" style="background:var(--g100);color:var(--g700);">✓ Activo</span>
        <?php elseif ($currentStatus === 'rejected'): ?>
          <span class="sb-badge red">Rechazado</span>
        <?php endif; ?>
      </a>
      <a class="sb-link" href="./perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
      <a class="sb-link" href="#"><i class="bi bi-star"></i> Reseñas</a>
      <a class="sb-link" href="#"><i class="bi bi-gear"></i> Configuración</a>
      <a class="sb-link" href="../controller/logout.php" style="color:rgba(239,68,68,.7);">
        <i class="bi bi-box-arrow-right"></i> Cerrar sesión
      </a>

      <div class="sb-divider"></div>
      <div class="sb-promo">
        <div class="sb-promo-icon">⭐</div>
        <p>Verifica tu negocio y genera confianza con miles de compradores.</p>
        <a href="#form-top">Solicitar ahora</a>
      </div>
    </aside>

    <!-- ══════════════════════════════════
         MAIN CONTENT
    ══════════════════════════════════ -->
    <main class="main-content" id="form-top">

      <!-- Page header -->
      <div class="pg-header">
        <div>
          <div class="pg-breadcrumb">
            <a href="./dashboard.php">Dashboard</a>
            <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
            <span>Verificación</span>
          </div>
          <h1 class="pg-title">Solicitar <span>verificación</span></h1>
          <p class="pg-subtitle">Verifica tu negocio y obtén el sello de confianza de ComercioLocal.</p>
        </div>
      </div>

      <?php if ($sent): ?>
      <!-- ── Flash: sent ── -->
      <div class="sv-status-card pending" style="margin-bottom:24px;">
        <div class="sv-status-icon"><i class="bi bi-send-check-fill"></i></div>
        <div class="sv-status-body">
          <h3>¡Solicitud enviada correctamente!</h3>
          <p>Hemos recibido tu solicitud. Nuestro equipo la revisará en un plazo de 24 a 48 horas hábiles. Te notificaremos por correo cuando haya una respuesta.</p>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($formError)): ?>
      <div class="sv-status-card rejected" style="margin-bottom:24px;">
        <div class="sv-status-icon"><i class="bi bi-exclamation-circle-fill"></i></div>
        <div class="sv-status-body">
          <h3>Error al enviar</h3>
          <p><?= htmlspecialchars($formError) ?></p>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($currentStatus === 'approved'): ?>
      <!-- ── APPROVED STATE ── -->
      <div class="sv-status-card approved">
        <div class="sv-status-icon"><i class="bi bi-patch-check-fill"></i></div>
        <div class="sv-status-body">
          <h3>¡Tu negocio está verificado! <span class="sv-verified-badge"><i class="bi bi-patch-check-fill"></i> Verificado</span></h3>
          <p>
            <strong><?= htmlspecialchars($existing['store_name']) ?></strong> cuenta con el sello oficial de ComercioLocal.
            Tus compradores pueden confiar plenamente en tu tienda.
          </p>
          <p style="margin-top:8px;font-size:.8rem;color:var(--ink3);">
            Verificado el <?= $existing['reviewed_at'] ? date('d/m/Y', strtotime($existing['reviewed_at'])) : '—' ?>.
          </p>
        </div>
      </div>

      <?php elseif ($currentStatus === 'pending'): ?>
      <!-- ── PENDING STATE ── -->
      <div class="sv-status-card pending">
        <div class="sv-status-icon"><i class="bi bi-hourglass-split"></i></div>
        <div class="sv-status-body">
          <h3>Solicitud en revisión</h3>
          <p>
            Tu solicitud para <strong><?= htmlspecialchars($existing['store_name']) ?></strong> fue enviada el
            <strong><?= date('d/m/Y', strtotime($existing['created_at'])) ?></strong>.
            Nuestro equipo la está revisando. Te notificaremos en 24–48 horas hábiles.
          </p>
          <div class="sv-status-actions">
            <button class="sv-submit" style="width:auto;height:40px;padding:0 20px;font-size:.88rem;"
                    onclick="cancelRequest()">
              <i class="bi bi-x-circle"></i> Cancelar solicitud
            </button>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ── FORM (new or resubmit after rejection) ── -->

      <?php if ($currentStatus === 'rejected'): ?>
      <div class="sv-status-card rejected">
        <div class="sv-status-icon"><i class="bi bi-x-circle-fill"></i></div>
        <div class="sv-status-body">
          <h3>Solicitud rechazada</h3>
          <p>Tu solicitud anterior fue rechazada. Puedes corregir los datos y volver a intentarlo.</p>
          <?php if (!empty($existing['rejection_reason'])): ?>
          <div class="sv-rejection-box">
            <strong>Motivo:</strong> <?= htmlspecialchars($existing['rejection_reason']) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Info box -->
      <div class="sv-info-box">
        <i class="bi bi-info-circle-fill"></i>
        <p>
          La verificación confirma la identidad de tu negocio ante nuestros compradores.
          Necesitarás un documento oficial (RUT, NIT, Cámara de Comercio o cédula) y los datos de tu tienda.
          La revisión toma entre <strong>24 y 48 horas hábiles</strong>.
        </p>
      </div>

      <!-- Form card -->
      <div class="sv-form-card">
        <div class="sv-form-header">
          <div class="sv-form-header-icon"><i class="bi bi-patch-check-fill"></i></div>
          <div class="sv-form-header-text">
            <h2><?= $currentStatus === 'rejected' ? 'Reenviar solicitud de verificación' : 'Nueva solicitud de verificación' ?></h2>
            <p>Completa los datos de tu negocio para solicitar el sello oficial</p>
          </div>
        </div>

        <div class="sv-form-body">
          <form method="POST" enctype="multipart/form-data" id="svForm" novalidate>

            <div class="sv-form-grid">
              <!-- Store name -->
              <div class="sv-field full">
                <label class="sv-label" for="store_name">Nombre del negocio <span>*</span></label>
                <input class="sv-input" type="text" id="store_name" name="store_name"
                       placeholder="Ej. TechStore Medellín"
                       value="<?= htmlspecialchars($_POST['store_name'] ?? ($existing['store_name'] ?? '')) ?>"
                       required>
                <span class="sv-field-err" id="err-store_name"></span>
              </div>

              <!-- Category -->
              <div class="sv-field">
                <label class="sv-label" for="category">Categoría principal <span>*</span></label>
                <select class="sv-select" id="category" name="category" required>
                  <option value="">Selecciona una categoría</option>
                  <?php
                  $cats = ['Electrónica','Ropa y moda','Hogar y jardín','Joyería y relojes',
                           'Automóviles','Libros y cultura','Fotografía','Muebles y decoración',
                           'Deportes y fitness','Salud y belleza','Alimentos y bebidas','Otro'];
                  $selCat = $_POST['category'] ?? ($existing['category'] ?? '');
                  foreach ($cats as $c) {
                      $sel = ($selCat === $c) ? 'selected' : '';
                      echo "<option value=\"" . htmlspecialchars($c) . "\" $sel>" . htmlspecialchars($c) . "</option>";
                  }
                  ?>
                </select>
                <span class="sv-field-err" id="err-category"></span>
              </div>

              <!-- City -->
              <div class="sv-field">
                <label class="sv-label" for="city">Ciudad <span>*</span></label>
                <input class="sv-input" type="text" id="city" name="city"
                       placeholder="Ej. Bogotá, Colombia"
                       value="<?= htmlspecialchars($_POST['city'] ?? ($existing['city'] ?? '')) ?>"
                       required>
                <span class="sv-field-err" id="err-city"></span>
              </div>

              <!-- Phone -->
              <div class="sv-field">
                <label class="sv-label" for="phone">Teléfono de contacto</label>
                <input class="sv-input" type="tel" id="phone" name="phone"
                       placeholder="+57 300 000 0000"
                       value="<?= htmlspecialchars($_POST['phone'] ?? ($existing['phone'] ?? '')) ?>">
              </div>

              <!-- Description -->
              <div class="sv-field full">
                <label class="sv-label" for="description">Descripción del negocio</label>
                <textarea class="sv-textarea" id="description" name="description"
                          placeholder="Cuéntanos brevemente a qué se dedica tu negocio, qué vendes y por qué merece la verificación…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <span class="sv-field-hint">Opcional — hasta 500 caracteres</span>
              </div>
            </div>

            <!-- Document type -->
            <div class="sv-field" style="margin-bottom:18px;">
              <label class="sv-label">Tipo de documento <span>*</span></label>
              <div class="sv-doc-types">
                <?php
                $docTypes = [
                  ['RUT',                  'bi-file-earmark-text',  'Registro Único Tributario'],
                  ['NIT',                  'bi-building',           'Número de Identificación'],
                  ['Cámara de Comercio',   'bi-award',              'Registro mercantil'],
                  ['Cédula de ciudadanía', 'bi-person-badge',       'Documento de identidad'],
                ];
                $selDoc = $_POST['document_type'] ?? ($existing['document_type'] ?? '');
                foreach ($docTypes as $dt) {
                  $isSel = ($selDoc === $dt[0]) ? 'selected' : '';
                ?>
                <label class="sv-doc-type <?= $isSel ?>" onclick="selectDocType(this)">
                  <input type="radio" name="document_type" value="<?= htmlspecialchars($dt[0]) ?>" <?= $isSel ? 'checked' : '' ?>>
                  <div class="sv-doc-type-icon"><i class="bi <?= $dt[1] ?>"></i></div>
                  <div class="sv-doc-type-text">
                    <strong><?= htmlspecialchars($dt[0]) ?></strong>
                    <small><?= htmlspecialchars($dt[2]) ?></small>
                  </div>
                </label>
                <?php } ?>
              </div>
              <span class="sv-field-err" id="err-document_type"></span>
            </div>

            <!-- Document upload -->
            <div class="sv-field" style="margin-bottom:24px;">
              <label class="sv-label">Adjuntar documento <span style="color:var(--ink3);font-weight:400;">(JPG, PNG o PDF · máx. 5 MB)</span></label>
              <div class="sv-file-drop" id="fileDrop">
                <input type="file" name="document" id="docFile" accept=".jpg,.jpeg,.png,.webp,.pdf"
                       onchange="handleFile(this)">
                <div id="fileDropContent">
                  <div class="sv-file-drop-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                  <p>Arrastra tu documento aquí o <strong style="color:var(--g600);">haz clic para seleccionar</strong></p>
                  <small>Formatos aceptados: JPG, PNG, WEBP, PDF — máx. 5 MB</small>
                </div>
              </div>
              <div class="sv-file-preview" id="filePreview">
                <i class="bi bi-file-earmark-check-fill"></i>
                <span id="fileName"></span>
                <button type="button" title="Quitar archivo" onclick="removeFile()"><i class="bi bi-x-lg"></i></button>
              </div>
            </div>

            <button type="submit" name="sv_submit" class="sv-submit" id="submitBtn">
              <i class="bi bi-patch-check-fill"></i>
              <?= $currentStatus === 'rejected' ? 'Reenviar solicitud' : 'Enviar solicitud de verificación' ?>
            </button>

          </form>
        </div>
      </div>
      <?php endif; ?>

    </main>
  </div>

  <!-- Toasts -->
  <div class="sv-toast-area" id="toastArea"></div>

<script>
/* ── Document type selector ── */
function selectDocType(el) {
  document.querySelectorAll('.sv-doc-type').forEach(d => d.classList.remove('selected'));
  el.classList.add('selected');
  el.querySelector('input[type=radio]').checked = true;
}

/* ── File upload ── */
function handleFile(input) {
  const file = input.files[0];
  if (!file) return;
  const maxSize = 5 * 1024 * 1024;
  if (file.size > maxSize) {
    toast('error', 'Archivo demasiado grande', 'El límite es 5 MB');
    input.value = '';
    return;
  }
  document.getElementById('fileName').textContent = file.name + ' (' + (file.size / 1024).toFixed(0) + ' KB)';
  document.getElementById('filePreview').classList.add('show');
}

function removeFile() {
  document.getElementById('docFile').value = '';
  document.getElementById('filePreview').classList.remove('show');
  document.getElementById('fileName').textContent = '';
}

/* ── Drag & drop ── */
const drop = document.getElementById('fileDrop');
if (drop) {
  drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag'); });
  drop.addEventListener('dragleave', () => drop.classList.remove('drag'));
  drop.addEventListener('drop', e => {
    e.preventDefault(); drop.classList.remove('drag');
    const dt = new DataTransfer();
    dt.items.add(e.dataTransfer.files[0]);
    const input = document.getElementById('docFile');
    input.files = dt.files;
    handleFile(input);
  });
}

/* ── Client validation ── */
document.getElementById('svForm')?.addEventListener('submit', function (e) {
  let ok = true;
  const clr = id => { const el = document.getElementById(id); if(el){el.classList.remove('show');el.previousElementSibling?.classList.remove('err');} };
  const err = (id, msg) => {
    const el = document.getElementById(id);
    if(el){el.textContent=msg;el.classList.add('show');}
    const inp = el?.previousElementSibling; if(inp) inp.classList.add('err');
    ok = false;
  };

  ['err-store_name','err-category','err-city','err-document_type'].forEach(clr);

  if (document.getElementById('store_name').value.trim().length < 2) err('err-store_name','Mínimo 2 caracteres');
  if (!document.getElementById('category').value) err('err-category','Selecciona una categoría');
  if (!document.getElementById('city').value.trim()) err('err-city','Indica tu ciudad');
  if (!document.querySelector('input[name="document_type"]:checked')) err('err-document_type','Selecciona el tipo de documento');

  if (!ok) e.preventDefault();
  else {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Enviando…';
  }
});

/* ── Cancel request ── */
async function cancelRequest() {
  if (!confirm('¿Cancelar tu solicitud de verificación?')) return;
  try {
    const res  = await fetch('../api/verificacion.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ action:'cancel' }),
    });
    const json = await res.json();
    if (json.ok) { toast('success','Cancelada','Solicitud cancelada.'); setTimeout(() => location.reload(), 1200); }
    else toast('error','Error', json.error);
  } catch { toast('error','Error de red','No se pudo conectar'); }
}

/* ── Toast ── */
function toast(type, title, msg) {
  const icons = { success:'bi-check-circle-fill', error:'bi-x-circle-fill' };
  const el = document.createElement('div');
  el.className = `sv-toast ${type}`;
  el.innerHTML = `<i class="bi ${icons[type]||'bi-info-circle-fill'}"></i>
    <div class="sv-toast-body"><strong>${title}</strong>${msg?`<span>${msg}</span>`:''}</div>`;
  document.getElementById('toastArea').appendChild(el);
  setTimeout(() => { el.classList.add('exit'); setTimeout(() => el.remove(), 300); }, 4000);
}

<?php if ($sent): ?>
toast('success', '¡Solicitud enviada!', 'La revisaremos en 24–48 horas.');
<?php endif; ?>
</script>
</body>
</html>
