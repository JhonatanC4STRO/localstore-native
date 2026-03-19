<?php
require_once "../../config/conexion.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar sesión – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../../style/login.css">
</head>
<body>

  <!-- ══ LEFT PANEL ══ -->
  <div class="left-panel">

    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <div class="left-content">

      <!-- Logo -->
      <div class="logo-wrap">
        <div class="logo-icon"><i class="bi bi-shop-window"></i></div>
        <a href="../inde.php" class="logo-text">Comercio<em>Local</em></a>
      </div>

      <!-- Scene -->
      <div class="scene">
        <div class="scene-bg">
          <div class="scene-inner">🛍️</div>

          <!-- Floating product cards -->
          <div class="float-card">
            <div class="fc-icon yellow">📱</div>
            <div class="fc-text">
              <div class="fc-price">$850.000</div>
              <div class="fc-name">iPhone 13</div>
            </div>
          </div>

          <div class="float-card">
            <div class="fc-icon green">🚲</div>
            <div class="fc-text">
              <div class="fc-price">$1.100.000</div>
              <div class="fc-name">MTB Trek 29"</div>
            </div>
          </div>

          <div class="float-card">
            <div class="fc-icon yellow">👟</div>
            <div class="fc-text">
              <div class="fc-price">$180.000</div>
              <div class="fc-name">Nike SB Talla 42</div>
            </div>
          </div>

          <div class="float-card">
            <div class="fc-icon green">💻</div>
            <div class="fc-text">
              <div class="fc-price">$1.200.000</div>
              <div class="fc-name">MacBook Air M1</div>
            </div>
          </div>
        </div>
      </div>

      <h2 class="scene-tagline">Compra y vende<br>en tu <em>ciudad</em></h2>
      <p class="scene-sub">Conectamos vecinos, emprendedores y compradores de tu barrio en un solo lugar. Rápido, seguro y 100% local.</p>

      <!-- Stats -->
      <div class="stats-row">
        <div class="stat-item">
          <div class="stat-num">48K+</div>
          <div class="stat-lbl">Anuncios</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">120K</div>
          <div class="stat-lbl">Usuarios</div>
        </div>
        <div class="stat-item">
          <div class="stat-num">32</div>
          <div class="stat-lbl">Ciudades</div>
        </div>
      </div>

    </div>

    <!-- Live ticker -->
    <div class="activity-ticker">
      <div class="ticker-dot"></div>
      <span class="ticker-text"><strong>Juanita M.</strong> acaba de publicar un artículo en Bogotá</span>
    </div>

  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="right-panel">
    <div class="corner-tl"></div>
    <div class="corner-br"></div>

    <div class="form-shell">

      <div class="form-welcome">
        <div class="form-eyebrow"><i class="bi bi-hand-wave"></i> ¡Hola de nuevo!</div>
        <h1 class="form-title">Bienvenido<br><span>de nuevo</span></h1>
        <div class="title-accent"></div>
        <p class="form-sub">Inicia sesión para acceder a tus anuncios, mensajes y favoritos.</p>
      </div>

      <!-- ── LOGIC PRESERVED: action, method, name attrs ── -->
      <form action="../../controller/process_login.php" method="POST">

        <div class="form-group">
          <label class="form-label" for="email">
            <i class="bi bi-envelope-fill"></i> Correo electrónico
          </label>
          <div class="input-wrap">
            <i class="bi bi-envelope input-icon"></i>
            <!-- LOGIC PRESERVED: name="email" -->
            <input type="email" id="email" name="email"
              class="form-control" placeholder="tucorreo@ejemplo.com" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">
            <i class="bi bi-lock-fill"></i> Contraseña
          </label>
          <div class="input-wrap">
            <i class="bi bi-lock input-icon"></i>
            <!-- LOGIC PRESERVED: name="password" -->
            <input type="password" id="password" name="password"
              class="form-control" placeholder="••••••••" required>
            <button type="button" class="pw-toggle" id="pwToggle" aria-label="Mostrar contraseña">
              <i class="bi bi-eye" id="pwIcon"></i>
            </button>
          </div>
        </div>

        <div class="form-row-spaced">
          <label class="remember-wrap">
            <input type="checkbox" name="remember"> Recordarme
          </label>
          <a class="forgot-link" href="#">¿Olvidaste tu contraseña?</a>
        </div>

        <!-- LOGIC PRESERVED: name="save" -->
        <button type="submit" name="save" class="btn-login">
          Iniciar sesión
          <div class="arrow-icon"><i class="bi bi-arrow-right"></i></div>
        </button>

      </form>

      <div class="or-divider">o continúa con</div>

      <!-- Social buttons (UI only, no logic change) -->
      <div class="social-btns">
        <button type="button" class="btn-social s-google">
          <div class="s-icon">
            <svg width="16" height="16" viewBox="0 0 24 24"><path fill="#EA4335" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#4285F4" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/><path fill="#34A853" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
          </div>
          Google
        </button>
        <button type="button" class="btn-social s-facebook">
          <div class="s-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="#1877F2"><path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.236 2.686.236v2.971h-1.513c-1.491 0-1.956.93-1.956 1.886v2.267h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/></svg>
          </div>
          Facebook
        </button>
      </div>

      <div class="register-prompt">
        ¿No tienes cuenta? <a href="./register.php">Crear cuenta gratis</a>
      </div>

      <div class="security-badge">
        <i class="bi bi-shield-check-fill"></i>
        Tus datos están protegidos con cifrado SSL
      </div>

    </div>
  </div>

  <script>
    /* Password visibility toggle */
    const pwToggle = document.getElementById('pwToggle');
    const pwInput  = document.getElementById('password');
    const pwIcon   = document.getElementById('pwIcon');

    pwToggle.addEventListener('click', () => {
      const isHidden = pwInput.type === 'password';
      pwInput.type  = isHidden ? 'text' : 'password';
      pwIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    /* Ticker rotation */
    const tickers = [
      { name: 'Juanita M.',  action: 'acaba de publicar un artículo en Bogotá' },
      { name: 'Carlos A.',   action: 'vendió una bicicleta en Medellín' },
      { name: 'Sara R.',     action: 'está buscando muebles en Cali' },
      { name: 'Andrés F.',   action: 'publicó un iPhone en Barranquilla' },
    ];
    let ti = 0;
    const tickerText = document.querySelector('.ticker-text');
    setInterval(() => {
      ti = (ti + 1) % tickers.length;
      tickerText.innerHTML = `<strong>${tickers[ti].name}</strong> ${tickers[ti].action}`;
    }, 3500);
  </script>

</body>
</html>