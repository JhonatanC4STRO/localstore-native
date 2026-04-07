<?php
session_start();
include(__DIR__ . '/../config/conexion.php');

/* ── LOGIC PRESERVED ── */
if (!isset($_SESSION['user'])) {
  header("Location: ./auth/login.php");
  exit();
}

$user_id         = $_SESSION['user']['id'];
$conversation_id = $_GET['conversation_id'] ?? null;

$isLoggedIn  = true;
$user        = $_SESSION['user'];
$userInitial = strtoupper(mb_substr($user['full_name'], 0, 1));
$userName    = explode(' ', $user['full_name'])[0];

/* LOGIC PRESERVED: conversations query — filtra las eliminadas por este usuario */
$uid = intval($user_id);
$sql = "SELECT
            c.id,
            p.id AS product_id,
            p.title AS product_name,
            p.price AS product_price,
            u.full_name AS other_user,
            u.id AS other_user_id,
            (SELECT image_url FROM product_images WHERE product_id = p.id AND p.id IS NOT NULL LIMIT 1) AS product_image
        FROM conversations c
        LEFT JOIN products p ON c.product_id = p.id
        JOIN users u
        ON (
            (c.buyer_id  = $uid AND u.id = c.seller_id)
            OR
            (c.seller_id = $uid AND u.id = c.buyer_id)
        )
        WHERE (c.buyer_id = $uid OR c.seller_id = $uid)
          AND NOT (c.buyer_id  = $uid AND c.hidden_by_buyer  = 1)
          AND NOT (c.seller_id = $uid AND c.hidden_by_seller = 1)
        ORDER BY c.id DESC";
$conversations = mysqli_query($conn, $sql);

/* Collect for JS reuse */
$convList = [];
while ($c = mysqli_fetch_assoc($conversations)) $convList[] = $c;
$isEmbed = isset($_GET['embed']);

