<?php
session_start();
include '../config/conexion.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user']);
$user = $isLoggedIn ? $_SESSION['user'] : null;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($user['full_name'], 0, 1)) : '';
$userName = $isLoggedIn ? explode(' ', $user['full_name'])[0] : ''; // First name only
?>


<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ComercioLocal – Compra y vende en tu ciudad</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../input.css">
  <link rel="stylesheet" href="../output.css">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />


</head>

<style>

</style>

<body>

  <!-- ======= NAVBAR ======= -->
  <nav class="navbar">
    <div class="nav-logo">
      <!-- <div class="logo-icon"><i class="bi bi-shop"></i></div> -->
      <!-- <span>Comercio<em>Local</em></span> -->
      <a href="" class=""><img class="h-28 w-28" src="./Logo de Comercio Local.png" alt=""></a>
    </div>

    <div class="nav-search">
      <input type="text" placeholder="Buscar productos, servicios...">
      <button><i class="bi bi-search"></i></button>
    </div>

    <div class="nav-city">
      <i class="bi bi-geo-alt-fill"></i>
      Bogotá, Colombia
      <i class="bi bi-chevron-down"></i>
    </div>

    <div class="nav-spacer"></div>

    <div class="nav-links">

      <?php if ($isLoggedIn): ?>
        <!-- ── LOGGED IN: user chip + dropdown ── -->
        <div class="user-menu-wrap" id="userMenuWrap">
          <div class="user-chip" id="userChip">
            <div style="position:relative;">
              <div class="user-avatar"><?php echo $userInitial; ?></div>
              <div class="online-dot"></div>
            </div>
            <div class="user-chip-info">
              <div class="user-chip-greeting">Hola,</div>
              <div class="user-chip-name"><?php echo htmlspecialchars($userName); ?></div>
            </div>
            <i class="bi bi-chevron-down user-chip-arrow"></i>
          </div>

          <div class="user-dropdown" id="userDropdown">
            <!-- Dropdown header with full info -->
            <div class="ud-header">
              <div class="ud-avatar-lg"><?php echo $userInitial; ?></div>
              <div class="ud-user-info">
                <div class="ud-user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="ud-user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                <div class="ud-verified"><i class="bi bi-patch-check-fill"></i> Verificado</div>
              </div>
            </div>

            <!-- Menu items -->
            <div class="ud-body">
              <a class="ud-link" href="./app/dashboard.php">
                <i class="bi bi-speedometer2"></i> Mi panel
              </a>
              <?php if ($isLoggedIn): ?>
                <a class="ud-link" href="./crear.php">
                  <i class="bi bi-plus-square"></i> Publicar anuncio
                </a>
              <?php else: ?>
                <a class="ud-link" href="./auth/login.php">
                  <i class="bi bi-plus-square"></i> Publicar anuncio
                </a>
              <?php endif; ?>
              <a class="ud-link" href="#">
                <i class="bi bi-box-seam"></i> Mis anuncios
                <span class="ud-badge">3</span>
              </a>
              <a class="ud-link" href="#">
                <i class="bi bi-heart"></i> Favoritos
              </a>
              <a class="ud-link" href="#">
                <i class="bi bi-chat-dots"></i> Mensajes
                <span class="ud-badge">5</span>
              </a>
              <div class="ud-divider"></div>
              <a class="ud-link" href="#">
                <i class="bi bi-person-circle"></i> Mi perfil
              </a>
              <a class="ud-link" href="#">
                <i class="bi bi-gear"></i> Configuración
              </a>
              <div class="ud-divider"></div>
              <a class="ud-link-logout" href="../controller/logout.php">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
              </a>
            </div>
          </div>
        </div>

        <a class="btn-publish" href="./dashboard.php">
          <i class="bi bi-plus-circle-fill"></i>
          Mi Panel de Control
        </a>

      <?php else: ?>
        <!-- ── NOT LOGGED IN: login + register buttons ── -->
        <a class="btn-ghost-nav" href="./auth/login.php">
          <i class="bi bi-person"></i> Iniciar sesión
        </a>
        <a class="btn-ghost-nav" href="./auth/register.php">Registrarse</a>
        <a class="btn-publish" href="./crear.php">
          <i class="bi bi-plus-circle-fill"></i>
          Publicar
        </a>
      <?php endif; ?>

    </div>
  </nav>

  <!-- ======= HERO ======= -->
  <section class="hero">
    <div class="hero-dots"></div>
    <div class="hero-content">
      <div class="hero-badge">
        <i class="bi bi-lightning-charge-fill"></i>
        +48,000 anuncios activos hoy
      </div>



      <?php if ($isLoggedIn): ?>
        <h1 class="animate__animated animate__slideInLeft">¡Bienvenido,<br><em><?php echo htmlspecialchars($userName); ?>!</em>
        </h1>

        <p>Qué bueno tenerte de vuelta. Explora los mejores productos de tu ciudad o publica algo nuevo hoy.</p>
      <?php else: ?>
        <h1>ComercioLocal<br><em>Compra y vende</em><br>en tu ciudad</h1>
        <p>La plataforma que conecta vecinos, emprendedores y compradores en tu comunidad. Rápido, seguro y local.</p>
      <?php endif; ?>

      <div class="hero-search">
        <input type="text" placeholder="¿Qué estás buscando hoy?">
        <div class="sep"></div>
        <select>
          <option>Toda Colombia</option>
          <option>Bogotá</option>
          <option>Medellín</option>
          <option>Cali</option>
          <option>Barranquilla</option>
        </select>
        <button class="btn-hero-search">
          <i class="bi bi-search"></i> Buscar
        </button>
      </div>

      <div class="hero-stats">
        <div class="hero-stat">
          <div class="num">48K+</div>
          <div class="lbl">Anuncios activos</div>
        </div>
        <div class="hero-stat">
          <div class="num">120K</div>
          <div class="lbl">Usuarios registrados</div>
        </div>
        <div class="hero-stat">
          <div class="num">32</div>
          <div class="lbl">Ciudades</div>
        </div>
      </div>
    </div>

    <div class="hero-illustration">
      <div class="hero-mini-card featured ">
        <img class="img-hero" width="150" height="150" src="../public/xtz150_azul-ABS-1.png" alt="">
        <div class="hmc-info">
          <div class="hmc-price">$8.800.000</div>
          <div class="hmc-title">Moto Xtz 150cc – Seminueva</div>
        </div>
      </div>
      <div class="hero-mini-card">
        <img src="../public/mac.webp" width="150" height="150" alt="MacBook Air M1" class="img-hero">
        <div class="hmc-info">
          <div class="hmc-price" style="color:var(--yellow-400);">$1.200.000</div>
          <div class="hmc-title">MacBook Air M1</div>
        </div>
      </div>
      <div class="hero-mini-card">
        <img src="../public/zapato.webp" width="150" height="150" alt="Zapatillas Nike SB" class="img-hero">
        <div class="hmc-info">
          <div class="hmc-price" style="color:var(--yellow-400);">$180.000</div>
          <div class="hmc-title">Zapatillas Nike SB</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ======= CATEGORIES ======= -->
  <?php
  $sqlCategories = "SELECT c.id, c.name, COUNT(p.id) AS product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id GROUP BY c.id";
  $resultCategories = $conn->query($sqlCategories);

  // Mapa de iconos por nombre de categoría (personalizable)
  $iconMap = [
    'tecnologia'   => 'bi-cpu-fill',
    'tecnología'   => 'bi-cpu-fill',
    'ropa'         => 'bi-bag-heart-fill',
    'moda'         => 'bi-bag-heart-fill',
    'vehiculos'    => 'bi-car-front-fill',
    'vehículos'    => 'bi-car-front-fill',
    'hogar'        => 'bi-house-door-fill',
    'deportes'     => 'bi-trophy-fill',
    'herramientas' => 'bi-tools',
    'jardin'       => 'bi-leaf-fill',
    'jardín'       => 'bi-leaf-fill',
    'libros'       => 'bi-book-fill',
    'electronica'  => 'bi-lightning-charge-fill',
    'electrónica'  => 'bi-lightning-charge-fill',
    'juguetes'     => 'bi-controller',
    'mascotas'     => 'bi-heart-fill',
    'belleza'      => 'bi-stars',
    'alimentos'    => 'bi-basket-fill',
    'default'      => 'bi-grid-fill',
  ];

  function getIcon($name, $map)
  {
    $key = strtolower(trim($name));
    foreach ($map as $keyword => $icon) {
      if (str_contains($key, $keyword)) return $icon;
    }
    return $map['default'];
  }

  function formatCount($count)
  {
    if ($count >= 1000) return round($count / 1000, 1) . 'K';
    return $count;
  }
  ?>

  <!-- Swiper CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

  <section class="categories-section">
    <div class="section-header">
      <h2 class="section-title">Explorar <span>categorías</span></h2>
      <a class="section-link" href="#">Ver todas <i class="bi bi-arrow-right"></i></a>
    </div>

    <div class="swiper categories-swiper">
      <div class="swiper-wrapper">
        <?php if ($resultCategories && $resultCategories->num_rows > 0): ?>
          <?php $first = true;
          while ($cat = $resultCategories->fetch_assoc()): ?>
            <div class="swiper-slide">
              <div class="cat-card <?= $first ? 'active' : '' ?>">
                <div class="cat-icon">
                  <i class="bi <?= getIcon($cat['name'], $iconMap) ?>"></i>
                </div>
                <div class="cat-label"><?= htmlspecialchars($cat['name']) ?></div>
              </div>
            </div>
          <?php $first = false;
          endwhile; ?>
        <?php else: ?>
          <div class="swiper-slide">
            <p class="no-categories">No hay categorías disponibles.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- Navegación -->
      <div class="swiper-button-prev"></div>
      <div class="swiper-button-next"></div>
    </div>
  </section>

  <!-- Swiper JS -->
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script>
    const categoriesSwiper = new Swiper('.categories-swiper', {
      grabCursor: true,
      loop: true,
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        0: {
          slidesPerView: 3,
          spaceBetween: 8
        },
        480: {
          slidesPerView: 4,
          spaceBetween: 10
        },
        768: {
          slidesPerView: 6,
          spaceBetween: 12
        },
        1024: {
          slidesPerView: 8,
          spaceBetween: 14
        },
        1280: {
          slidesPerView: 10,
          spaceBetween: 16
        },
      },
    });
  </script>
  <!-- ======= MAIN LAYOUT ======= -->
  <div class="main-layout">
    <aside class="sidebar">
      <div class="filter-card">
        <div class="filter-title"><i class="bi bi-sliders"></i> Filtros</div>
        <div class="filter-group">
          <div class="filter-group-label">Categoría</div>
          <div class="filter-options">
            <label class="filter-opt"><input type="checkbox" checked><span class="filter-opt-label">Tecnología</span><span class="filter-opt-count">4.2K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Ropa y Moda</span><span class="filter-opt-count">8.1K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Vehículos</span><span class="filter-opt-count">2.9K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Hogar</span><span class="filter-opt-count">5.6K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Deportes</span><span class="filter-opt-count">3.3K</span></label>
          </div>
        </div>
        <div class="filter-group">
          <div class="filter-group-label">Rango de precio</div>
          <div class="price-range">
            <input type="text" class="price-input" placeholder="Mín" value="0">
            <span class="price-sep">–</span>
            <input type="text" class="price-input" placeholder="Máx" value="5.000.000">
          </div>
          <input type="range" class="range-slider" min="0" max="10000000" value="5000000">
        </div>
        <div class="filter-group">
          <div class="filter-group-label">Estado del producto</div>
          <div class="condition-tags">
            <div class="cond-tag active">Todos</div>
            <div class="cond-tag">Nuevo</div>
            <div class="cond-tag">Usado</div>
            <div class="cond-tag">Reacondicionado</div>
          </div>
        </div>
        <div class="filter-group">
          <div class="filter-group-label">Ciudad</div>
          <div class="filter-options">
            <label class="filter-opt"><input type="checkbox" checked><span class="filter-opt-label">Bogotá</span><span class="filter-opt-count">18K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Medellín</span><span class="filter-opt-count">9K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Cali</span><span class="filter-opt-count">7K</span></label>
            <label class="filter-opt"><input type="checkbox"><span class="filter-opt-label">Barranquilla</span><span class="filter-opt-count">5K</span></label>
          </div>
        </div>
        <button class="btn-apply-filter">Aplicar filtros</button>
      </div>
      <!-- <div class="sidebar-promo">
        <div class="promo-tag">🚀 Destacar anuncio</div>
        <h4>¿Vendes algo?</h4>
        <p>Publica gratis y llega a miles de compradores en tu ciudad hoy mismo.</p>
        <a href="#">Publicar ahora <i class="bi bi-arrow-right"></i></a>
      </div> -->
    </aside>

    <div class="content-area">
      <?php
      // Consulta para obtener los primeros 4 productos con información del usuario
      $sqlProducts = "SELECT p.id, p.title, p.price, p.condition_type, p.longitude, p.latitude, u.full_name
                      FROM products p
                      LEFT JOIN users u ON p.user_id = u.id
                      WHERE p.status = 'disponible'
                      ORDER BY p.id DESC
                      LIMIT 4";
      $resultProducts = $conn->query($sqlProducts);

      function getInitials($name)
      {
        if (empty($name)) return 'U';
        $parts = explode(' ', trim($name));
        $initials = '';
        foreach ($parts as $part) {
          if (!empty($part)) {
            $initials .= strtoupper($part[0]);
          }
        }
        return substr($initials, 0, 2);
      }

      function timeAgo($date)
      {
        if (empty($date)) return 'Recientemente';
        $time = strtotime($date);
        $diff = time() - $time;

        if ($diff < 60) return 'Hace unos segundos';
        if ($diff < 3600) return 'Hace ' . floor($diff / 60) . ' min';
        if ($diff < 86400) return 'Hace ' . floor($diff / 3600) . ' h';
        if ($diff < 604800) return 'Hace ' . floor($diff / 86400) . ' días';
        return date('d/m/Y', $time);
      }
      ?>

      <div class="content-section">
        <div class="nearby-section">
          <div class="section-header">
            <div>
              <div class="nearby-badge"><i class="bi bi-geo-alt-fill"></i> Tu zona</div>
              <h2 class="section-title">Productos cerca <span>de ti</span></h2>
            </div>
            <a class="section-link" href="#">Ver más <i class="bi bi-arrow-right"></i></a>
          </div>
          <div class="product-grid">
            <?php if ($resultProducts && $resultProducts->num_rows > 0): ?>
              <?php while ($product = $resultProducts->fetch_assoc()):
                $isBadge = strtolower($product['condition_type']) === 'nuevo' ? 'badge-new' : 'badge-used';
                $badgeText = ucfirst($product['condition_type']);
                $price = number_format($product['price'], 0, ',', '.');
                $initials = getInitials($product['full_name']);
                $timeString = 'Recientemente';
                $location = 'Bogotá';
              ?>
                <div class="product-card">
                  <div class="<?php echo $isBadge; ?>"><?php echo $badgeText; ?></div>
                  <button class="btn-fav"><i class="bi bi-heart"></i></button>
                  <div class="product-img-placeholder">
                    <?php
                    $pid = $row['id'];
                    $img_q = "SELECT * FROM product_images WHERE product_id = '$pid' LIMIT 1";
                    $img_r = mysqli_query($conn, $img_q);
                    $img_row = mysqli_fetch_assoc($img_r);
                    if ($img_row): ?>
                      <img src="./productos/uploads/<?php echo htmlspecialchars($img_row['image_url']); ?>" alt="">
                    <?php else: ?>
                      <i class="bi bi-box-seam"></i>
                    <?php endif; ?>

                  </div>
                  <div class="product-body">
                    <div class="product-price">$<?php echo $price; ?></div>
                    <div class="product-title"><?php echo htmlspecialchars($product['title']); ?></div>
                    <div class="product-meta">
                      <div class="product-meta-row"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($location); ?></div>
                      <div class="product-meta-row"><i class="bi bi-clock"></i> <?php echo $timeString; ?></div>
                    </div>
                    <div class="product-seller">
                      <div class="seller-avatar"><?php echo $initials; ?></div>
                      <div class="seller-info">
                        <div class="seller-name"><?php echo htmlspecialchars($product['full_name'] ?? 'Usuario'); ?></div>
                        <div class="seller-rating">
                          <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                          <span>4.5</span>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <p style="text-align: center; padding: 20px; color: var(--text-soft);">No hay productos disponibles.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="content-section">
        <div class="section-header">
          <div>
            <h2 class="section-title">Productos <span>recientes</span></h2>
          </div>
          <div style="display:flex;gap:10px;align-items:center;">
            <span style="font-size:.82rem;color:var(--text-soft);">Ordenar por:</span>
            <select style="border:1.5px solid #ddd;border-radius:8px;padding:6px 12px;font-family:'DM Sans',sans-serif;font-size:.84rem;outline:none;color:var(--text-dark);cursor:pointer;">
              <option>Más recientes</option>
              <option>Menor precio</option>
              <option>Mayor precio</option>
              <option>Más cercanos</option>
            </select>
            <a class="section-link" href="#">Ver todos <i class="bi bi-arrow-right"></i></a>
          </div>
        </div>
        <div class="divider-line"></div>
        <div class="product-grid">
          <div class="product-card">
            <div class="badge-new">Nuevo</div><button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🎧</div>
            <div class="product-body">
              <div class="product-price">$380.000</div>
              <div class="product-title">Sony WH-1000XM4 Noise Cancelling</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Teusaquillo, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 20 min</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">MG</div>
                <div class="seller-info">
                  <div class="seller-name">Miguel García</div>
                  <div class="seller-rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i><span>4.3</span></div>
                </div>
              </div>
            </div>
          </div>
          <div class="product-card">
            <div class="badge-used">Usado</div><button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🏋️</div>
            <div class="product-body">
              <div class="product-price">$450.000</div>
              <div class="product-title">Set de mancuernas 5 a 30 kg ajustables</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Fontibón, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 45 min</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">AF</div>
                <div class="seller-info">
                  <div class="seller-name">Andrés Forero</div>
                  <div class="seller-rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><span>5.0</span></div>
                </div>
              </div>
            </div>
          </div>
          <div class="product-card">
            <div class="badge-new">Nuevo</div><button class="btn-fav"><i class="bi bi-heart"></i></button>
            <div class="product-img-placeholder">🖨️</div>
            <div class="product-body">
              <div class="product-price">$290.000</div>
              <div class="product-title">Impresora HP LaserJet Pro M15w WiFi</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> Barrios Unidos, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 1 hora</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">VC</div>
                <div class="seller-info">
                  <div class="seller-name">Valentina Cruz</div>
                  <div class="seller-rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i><span>4.7</span></div>
                </div>
              </div>
            </div>
          </div>
          <div class="product-card">
            <div class="badge-used">Usado</div><button class="btn-fav active"><i class="bi bi-heart-fill"></i></button>
            <div class="product-img-placeholder">🪴</div>
            <div class="product-body">
              <div class="product-price">$45.000</div>
              <div class="product-title">Plantas suculentas variadas con maceta</div>
              <div class="product-meta">
                <div class="product-meta-row"><i class="bi bi-geo-alt"></i> La Candelaria, Bogotá</div>
                <div class="product-meta-row"><i class="bi bi-clock"></i> Hace 2 horas</div>
              </div>
              <div class="product-seller">
                <div class="seller-avatar">NB</div>
                <div class="seller-info">
                  <div class="seller-name">Natalia Bernal</div>
                  <div class="seller-rating"><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star"></i><span>4.1</span></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- ======= FOOTER ======= -->
  <footer class="footer">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="brand-name">
          <div class="brand-icon"><i class="bi bi-shop"></i></div>ComercioLocal
        </div>
        <p>Conectamos compradores y vendedores dentro de la misma ciudad. Más local, más rápido, más seguro.</p>
        <div class="social-links">
          <div class="social-btn"><i class="bi bi-facebook"></i></div>
          <div class="social-btn"><i class="bi bi-instagram"></i></div>
          <div class="social-btn"><i class="bi bi-twitter-x"></i></div>
          <div class="social-btn"><i class="bi bi-whatsapp"></i></div>
          <div class="social-btn"><i class="bi bi-youtube"></i></div>
          <div class="social-btn"><i class="bi bi-tiktok"></i></div>
        </div>
      </div>
      <div class="footer-col">
        <h5>Explorar</h5>
        <ul>
          <li><a href="#">Tecnología</a></li>
          <li><a href="#">Vehículos</a></li>
          <li><a href="#">Hogar</a></li>
          <li><a href="#">Ropa y Moda</a></li>
          <li><a href="#">Deportes</a></li>
          <li><a href="#">Servicios</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Cuenta</h5>
        <ul>
          <?php if ($isLoggedIn): ?>
            <li><a href="./app/dashboard.php">Mi panel</a></li>
            <li><a href="#">Mis anuncios</a></li>
            <li><a href="#">Mensajes</a></li>
            <li><a href="#">Favoritos</a></li>
            <li><a href="./controller/logout.php">Cerrar sesión</a></li>
          <?php else: ?>
            <li><a href="./auth/login.php">Iniciar sesión</a></li>
            <li><a href="./auth/register.php">Registrarse</a></li>
            <li><a href="#">Mis anuncios</a></li>
            <li><a href="#">Mensajes</a></li>
            <li><a href="#">Favoritos</a></li>
          <?php endif; ?>
          <li><a href="#">Centro de ayuda</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h5>Descarga la app</h5>
        <div class="app-btns">
          <div class="app-btn"><i class="bi bi-apple"></i>
            <div class="app-btn-text">Disponible en<br><strong>App Store</strong></div>
          </div>
          <div class="app-btn"><i class="bi bi-google-play"></i>
            <div class="app-btn-text">Disponible en<br><strong>Google Play</strong></div>
          </div>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 ComercioLocal. Hecho con <span style="color:var(--yellow-400);">♥</span> en Colombia. Todos los derechos reservados.</p>
      <div class="footer-legal">
        <a href="#">Términos de uso</a>
        <a href="#">Privacidad</a>
        <a href="#">Cookies</a>
        <a href="#">Contacto</a>
      </div>
    </div>
  </footer>

  <script>
    // ── User dropdown toggle ──
    const wrap = document.getElementById('userMenuWrap');
    const chip = document.getElementById('userChip');

    if (chip) {
      chip.addEventListener('click', (e) => {
        e.stopPropagation();
        wrap.classList.toggle('open');
      });

      document.addEventListener('click', (e) => {
        if (!wrap.contains(e.target)) {
          wrap.classList.remove('open');
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') wrap.classList.remove('open');
      });
    }

    // ── Category toggle ──
    document.querySelectorAll('.cat-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.cat-card').forEach(c => c.classList.remove('active'));
        card.classList.add('active');
      });
    });

    // ── Condition tag toggle ──
    document.querySelectorAll('.cond-tag').forEach(tag => {
      tag.addEventListener('click', () => {
        document.querySelectorAll('.cond-tag').forEach(t => t.classList.remove('active'));
        tag.classList.add('active');
      });
    });

    // ── Fav toggle ──
    document.querySelectorAll('.btn-fav').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        btn.classList.toggle('active');
        btn.querySelector('i').className = btn.classList.contains('active') ?
          'bi bi-heart-fill' : 'bi bi-heart';
      });
    });
  </script>

</body>

</html>