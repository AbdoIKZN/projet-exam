<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<main class="wrap" style="max-width: 1120px; margin: 0 auto; padding: 32px 24px 60px;">
  <div class="headline" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 18px; margin-bottom: 24px;">
    <div>
      <h1 style="margin: 0 0 6px; font-size: 30px; color: #0f172a; font-weight: 800;">Dashboard organisateur</h1>
      <p class="sub" style="color: var(--muted); margin: 0; font-size: 14px;">Statistiques temps réel, mises à jour automatiquement toutes les 30 secondes.</p>
    </div>
    <div class="status-pill" style="background: #fff; border: 1px solid var(--border); border-radius: 999px; padding: 10px 14px; color: var(--muted); font-size: 12px; white-space: nowrap; box-shadow: 0 6px 16px rgba(15, 23, 42, .05);">
        <span class="dot" style="display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: var(--green); margin-right: 8px; vertical-align: middle;"></span>
        <span id="last-update">Chargement...</span>
    </div>
  </div>

  <div id="error-box" class="error" style="display: none; margin-bottom: 16px; border: 1px solid #fecaca; background: #fef2f2; color: #991b1b; padding: 12px 14px; border-radius: 12px; font-size: 13px;"></div>

  <section class="grid kpis" style="display: grid; gap: 16px; grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 20px;">
    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <p class="label" style="margin: 0 0 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 11px; font-weight: 800;">Total inscrits</p>
      <p class="num blue" id="d-total" style="margin: 0; font-size: 42px; line-height: 1; font-weight: 900; color: #9f1239;">0</p>
      <p class="hint" style="margin: 8px 0 0; color: #94a3b8; font-size: 12px;">tous événements</p>
    </article>
    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <p class="label" style="margin: 0 0 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 11px; font-weight: 800;">Nouvelles 24h</p>
      <p class="num green" id="d-new" style="margin: 0; font-size: 42px; line-height: 1; font-weight: 900; color: #16a34a;">0</p>
      <p class="hint" style="margin: 8px 0 0; color: #94a3b8; font-size: 12px;">inscriptions récentes</p>
    </article>
    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <p class="label" style="margin: 0 0 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 11px; font-weight: 800;">Taux moyen</p>
      <p class="num amber" id="d-taux" style="margin: 0; font-size: 42px; line-height: 1; font-weight: 900; color: #f59e0b;">0%</p>
      <p class="hint" style="margin: 8px 0 0; color: #94a3b8; font-size: 12px;">remplissage global</p>
    </article>
    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <p class="label" style="margin: 0 0 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .08em; font-size: 11px; font-weight: 800;">Alertes 80%</p>
      <p class="num red" id="d-alert" style="margin: 0; font-size: 42px; line-height: 1; font-weight: 900; color: #dc2626;">0</p>
      <p class="hint" style="margin: 8px 0 0; color: #94a3b8; font-size: 12px;">événements critiques</p>
    </article>
  </section>

  <section class="grid two-col" style="display: grid; gap: 16px; grid-template-columns: 1.05fr .95fr; align-items: start;">
    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <div class="section-title" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="margin: 0; font-size: 17px; color: #0f172a; font-weight: 700;">Top 3 des événements les plus remplis</h2>
      </div>
      <div id="top-list" class="top-list" style="display: grid; gap: 12px;"></div>
    </article>

    <article class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05);">
      <div class="section-title" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
        <h2 style="margin: 0; font-size: 17px; color: #0f172a; font-weight: 700;">Nouvelles inscriptions par jour</h2>
      </div>
      <div id="chart" class="chart" style="display: grid; grid-template-columns: repeat(7, 1fr); align-items: end; gap: 10px; height: 190px; padding-top: 16px;"></div>
    </article>
  </section>

  <section class="card" style="background: #fff; border: 1px solid var(--border); border-radius: 16px; padding: 20px; box-shadow: 0 10px 26px rgba(15, 23, 42, .05); margin-top: 16px;">
    <div class="section-title" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px;">
      <h2 style="margin: 0; font-size: 17px; color: #0f172a; font-weight: 700;">Suivi par événement &amp; Rapports</h2>
      <span class="badge" id="event-count" style="background: #ffe4e6; color: #9f1239; border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; white-space: nowrap;">0 événement</span>
    </div>
    <div class="table-row label" style="margin:0; display: grid; grid-template-columns: 2fr 82px 82px 92px 130px; gap: 10px; align-items: center; padding: 11px 0; border-bottom: 1px solid #eef2f7; font-size: 13px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .08em;">
      <span>Événement</span>
      <span class="hide-sm">Inscrits</span>
      <span class="hide-sm">Capacité</span>
      <span>Remplissage</span>
      <span>Actions</span>
    </div>
    <div id="events-list" class="events-list" style="display: grid; gap: 12px;"></div>
  </section>
</main>

<script>
let dashTimer = null;
let retryTimer = null;
let lastStats = null;

document.addEventListener('DOMContentLoaded', startDashboard);

function startDashboard() {
  clearTimeout(retryTimer);
  clearInterval(dashTimer);
  fetchDashboardStats();
  dashTimer = setInterval(fetchDashboardStats, 30000);
}

