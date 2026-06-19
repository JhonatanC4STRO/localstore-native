<?php
session_start();
require_once "../../config/conexion.php";

// Validar que el usuario provenga de la migración de contraseña
if (!isset($_SESSION['migrate_user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['migrate_user_id'];
$query = mysqli_prepare($conn, "SELECT full_name, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($query, "i", $user_id);
mysqli_stmt_execute($query);
$res = mysqli_stmt_get_result($query);
$user = mysqli_fetch_assoc($res);
mysqli_stmt_close($query);

if (!$user) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Restablecer contraseña – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../../public/css/login.css">
  <link rel="stylesheet" href="../../../public/css/output.css">
  <style>
    /* Estilos Premium para la pantalla de Restablecimiento */
    .migrate-alert {
      background: rgba(239, 68, 68, 0.08);
      border: 1px solid rgba(239, 68, 68, 0.2);
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      gap: 10px;
      color: #b91c1c;
      font-size: 0.88rem;
      line-height: 1.4;
      align-items: flex-start;
    }
    .migrate-alert i {
      font-size: 1.2rem;
      color: #ef4444;
      margin-top: 2px;
    }
    .user-pill {
      background: rgba(255, 255, 255, 0.08);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 30px;
      padding: 8px 16px;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      margin-bottom: 1.5rem;
      font-size: 0.85rem;
      color: var(--text-soft, #94a3b8);
    }
    .user-pill strong {
      color: #f8fafc;
    }
  </style>
</head>

<body>

  <!-- ══ LEFT PANEL ══ -->
  <div class="left-panel">
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="left-content">
      <a href="../home.php" class="flex justify-center"><img class="h-40 w-40" src="../../../public/img/logo.png" alt="Logo"></a>

      <div class="scene">
        <div class="scene-bg">
          <div class="scene-inner">🔒</div>
        </div>
      </div>

      <h2 class="scene-tagline">Seguridad<br>ante <em>todo</em></h2>
      <p class="scene-sub">Hemos actualizado nuestros algoritmos de protección. Tu cuenta ahora se cifrará con encriptación militar robusta y moderna.</p>
    </div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="right-panel">
    <div class="corner-tl"></div>
    <div class="corner-br"></div>

    <div class="form-shell">
      <div class="form-welcome">
        <div class="form-eyebrow"><i class="bi bi-shield-lock-fill"></i> Actualización de Seguridad</div>
        <h1 class="form-title">Actualiza tu<br><span>contraseña</span></h1>
        <div class="title-accent"></div>
        <p class="form-sub">Para asegurar tu información y migrar tu cuenta con éxito, por favor introduce una contraseña nueva.</p>
      </div>

      <div class="user-pill">
        <i class="bi bi-person-circle"></i>
        <span>Usuario: <strong><?= htmlspecialchars($user['full_name']) ?></strong> (<?= htmlspecialchars($user['email']) ?>)</span>
      </div>

      <div class="migrate-alert">
        <i class="bi bi-shield-exclamation"></i>
        <div>
          <strong>¡Acción requerida!</strong> Tu contraseña actual está almacenada con un formato heredado de baja seguridad. Es necesario establecer una nueva contraseña cifrada para poder continuar.
        </div>
      </div>

      <form action="../../controllers/auth_reset_migrado.php" method="POST" id="resetForm">
        <?php require_once __DIR__ . "/../../config/csrf.php"; insert_csrf_input(); ?>
        
        <div class="form-group">
          <label class="form-label" for="password">
            <i class="bi bi-key-fill"></i> Nueva contraseña
          </label>
          <div class="input-wrap">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required minlength="6">
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Mostrar contraseña">
              <i class="bi bi-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">
            <i class="bi bi-key-fill"></i> Confirmar nueva contraseña
          </label>
          <div class="input-wrap">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repite la contraseña" required>
            <button type="button" class="pw-toggle" id="pwToggleConfirm" aria-label="Mostrar contraseña">
              <i class="bi bi-eye" id="pwIconConfirm"></i>
            </button>
          </div>
        </div>

        <?php if (isset($_GET['error'])): ?>
          <div style="color: #ef4444; font-size: 0.875rem; margin-bottom: 1rem; text-align: center; font-weight: 600;">
            <i class="bi bi-exclamation-circle-fill"></i> Las contraseñas no coinciden o no cumplen con los requisitos.
          </div>
        <?php endif; ?>

        <button type="submit" name="reset" class="btn-login">
          Guardar y entrar
          <div class="arrow-icon"><i class="bi bi-arrow-right"></i></div>
        </button>
      </form>

      <div class="register-prompt">
        ¿Prefieres volver? <a href="./login.php">Iniciar sesión con otra cuenta</a>
      </div>
    </div>
  </div>

  <script>
    // Toggle visibilidad contraseña
    const pwToggle = document.getElementById('pwToggle');
    const pwInput = document.getElementById('password');
    const pwIcon = document.getElementById('pwIcon');
    pwToggle.addEventListener('click', () => {
      const isPw = pwInput.type === 'password';
      pwInput.type = isPw ? 'text' : 'password';
      pwIcon.className = isPw ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    const pwToggleConfirm = document.getElementById('pwToggleConfirm');
    const pwInputConfirm = document.getElementById('confirm_password');
    const pwIconConfirm = document.getElementById('pwIconConfirm');
    pwToggleConfirm.addEventListener('click', () => {
      const isPw = pwInputConfirm.type === 'password';
      pwInputConfirm.type = isPw ? 'text' : 'password';
      pwIconConfirm.className = isPw ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    // Validar coincidencia de contraseñas
    const form = document.getElementById('resetForm');
    form.addEventListener('submit', (e) => {
      if (pwInput.value !== pwInputConfirm.value) {
        e.preventDefault();
        alert('Las contraseñas no coinciden.');
      }
    });
  </script>
</body>
</html>
