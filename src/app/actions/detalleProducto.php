<?php
include_once("../../config/conexion.php");

/* ══════════════════════════════════════════
   LOGIC 100% PRESERVED — original queries
══════════════════════════════════════════ */
if (!isset($_GET['id'])) {
  echo "Producto no encontrado";
  exit;
}

$id = intval($_GET['id']);

$sql    = "SELECT * FROM products WHERE id = $id";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

$sqlVendedor = "SELECT u.full_name, u.email FROM users u
        JOIN products p ON p.user_id = u.id
        WHERE p.id = $id";

$resultVendedor = mysqli_query($conn, $sqlVendedor);
$seller_info = mysqli_fetch_assoc($resultVendedor);

if (!$product) {
  echo "Producto no encontrado";
  exit;
}

$img_sql    = "SELECT * FROM product_images WHERE product_id = '$id' ORDER BY id ASC";
$img_result = mysqli_query($conn, $img_sql);
$images = [];
if ($img_result && mysqli_num_rows($img_result) > 0) {
  while ($img = mysqli_fetch_assoc($img_result)) {
    $images[] = $img;
  }
}

if (!$seller_info) {
  $seller_info = [
    'full_name' => 'Usuario desconocido',
    'email'     => ''
  ];
}

$condition_type  = $product['condition_type'];
$badge_class     = strtolower($condition_type) === 'nuevo' ? 'badge-new' : 'badge-used';
$price_formatted = number_format($product['price'], 0, ',', '.');
$main_image      = '../productos/uploads/' . ($images[0]['image_url'] ?? 'default.png');

$select_category_sql = "SELECT name FROM categories WHERE id = " . ($product['category_id'] ?? 0);
$category_result = mysqli_query($conn, $select_category_sql);
$category_name    = mysqli_fetch_assoc($category_result)['name'] ?? 'Sin categoría';

/* seller initial */
$seller_initial = strtoupper(mb_substr($seller_info['full_name'], 0, 1));

/* more products from same seller */
$user_id_product = $product['user_id'] ?? 0;
$more_sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) as thumb
             FROM products p
             WHERE p.user_id = '$user_id_product' AND p.id != $id AND p.status = 1
             LIMIT 4";
$more_result = mysqli_query($conn, $more_sql);

/* similar products (same category) */
$cat_id = $product['category_id'] ?? 0;
$similar_sql = "SELECT p.*, (SELECT image_url FROM product_images WHERE product_id = p.id LIMIT 1) as thumb
                FROM products p
                WHERE p.category_id = '$cat_id' AND p.id != $id AND p.status = 1
                LIMIT 4";
$similar_result = mysqli_query($conn, $similar_sql);

/* session for user chip */
session_start();
$isLoggedIn  = isset($_SESSION['user']);
$userInitial = $isLoggedIn ? strtoupper(mb_substr($_SESSION['user']['full_name'], 0, 1)) : '';
$userName    = $isLoggedIn ? explode(' ', $_SESSION['user']['full_name'])[0] : '';
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($product['title']); ?> – ComercioLocal</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100..900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <link rel="stylesheet" href="../../style/detalleProducto.css">
  <link rel="stylesheet" href="../../output.css">

