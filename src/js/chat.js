/* ═══════════════════════════════════════
     LOGIC PRESERVED — original JS + extras
  ═══════════════════════════════════════ */
    let socket = null;
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

    /* ── LOGIC PRESERVED: openChat ── */
    function openChat(id, otherUser, productName, initial, isOnline, price, productImage) {
      conversation_id = id;

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

      /* product pin & right sidebar product image */
      const pin = document.getElementById('productPin');
      const ssProdSection = document.getElementById('ssProdCard').closest('.ss-section');

      if (productName && productName !== 'null' && productName !== 'undefined') {
        pin.style.display = 'flex';
        if(ssProdSection) ssProdSection.style.display = 'block';
        document.getElementById('ppTitle').textContent = productName;
        document.getElementById('ppPrice').textContent = price || '';
        document.getElementById('ssProdTitle').textContent = productName;
        document.getElementById('ssProdPrice').textContent = price || '';

        const imgHTML = productImage ? `<img src="${productImage}" alt="${productName}">` : '<i class="bi bi-box-seam"></i>';
        document.getElementById('ppImg').innerHTML = imgHTML;
        document.getElementById('ssProdImg').innerHTML = imgHTML;
      } else {
        pin.style.display = 'none';
        if(ssProdSection) ssProdSection.style.display = 'none';
      }

      /* Open right panel when chat is opened (if not already open) */
      const sidebar = document.getElementById('sellerSidebar');
      const app = document.querySelector('.app');
      if (sidebar.style.display !== 'flex') {
        sidebar.style.display = 'flex';
        document.getElementById('ssTabIcon').style.transform = 'rotate(0deg)';
        requestAnimationFrame(() => app.classList.add('sidebar-open'));
      }
      /* Show the floating open-tab (in case sidebar was hidden before) */
      document.getElementById('ssFloatingTab').style.display = 'none';

      /* clear messages, add date separator + demo messages */
      const msgs = document.getElementById('messages');
      msgs.innerHTML = '';
      addDateSep(getDateLabel());

      /* Load history via AJAX — LOGIC PRESERVED */
      fetch(`get_messages.php?conversation_id=${id}`)
        .then(res => res.json())
        .then(data => {
          if (data && data.length) {
            // Use is_me and time_label from improved get_messages.php
            data.forEach(msg => {
              addMessage(msg.message, msg.is_me, false, msg.time_label, msg.sender_name);
            });
          } else {
            /* Demo messages if no history */
            addMessage('Hola! Estoy interesado en tu producto 👋', false);
            addMessage('¡Hola! Claro, con mucho gusto. ¿Qué quieres saber?', true);
            addMessage('¿Está disponible para verlo esta semana?', false);
            /* quick replies */
            const qr = document.createElement('div');
            qr.className = 'quick-replies';
            qr.innerHTML = `
            <span class="qr-pill" onclick="sendQuickReply('Sí, claro! ¿Cuándo te queda bien?')">Sí, disponible</span>
            <span class="qr-pill" onclick="sendQuickReply('Puedo el sábado en la tarde.')">El sábado</span>
            <span class="qr-pill" onclick="sendQuickReply('Te envío las fotos ahora mismo.')">Enviar fotos</span>`;
            msgs.appendChild(qr);
          }
        })
        .catch(() => {
          /* No endpoint yet: show demo */
          addMessage('Hola! ¿Está disponible este producto?', false);
          addMessage('Sí, claro. ¿Cuándo quieres verlo?', true);
        });

      /* enable input */
      document.getElementById('msg').disabled = false;
      document.getElementById('btnSend').disabled = false;
      document.getElementById('msg').placeholder = 'Escribe un mensaje...';
      document.getElementById('msg').focus();

      /* close old socket, open new — LOGIC PRESERVED */
      if (socket) socket.close();
      try {
        socket = new WebSocket("ws://localhost:8080/chat");
        socket.onopen = () => {
          socket.send(JSON.stringify({
            type: "init",
            user_id: USER_ID,
            conversation_id: id
          }));
        };
        socket.onmessage = (event) => {
          const data = JSON.parse(event.data);
          addMessage(data.message, data.sender_id == USER_ID);
        };
      } catch (e) {
        console.warn('WebSocket not available (demo mode)');
      }
    }

    function closeChat() {
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

      // Try WebSocket first
      if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({
          type: 'message',
          message: msg,
          sender_id: USER_ID,
          conversation_id: conversation_id
        }));
      }

      // Always persist via HTTP (WebSocket server also should persist,
      // but this ensures delivery even without a WS server running)
      fetch('save_message.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          conversation_id,
          message: msg
        })
      }).catch(err => console.warn('save_message error:', err));

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

      triggerAutoReply();
    }

    /* Quick reply helper */
    function sendQuickReply(msg) {
      document.getElementById('msg').value = msg;
      sendMessage();
    }

    /* ── LOGIC PRESERVED: auto-open if URL param ── */
    window.onload = () => {
      const params = new URLSearchParams(window.location.search);
      const id = params.get('conversation_id');
      if (id) {
        const convEl = document.getElementById('conv-' + id);
        if (convEl) convEl.click();
      }
    };

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
      const safeProdImg     = (c.product_image ? './productos/uploads/' + c.product_image : '').replace(/'/g, "\\'");

      return `
      <div class="conv-item"
           onclick="openChat(${c.id},'${safeOtherUser}','${safeProductName}','${c.initial}',${isOnline},'${price}','${safeProdImg}')"
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
      fetch('get_conversations.php')
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
        const res  = await fetch('./eliminar_chat.php', {
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
