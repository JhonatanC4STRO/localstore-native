/* Password visibility toggle */
    const pwToggle = document.getElementById('pwToggle');
    const pwInput = document.getElementById('password');
    const pwIcon = document.getElementById('pwIcon');

    pwToggle.addEventListener('click', () => {
      const isHidden = pwInput.type === 'password';
      pwInput.type = isHidden ? 'text' : 'password';
      pwIcon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
    });

    /* Ticker rotation */
    const tickers = [{
        name: 'Juanita M.',
        action: 'acaba de publicar un artículo en Bogotá'
      },
      {
        name: 'Carlos A.',
        action: 'vendió una bicicleta en Medellín'
      },
      {
        name: 'Sara R.',
        action: 'está buscando muebles en Cali'
      },
      {
        name: 'Andrés F.',
        action: 'publicó un iPhone en Barranquilla'
      },
    ];
    let ti = 0;
    const tickerText = document.querySelector('.ticker-text');
    setInterval(() => {
      ti = (ti + 1) % tickers.length;
      tickerText.innerHTML = `<strong>${tickers[ti].name}</strong> ${tickers[ti].action}`;
    }, 3500);

