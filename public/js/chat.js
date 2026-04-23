/* ═══════════════════════════════════════
     LOGIC PRESERVED — original JS + extras
  ═══════════════════════════════════════ */
    let socket = null;
    let _wsConversationId = null; // conversación activa en el socket
    let _wsReconnectDelay = 1000; // ms, crece con backoff
    /* conversation_id y USER_ID se inyectan inline desde chat.php */
    let typingTimer = null;
    let typingEl = null;

    /* ── Helpers ── */
    function getTime() {
      const now = new Date();
      return now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    }

    function getDateLabel() {
      const d = new Date();
      const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      const diff = 0; // today
      if (diff === 0) return 'Hoy';
      if (diff === 1) return 'Ayer';
      return days[d.getDay()];
    }

    function addDateSep(label) {
      const sep = document.createElement('div');
      sep.className = 'date-sep';
      sep.innerHTML = `<span>${label}</span>`;
      document.getElementById('messages').appendChild(sep);
    }

    /* ── LOGIC PRESERVED: addMessage ── */
    function addMessage(text, isMe, isImage = false, customTime = null, senderName = null) {
      const msgs = document.getElementById('messages');

      const wrap = document.createElement('div');
      wrap.className = 'msg-wrap ' + (isMe ? 'me' : 'other');

      const time = customTime || getTime();
      const ticks = isMe ? '<div class="bubble-ticks read"><i class="bi bi-check-all"></i></div>' : '';

      if (isImage) {
        wrap.innerHTML = `
        <div class="bubble ${isMe ? 'me' : 'other'}" style="padding:5px;">
          <div class="bubble-img"><img src="${text}" alt="imagen" loading="lazy"></div>
          <div class="bubble-meta"><span class="bubble-time">${time}</span>${ticks}</div>
        </div>`;
      } else {
        wrap.innerHTML = `
        <div class="bubble ${isMe ? 'me' : 'other'}">
          <div class="bubble-text">${text}</div>
          <div class="bubble-meta"><span class="bubble-time">${time}</span>${ticks}</div>
        </div>`;
      }

      msgs.appendChild(wrap);
      wrap.scrollIntoView({
        behavior: 'smooth',
        block: 'end'
      });
    }

    /* Typing indicator */
    function showTyping() {
      if (typingEl) return;
      const msgs = document.getElementById('messages');
      const wrap = document.createElement('div');
      wrap.className = 'msg-wrap other';
      wrap.innerHTML = `<div class="typing-indicator"><div class="td"></div><div class="td"></div><div class="td"></div></div>`;
      msgs.appendChild(wrap);
      typingEl = wrap;
      wrap.scrollIntoView({
        behavior: 'smooth',
        block: 'end'
      });
    }

    function hideTyping() {
      if (typingEl) {
        typingEl.remove();
        typingEl = null;
      }
    }

    /* Auto responses (demo) */
    const autoReplies = [
      '¡Claro! Todavía está disponible 😊',
      'Sí, podemos negociar el precio.',
      'Te puedo enviar más fotos si quieres.',
      '¿Cuándo te quedaría bien vernos?',
      'El producto está en perfecto estado.',
      '¡Listo! Con gusto te lo aparto.',
    ];

    function triggerAutoReply() {
      clearTimeout(typingTimer);
      typingTimer = setTimeout(() => {
        showTyping();
        setTimeout(() => {
          hideTyping();
          const reply = autoReplies[Math.floor(Math.random() * autoReplies.length)];
          addMessage(reply, false);
        }, 1800);
      }, 600);
    }

    /* Estado del producto de la conversación abierta (para el botón "Marcar vendido") */
    let currentConv = { productId: 0, isSeller: false, productStatus: '' };

    /* ── LOGIC PRESERVED: openChat ── */
    function openChat(id, otherUser, productName, initial, isOnline, price, productImage, productId, isSeller, productStatus) {
      conversation_id = id;
      currentConv = {
        productId: parseInt(productId) || 0,
        isSeller: !!isSeller,
        productStatus: (productStatus || '').toLowerCase()
      };

      /* show chat panel */
      document.getElementById('chatEmpty').style.display = 'none';
      const active = document.getElementById('chatActive');
      active.style.display = 'flex';

      /* mark active in sidebar */
      document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
      const convEl = document.getElementById('conv-' + id);
      if (convEl) {
        convEl.classList.add('active');
        const badge = convEl.querySelector('.ci-unread');
        if (badge) badge.remove();
      }

      /* update header */
      document.getElementById('chAvatarInitial').textContent = initial;
      document.getElementById('chName').textContent = otherUser;
      const onlineDot = document.getElementById('chOnlineDot');
      const statusText = document.getElementById('chStatusText');
      onlineDot.style.background = isOnline ? 'var(--g400)' : '#ccc';
      statusText.textContent = isOnline ? 'En línea' : 'Visto recientemente';

      /* update right sidebar */
      document.getElementById('ssAvatar').textContent = initial;
      document.getElementById('ssName').textContent = otherUser;

      /* product pin & right sidebar product image — null-safe */
      const pin = document.getElementById('productPin');
      const ssProdCard = document.getElementById('ssProdCard');
      const ssProdSection = ssProdCard ? ssProdCard.closest('.ss-section') : null;

      const hasProduct = productName && productName !== 'null' && productName !== 'undefined' && productId > 0;

      if (hasProduct) {
        if (pin) pin.style.display = 'flex';
        if (ssProdSection) ssProdSection.style.display = 'block';
        const ppTitle = document.getElementById('ppTitle');
        const ppPrice = document.getElementById('ppPrice');
        const ssProdTitle = document.getElementById('ssProdTitle');
        const ssProdPrice = document.getElementById('ssProdPrice');
        if (ppTitle) ppTitle.textContent = productName;
        if (ppPrice) ppPrice.textContent = price || '';
        if (ssProdTitle) ssProdTitle.textContent = productName;
        if (ssProdPrice) ssProdPrice.textContent = price || '';

        const ppVerBtn = document.getElementById('ppVerBtn');
        if (ppVerBtn && productId) ppVerBtn.href = `../products/detalle.php?id=${productId}`;

        const imgHTML = productImage
          ? `<img src="${productImage}" alt="${productName}">`
          : '<i class="bi bi-box-seam"></i>';
        const ppImg = document.getElementById('ppImg');
        const ssProdImg = document.getElementById('ssProdImg');
        if (ppImg) ppImg.innerHTML = imgHTML;
        if (ssProdImg) ssProdImg.innerHTML = imgHTML;
      } else {
        if (pin) pin.style.display = 'none';
        if (ssProdSection) ssProdSection.style.display = 'none';
      }

      /* "Marcar vendido" — solo visible para el vendedor y si aún no se vendió */
      refreshMarkSoldUI();

      /* Open right panel */
      const sidebar = document.getElementById('sellerSidebar');
      const app = document.querySelector('.app');
      if (sidebar && sidebar.style.display !== 'flex') {
        sidebar.style.display = 'flex';
        const ssTabIcon = document.getElementById('ssTabIcon');
        if (ssTabIcon) ssTabIcon.style.transform = 'rotate(0deg)';
        requestAnimationFrame(() => app && app.classList.add('sidebar-open'));
      }
      const ssFloatingTab = document.getElementById('ssFloatingTab');
      if (ssFloatingTab) ssFloatingTab.style.display = 'none';

      /* Clear messages area */
      const msgs = document.getElementById('messages');
      msgs.innerHTML = '';
      addDateSep(getDateLabel());

      /* Load real message history */
      fetch(`../../api/chat/get_messages.php?conversation_id=${id}`)
        .then(res => {
          if (!res.ok) throw new Error('HTTP ' + res.status);
          return res.json();
        })
        .then(data => {
          if (Array.isArray(data) && data.length > 0) {
            data.forEach(msg => {
              addMessage(msg.message, msg.is_me, false, msg.time_label, msg.sender_name);
            });
          } else {
            /* Empty conversation — show neutral placeholder */
            const empty = document.createElement('div');
            empty.style.cssText = 'text-align:center;color:#888;font-size:.85rem;margin-top:40px;padding:20px;';
            empty.innerHTML = '<i class="bi bi-chat-dots" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4"></i>No hay mensajes aún.<br>¡Sé el primero en escribir!';
            msgs.appendChild(empty);
          }
        })
        .catch(err => {
          console.warn('get_messages error:', err);
          const errEl = document.createElement('div');
          errEl.style.cssText = 'text-align:center;color:#e55;font-size:.8rem;margin-top:20px;';
          errEl.textContent = 'No se pudieron cargar los mensajes. Intenta recargar.';
          msgs.appendChild(errEl);
        });

      /* Enable input */
      const msgInput = document.getElementById('msg');
      const btnSend = document.getElementById('btnSend');
      if (msgInput) { msgInput.disabled = false; msgInput.placeholder = 'Escribe un mensaje...'; msgInput.focus(); }
      if (btnSend) btnSend.disabled = false;

      /* Connect WebSocket */
      _connectSocket(id);
    }

    /* ── WebSocket: conexión con reconexión automática ── */
    function _connectSocket(id) {
      _wsConversationId = id;

      if (socket) {
        socket.onclose = null; // evitar reconexión de la conexión vieja
        socket.close();
      }

      const wsProto = window.location.protocol === 'https:' ? 'wss' : 'ws';
      const wsHost  = window.location.hostname;

      try {
        socket = new WebSocket(`${wsProto}://${wsHost}:8080/chat`);

        socket.onopen = () => {
          _wsReconnectDelay = 1000; // resetear backoff al conectar
          socket.send(JSON.stringify({
            type: 'init',
            conversation_id: id,
            user_id: USER_ID
          }));
        };

        socket.onmessage = (event) => {
          try {
            const data = JSON.parse(event.data);
            if (data.type === 'message') {
              // Solo mostrar mensajes del OTRO usuario; el propio ya se muestra al enviar
              if (data.sender_id != USER_ID) {
                addMessage(data.message, false);
              }
            }
          } catch (e) {
            console.warn('WS mensaje inválido:', e);
          }
        };

        socket.onclose = () => {
          if (_wsConversationId === id) {
            // Reconectar con backoff exponencial (máx. 30s)
            setTimeout(() => _connectSocket(id), _wsReconnectDelay);
            _wsReconnectDelay = Math.min(_wsReconnectDelay * 2, 30000);
          }
        };

        socket.onerror = (e) => {
          console.warn('WebSocket error:', e);
        };

      } catch (e) {
        console.warn('WebSocket no disponible:', e);
      }
    }

    function closeChat() {
      // Cerrar socket limpiamente sin intentar reconectar
      _wsConversationId = null;
      if (socket) {
        socket.onclose = null;
        socket.close();
        socket = null;
      }

      document.getElementById('chatEmpty').style.display = 'flex';
      document.getElementById('chatActive').style.display = 'none';
      document.querySelectorAll('.conv-item').forEach(el => el.classList.remove('active'));
      // Hide right sidebar and floating tab when no chat is open
      const sidebar = document.getElementById('sellerSidebar');
      document.querySelector('.app').classList.remove('sidebar-open');
      setTimeout(() => {
        sidebar.style.display = 'none';
      }, 300);
      document.getElementById('ssFloatingTab').style.display = 'none';
    }

    /* ── LOGIC PRESERVED + ENHANCED: sendMessage ── */
    function sendMessage() {
      const input = document.getElementById('msg');
      const msg = input.value.trim();
      if (!msg || !conversation_id) return;

      // Always persist via HTTP (reliable)
      fetch('../../api/chat/save_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ conversation_id, message: msg })
      }).catch(err => console.warn('save_message error:', err));

      // Also send via WebSocket for real-time delivery (if connected)
      if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({ type: 'message', message: msg }));
      }

      addMessage(msg, true);
      input.value = '';
      input.style.height = 'auto';

      // update sidebar preview
      const convEl = document.getElementById('conv-' + conversation_id);
      if (convEl) {
        const prev = convEl.querySelector('.ci-preview');
        if (prev) prev.textContent = msg;
        const t = convEl.querySelector('.ci-time');
        if (t) t.textContent = getTime();
      }
    }

    /* Quick reply helper */
    function sendQuickReply(msg) {
      document.getElementById('msg').value = msg;
      sendMessage();
    }

    /* ── Auto-open conversation from URL param ── */
    function autoOpenConversation() {
      const params = new URLSearchParams(window.location.search);
      const id = params.get('conversation_id');
      if (!id) return;
      const convEl = document.getElementById('conv-' + id);
      if (convEl) {
        convEl.click();
      } else {
        // conv item may not exist yet (new conversation, not in list yet)
        // Try opening directly via fetch
        fetch(`../../api/chat/get_messages.php?conversation_id=${id}`)
          .then(r => r.json())
          .then(() => {
            // Conversation is valid — open it directly without a sidebar item
            openChat(parseInt(id), '', '', '?', false, '', '', 0, false, '');
            // Refresh sidebar list to include this new conv
            pollConversations();
          })
          .catch(() => {});
      }
    }
    // Run after DOM + scripts are ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', autoOpenConversation);
    } else {
      autoOpenConversation();
    }

    /* ── Extra UI ── */
    function handleKey(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    }

    function autoResize(el) {
      el.style.height = 'auto';
      el.style.height = Math.min(el.scrollHeight, 100) + 'px';
    }

    function toggleEmoji() {
      document.getElementById('emojiPanel').classList.toggle('show');
    }

    function insertEmoji(emoji) {
      const input = document.getElementById('msg');
      input.value += emoji;
      input.focus();
    }

    function handleImageAttach(input) {
      const file = input.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = ev => addMessage(ev.target.result, true, true);
      reader.readAsDataURL(file);
      input.value = '';
    }

    function toggleRightPanel() {
      const sidebar = document.getElementById('sellerSidebar');
      const app = document.querySelector('.app');
      const icon = document.getElementById('ssTabIcon');
      const floatingTab = document.getElementById('ssFloatingTab');
      const isOpen = sidebar.style.display === 'flex';

      if (isOpen) {
        // ── Collapse: animate grid then hide ──
        app.classList.remove('sidebar-open');
        icon.style.transform = 'rotate(180deg)';
        setTimeout(() => {
          sidebar.style.display = 'none';
          // Show floating tab so user can re-open
          if (conversation_id) floatingTab.style.display = 'flex';
        }, 300);
      } else {
        // ── Expand: show then animate grid ──
        floatingTab.style.display = 'none';
        sidebar.style.display = 'flex';
        icon.style.transform = 'rotate(0deg)';
        requestAnimationFrame(() => app.classList.add('sidebar-open'));
      }
    }

    function filterChats() {
      const q = document.getElementById('searchInput').value.toLowerCase();
      document.querySelectorAll('.conv-item').forEach(item => {
        const name = item.dataset.name?.toLowerCase() || '';
        item.style.display = name.includes(q) ? 'flex' : 'none';
      });
    }

    function filterByType(el, type) {
      document.querySelectorAll('.cf-chip').forEach(c => c.classList.remove('active'));
      el.classList.add('active');
    }

    /* ══════════════════════════════════
       SIDEBAR POLLING
       Consulta cada 5s si hay conversaciones
       nuevas o reactivadas (ej: alguien escribe
       en un chat que el otro había eliminado).
    ══════════════════════════════════ */
    let _knownConvIds = new Set(
      [...document.querySelectorAll('.conv-item')].map(el => el.id.replace('conv-', ''))
    );

    function buildConvItem(c) {
      const isOnline = Math.random() > 0.5; // en producción usar presencia real
      const onlineClass = isOnline ? 'online-ring' : 'offline-ring';
      const unreadBadge = c.unread_count > 0 ?
        `<div class="ci-unread">${c.unread_count}</div>` : '';
      const price = c.product_price ?
        '$' + Number(c.product_price).toLocaleString('es-CO') : '';
      
      const safeOtherUser   = (c.other_user || 'Usuario').replace(/'/g, "\\'");
      const safeProductName = (c.product_name || '').replace(/'/g, "\\'");
      const safeProdImg     = (c.product_image ? '../../../public/uploads/products/' + c.product_image : '').replace(/'/g, "\\'");

      const safeProductStatus = (c.product_status || '').replace(/'/g, "\\'");
      const isSellerFlag = c.is_seller ? 'true' : 'false';
      return `
      <div class="conv-item"
           onclick="openChat(${c.id},'${safeOtherUser}','${safeProductName}','${c.initial}',${isOnline},'${price}','${safeProdImg}',${c.product_id || 0},${isSellerFlag},'${safeProductStatus}')"
           data-name="${c.other_user}"
           id="conv-${c.id}"
           style="animation:slideIn .35s both;">
        <div class="ci-avatar">
          ${c.initial}
          <div class="${onlineClass}"></div>
        </div>
        <div class="ci-body">
          <div class="ci-top">
            <div class="ci-name">${c.other_user}</div>
            <div class="ci-time">${c.time_label}</div>
          </div>
          <div class="ci-bottom">
            <div class="ci-preview">${c.last_message || 'Sin mensajes aún'}</div>
            ${unreadBadge}
          </div>
          <div style="margin-top:3px;">
            <div class="ci-product-tag">${c.product_name || 'Conversación directa'}</div>
          </div>
        </div>
        <button class="ci-del-btn" title="Eliminar chat"
                onclick="event.stopPropagation();confirmDeleteChat(${c.id},'${safeOtherUser}')" >
          <i class="bi bi-trash-fill"></i>
        </button>
      </div>`;
    }

    function pollConversations() {
      fetch('../../api/chat/get_conversations.php')
        .then(r => r.json())
        .then(list => {
          if (!Array.isArray(list)) return;

          const convList = document.getElementById('convList');

          list.forEach(c => {
            const idStr = String(c.id);

            if (!_knownConvIds.has(idStr)) {
              // ── NUEVA o REACTIVADA: insertar al tope ──
              _knownConvIds.add(idStr);

              // Quitar empty state si estaba
              const empty = convList.querySelector('.cs-empty');
              if (empty) empty.remove();

              // Insertar al inicio de la lista
              convList.insertAdjacentHTML('afterbegin', buildConvItem(c));

              // Notificación visual si no es el chat activo
              if (c.id !== conversation_id && c.unread_count > 0) {
                showToast(`💬 Nuevo mensaje de ${c.other_user}`);
              }

            } else {
              // ── EXISTENTE: actualizar preview y hora ──
              const el = document.getElementById('conv-' + c.id);
              if (!el) return;
              const preview = el.querySelector('.ci-preview');
              const time = el.querySelector('.ci-time');
              if (preview && c.last_message) preview.textContent = c.last_message;
              if (time && c.time_label) time.textContent = c.time_label;

              // actualizar badge de no leídos
              let badge = el.querySelector('.ci-unread');
              if (c.unread_count > 0 && c.id !== conversation_id) {
                if (!badge) {
                  const bottom = el.querySelector('.ci-bottom');
                  if (bottom) {
                    badge = document.createElement('div');
                    badge.className = 'ci-unread';
                    bottom.appendChild(badge);
                  }
                }
                if (badge) badge.textContent = c.unread_count;
              } else if (badge) {
                badge.remove();
              }
            }
          });

          // Detectar conversaciones eliminadas por el servidor
          // (si ya no vienen en la lista, removerlas)
          _knownConvIds.forEach(idStr => {
            if (!list.find(c => String(c.id) === idStr)) {
              _knownConvIds.delete(idStr);
              // No removemos del DOM aquí porque puede ser que la eliminó
              // este mismo usuario y ya fue animada por executeDeleteChat()
            }
          });
        })
        .catch(() => {}); // silenciar errores de red
    }

    // Arrancar polling cada 5 segundos
    setInterval(pollConversations, 5000);

    /* close emoji on click outside */
    document.addEventListener('click', e => {
      if (!e.target.closest('.emoji-panel') && !e.target.closest('.input-btn')) {
        document.getElementById('emojiPanel').classList.remove('show');
      }
    });

    /* ══════════════════════════════════
       DELETE CHAT LOGIC
    ══════════════════════════════════ */
    let _pendingDeleteId = null;
    let _pendingDeleteName = null;

    function confirmDeleteChat(convId, otherUser) {
      _pendingDeleteId = convId;
      _pendingDeleteName = otherUser;
      document.getElementById('delModalName').textContent = otherUser;
      /* Resetear botón al abrir modal */
      const btn = document.getElementById('delModalConfirmBtn');
      btn.innerHTML = '<i class="bi bi-trash-fill"></i> Eliminar';
      btn.disabled  = false;
      document.getElementById('delModal').classList.add('show');
    }

    function closeDeleteModal() {
      document.getElementById('delModal').classList.remove('show');
      _pendingDeleteId = null;
      _pendingDeleteName = null;
    }

    /* Close modal clicking outside */
    document.getElementById('delModal').addEventListener('click', function(e) {
      if (e.target === this) closeDeleteModal();
    });

    /* Close modal with Escape */
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') closeDeleteModal();
    });

    /* track other_user_id for "ver perfil" button */
    let _currentOtherUserId = null;

    /* Patch openChat to store other_user_id */
    const _origOpenChat = openChat;
    // We override the call to store other_user_id from the PHP-rendered onclick
    // For PHP-rendered items the id is passed; we capture it via pollConversations data
    // and from initial list we read it from data attribute:
    document.querySelectorAll('.conv-item').forEach(el => {
      const onclick = el.getAttribute('onclick') || '';
      // first numeric arg is conv id; second arg is other_user_id from select
      // We piggyback on conv-{id} and look up from convList
    });

    function showToast(msg) {
      const toast = document.getElementById('delToast');
      toast.innerHTML = `<i class="bi bi-check-circle-fill"></i> ${msg}`;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 3200);
    }

    /* ══ executeDeleteChat — ocultar conversación via AJAX ══ */
    async function executeDeleteChat() {
      if (!_pendingDeleteId) return;
      const convId = _pendingDeleteId;
      const name   = _pendingDeleteName;

      const btn = document.getElementById('delModalConfirmBtn');
      btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Eliminando...';
      btn.disabled  = true;

      try {
        const res  = await fetch('../../api/chat/eliminar_chat.php', {
          method : 'POST',
          headers: { 'Content-Type': 'application/json' },
          body   : JSON.stringify({ conversation_id: convId })
        });
        const data = await res.json();

        if (data.ok) {
          /* Animate out of sidebar */
          const el = document.getElementById('conv-' + convId);
          if (el) {
            el.style.transition = 'opacity .3s, transform .3s';
            el.style.opacity    = '0';
            el.style.transform  = 'translateX(-20px)';
            setTimeout(() => el.remove(), 300);
          }
          _knownConvIds.delete(String(convId));

          /* If this was the active chat, reset to empty state */
          if (conversation_id === convId) {
            closeChat();
            conversation_id = null;
          }

          /* Resetear botón antes de cerrar modal */
          btn.innerHTML = '<i class="bi bi-trash-fill"></i> Eliminar';
          btn.disabled  = false;
          
          closeDeleteModal();
          showToast(`💬 Chat con ${name} eliminado.`);
        } else {
          alert('Error: ' + (data.error || 'No se pudo eliminar'));
          btn.innerHTML = '<i class="bi bi-trash-fill"></i> Eliminar';
          btn.disabled  = false;
        }
      } catch {
        alert('Error de conexión. Intenta de nuevo.');
        btn.innerHTML = '<i class="bi bi-trash-fill"></i> Eliminar';
        btn.disabled  = false;
      }
    }

    /* ══ "Marcar vendido" / "Deshacer venta" desde el header del chat ══ */
    function refreshMarkSoldUI() {
      const btn       = document.getElementById('chSoldBtn');
      const btnIcon   = document.getElementById('chSoldBtnIcon');
      const btnLabel  = document.getElementById('chSoldBtnLabel');
      const chip      = document.getElementById('chSoldChip');
      if (!btn || !chip || !btnLabel || !btnIcon) return;

      const hasProduct = currentConv.productId > 0;
      const isSold     = currentConv.productStatus === 'vendido';

      /* Comprador: sin botón, solo el chip "Vendido" si corresponde */
      if (!hasProduct || !currentConv.isSeller) {
        btn.style.display  = 'none';
        chip.style.display = (hasProduct && isSold) ? 'inline-flex' : 'none';
        return;
      }

      /* Vendedor: botón que alterna entre "Marcar vendido" y "Deshacer venta" */
      chip.style.display = 'none';
      btn.style.display  = 'inline-flex';
      btn.disabled = false;
      if (isSold) {
        btn.classList.remove('is-mark'); btn.classList.add('is-undo');
        btn.title = 'Deshacer la venta y volver a publicar';
        btnIcon.className = 'bi bi-arrow-counterclockwise';
        btnLabel.textContent = 'Deshacer venta';
      } else {
        btn.classList.remove('is-undo'); btn.classList.add('is-mark');
        btn.title = 'Marcar este producto como vendido';
        btnIcon.className = 'bi bi-check2-circle';
        btnLabel.textContent = 'Marcar vendido';
      }
    }

    async function toggleConvProductSold() {
      if (!currentConv.productId || !currentConv.isSeller) return;

      const isSold = currentConv.productStatus === 'vendido';
      const action = isSold ? 'undo' : 'mark';
      const confirmMsg = isSold
        ? '¿Deshacer la venta? El producto volverá a estar disponible en la tienda.'
        : '¿Confirmar que este producto ya fue vendido? Se retirará de la tienda.';
      if (!confirm(confirmMsg)) return;

      const btn      = document.getElementById('chSoldBtn');
      const btnIcon  = document.getElementById('chSoldBtnIcon');
      const btnLabel = document.getElementById('chSoldBtnLabel');
      if (btn) {
        btn.disabled = true;
        if (btnIcon)  btnIcon.className  = 'bi bi-hourglass-split';
        if (btnLabel) btnLabel.textContent = 'Guardando...';
      }

      try {
        const res = await fetch('../../api/products/mark_sold.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          credentials: 'same-origin',
          body: JSON.stringify({ product_id: currentConv.productId, action })
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok || !data.ok) {
          alert(data.error || 'No se pudo actualizar el producto');
          refreshMarkSoldUI();
          return;
        }

        currentConv.productStatus = data.status || (isSold ? 'disponible' : 'vendido');
        refreshMarkSoldUI();
        if (typeof showToast === 'function') {
          showToast(isSold ? 'Venta deshecha — producto disponible' : 'Producto marcado como vendido');
        }
      } catch {
        alert('Error de conexión. Intenta de nuevo.');
        refreshMarkSoldUI();
      }
    }


    /* -- MOBILE NAVIGATION --
       En movil (<=700px) el chat ocupa la pantalla completa.
       openChat() oculta la lista; closeChat() la restaura.
    ===================================================== */
    const _isMobile = () => window.innerWidth <= 700;

    const _origOpenChatFn = openChat;
    openChat = function(...args) {
      _origOpenChatFn(...args);
      if (_isMobile()) {
        const cs = document.querySelector('.conv-sidebar');
        if (cs) { cs.classList.remove('mobile-open'); cs.style.display = 'none'; }
      }
    };

    const _origCloseChatFn = closeChat;
    closeChat = function() {
      _origCloseChatFn();
      if (_isMobile()) {
        const cs = document.querySelector('.conv-sidebar');
        if (cs) {
          cs.style.display = 'flex';
          cs.style.flexDirection = 'column';
          cs.classList.add('mobile-open');
        }
      }
    };

    /* Mostrar lista al cargar si no hay chat activo en movil */
    document.addEventListener('DOMContentLoaded', () => {
      if (_isMobile()) {
        const cs = document.querySelector('.conv-sidebar');
        const active = document.getElementById('chatActive');
        if (cs && !(active && active.style.display === 'flex')) {
          cs.style.display = 'flex';
          cs.style.flexDirection = 'column';
          cs.classList.add('mobile-open');
        }
      }
    });

    /* Restaurar layout al redimensionar a desktop */
    window.addEventListener('resize', () => {
      const cs = document.querySelector('.conv-sidebar');
      if (!cs) return;
      if (!_isMobile()) {
        cs.style.display = '';
        cs.style.position = '';
        cs.style.width = '';
        cs.classList.remove('mobile-open');
      }
    });
