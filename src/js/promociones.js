/**
 * promociones.js – Product Promotion Wizard
 * ComercioLocal
 *
 * Requires: PRODUCT_DATA, PRODUCT_IMAGE, ACTIVE_PROMO,
 *           PRODUCT_ID, INITIAL_SCREEN (injected by PHP)
 */

/* ── Plan Config (client-side display config) ──────────── */
const PLANS = {
  basic: {
    type:     'basic',
    name:     'Plan Básico',
    price:    3000,
    display:  '$3.000',
    duration: 3,
    badge:    'Destacado',
    icon:     '⭐',
    color:    '#16a34a',
    bg:       '#dcfce7',
    methods:  { nequi: 'Nequi', card: 'Tarjeta', cash: 'Efectivo' },
  },
  recommended: {
    type:     'recommended',
    name:     'Plan Recomendado',
    price:    5000,
    display:  '$5.000',
    duration: 7,
    badge:    'Más popular',
    icon:     '🚀',
    color:    '#ca8a04',
    bg:       '#fef9c3',
    methods:  { nequi: 'Nequi', card: 'Tarjeta', cash: 'Efectivo' },
  },
  premium: {
    type:     'premium',
    name:     'Plan Premium',
    price:    10000,
    display:  '$10.000',
    duration: 7,
    badge:    'Premium',
    icon:     '💎',
    color:    '#d4a017',
    bg:       'rgba(212,160,23,.15)',
    methods:  { nequi: 'Nequi', card: 'Tarjeta', cash: 'Efectivo' },
  },
};

const METHOD_LABELS = { nequi: 'Nequi', card: 'Tarjeta de crédito/débito', cash: 'Efectivo' };

/* ── State ──────────────────────────────────────────────── */
const state = {
  screen:         null,
  selectedPlan:   null,
  selectedMethod: null,
  txnData:        null,
  error:          '',
};

/* ── Helpers ────────────────────────────────────────────── */
const $  = (id) => document.getElementById(id);
const el = (id) => document.getElementById(id);

