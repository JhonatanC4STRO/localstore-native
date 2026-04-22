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

// Badge sidebar
$totalProducts = 0;
$rc = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = '$user_id'");
if ($rc) $totalProducts = (int) mysqli_fetch_assoc($rc)['total'];

// Asegurar tabla
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS user_settings (
    user_id INT PRIMARY KEY,
    notif_email_messages TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_reviews  TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_sales    TINYINT(1) NOT NULL DEFAULT 1,
    notif_email_promos   TINYINT(1) NOT NULL DEFAULT 1,
    notif_browser        TINYINT(1) NOT NULL DEFAULT 1,
    privacy_show_phone   TINYINT(1) NOT NULL DEFAULT 1,
    privacy_show_email   TINYINT(1) NOT NULL DEFAULT 0,
    privacy_who_can_message ENUM('all','verified','nobody') NOT NULL DEFAULT 'all',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Cargar preferencias
$settings = [
  'notif_email_messages'    => 1,
  'notif_email_reviews'     => 1,
  'notif_email_sales'       => 1,
  'notif_email_promos'      => 1,
  'notif_browser'           => 1,
  'privacy_show_phone'      => 1,
  'privacy_show_email'      => 0,
  'privacy_who_can_message' => 'all',
];
$q = mysqli_query($conn, "SELECT * FROM user_settings WHERE user_id = $user_id");
if ($q && mysqli_num_rows($q) > 0) {
  $row = mysqli_fetch_assoc($q);
  foreach ($settings as $k => $v) {
    if (isset($row[$k])) $settings[$k] = $row[$k];
  }
}
$chk = fn($k) => ((int) $settings[$k]) === 1 ? 'checked' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuración – ComercioLocal</title>
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
    }
    .cfg-wrap { display: flex; flex-direction: column; gap: 20px; max-width: 820px; }
    .cfg-card {
      background: #fff;
      border: 1.5px solid var(--bord);
      border-radius: 16px;
      padding: 24px 26px;
    }
    .cfg-head {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 6px;
    }
    .cfg-head-icon {
      width: 40px; height: 40px;
      border-radius: 10px;
      background: var(--g50);
      color: var(--g700);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
    }
    .cfg-head h2 {
      font-size: 1.05rem;
      font-weight: 700;
      color: var(--ink);
      margin: 0;
    }
    .cfg-head p {
      font-size: .8rem;
      color: var(--ink3);
      margin: 2px 0 0;
    }
    .cfg-divider { border-top: 1px solid var(--bord); margin: 16px 0; }
    .cfg-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 10px 0;
    }
    .cfg-row + .cfg-row { border-top: 1px dashed var(--bord); }
    .cfg-row-info { min-width: 0; flex: 1; }
    .cfg-row-title {
      font-size: .9rem;
      font-weight: 600;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .cfg-row-title i { color: var(--ink3); font-size: 1rem; }
    .cfg-row-sub {
      font-size: .78rem;
      color: var(--ink3);
      margin-top: 2px;
    }

    /* Toggle switch */
    .switch {
      position: relative;
      display: inline-block;
      width: 46px;
      height: 26px;
      flex-shrink: 0;
    }
    .switch input { opacity: 0; width: 0; height: 0; }
    .slider {
      position: absolute;
      inset: 0;
      cursor: pointer;
      background: #cbd5e1;
      border-radius: 999px;
      transition: .2s;
    }
    .slider::before {
      content: "";
      position: absolute;
      height: 20px;
      width: 20px;
      left: 3px;
      bottom: 3px;
      background: #fff;
      border-radius: 50%;
      transition: .2s;
      box-shadow: 0 1px 3px rgba(0,0,0,.15);
    }
    .switch input:checked + .slider { background: var(--g500); }
    .switch input:checked + .slider::before { transform: translateX(20px); }

    .cfg-select {
      padding: 8px 12px;
      border: 1.5px solid var(--bord);
      border-radius: 10px;
      font-size: .85rem;
      font-weight: 600;
      color: var(--ink2);
      background: #fff;
      cursor: pointer;
      outline: none;
      transition: border-color .15s;
    }
    .cfg-select:focus { border-color: var(--g500); }

    .cfg-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 22px;
      background: #fff;
      border: 1.5px solid var(--bord);
      border-radius: 16px;
      position: sticky;
      bottom: 16px;
      z-index: 5;
    }
    .cfg-footer-hint {
      font-size: .8rem;
      color: var(--ink3);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .btn-save {
      background: var(--g500);
      color: #fff;
      border: none;
      padding: 10px 22px;
      border-radius: 10px;
      font-weight: 700;
      font-size: .88rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background .15s;
    }
    .btn-save:hover { background: var(--g600); }
    .btn-save:disabled { opacity: .6; cursor: not-allowed; }

    .cfg-toast {
      position: fixed;
      right: 20px;
      bottom: 20px;
      padding: 14px 18px;
      background: var(--g700);
      color: #fff;
      border-radius: 12px;
      font-size: .88rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 8px;
      box-shadow: 0 10px 30px rgba(0,0,0,.2);
      opacity: 0;
      transform: translateY(12px);
      transition: all .25s;
      z-index: 100;
      pointer-events: none;
    }
    .cfg-toast.show { opacity: 1; transform: translateY(0); }
    .cfg-toast.err { background: #dc2626; }

    .danger-card {
      border-color: #fecaca;
      background: #fff;
    }
    .danger-card .cfg-head-icon { background: #fee2e2; color: #dc2626; }
    .btn-danger {
      background: #fff;
      border: 1.5px solid #fecaca;
      color: #dc2626;
      padding: 8px 14px;
      border-radius: 10px;
      font-size: .82rem;
      font-weight: 700;
      cursor: pointer;
      transition: all .15s;
    }
    .btn-danger:hover { background: #fee2e2; }

    @media (max-width: 640px) {
      .cfg-row { flex-direction: column; align-items: flex-start; gap: 8px; }
      .cfg-footer { flex-direction: column; align-items: stretch; }
      .btn-save { width: 100%; justify-content: center; }
    }
  </style>
</head>
<body>

  <?php $basePath = "../../"; include __DIR__ . '/../components/header.php'; ?>

  <div class="page-shell">
    <?php $activeTab = 'configuracion'; include __DIR__ . '/../components/sidebar.php'; ?>

    <main class="main-content">

      <div class="pg-header">
        <div>
          <div class="pg-breadcrumb">
            <a href="./dashboard.php">Dashboard</a>
            <i class="bi bi-chevron-right" style="font-size:.65rem;"></i>
            <span>Configuración</span>
          </div>
          <h1 class="pg-title">Configuración de <span>la cuenta</span></h1>
          <p class="pg-subtitle">
            Controla tus notificaciones y quién puede ver tu información. Los datos personales se editan en tu <a href="./perfil.php" style="color:var(--g600);font-weight:700;">perfil</a>.
          </p>
        </div>
      </div>

      <form id="cfgForm" class="cfg-wrap" onsubmit="return false;">

        <!-- ══ NOTIFICACIONES ══ -->
        <div class="cfg-card">
          <div class="cfg-head">
            <div class="cfg-head-icon"><i class="bi bi-bell-fill"></i></div>
            <div>
              <h2>Notificaciones</h2>
              <p>Elige cuándo quieres que te avisemos.</p>
            </div>
          </div>
          <div class="cfg-divider"></div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-chat-dots"></i> Nuevos mensajes</div>
              <div class="cfg-row-sub">Recibe un correo cuando alguien te escriba.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="notif_email_messages" value="1" <?= $chk('notif_email_messages') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-star"></i> Nuevas reseñas</div>
              <div class="cfg-row-sub">Cuando un comprador deje una reseña en tus productos.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="notif_email_reviews" value="1" <?= $chk('notif_email_reviews') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-bag-check"></i> Ventas y transacciones</div>
              <div class="cfg-row-sub">Avisos cuando un producto tuyo se vende o cambia de estado.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="notif_email_sales" value="1" <?= $chk('notif_email_sales') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-rocket-takeoff"></i> Promociones por expirar</div>
              <div class="cfg-row-sub">Recordatorio antes de que termine una promoción activa.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="notif_email_promos" value="1" <?= $chk('notif_email_promos') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-window"></i> Notificaciones del navegador</div>
              <div class="cfg-row-sub">Avisos en tiempo real mientras tengas la página abierta.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="notif_browser" value="1" <?= $chk('notif_browser') ?>>
              <span class="slider"></span>
            </label>
          </div>
        </div>

        <!-- ══ PRIVACIDAD ══ -->
        <div class="cfg-card">
          <div class="cfg-head">
            <div class="cfg-head-icon"><i class="bi bi-shield-lock-fill"></i></div>
            <div>
              <h2>Privacidad</h2>
              <p>Decide qué información tuya pueden ver los demás.</p>
            </div>
          </div>
          <div class="cfg-divider"></div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-telephone"></i> Mostrar mi teléfono</div>
              <div class="cfg-row-sub">Visible en la ficha de tus productos para que te contacten.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="privacy_show_phone" value="1" <?= $chk('privacy_show_phone') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-envelope"></i> Mostrar mi correo</div>
              <div class="cfg-row-sub">Si está apagado, los compradores solo podrán contactarte por el chat.</div>
            </div>
            <label class="switch">
              <input type="checkbox" name="privacy_show_email" value="1" <?= $chk('privacy_show_email') ?>>
              <span class="slider"></span>
            </label>
          </div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-chat-left-text"></i> ¿Quién puede enviarte mensajes?</div>
              <div class="cfg-row-sub">Controla quién inicia conversaciones contigo.</div>
            </div>
            <select class="cfg-select" name="privacy_who_can_message">
              <option value="all"      <?= $settings['privacy_who_can_message'] === 'all'      ? 'selected' : '' ?>>Cualquiera</option>
              <option value="verified" <?= $settings['privacy_who_can_message'] === 'verified' ? 'selected' : '' ?>>Solo usuarios verificados</option>
              <option value="nobody"   <?= $settings['privacy_who_can_message'] === 'nobody'   ? 'selected' : '' ?>>Nadie (chat cerrado)</option>
            </select>
          </div>
        </div>

        <!-- ══ CUENTA ══ -->
        <div class="cfg-card danger-card">
          <div class="cfg-head">
            <div class="cfg-head-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div>
              <h2>Zona de cuenta</h2>
              <p>Acciones sobre tu sesión.</p>
            </div>
          </div>
          <div class="cfg-divider"></div>

          <div class="cfg-row">
            <div class="cfg-row-info">
              <div class="cfg-row-title"><i class="bi bi-box-arrow-right"></i> Cerrar sesión</div>
              <div class="cfg-row-sub">Termina tu sesión actual en este dispositivo.</div>
            </div>
            <a href="../controllers/auth_logout.php" class="btn-danger">Cerrar sesión</a>
          </div>
        </div>

        <!-- ══ FOOTER ══ -->
        <div class="cfg-footer">
          <div class="cfg-footer-hint">
            <i class="bi bi-info-circle"></i>
            Los cambios se aplican al guardar.
          </div>
          <button type="submit" class="btn-save" id="btnSave">
            <i class="bi bi-check2-circle"></i> Guardar cambios
          </button>
        </div>

      </form>

    </main>
  </div>

  <div class="cfg-toast" id="cfgToast"><i class="bi bi-check-circle-fill"></i><span id="cfgToastMsg">Guardado</span></div>

  <script>
    const form     = document.getElementById('cfgForm');
    const btnSave  = document.getElementById('btnSave');
    const toast    = document.getElementById('cfgToast');
    const toastMsg = document.getElementById('cfgToastMsg');

    function showToast(msg, isErr = false) {
      toast.classList.toggle('err', isErr);
      toast.querySelector('i').className = isErr
        ? 'bi bi-exclamation-circle-fill'
        : 'bi bi-check-circle-fill';
      toastMsg.textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2600);
    }

    btnSave.addEventListener('click', async () => {
      const fd = new FormData();
      const switches = [
        'notif_email_messages','notif_email_reviews','notif_email_sales',
        'notif_email_promos','notif_browser',
        'privacy_show_phone','privacy_show_email'
      ];
      switches.forEach(name => {
        const el = form.querySelector(`[name="${name}"]`);
        fd.append(name, el && el.checked ? '1' : '0');
      });
      const who = form.querySelector('[name="privacy_who_can_message"]');
      fd.append('privacy_who_can_message', who ? who.value : 'all');

      btnSave.disabled = true;
      btnSave.innerHTML = '<i class="bi bi-hourglass-split"></i> Guardando...';
      try {
        const res  = await fetch('../controllers/settings_action.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) showToast(data.message || 'Preferencias guardadas.');
        else              showToast(data.message || 'No se pudo guardar.', true);
      } catch (e) {
        showToast('Error de conexión.', true);
      } finally {
        btnSave.disabled = false;
        btnSave.innerHTML = '<i class="bi bi-check2-circle"></i> Guardar cambios';
      }
    });
  </script>

</body>
</html>
