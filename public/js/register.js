/* ── Account type toggle ── */
    function selectAcct(type) {
      const value = (type === 'personal') ? 'usuario' : 'tienda';
      document.getElementById('type_user').value = value;
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



