/* ── Avatar preview ── */
        function previewAvatar(input) {
            const file = input.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => {
                const preview = document.getElementById('avatarPreview');
                const initial = document.getElementById('avatarInitial');
                preview.src = ev.target.result;
                preview.style.display = 'block';
                if (initial) initial.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }

        /* ── Password visibility toggle ── */
        function togglePw(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            const hidden = input.type === 'password';
            input.type = hidden ? 'text' : 'password';
            icon.className = hidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        }

        /* ── Password strength ── */
        function checkPwStrength(input) {
            const v = input.value;
            const wrap = document.getElementById('pwStrength');
            wrap.style.display = v.length ? 'flex' : 'none';
            let score = 0;
            if (v.length >= 8) score++;
            if (/[A-Z]/.test(v)) score++;
            if (/[0-9]/.test(v)) score++;
            if (/[^A-Za-z0-9]/.test(v)) score++;
            const bars = ['pb1', 'pb2', 'pb3', 'pb4'];
            const cls = score <= 1 ? 'weak' : score <= 2 ? 'med' : 'strong';
            const labels = ['', 'Débil', 'Regular', 'Buena', 'Fuerte'];
            bars.forEach((id, i) => {
                const b = document.getElementById(id);
                b.className = 'pw-bar' + (i < score ? ' ' + cls : '');
            });
            const lbl = document.getElementById('pwLabel');
            lbl.textContent = labels[score] || 'Débil';
            lbl.style.color = score <= 1 ? '#f87171' : score <= 2 ? 'var(--y500)' : 'var(--g500)';
        }

        /* ── Password match ── */
        function checkPwMatch(input) {
            const newPw = document.getElementById('new_pw').value;
            const hint = document.getElementById('pwMatchHint');
            if (!input.value) {
                hint.innerHTML = '';
                return;
            }
            const match = input.value === newPw;
            hint.innerHTML = match ?
                '<i class="bi bi-check-circle-fill"></i> Las contraseñas coinciden' :
                '<i class="bi bi-x-circle-fill"></i> No coinciden';
            hint.className = 'form-hint ' + (match ? 'ok' : '');
            hint.style.color = match ? 'var(--g500)' : '#ef4444';
        }

        /* ── Auto-dismiss toast after 5s ── */
        const toast = document.querySelector('.toast');
        if (toast) setTimeout(() => {
            toast.style.transition = 'opacity .4s, transform .4s';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-8px)';
            setTimeout(() => toast.remove(), 400);
        }, 5000);