$totalProducts = 0;
$r_tp = mysqli_query($conn, "SELECT COUNT(*) as total FROM products WHERE user_id = $uid");
if ($r_tp) $totalProducts = mysqli_fetch_assoc($r_tp)['total'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mensajes – ComercioLocal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../output.css">
  <link rel="stylesheet" href="../style/dashborad.css">
  <link rel="stylesheet" href="../style/chat.css">
</head>

<body>
  <!-- header -->
  <?php $basePath = '../'; include __DIR__ . '/../components/header.php'; ?>

  <div class="page-shell">

    <!-- ══ NAV SIDEBAR ══ -->
    <aside class="sidebar">
      <div class="sb-section-label">Principal</div>
      <a class="sb-link" href="./dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
      <a class="sb-link" href="./crear.php"><i class="bi bi-plus-square-fill"></i> Publicar producto</a>
      <a class="sb-link" href="./misProductos.php">
        <i class="bi bi-box-seam"></i> Mis productos
        <span class="sb-badge"><?php echo $totalProducts; ?></span>
      </a>
      <a class="sb-link active" href="./chat.php">
        <i class="bi bi-chat-dots"></i> Mensajes
        <span class="sb-badge yellow">5</span>
      </a>
      <a class="sb-link" href="#"><i class="bi bi-heart"></i> Favoritos</a>
      <div class="sb-divider"></div>
      <div class="sb-section-label">Cuenta</div>
      <a class="sb-link" href="./perfil.php"><i class="bi bi-person-circle"></i> Mi perfil</a>
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

  <div class="app">

    <!-- ══ LEFT SIDEBAR ══ -->
    <div class="conv-sidebar">

      <div class="cs-search">
        <div class="cs-search-wrap">
          <i class="bi bi-search"></i>
          <input type="text" placeholder="Buscar chats..." id="searchInput" oninput="filterChats()">
        </div>
      </div>

      <div class="cs-filters">
        <div class="cf-chip active" onclick="filterByType(this,'all')">Todos</div>
        <div class="cf-chip" onclick="filterByType(this,'unread')">No leídos</div>
        <div class="cf-chip" onclick="filterByType(this,'buying')">Comprando</div>
        <div class="cf-chip" onclick="filterByType(this,'selling')">Vendiendo</div>
      </div>

      <div class="conv-list" id="convList">
        <?php if (!empty($convList)): ?>
          <?php foreach ($convList as $idx => $c):
            $initial = strtoupper(mb_substr($c['other_user'], 0, 1));
            $isOnline = ($idx % 2 === 0); // alternate for demo
            $unread   = ($idx === 0) ? 2 : 0;
            $price    = !empty($c['product_price']) ? '$' . number_format($c['product_price'], 0, ',', '.') : '';
            $prodImg  = !empty($c['product_image']) ? './productos/uploads/' . $c['product_image'] : '';
          ?>
            <div class="conv-item <?php echo ($c['id'] == $conversation_id) ? 'active' : ''; ?>"
              onclick="openChat(<?php echo $c['id']; ?>, '<?php echo addslashes($c['other_user']); ?>', '<?php echo addslashes($c['product_name']); ?>', '<?php echo $initial; ?>', <?php echo $isOnline ? 'true' : 'false'; ?>, '<?php echo addslashes($price); ?>', '<?php echo addslashes($prodImg); ?>', <?php echo intval($c['product_id'] ?? 0); ?>)"
              data-name="<?php echo htmlspecialchars($c['other_user']); ?>"
              id="conv-<?php echo $c['id']; ?>">
              <div class="ci-avatar">
                <?php echo $initial; ?>
                <div class="<?php echo $isOnline ? 'online-ring' : 'offline-ring'; ?>"></div>
              </div>
              <div class="ci-body">
                <div class="ci-top">
                  <div class="ci-name"><?php echo htmlspecialchars($c['other_user']); ?></div>
                  <div class="ci-time">Ahora</div>
                </div>
                <div class="ci-bottom">
                  <div class="ci-preview">Hola, ¿está disponible?</div>
                  <?php if ($unread > 0): ?>
                    <div class="ci-unread"><?php echo $unread; ?></div>
                  <?php endif; ?>
                </div>
                <div style="margin-top:3px;">
                  <div class="ci-product-tag"><?php echo htmlspecialchars($c['product_name'] ?? 'Conversación directa'); ?></div>
                </div>
              </div>
              <!-- DELETE BUTTON (appears on hover) -->
              <button class="ci-del-btn" title="Eliminar chat"
                onclick="event.stopPropagation(); confirmDeleteChat(<?php echo $c['id']; ?>, '<?php echo addslashes($c['other_user']); ?>')">
                <i class="bi bi-trash-fill"></i>
              </button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="cs-empty">
            <i class="bi bi-chat-dots"></i>
            <p>No tienes conversaciones aún.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- User chip at bottom -->
      <div style="padding:12px 14px;border-top:1.5px solid var(--border);display:flex;align-items:center;gap:10px;background:var(--g50);">
        <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--y400),var(--y300));display:flex;align-items:center;justify-content:center;font-family:'Syne',sans-serif;font-weight:800;font-size:.88rem;color:var(--g900);flex-shrink:0;">
          <?php echo $userInitial; ?>
        </div>
        <div style="flex:1;min-width:0;">
          <div style="font-size:.84rem;font-weight:600;color:var(--ink);overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><?php echo htmlspecialchars($user['full_name']); ?></div>
          <div style="font-size:.7rem;color:var(--g500);">● En línea</div>
        </div>
        <a href="./inde.php" style="color:var(--ink3);font-size:.95rem;transition:color .2s;" title="Ir al inicio"><i class="bi bi-house-fill"></i></a>
      </div>

    </div>

    <!-- ══ CENTER CHAT ══ -->
    <div class="chat-window">

      <!-- Chat empty state (shown when no conversation selected) -->
      <div class="chat-empty" id="chatEmpty">
        <div class="ce-icon"><i class="bi bi-chat-dots-fill"></i></div>
        <div class="ce-title">ComercioLocal Mensajes</div>
        <div class="ce-sub">Selecciona una conversación para empezar a chatear con compradores y vendedores de tu ciudad.</div>
        <div class="ce-badge"><i class="bi bi-shield-check-fill"></i> Mensajes seguros y cifrados</div>
      </div>

      <!-- Chat active (hidden until conversation selected) -->
      <div id="chatActive" style="display:none;flex-direction:column;height:100%;">

        <!-- Header -->
        <div class="chat-header">
          <button class="ch-back" onclick="closeChat()"><i class="bi bi-arrow-left"></i></button>
          <div class="ch-avatar" id="chAvatar" onclick="toggleRightPanel()">
            <span id="chAvatarInitial">?</span>
            <div class="ch-av-online" id="chOnlineDot"></div>
          </div>
          <div class="ch-info">
            <div class="ch-name" id="chName">–</div>
            <div class="ch-status">
              <div class="ch-status-dot" id="chStatusDot"></div>
              <span id="chStatusText">En línea</span>
            </div>
          </div>
          <div class="ch-actions">
            <button class="ch-action-btn" title="Buscar"><i class="bi bi-search"></i></button>
            <button class="ch-action-btn" title="Videollamada"><i class="bi bi-camera-video"></i></button>
            <button class="ch-action-btn" title="Llamada"><i class="bi bi-telephone"></i></button>
            <button class="ch-action-btn" title="Más opciones"><i class="bi bi-three-dots-vertical"></i></button>
          </div>
        </div>

        <!-- Product pin -->
        <div class="product-pin" id="productPin">
          <div class="pp-img" id="ppImg"><i class="bi bi-box-seam"></i></div>
          <div class="pp-info">
            <div class="pp-label">Producto en discusión</div>
            <div class="pp-title" id="ppTitle">–</div>
            <div class="pp-price" id="ppPrice"></div>
          </div>
          <a href="./actions/detalleProducto.php?id=<?php echo $c['product_id']; ?>" id="ppVerBtn" class="pp-btn">
            <i class="bi bi-eye-fill"></i> Ver m
          </a>
        </div>

        <!-- Messages -->
        <div class="messages-area" id="messages">
          <!-- populated by JS -->
        </div>

        <!-- Emoji panel -->
        <div class="emoji-panel" id="emojiPanel">
          <?php
          $emojis = ['😊', '😂', '❤️', '👍', '🙏', '🔥', '💯', '✅', '🎉', '😎', '🤔', '👀', '💰', '📦', '🏷️', '🚀', '⭐', '🛒', '🤝', '💬'];
          foreach ($emojis as $e) echo "<span class='emoji-btn' onclick=\"insertEmoji('$e')\">$e</span>";
          ?>
        </div>

        <!-- Input bar -->
        <div class="msg-input-bar">
          <div class="input-side-btns">
            <button class="input-btn" onclick="toggleEmoji()" title="Emojis"><i class="bi bi-emoji-smile"></i></button>
            <button class="input-btn" title="Adjuntar" onclick="document.getElementById('attachInput').click()">
              <i class="bi bi-paperclip"></i>
            </button>
            <input type="file" id="attachInput" accept="image/*" style="display:none" onchange="handleImageAttach(this)">
          </div>
          <div class="input-field-wrap">
            <textarea id="msg" rows="1" placeholder="Escribe un mensaje..." onkeydown="handleKey(event)" oninput="autoResize(this)"></textarea>
          </div>
          <!-- LOGIC PRESERVED: onclick="sendMessage()" -->
          <button class="btn-send" id="btnSend" onclick="sendMessage()">
            <i class="bi bi-send-fill"></i>
          </button>
        </div>
      </div>

    </div>

    <!-- ══ RIGHT SIDEBAR – Seller info ══ -->
    <div class="seller-sidebar" id="sellerSidebar">

      <!-- Toggle tab: visible on the left edge of the sidebar -->
      <div class="ss-toggle-tab" id="ssToggleTab" onclick="toggleRightPanel()" title="Ocultar panel">
        <i class="bi bi-chevron-right" id="ssTabIcon"></i>
      </div>
      <div class="ss-header">
        <div class="ss-avatar" id="ssAvatar">?</div>
        <div class="ss-name" id="ssName">Vendedor</div>
        <div class="ss-role"><i class="bi bi-circle-fill" style="font-size:.5rem;color:var(--g400);"></i> En línea ahora</div>
        <div class="ss-verified"><i class="bi bi-patch-check-fill"></i> Verificado</div>
      </div>

      <div class="ss-stats">
        <div class="ss-stat">
          <div class="ss-num">98%</div>
          <div class="ss-lbl">Respuesta</div>
        </div>
        <div class="ss-stat">
          <div class="ss-num">4.8</div>
          <div class="ss-lbl">Rating</div>
        </div>
        <div class="ss-stat">
          <div class="ss-num">47</div>
          <div class="ss-lbl">Ventas</div>
        </div>
      </div>

      <div class="ss-section">
        <div class="ss-section-label"><i class="bi bi-info-circle-fill"></i> Información</div>
        <div class="ss-row"><i class="bi bi-geo-alt-fill"></i> Bogotá, Colombia</div>
        <div class="ss-row"><i class="bi bi-clock-fill"></i> Miembro desde 2023</div>
        <div class="ss-row"><i class="bi bi-shield-check-fill"></i> Identidad verificada</div>
        <div style="margin-top:10px;">
          <div class="stars">
            <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
          </div>
          <div class="rating-text">4.8 de 5 · 24 reseñas</div>
        </div>
      </div>

      <div class="ss-section">
        <div class="ss-section-label"><i class="bi bi-box-seam-fill"></i> Producto en discusión</div>
        <div class="ss-product-card" id="ssProdCard">
          <div class="ss-product-img" id="ssProdImg"><i class="bi bi-box-seam"></i></div>
          <div class="ss-product-body">
            <div class="ss-product-price" id="ssProdPrice">–</div>
            <div class="ss-product-title" id="ssProdTitle">–</div>
          </div>
        </div>
      </div>

      <div class="ss-section">
        <div class="ss-section-label"><i class="bi bi-lightning-charge-fill"></i> Respuestas rápidas</div>
        <div style="display:flex;flex-direction:column;gap:7px;">
          <button onclick="sendQuickReply('¿Está disponible?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">💬 "¿Está disponible?"</button>
          <button onclick="sendQuickReply('¿Acepta ofertas?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">💰 "¿Acepta ofertas?"</button>
          <button onclick="sendQuickReply('¿Cuándo podemos vernos?')" style="background:var(--g50);border:1.5px solid var(--g100);color:var(--g700);border-radius:10px;padding:8px 12px;font-family:'DM Sans',sans-serif;font-size:.8rem;font-weight:600;cursor:pointer;text-align:left;transition:all .2s;" onmouseover="this.style.background='var(--g100)'" onmouseout="this.style.background='var(--g50)'">📅 "¿Cuándo podemos vernos?"</button>
        </div>
      </div>

      <a href="" class="btn-view-seller" id="btnViewSeller" onclick="if(_currentOtherUserId) window.open('./perfil.php?id='+_currentOtherUserId,'_blank')">
        <i class="bi bi-person-circle"></i> Ver perfil del vendedor
      </a>

    </div>

  </div><!-- /app -->
  </div><!-- /page-shell -->

  <!-- Floating tab to reopen right panel when it's hidden -->
  <div class="ss-floating-tab" id="ssFloatingTab" onclick="toggleRightPanel()" title="Ver detalles del vendedor">
    <i class="bi bi-chevron-left"></i>
  </div>

  <!-- ══ DELETE CHAT MODAL ══ -->
  <div class="del-modal-overlay" id="delModal">
    <div class="del-modal">
      <div class="del-modal-icon" style="background:#fee2e2;">
        <i class="bi bi-trash-fill" style="color:#dc2626;"></i>
      </div>
      <div class="del-modal-title">¿Eliminar conversación?</div>
      <div class="del-modal-sub">
        Vas a eliminar el chat con
        <strong id="delModalName">este usuario</strong>.
        Esta acción <strong>no se puede deshacer</strong>.
      </div>
      <div class="del-modal-btns">
        <button class="del-modal-cancel" onclick="closeDeleteModal()">Cancelar</button>
        <button class="del-modal-confirm" id="delModalConfirmBtn"
          onclick="executeDeleteChat()"
          style="background:#dc2626;">
          <i class="bi bi-trash-fill"></i> Eliminar
        </button>
      </div>
    </div>
  </div>

  <!-- ══ TOAST ══ -->
  <div class="del-toast" id="delToast">
    <i class="bi bi-check-circle-fill"></i>
    Chat eliminado.
  </div>

  <script>
    let conversation_id = <?php echo $conversation_id ? intval($conversation_id) : 'null'; ?>;
    const USER_ID = <?php echo intval($user_id); ?>;
  </script>
  <script src="../js/chat.js"></script>

</body>

</html>