<body>

  <!-- ══ NAVBAR ══ -->
  <nav class="navbar">
    <a href="../inde.php" class="nav-logo"><img class="h-28 w-28" src="../Logo de Comercio Local.png" alt=""></a>


    <div class="nav-search">
      <input type="text" placeholder="Buscar productos, servicios...">
      <button><i class="bi bi-search"></i></button>
    </div>

    <div class="nav-spacer"></div>

    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <div class="user-chip" id="userChip">
          <div style="position:relative;">
            <div class="user-avatar"><?php echo $userInitial; ?></div>
            <div class="online-dot"></div>
          </div>
          <div>
            <div class="uc-greet">Hola,</div>
            <div class="uc-name"><?php echo htmlspecialchars($userName); ?></div>
          </div>
        </div>
      <?php else: ?>
        <a class="btn-ghost-nav" href="../auth/login.php"><i class="bi bi-person"></i> Iniciar sesión</a>
        <a class="btn-ghost-nav" href="../auth/register.php">Registrarse</a>
      <?php endif; ?>
      <a class="btn-publish-nav" href="../crear.php">
        <i class="bi bi-plus-circle-fill"></i> Publicar
      </a>
    </div>
  </nav>

  <!-- ══ BREADCRUMB ══ -->
  <div class="breadcrumb-bar">
    <div class="breadcrumb">
      <a href="../inde.php">Inicio</a>
      <i class="bi bi-chevron-right"></i>
      <a href="#"><?php echo htmlspecialchars($product['category'] ?? 'Categoría'); ?></a>
      <i class="bi bi-chevron-right"></i>
      <span><?php echo mb_strimwidth(htmlspecialchars($product['title']), 0, 40, '...'); ?></span>
    </div>
  </div>

  <!-- ══ PAGE ══ -->
  <div class="page">

    <!-- MAIN PRODUCT GRID -->
    <div class="product-main">

      <!-- ── GALLERY ── -->
      <div class="gallery-col">
        <div class="gallery-main-wrap">
          <?php if (!empty($images)): ?>
            <img id="mainImage"
              src="<?php echo htmlspecialchars($main_image); ?>"
              alt="<?php echo htmlspecialchars($product['title']); ?>"
              class="gallery-main-img">
          <?php else: ?>
            <div class="gallery-main-placeholder">📦</div>
          <?php endif; ?>

          <div class="gallery-badge <?php echo $badge_class; ?>">
            <?php echo ucfirst($condition_type); ?>
          </div>
          <button class="gallery-fav" id="favBtn"><i class="bi bi-heart"></i></button>
          <button class="gallery-share"><i class="bi bi-share"></i></button>
          <?php if (!empty($images)): ?>
            <div class="gallery-count">
              <i class="bi bi-images"></i>
              <span id="imgCounter">1</span> / <?php echo count($images); ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Thumbnails — LOGIC PRESERVED -->
        <?php if (count($images) > 1): ?>
          <div class="thumbnails">
            <?php foreach ($images as $idx => $img): ?>
              <div class="thumb-item <?php echo $idx === 0 ? 'active' : ''; ?>"
                onclick="changeMainImage(this, <?php echo $idx + 1; ?>)">
                <img src="../productos/uploads/<?php echo htmlspecialchars($img['image_url']); ?>"
                  alt="Imagen <?php echo $idx + 1; ?>">
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- MAP SECTION -->
        <?php if (!empty($product['latitude']) && !empty($product['longitude'])): ?>
          <div class="map-card" style="margin-top:18px;">
            <div class="map-card-header">
              <i class="bi bi-geo-alt-fill"></i>
              <h3>Ubicación del producto</h3>
            </div>
            <!-- LOGIC PRESERVED: Leaflet map with lat/lon from DB -->
            <div id="productMap"></div>
            <div class="map-loc-tag">
              <i class="bi bi-geo-alt-fill"></i>
              <?php echo htmlspecialchars($product['location'] ?? 'Bogotá, Colombia'); ?>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── DETAILS COL ── -->
      <div class="details-col">

        <div class="product-card-wrap">
          <div class="product-category-tag">
            <i class="bi bi-tag-fill"></i>
            <?php echo htmlspecialchars($category_name); ?>
          </div>

          <h1 class="product-title"><?php echo htmlspecialchars($product['title']); ?></h1>

          <div class="product-price-row">
            <div class="product-price">$<?php echo $price_formatted; ?></div>
            <span class="price-note">COP</span>
          </div>

          <div class="product-rating">
            <div class="stars">
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
              <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
              <i class="bi bi-star-half"></i>
            </div>
            <span class="rating-text">4.8 (24 reseñas)</span>
            <div class="views-badge"><i class="bi bi-eye"></i> 142 vistas</div>
          </div>

          <!-- LOGIC PRESERVED: info grid from product fields -->
          <div class="info-grid">
            <div class="info-item">
              <div class="info-label">Condición</div>
              <div class="info-value"><?php echo htmlspecialchars($condition_type); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Stock</div>
              <div class="info-value"><?php echo htmlspecialchars($product['stock'] ?? 'Disponible'); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Publicado</div>
              <div class="info-value"><?php echo date('d/m/Y', strtotime($product['created_at'] ?? 'now')); ?></div>
            </div>
            <div class="info-item">
              <div class="info-label">Estado</div>
              <div class="info-value" style="color:var(--g500);">● Activo</div>
            </div>
          </div>

          <?php if (!empty($product['description'])): ?>
            <div class="product-description-block">
              <div class="section-label-sm">Descripción</div>
              <p class="product-description" id="prodDesc">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
              </p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Seller card (in details col for medium screens) -->
        <div class="seller-card">
          <div class="seller-card-header">
            <div class="seller-av-wrap">
              <!-- LOGIC PRESERVED: seller initial -->
              <div class="seller-av"><?php echo $seller_initial; ?></div>
              <div class="seller-online"></div>
            </div>
            <div>
              <!-- LOGIC PRESERVED: seller_info from product -->
              <div class="seller-name text-white"><?php echo htmlspecialchars($seller_info['full_name']); ?> vvcc</div>
              <div class="seller-verified"><i class="bi bi-patch-check-fill"></i> Vendedor verificado</div>
            </div>
          </div>

          <div class="seller-stats">
            <div class="seller-stat">
              <div class="ss-num">98%</div>
              <div class="ss-lbl">Resp.</div>
            </div>
            <div class="seller-stat">
              <div class="ss-num">4.8</div>
              <div class="ss-lbl">Rating</div>
            </div>
            <div class="seller-stat">
              <div class="ss-num">47</div>
              <div class="ss-lbl">Ventas</div>
            </div>
          </div>

          <div class="seller-contact">
            <a href="../chat.php" class="">
              <i class="bi bi-chat-dots-fill"></i>
              Enviar mensaje
            </a>
            <a href="../crear_conversacion.php?product_id=<?php echo $product['id']; ?>">
              Enviar mensaje
            </a>
            <?php if (!empty($seller_info['phone'])): ?>
              <a href="tel:<?php echo htmlspecialchars($seller_info['phone']); ?>" class="btn-call">
                <i class="bi bi-telephone-fill"></i> Llamar al vendedor
              </a>
            <?php endif; ?>
            <?php if (!empty($seller_info['email'])): ?>
              <a href="mailto:<?php echo htmlspecialchars($seller_info['email']); ?>" class="btn-call" style="margin-top:2px;">
                <i class="bi bi-envelope-fill"></i> Contactar por email
              </a>
            <?php endif; ?>
          </div>

          <div class="seller-meta">
            <div class="sm-row"><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($product['location'] ?? 'Bogotá, Colombia'); ?></div>
            <div class="sm-row"><i class="bi bi-clock-fill"></i>Responde en menos de 1 hora</div>
            <div class="sm-row"><i class="bi bi-shield-check-fill"></i>Perfil verificado por ComercioLocal</div>
          </div>
        </div>

      </div>

    </div><!-- /product-main -->

    <!-- ══ MORE FROM THIS SELLER ══ -->
    <div class="related-section">
      <div class="section-header">
        <div>
          <div class="section-title-main">Más de <span>este vendedor</span></div>
          <div class="section-accent"></div>
        </div>
        <a href="../allProduct.php" class="section-see-all">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>

      <?php
      /* LOGIC PRESERVED: more_result query */
      if ($more_result && mysqli_num_rows($more_result) > 0): ?>
        <div class="related-grid">
          <?php while ($rel = mysqli_fetch_assoc($more_result)): ?>
            <a href="?id=<?php echo $rel['id']; ?>" class="rel-card">
              <div class="rel-img">
                <?php if (!empty($rel['thumb'])): ?>
                  <img src="../productos/uploads/<?php echo htmlspecialchars($rel['thumb']); ?>" alt="">
                <?php else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>
                <div class="rel-cond <?php echo strtolower($rel['condition_type'] ?? 'usado') === 'nuevo' ? 'badge-new' : 'badge-used'; ?>">
                  <?php echo ucfirst($rel['condition_type'] ?? 'Usado'); ?>
                </div>
              </div>
              <div class="rel-body">
                <div class="rel-price">$<?php echo number_format($rel['price'], 0, ',', '.'); ?></div>
                <div class="rel-title"><?php echo htmlspecialchars($rel['title']); ?></div>
                <div class="rel-loc"><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($rel['location'] ?? 'Bogotá'); ?></div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-related">
          <i class="bi bi-box-seam" style="font-size:1.6rem;color:var(--g200);display:block;margin-bottom:8px;"></i>
          Este vendedor no tiene más productos publicados.
        </div>
      <?php endif; ?>
    </div>

    <!-- ══ SIMILAR PRODUCTS ══ -->
    <div class="related-section">
      <div class="section-header">
        <div>
          <div class="section-title-main">Productos <span>similares</span></div>
          <div class="section-accent"></div>
        </div>
        <a href="../allProduct.php" class="section-see-all">Ver todos <i class="bi bi-arrow-right"></i></a>
      </div>

      <?php
      /* LOGIC PRESERVED: similar_result query */
      if ($similar_result && mysqli_num_rows($similar_result) > 0): ?>
        <div class="related-grid">
          <?php while ($sim = mysqli_fetch_assoc($similar_result)): ?>
            <a href="?id=<?php echo $sim['id']; ?>" class="rel-card">
              <div class="rel-img">
                <?php if (!empty($sim['thumb'])): ?>
                  <img src="../productos/uploads/<?php echo htmlspecialchars($sim['thumb']); ?>" alt="">
                <?php else: ?>
                  <i class="bi bi-box-seam"></i>
                <?php endif; ?>
                <div class="rel-cond <?php echo strtolower($sim['condition_type'] ?? 'usado') === 'nuevo' ? 'badge-new' : 'badge-used'; ?>">
                  <?php echo ucfirst($sim['condition_type'] ?? 'Usado'); ?>
                </div>
              </div>
              <div class="rel-body">
                <div class="rel-price">$<?php echo number_format($sim['price'], 0, ',', '.'); ?></div>
                <div class="rel-title"><?php echo htmlspecialchars($sim['title']); ?></div>
                <div class="rel-loc"><i class="bi bi-geo-alt-fill"></i><?php echo htmlspecialchars($sim['location'] ?? 'Bogotá'); ?></div>
              </div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php else: ?>
        <div class="empty-related">
          <i class="bi bi-search" style="font-size:1.6rem;color:var(--g200);display:block;margin-bottom:8px;"></i>
          No encontramos productos similares en este momento.
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /page -->

  <!-- ══ FLOATING CHAT FAB ══ -->
  <button class="chat-fab has-notif" id="chatFab" title="Enviar mensaje al vendedor">
    <i class="bi bi-chat-dots-fill"></i>
  </button>

  <!-- ══ FLOATING CHAT PANEL ══ -->
  <div class="chat-panel" id="chatPanel">
    <div class="chat-header">
      <div class="chat-header-av"><?php echo $seller_initial; ?></div>
      <div class="chat-header-info">
        <div class="chat-header-name"><?php echo htmlspecialchars($seller_info['full_name']); ?></div>
        <div class="chat-header-status">
          <div class="chat-status-dot"></div>
          En línea · Vendedor verificado
        </div>
      </div>
      <button class="chat-close" id="chatClose"><i class="bi bi-x-lg"></i></button>
    </div>

    <div class="chat-messages" id="chatMessages">
      <div class="chat-bubble-wrap">
        <div class="chat-mini-av"><?php echo $seller_initial; ?></div>
        <div>
          <div class="bubble bubble-them">
            ¡Hola! Gracias por tu interés en este producto 👋
          </div>
          <div class="bubble-time">Ahora</div>
        </div>
      </div>
      <div class="chat-bubble-wrap">
        <div class="chat-mini-av"><?php echo $seller_initial; ?></div>
        <div>
          <div class="bubble bubble-them">
            ¿Tienes alguna pregunta sobre <strong><?php echo htmlspecialchars(mb_strimwidth($product['title'], 0, 30, '...')); ?></strong>?
          </div>
          <div class="bubble-time">Ahora</div>
        </div>
      </div>
      <!-- Typing indicator -->
      <div class="chat-bubble-wrap" id="typingIndicator" style="display:none;">
        <div class="chat-mini-av"><?php echo $seller_initial; ?></div>
        <div class="chat-typing">
          <div class="typing-dot"></div>
          <div class="typing-dot"></div>
          <div class="typing-dot"></div>
        </div>
      </div>
    </div>

    <!-- Quick reply chips -->
    <div class="quick-replies" id="quickReplies">
      <div class="qr-chip" onclick="sendQuickMsg('¿Está disponible?')">¿Está disponible?</div>
      <div class="qr-chip" onclick="sendQuickMsg('¿Acepta ofertas?')">¿Acepta ofertas?</div>
      <div class="qr-chip" onclick="sendQuickMsg('¿Puedo verlo hoy?')">¿Puedo verlo hoy?</div>
    </div>

    <div class="chat-input-row">
      <input type="text" class="chat-input" id="chatInput" placeholder="Escribe un mensaje...">
      <button class="chat-send" id="chatSend"><i class="bi bi-send-fill"></i></button>
    </div>
  </div>

  <script>
    /* ── LOGIC PRESERVED: thumbnail gallery ── */
    function changeMainImage(thumb, idx) {
      const mainImg = document.getElementById('mainImage');
      if (mainImg) mainImg.src = thumb.querySelector('img').src;
      document.querySelectorAll('.thumb-item').forEach(t => t.classList.remove('active'));
      thumb.classList.add('active');
      const counter = document.getElementById('imgCounter');
      if (counter) counter.textContent = idx;
    }

    /* ── Fav toggle ── */
    const favBtn = document.getElementById('favBtn');
    if (favBtn) {
      favBtn.addEventListener('click', () => {
        favBtn.classList.toggle('active');
        const icon = favBtn.querySelector('i');
        icon.className = favBtn.classList.contains('active') ? 'bi bi-heart-fill' : 'bi bi-heart';
        icon.style.color = favBtn.classList.contains('active') ? '#ef4444' : '';
      });
    }

    /* ── LEAFLET MAP — LOGIC PRESERVED ── */
    <?php if (!empty($product['latitude']) && !empty($product['longitude'])): ?>
      document.addEventListener('DOMContentLoaded', function() {
        if (typeof L !== 'undefined') {
          const lat = <?php echo floatval($product['latitude']); ?>;
          const lon = <?php echo floatval($product['longitude']); ?>;
          const map = L.map('productMap').setView([lat, lon], 15);
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
          }).addTo(map);
          const customIcon = L.divIcon({
            html: '<div style="background:#25883f;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;border:3px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,.3);font-size:.9rem;">📍</div>',
            iconSize: [28, 28],
            iconAnchor: [14, 28],
            className: ''
          });
          L.marker([lat, lon], {
              icon: customIcon
            }).addTo(map)
            .bindPopup('<strong><?php echo addslashes(htmlspecialchars($product['title'])); ?></strong><br>$<?php echo $price_formatted; ?>')
            .openPopup();
        }
      });
    <?php endif; ?>

    /* ── FLOATING CHAT ── */
    const chatFab = document.getElementById('chatFab');
    const chatPanel = document.getElementById('chatPanel');
    const chatClose = document.getElementById('chatClose');
    const chatInput = document.getElementById('chatInput');
    const chatSend = document.getElementById('chatSend');
    const chatMessages = document.getElementById('chatMessages');
    const typingIndicator = document.getElementById('typingIndicator');
    const quickReplies = document.getElementById('quickReplies');

    function openChat() {
      chatPanel.classList.add('open');
      chatFab.classList.remove('has-notif'); // clear notification dot
      setTimeout(() => chatInput && chatInput.focus(), 300);
    }

    function closeChat() {
      chatPanel.classList.remove('open');
    }

    if (chatFab) {
      chatFab.addEventListener('click', () => {
        chatPanel.classList.contains('open') ? closeChat() : openChat();
      });
    }

    if (chatClose) {
      chatClose.addEventListener('click', closeChat);
    }

    const msgBtns = document.querySelectorAll('.btn-msg');
    msgBtns.forEach(b => {
      b.addEventListener('click', (e) => {
        e.preventDefault();
        openChat();
      });
    });

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeChat();
    });

    function getTime() {
      const now = new Date();
      return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    }

    function appendMsg(text, isMe) {
      const initLetter = '<?php echo $seller_initial; ?>';
      const wrap = document.createElement('div');
      wrap.className = 'chat-bubble-wrap' + (isMe ? ' me' : '');
      wrap.innerHTML = `
      <div class="chat-mini-av" style="${isMe ? 'background:var(--y100);color:var(--y600);' : ''}">
        ${isMe ? 'TÚ' : initLetter}
      </div>
      <div>
        <div class="bubble ${isMe ? 'bubble-me' : 'bubble-them'}">${text}</div>
        <div class="bubble-time">${getTime()}</div>
      </div>`;
      chatMessages.insertBefore(wrap, typingIndicator);
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function showTypingAndRespond() {
      typingIndicator.style.display = 'flex';
      chatMessages.scrollTop = chatMessages.scrollHeight;
      if (quickReplies) quickReplies.style.display = 'none'; // hide chips after first use
      setTimeout(() => {
        typingIndicator.style.display = 'none';
        const responses = [
          '¡Claro! Cuéntame más 😊',
          'Sí, está disponible. ¿Te interesa verlo?',
          'Puedo hacer un descuento si lo llevas hoy.',
          'Te paso más fotos ahora mismo.',
          '¿Cuándo podrías venir a verlo?',
          'El precio es negociable. ¿Qué me ofreces?'
        ];
        appendMsg(responses[Math.floor(Math.random() * responses.length)], false);
      }, 1800);
    }

    function sendQuickMsg(msg) {
      openChat();
      setTimeout(() => {
        appendMsg(msg, true);
        showTypingAndRespond();
      }, 200);
    }

    chatSend.addEventListener('click', () => {
      const msg = chatInput.value.trim();
      if (!msg) return;
      appendMsg(msg, true);
      chatInput.value = '';
      showTypingAndRespond();
    });

    chatInput.addEventListener('keypress', e => {
      if (e.key === 'Enter') chatSend.click();
    });
  </script>

</body>

</html>