async function fetchDashboardStats() {
  try {
    const response = await fetch('/MVPexam/api/stats', { headers: { 'Accept': 'application/json' } });
    if (!response.ok) throw new Error('HTTP ' + response.status);

    const data = await response.json();
    if (!data.success) throw new Error(data.error || 'Erreur stats');

    hideError();
    const hadPreviousStats = lastStats !== null;
    const previousFullIds = new Set((lastStats?.per_event || [])
      .filter(event => event.is_full)
      .map(event => Number(event.id)));
    lastStats = data;

    animateCounter('d-total', Number(data.summary.total_registered || 0));
    animateCounter('d-new', Number(data.summary.new_last_24h || 0));
    document.getElementById('d-taux').textContent = Number(data.summary.avg_fill_pct || 0) + '%';
    document.getElementById('d-alert').textContent = Number(data.summary.alert_count || 0);
    document.getElementById('event-count').textContent =
      Number(data.summary.total_events || 0) + ' événement(s)';

    renderTop3(data.top3 || []);
    renderEvents(data.per_event || [], previousFullIds, hadPreviousStats);
    renderChart(data.registrations_by_day || []);

    document.getElementById('last-update').textContent =
      'Mis à jour à ' + new Date().toLocaleTimeString('fr-FR');
  } catch (error) {
    console.error('[dashboard]', error);
    clearInterval(dashTimer);
    showError('Erreur API (Vérifiez votre connexion ou les droits de session). Réessai automatique dans 10 secondes.');
    toast('Dashboard indisponible, nouvel essai dans 10s.', 'error');
    retryTimer = setTimeout(startDashboard, 10000);
  }
}

function renderTop3(events) {
  const list = document.getElementById('top-list');
  if (!events.length) {
    list.innerHTML = '<p class="hint">Aucune donnée disponible.</p>';
    return;
  }

  list.innerHTML = events.map((event, index) => {
    const pct = Number(event.fill_pct || 0);
    const color = pct >= 100 ? '#dc2626' : pct >= 80 ? '#f59e0b' : '#9f1239';
    return `
      <div class="row" style="display: grid; grid-template-columns: 34px 1fr auto; gap: 12px; align-items: center; background: #f8fafc; border-radius: 12px; padding: 12px;">
        <span class="rank" style="color: #cbd5e1; font-size: 24px; font-weight: 900;">0${index + 1}</span>
        <div>
          <p class="title" style="margin: 0 0 7px; font-weight: 800; color: #0f172a; font-size: 14px;">${escapeHtml(event.title)}</p>
          <div class="bar" style="height: 6px; border-radius: 999px; background: #e2e8f0; overflow: hidden;"><span style="display: block; height: 100%; border-radius: inherit; transition: width .45s ease; width:${pct}%; background:${color}"></span></div>
        </div>
        <span class="badge" style="border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; background: #ffe4e6; color: #9f1239; white-space: nowrap;">${pct}%</span>
      </div>`;
  }).join('');
}

function renderEvents(events, previousFullIds, shouldNotify) {
  const list = document.getElementById('events-list');
  if (!events.length) {
    list.innerHTML = '<p class="hint">Aucun événement trouvé.</p>';
    return;
  }

  list.innerHTML = events.map(event => {
    const pct = Number(event.fill_pct || 0);
    const color = pct >= 100 ? '#dc2626' : pct >= 80 ? '#f59e0b' : '#9f1239';
    if (shouldNotify && event.is_full && !previousFullIds.has(Number(event.id))) {
      toast(event.title + ' est maintenant complet.', 'success');
    }
    return `
      <div class="table-row" style="display: grid; grid-template-columns: 2fr 82px 82px 92px 130px; gap: 10px; align-items: center; padding: 11px 0; border-bottom: 1px solid #eef2f7; font-size: 13px;">
        <strong>${escapeHtml(event.title)}</strong>
        <span class="hide-sm">${Number(event.registered || 0)}</span>
        <span class="hide-sm">${Number(event.capacity || 0)}</span>
        <span class="badge" style="border-radius: 999px; padding: 5px 10px; font-size: 11px; font-weight: 800; background:${pct >= 80 ? '#fef3c7' : '#ffe4e6'}; color:${pct >= 80 ? '#b45309' : '#9f1239'}; text-align: center; display: inline-block;">${pct}%</span>
        <div>
          <a href="/MVPexam/pdf/report?event_id=${event.id}" 
             class="inline-block px-2.5 py-1 text-xs font-semibold text-white bg-slate-800 rounded-md hover:bg-slate-700 transition" 
             style="text-decoration:none;">
            Rapport PDF
          </a>
        </div>
      </div>`;
  }).join('');
}

function renderChart(days) {
  const chart = document.getElementById('chart');
  const max = Math.max(1, ...days.map(day => Number(day.count || 0)));

  chart.innerHTML = days.map(day => {
    const count = Number(day.count || 0);
    const height = Math.max(6, Math.round((count / max) * 150));
    const label = new Date(day.day + 'T00:00:00').toLocaleDateString('fr-FR', { weekday: 'short' });
    return `
      <div class="chart-item" title="${count} inscription(s)" style="display: grid; gap: 8px; justify-items: center; align-items: end; height: 100%;">
        <div class="chart-bar" style="width: 100%; min-height: 6px; border-radius: 8px 8px 3px 3px; background: linear-gradient(180deg, #9f1239, #7f1d1d); transition: height .5s ease; height:${height}px"></div>
        <span class="chart-label" style="color: var(--muted); font-size: 11px;">${label}</span>
      </div>`;
  }).join('');
}

function showError(message) {
  const box = document.getElementById('error-box');
  if (box) {
      box.style.display = 'block';
      box.textContent = message;
  }
  document.getElementById('last-update').textContent = 'Erreur - retry 10s';
}

function hideError() {
  const box = document.getElementById('error-box');
  if (box) {
      box.style.display = 'none';
      box.textContent = '';
  }
}

function animateCounter(id, target) {
  const el = document.getElementById(id);
  if (!el) return;
  const start = parseInt(el.textContent, 10) || 0;
  const diff = target - start;
  const steps = 18;
  let step = 0;
  const timer = setInterval(() => {
    step++;
    el.textContent = Math.round(start + diff * (step / steps));
    if (step >= steps) {
      el.textContent = target;
      clearInterval(timer);
    }
  }, 20);
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
