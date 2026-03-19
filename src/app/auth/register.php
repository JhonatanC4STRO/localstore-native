<?php
require_once "../../config/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Crear cuenta – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../style/register.css">
  <link rel="stylesheet" href="../../output.css">
</head>

<body>

  <!-- ══ LEFT PANEL ══ -->
  <div class="left-panel">
    <div class="lp-blob lp-blob-1"></div>
    <div class="lp-blob lp-blob-2"></div>

    <div class="lp-content">

       <a href="../inde.php" class=" flex justify-center" ><img class="h-40 w-40" src="../Logo de Comercio Local.png" alt=""></a>

      <h2 class="lp-headline">Únete a la<br>comunidad <em>local</em><br>más grande</h2>
      <p class="lp-sub">Más de 120.000 personas ya compran y venden en su ciudad con ComercioLocal. ¡Sé parte hoy!</p>

      <div class="benefit-cards">

        <div class="benefit-card">
          <div class="bc-icon bc-green"><i class="bi bi-shop"></i></div>
          <div class="bc-text">
            <h4>Compra y vende localmente</h4>
            <p>Publica tus productos en minutos y llega a compradores en tu misma ciudad.</p>
          </div>
        </div>

        <div class="benefit-card">
          <div class="bc-icon bc-yellow"><i class="bi bi-geo-alt-fill"></i></div>
          <div class="bc-text">
            <h4>Descubre productos cercanos</h4>
            <p>Encuentra artículos de calidad a pocas cuadras de tu casa o trabajo.</p>
          </div>
        </div>

        <div class="benefit-card">
          <div class="bc-icon bc-blue"><i class="bi bi-chat-dots-fill"></i></div>
          <div class="bc-text">
            <h4>Conecta con vendedores</h4>
            <p>Chatea directamente, negocia precios y coordina entregas de forma segura.</p>
          </div>
        </div>

        <div class="benefit-card">
          <div class="bc-icon bc-purple"><i class="bi bi-shield-check-fill"></i></div>
          <div class="bc-text">
            <h4>Transacciones seguras</h4>
            <p>Perfiles verificados, reseñas reales y soporte en cada paso del camino.</p>
          </div>
        </div>

      </div>

      <div class="lp-tagline">
        <i class="bi bi-lightning-charge-fill"></i>
        <p>Registro <strong>gratuito</strong> · Sin comisiones ocultas · Publica en menos de <strong>2 minutos</strong></p>
      </div>

    </div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="right-panel">
    <div class="rp-deco-tl"></div>
    <div class="rp-deco-br"></div>

    <div class="form-shell">

      <!-- Progress bar (visual only) -->
      <div class="progress-bar-wrap">
        <div class="progress-bar-fill" id="progressFill"></div>
      </div>

      <div class="form-header">
        <div class="form-eyebrow"><i class="bi bi-person-plus-fill"></i> Nueva cuenta</div>
        <h1 class="form-title">Crear <span>cuenta</span></h1>
        <div class="accent-line"></div>
        <p class="form-sub">Completa el formulario para unirte a la comunidad ComercioLocal.</p>
      </div>

      <?php

      ?>
      <!-- Account type (visual, no logic change) -->
      <div class="section-label">Tipo de cuenta</div>
      <div class="acct-type-row">
        <div class="acct-opt active" id="opt-personal" onclick="selectAcct('personal')">
          <div class="ao-check"><i class="bi bi-check-lg"></i></div>
          <div class="ao-icon ao-personal"><i class="bi bi-person-fill"></i></div>
          <span class="ao-label" onclick="selectAcct('personal')">Personal</span>
          <span class="ao-desc">Compra y vende como particular</span>
        </div>
        <div class="acct-opt" id="opt-tienda" onclick="selectAcct('tienda')">
          <div class="ao-check"><i class="bi bi-check-lg"></i></div>
          <div class="ao-icon ao-tienda"><i class="bi bi-shop-window"></i></div>
          <span class="ao-label" onclick="selectAcct('tienda')">Tienda</span>
          <span class="ao-desc">Perfil comercial con más visibilidad</span>
        </div>
      </div>

      <!-- ── LOGIC 100% PRESERVED: action, method, name attrs ── -->
      <form action="../../controller/process_register.php" method="POST" id="regForm" novalidate>
        <input type="hidden" name="type_user" id="type_user" value="usuario">
        <div class="section-label">Tus datos</div>

        <div class="form-group">
          <label class="form-label" for="full_name">
            <i class="bi bi-person-fill"></i> Nombre completo <span class="req">*</span>
          </label>
          <div class="input-wrap">
            <i class="bi bi-person input-icon"></i>
            <!-- LOGIC PRESERVED: name="full_name" -->
            <input type="text" id="full_name" name="full_name"
              class="form-control" placeholder="Ej: María García López" required>
          </div>
        </div>

        <!-- LOGIC PRESERVED: name="email" -->
        <div class="form-group">
          <label class="form-label" for="email">
            <i class="bi bi-envelope-fill"></i> Correo electrónico <span class="req">*</span>
          </label>
          <div class="input-wrap">
            <i class="bi bi-envelope input-icon"></i>
            <input type="email" id="email" name="email"
              class="form-control" placeholder="tucorreo@ejemplo.com" required>
          </div>
        </div>

        <!-- LOGIC PRESERVED: name="password" -->
        <div class="form-group">
          <label class="form-label" for="password">
            <i class="bi bi-lock-fill"></i> Contraseña <span class="req">*</span>
          </label>
          <div class="input-wrap">
            <i class="bi bi-lock input-icon"></i>
            <input type="password" id="password" name="password"
              class="form-control" placeholder="Mínimo 8 caracteres" required>
            <button type="button" class="pw-toggle" id="pwToggle1">
              <i class="bi bi-eye" id="pwIcon1"></i>
            </button>
          </div>
          <div class="pw-strength" id="pwStrength" style="display:none;">
            <div class="pw-bar" id="bar1"></div>
            <div class="pw-bar" id="bar2"></div>
            <div class="pw-bar" id="bar3"></div>
            <div class="pw-bar" id="bar4"></div>
            <span class="pw-label" id="pwLabel">Débil</span>
          </div>
        </div>

        <!-- Confirm password (UI only, visual hint) -->
        <div class="form-group">
          <label class="form-label" for="confirm_pw">
            <i class="bi bi-shield-lock-fill"></i> Confirmar contraseña <span class="req">*</span>
          </label>
          <div class="input-wrap">
            <i class="bi bi-shield-lock input-icon"></i>
            <input type="password" id="confirm_pw" name="confirm_password"
              class="form-control" placeholder="Repite tu contraseña">
            <button type="button" class="pw-toggle" id="pwToggle2">
              <i class="bi bi-eye" id="pwIcon2"></i>
            </button>
          </div>
          <span class="field-hint" id="confirmHint"></span>
        </div>

        <!-- Terms — name preserved as needed -->
        <label class="terms-wrap" for="terms">
          <input type="checkbox" id="terms" name="terms" required>
          <span class="terms-text">
            Acepto los <a href="#">Términos y Condiciones</a> y la
            <a href="#">Política de Privacidad</a> de ComercioLocal. Me comprometo a usar la plataforma de manera responsable.
          </span>
        </label>

        <!-- LOGIC PRESERVED: name="save" -->
        <button type="submit" name="save" class="btn-register">
          <i class="bi bi-rocket-takeoff-fill"></i>
          Crear mi cuenta gratis
          <div class="btn-arrow"><i class="bi bi-arrow-right"></i></div>
        </button>

      </form>

      <div class="or-divider">o regístrate con</div>

      <div class="social-btns">
        <button type="button" class="btn-social s-google">
          <div class="s-icon">
            <svg width="16" height="16" viewBox="0 0 24 24">
              <path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
              <path fill="#4285F4" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
              <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" />
              <path fill="#34A853" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.47 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
            </svg>
          </div>
          Google
        </button>
        <button type="button" class="btn-social s-facebook">
          <div class="s-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2">
              <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z" />
            </svg>
          </div>
          Facebook
        </button>
      </div>

      <div class="login-prompt">
        ¿Ya tienes una cuenta? <a href="./login.php">Iniciar sesión</a>
      </div>

      <div class="security-badge">
        <i class="bi bi-shield-check-fill"></i>
        Tus datos están protegidos con cifrado SSL de 256 bits
      </div>

    </div>
  </div>

  <script>
    /* ── Account type toggle (visual only) ── */
    function selectAcct(type) {
      document.getElementById('opt-personal').classList.toggle('active', type === 'personal');
      document.getElementById('opt-tienda').classList.toggle('active', type === 'tienda');
    }

    /* ── Password visibility toggles ── */
    function makePwToggle(btnId, iconId, inputId) {
      document.getElementById(btnId).addEventListener('click', () => {
        const inp = document.getElementById(inputId);
        const ico = document.getElementById(iconId);
        const hidden = inp.type === 'password';
        inp.type = hidden ? 'text' : 'password';
        ico.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
      });
    }
    makePwToggle('pwToggle1', 'pwIcon1', 'password');
    makePwToggle('pwToggle2', 'pwIcon2', 'confirm_pw');

    /* ── Password strength meter ── */
    const pwInput = document.getElementById('password');
    const pwStrength = document.getElementById('pwStrength');
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
      document.getElementById('bar3'), document.getElementById('bar4')
    ];
    const pwLabel = document.getElementById('pwLabel');

    pwInput.addEventListener('input', () => {
      const v = pwInput.value;
      pwStrength.style.display = v.length ? 'flex' : 'none';
      let score = 0;
      if (v.length >= 8) score++;
      if (/[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;

      bars.forEach((b, i) => {
        b.className = 'pw-bar';
        if (i < score) {
          b.classList.add(score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong');
        }
      });
      pwLabel.textContent = score <= 1 ? 'Débil' : score <= 2 ? 'Regular' : score <= 3 ? 'Buena' : 'Fuerte';
      pwLabel.style.color = score <= 1 ? '#f87171' : score <= 2 ? 'var(--y500)' : 'var(--g500)';

      checkProgress();
    });

    /* ── Confirm password hint ── */
    document.getElementById('confirm_pw').addEventListener('input', function() {
      const hint = document.getElementById('confirmHint');
      const match = this.value === pwInput.value;
      hint.innerHTML = this.value.length === 0 ? '' :
        match ?
        '<i class="bi bi-check-circle-fill"></i> Las contraseñas coinciden' :
        '<i class="bi bi-x-circle-fill"></i> Las contraseñas no coinciden';
      hint.className = 'field-hint ' + (this.value.length === 0 ? '' : match ? 'ok' : 'err');
      checkProgress();
    });

    /* ── Progress bar ── */
    function checkProgress() {
      const fields = [
        document.getElementById('full_name').value.trim(),
        document.getElementById('email').value.trim(),
        pwInput.value,
        document.getElementById('confirm_pw').value,
        document.getElementById('terms').checked ? '1' : ''
      ];
      const filled = fields.filter(Boolean).length;
      document.getElementById('progressFill').style.width = (filled / fields.length * 100) + '%';
    }

    ['full_name', 'email'].forEach(id => {
      document.getElementById(id).addEventListener('input', checkProgress);
    });
    document.getElementById('terms').addEventListener('change', checkProgress);
  </script>

  <script>
    function selectAcct(type) {

      // Cambiar valor para PHP
      let value = (type === 'personal') ? 'usuario' : 'tienda';
      document.getElementById('type_user').value = value;

      // UI activa (lo que ya tienes)
      document.getElementById('opt-personal').classList.remove('active');
      document.getElementById('opt-store').classList.remove('active');

      if (type === 'personal') {
        document.getElementById('opt-personal').classList.add('active');
      } else {
        document.getElementById('opt-store').classList.add('active');
      }
    }
  </script>

</body>

</html>