function fmtDate(dateStr) {
  if (!dateStr) return '—';
  const d = new Date(dateStr.replace(' ', 'T'));
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

function addDays(days) {
  const d = new Date();
  d.setDate(d.getDate() + days);
  return d.toLocaleDateString('es-CO', { day: '2-digit', month: 'short', year: 'numeric' });
}

/* ── Step Indicator ─────────────────────────────────────── */
const STEP_MAP = { plans: 1, summary: 2, payment: 3, confirm: 4 };

function updateStepIndicator(screen) {
  const si = $('stepIndicator');
  const n  = STEP_MAP[screen] || 0;
  if (!n) { si.style.display = 'none'; return; }
  si.style.display = 'flex';

  [1,2,3,4].forEach(i => {
    const item = $(`si${i}`);
    item.classList.remove('active', 'done');
    if (i < n)      item.classList.add('done');
    else if (i === n) item.classList.add('active');
  });
  [1,2,3].forEach(i => {
    const line = $(`sl${i}`);
    if (line) line.classList.toggle('done', i < n);
  });
}

/* ── Show Screen ────────────────────────────────────────── */
function goTo(screen) {
  // Disable blocked screens if has active promo
  if (ACTIVE_PROMO && (screen === 'plans' || screen === 'summary' ||
      screen === 'payment' || screen === 'confirm')) {
    // Allow plans screen to show the warning
  }

  document.querySelectorAll('.wizard-screen').forEach(s => s.classList.remove('active'));

  const screenMap = {
    selector:   'screenSelector',
    plans:      'screenPlans',
    summary:    'screenSummary',
    payment:    'screenPayment',
    confirm:    'screenConfirm',
    processing: 'screenProcessing',
    success:    'screenSuccess',
    error:      'screenError',
  };

  const target = el(screenMap[screen]);
  if (target) { target.classList.add('active'); }

  state.screen = screen;
  updateStepIndicator(screen);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── Product Summary Card ───────────────────────────────── */
function renderProductSummary() {
  if (!PRODUCT_DATA) return;

  const imgWrap = $('psSummaryImg');
  if (PRODUCT_IMAGE) {
    imgWrap.innerHTML = `<img src="./productos/uploads/${PRODUCT_IMAGE}" alt="">`;
  }

  const title = $('psSummaryTitle');
  if (title) title.textContent = PRODUCT_DATA.title || '—';

  const price = $('psSummaryPrice');
  if (price) {
    const p = parseFloat(PRODUCT_DATA.price || 0);
    price.textContent = '$' + p.toLocaleString('es-CO') + ' COP';
  }

  const cat = $('psSummaryCat');
  if (cat && PRODUCT_DATA.category_name) {
    cat.innerHTML = `<i class="bi bi-tag-fill"></i> ${PRODUCT_DATA.category_name}`;
  }
}

/* ── Active Promo Alert ─────────────────────────────────── */
function showActivePromoAlert() {
  const alert = $('activePromoAlert');
  if (!alert) return;
  alert.style.display = 'flex';
  const planName = PLANS[ACTIVE_PROMO.plan_type]?.name || ACTIVE_PROMO.plan_type;
  const endFmt   = fmtDate(ACTIVE_PROMO.end_date);
  $('activePromoText').textContent = ` Tu producto tiene el ${planName} activo hasta el ${endFmt}.`;

  // Disable plan cards
  document.querySelectorAll('.plan-card').forEach(c => {
    c.style.opacity       = '0.45';
    c.style.pointerEvents = 'none';
    c.style.cursor        = 'not-allowed';
  });
}

/* ── Select Plan ────────────────────────────────────────── */
function selectPlan(type) {
  if (ACTIVE_PROMO) return;

  const plan = PLANS[type];
  if (!plan) return;

  state.selectedPlan = type;

  // Highlight selected card
  document.querySelectorAll('.plan-card').forEach(c => c.classList.remove('selected'));
  const card = $(`card${type.charAt(0).toUpperCase() + type.slice(1)}`);
  if (card) card.classList.add('selected');

  // Fill summary screen
  fillSummary(plan);

  // Proceed to summary after short delay for visual feedback
  setTimeout(() => goTo('summary'), 220);
}

/* ── Fill Summary Screen ────────────────────────────────── */
function fillSummary(plan) {
  const setTxt = (id, v) => { const e = el(id); if (e) e.textContent = v; };
  setTxt('sumProductTitle', PRODUCT_DATA?.title || '—');
  setTxt('sumPlanName',     plan.icon + ' ' + plan.name);
  setTxt('sumDuration',     plan.duration + ' días');
  setTxt('sumStartDate',    new Date().toLocaleDateString('es-CO', { day:'2-digit', month:'short', year:'numeric' }));
  setTxt('sumEndDate',      addDays(plan.duration));
  setTxt('sumTotal',        plan.display + ' COP');
}

/* ── Select Payment Method ──────────────────────────────── */
function selectMethod(method) {
  state.selectedMethod = method;

  document.querySelectorAll('.payment-method-card').forEach(c => c.classList.remove('selected'));
  const map = { nequi: 'pmNequi', card: 'pmCard', cash: 'pmCash' };
  const card = el(map[method]);
  if (card) card.classList.add('selected');

  const btn = $('btnToConfirm');
  if (btn) btn.removeAttribute('disabled');

  fillConfirm();
}

/* ── Fill Confirm Screen ────────────────────────────────── */
function fillConfirm() {
  const plan = PLANS[state.selectedPlan];
  if (!plan) return;

  const setTxt = (id, v) => { const e = el(id); if (e) e.textContent = v; };
  setTxt('confProduct',  PRODUCT_DATA?.title || '—');
  setTxt('confPlan',     plan.icon + ' ' + plan.name);
  setTxt('confDuration', plan.duration + ' días');
  setTxt('confMethod',   METHOD_LABELS[state.selectedMethod] || '—');
  setTxt('confTotal',    plan.display + ' COP');

  // Plan badge
  const badge = $('confirmPlanBadge');
  if (badge) {
    badge.innerHTML = `
      <span class="confirm-plan-badge" style="background:${plan.bg};color:${plan.color};border:1.5px solid ${plan.color}40;margin-bottom:16px;">
        ${plan.icon} ${plan.name} — ${plan.badge}
      </span>`;
  }
}

/* ── Confirm Payment ────────────────────────────────────── */
async function confirmPayment() {
  if (!state.selectedPlan || !state.selectedMethod) return;

  const btn = $('btnConfirmPay');
  if (btn) { btn.disabled = true; btn.textContent = 'Procesando…'; }

  goTo('processing');

  // Artificial delay for UX
  await new Promise(r => setTimeout(r, 2000));

  try {
    const res = await fetch('../api/payments_simulate.php', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        product_id: PRODUCT_ID,
        plan_type:  state.selectedPlan,
        method:     state.selectedMethod,
      }),
    });

    const json = await res.json();

    if (json.success) {
      state.txnData = json.data;
      showSuccess(json.data);
    } else {
      showError(json.message || 'Error al procesar el pago.');
    }
  } catch (e) {
    showError('Error de conexión. Verifica tu internet e intenta de nuevo.');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-lock-fill"></i> Confirmar pago'; }
  }
}

/* ── Show Success ───────────────────────────────────────── */
function showSuccess(data) {
  const setTxt = (id, v) => { const e = el(id); if (e) e.textContent = v; };
  setTxt('txnId',      data.transaction_id || '—');
  setTxt('resProduct', data.product_title  || PRODUCT_DATA?.title || '—');
  setTxt('resPlan',    data.plan_name      || '—');
  setTxt('resStart',   fmtDate(data.start_date));
  setTxt('resEnd',     fmtDate(data.end_date));
  goTo('success');
}

/* ── Show Error ─────────────────────────────────────────── */
function showError(msg) {
  state.error = msg;
  const e = $('errorMessage');
  if (e) e.textContent = msg;
  goTo('error');
}

/* ── Init ───────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  goTo(INITIAL_SCREEN || 'selector');

  if (INITIAL_SCREEN === 'plans') {
    renderProductSummary();
    if (ACTIVE_PROMO) showActivePromoAlert();
  }
});
