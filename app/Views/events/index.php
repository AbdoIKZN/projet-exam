<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- HERO -->
<section class="hero-bg dot-bg py-14 px-6">
  <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12 items-center">
    <div>
      <span class="inline-block badge mb-4 text-xs" style="background:rgba(254,205,211,.18);color:#fecdd3">
        MVC Pro · Examen PHP Avancé · ENSA Marrakech
      </span>
      <h1 class="text-4xl md:text-5xl font-display font-extrabold text-white leading-tight mb-4">
        Gérez vos<br/><span style="color:#fecdd3">événements</span><br/>intelligemment
      </h1>
      <p class="text-slate-300 text-sm leading-relaxed mb-8">
        Plateforme de gestion d'événements — Inscriptions, tickets PDF,<br/>notifications email, statistiques temps réel.
      </p>
      <div class="flex flex-wrap gap-3">
        <a href="#sec-events"
          class="px-6 py-3 rounded-xl font-display font-bold text-sm text-white inline-block text-center"
          style="background:#9f1239">Voir les événements →</a>
        <a href="/MVPexam/events/create"
          class="px-6 py-3 rounded-xl font-display font-bold text-sm border border-white/20 text-white hover:bg-white/10 transition inline-block text-center">
          + Créer un événement</a>
      </div>
    </div>
    <!-- stats hero -->
    <div class="grid grid-cols-2 gap-4">
      <div class="rounded-2xl p-5" style="background:rgba(255,255,255,.08)">
        <div class="stat-num text-white" id="h-total">—</div>
        <div class="text-white/50 text-sm mt-1 font-display">Événements</div>
      </div>
      <div class="rounded-2xl p-5" style="background:rgba(255,255,255,.08)">
        <div class="stat-num text-white" id="h-inscrits">—</div>
        <div class="text-white/50 text-sm mt-1 font-display">Inscrits</div>
      </div>
      <div class="rounded-2xl p-5" style="background:rgba(255,255,255,.08)">
        <div class="stat-num" style="color:#fecdd3" id="h-complets">—</div>
        <div class="text-white/50 text-sm mt-1 font-display">Complets</div>
      </div>
      <div class="rounded-2xl p-5" style="background:rgba(255,255,255,.08)">
        <div class="stat-num text-teal-400" id="h-new24">—</div>
        <div class="text-white/50 text-sm mt-1 font-display">Nouvelles 24h</div>
      </div>
    </div>
  </div>
</section>

<!-- MAIN -->
<main class="max-w-6xl mx-auto px-6 py-10">

  <!-- ── EVENTS ── -->
  <section id="sec-events">
    <!-- Filtres -->
    <div class="flex flex-col md:flex-row gap-4 mb-8 items-start md:items-center">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
          fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input id="search-input" type="text" placeholder="Rechercher un événement…"
          class="form-input pl-10" oninput="debounceSearch()" />
      </div>
      <div class="flex gap-2 flex-wrap">
        <select id="filter-cat" class="form-input w-auto text-sm" onchange="loadEvents()">
          <option value="">Toutes catégories</option>
          <option value="tech">Tech</option>
          <option value="design">Design</option>
          <option value="business">Business</option>
          <option value="science">Science</option>
        </select>
        <select id="filter-places" class="form-input w-auto text-sm" onchange="loadEvents()">
          <option value="">Toutes places</option>
          <option value="1">Avec places dispo</option>
        </select>
      </div>
    </div>

    <!-- Tabs -->
    <div class="flex gap-2 mb-7 bg-white border border-slate-200 p-1 rounded-full w-fit shadow-sm">
      <button class="tab-btn active" onclick="filterTab('all',this)">Tous</button>
      <button class="tab-btn" onclick="filterTab('upcoming',this)">À venir</button>
      <button class="tab-btn" onclick="filterTab('full',this)">Complets</button>
    </div>

    <!-- Grid -->
    <div id="events-grid" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6"></div>

    <div class="mt-8 p-5 rounded-xl border-2 border-dashed border-green-300 bg-green-50">
      <p class="font-display font-bold text-green-900 text-sm mb-2">✅ Architecture MVC connectée</p>
      <div class="todo-block">// Action MVC : GET /
// API chargée via endpoint : POST /api/events
// Inscriptions traitées par : POST /events/register</div>
    </div>
  </section>
</main>

<!-- MODAL INSCRIPTION -->
<div id="modal-reg" class="modal-overlay hidden">
  <div class="modal-box">
    <div class="p-6 border-b border-slate-100 flex items-start justify-between">
      <div>
        <h3 class="font-display font-bold text-slate-900 text-lg" id="m-title">Inscription</h3>
        <p class="text-slate-500 text-sm mt-1" id="m-info">—</p>
      </div>
      <button onclick="closeReg()" class="text-slate-400 hover:text-slate-700 text-xl font-bold leading-none">✕</button>
    </div>
    <div class="p-6 space-y-4">
      <div>
        <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Nom complet *</label>
        <input id="r-name" type="text" class="form-input" placeholder="Votre nom complet" />
      </div>
      <div>
        <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Email *</label>
        <input id="r-email" type="email" class="form-input" placeholder="votre@email.ma" />
      </div>
      <div class="rounded-xl p-4 bg-slate-50 border border-slate-200">
        <div class="flex justify-between text-xs font-display font-bold mb-2">
          <span class="text-slate-500">Places restantes</span>
          <span class="text-slate-900" id="m-places">—</span>
        </div>
        <div class="cap-bar"><div class="cap-bar-fill" id="m-bar" style="width:0%;background:#9f1239"></div></div>
      </div>

      <button onclick="submitReg()" id="btn-reg"
        class="w-full py-3 rounded-xl font-display font-bold text-sm text-white flex items-center justify-center gap-2"
        style="background:#9f1239">
        <span id="lbl-reg">S'inscrire &amp; recevoir le ticket PDF</span>
        <span id="spn-reg" class="spinner hidden"></span>
      </button>
    </div>
  </div>
</div>

<script>
let currentTab = 'all';
let selected   = null;
let debTimer   = null;
let EVENTS     = [];

// ── LOAD EVENTS — vrai fetch AJAX ────────────────
async function loadEvents() {
  const kw   = document.getElementById('search-input').value.trim();
  const cat  = document.getElementById('filter-cat').value;
  const pl   = document.getElementById('filter-places').value === '1';
  showSkeletons();

  try {
    const res = await fetch('/MVPexam/api/events', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        keyword: kw,
        category: cat,
        has_places: pl,
        tab: currentTab,
        page: 1
      })
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Erreur API');

    EVENTS = json.data || [];
    renderCards(EVENTS);
    updateHero();
  } catch (err) {
    console.error('[loadEvents]', err);
    showGridError('Impossible de charger les événements.');
    toast('Erreur réseau pendant le chargement des événements.', 'error');
  }
}

function filterTab(tab, el) {
  currentTab = tab;
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  el.classList.add('active');
  loadEvents();
}

// ── RENDER CARDS ───────────────────────────────────────────────
const CAT_STYLE = {
  tech:     { bg:'#ffe4e6', tx:'#9f1239' },
  design:   { bg:'#f3e8ff', tx:'#7e22ce' },
  business: { bg:'#ffedd5', tx:'#c2410c' },
  science:  { bg:'#dcfce7', tx:'#15803d' },
};

function renderCards(list) {
  const grid = document.getElementById('events-grid');
  if (!list.length) {
    grid.innerHTML = `<div class="col-span-3 text-center py-16">
      <div class="text-5xl mb-4">🔍</div>
      <p class="font-display font-bold text-slate-600">Aucun événement trouvé</p>
      <p class="text-slate-400 text-sm mt-2">Modifiez vos filtres</p></div>`;
    return;
  }
  grid.innerHTML = list.map(e => {
    const pct  = parseInt(e.fill_percentage, 10) || 0;
    const full = parseInt(e.available_places, 10) <= 0;
    const warn = pct >= 80 && !full;
    const cat  = e.category || 'tech';
    const bar  = full ? '#dc2626' : warn ? '#f59e0b' : categoryColor(cat);
    const cs   = CAT_STYLE[cat] || { bg:'#f1f5f9', tx:'#334155' };
    const d    = new Date(e.event_date).toLocaleDateString('fr-FR',
      { weekday:'short', day:'numeric', month:'short', hour:'2-digit', minute:'2-digit' });
    const remaining = parseInt(e.available_places, 10) || 0;
    const registered = parseInt(e.registered_count, 10) || 0;
    const capacity = parseInt(e.capacity, 10) || 0;
    return `
    <div class="event-card bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm" data-id="${e.id}">
      <div class="h-2" style="background:${categoryColor(cat)}"></div>
      <div class="p-5 flex flex-col flex-1">
        <div class="flex items-start gap-2 mb-3 flex-wrap">
          <span class="badge" style="background:${cs.bg};color:${cs.tx}">${cat}</span>
          ${full ? '<span class="badge" style="background:#fee2e2;color:#dc2626">Complet</span>'
            : warn ? '<span class="badge" style="background:#fef3c7;color:#b45309">🔥 Quasi plein</span>' : ''}
        </div>
        <h3 class="font-display font-bold text-base text-slate-900 mb-1 leading-snug">${escapeHtml(e.title)}</h3>
        <p class="text-xs text-slate-500 mb-1 flex items-center gap-1">
          <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${d}
        </p>
        <p class="text-xs text-slate-500 mb-3 flex items-center gap-1">
          <svg class="w-3 h-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/></svg>${escapeHtml(e.location)}
        </p>
        <p class="text-xs text-slate-600 leading-relaxed flex-1">${escapeHtml(e.description || '')}</p>
        <div class="mt-4">
          <div class="flex justify-between text-xs font-display font-bold mb-1">
            <span class="text-slate-500">Capacité</span>
            <span style="color:${bar}" id="pl-${e.id}">${registered} / ${capacity}</span>
          </div>
          <div class="cap-bar">
            <div class="cap-bar-fill" id="bar-${e.id}" style="width:${pct}%;background:${bar}"></div>
          </div>
          ${!full ? `<p class="text-xs text-slate-400 mt-1">${remaining} place${remaining>1?'s':''} restante${remaining>1?'s':''}</p>` : ''}
        </div>
        <button
          ${full ? 'disabled' : `onclick="openReg(${e.id})"`}
          id="btn-${e.id}"
          class="mt-4 w-full py-2.5 rounded-xl font-display font-bold text-xs text-white tracking-wide transition
            ${full ? 'opacity-40 cursor-not-allowed' : 'hover:opacity-90'}"
          style="background:${full ? '#94a3b8' : categoryColor(cat)}">
          ${full ? 'Complet' : "S'inscrire →"}
        </button>
      </div>
    </div>`;
  }).join('');
}

// ── MODAL INSCRIPTION ──────────────────────────────────────────
function openReg(id) {
  selected = EVENTS.find(e => parseInt(e.id, 10) === parseInt(id, 10));
  if (!selected) return;
  const pct = parseInt(selected.fill_percentage, 10) || 0;
  const rem = parseInt(selected.available_places, 10) || 0;
  document.getElementById('m-title').textContent = selected.title;
  document.getElementById('m-info').textContent  =
    new Date(selected.event_date).toLocaleDateString('fr-FR',{day:'numeric',month:'long',year:'numeric'})
    + ' · ' + selected.location;
  document.getElementById('m-places').textContent = `${rem} place${rem>1?'s':''} restante${rem>1?'s':''}`;
  document.getElementById('m-bar').style.width      = pct + '%';
  document.getElementById('m-bar').style.background = pct>=80 ? '#f59e0b' : '#9f1239';
  document.getElementById('r-name').value = '';
  document.getElementById('r-email').value = '';
  document.getElementById('modal-reg').classList.remove('hidden');
}

function closeReg() {
  document.getElementById('modal-reg').classList.add('hidden');
  selected = null;
}

async function submitReg() {
  const name  = document.getElementById('r-name').value.trim();
  const email = document.getElementById('r-email').value.trim();
  if (!name || !email) { toast('Remplissez tous les champs', 'error'); return; }
  if (!selected) return;

  setLoad('btn-reg','lbl-reg','spn-reg', true, 'Inscription…');

  try {
    const res = await fetch('/MVPexam/events/register', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ event_id: selected.id, name, email })
    });
    const json = await res.json();
    if (!json.success) {
      toast(json.error || "Erreur lors de l'inscription.", 'error');
      return;
    }

    applyRegistrationUpdate(selected.id, json);
    closeReg();
    toast(json.email_sent
      ? 'Inscription réussie ! Votre ticket PDF a été envoyé par email.'
      : 'Inscription réussie. Email non envoyé : vérifiez la configuration SMTP.',
      json.email_sent ? 'success' : 'info');
    if (json.alert_sent) {
      toast("Alerte 80% envoyée à l'organisateur.", 'info');
    }
    updateHero();
  } catch (err) {
    console.error('[submitReg]', err);
    toast('Erreur réseau. Veuillez réessayer.', 'error');
  } finally {
    setLoad('btn-reg','lbl-reg','spn-reg', false, "S'inscrire & recevoir le ticket PDF");
  }
}

// ── HERO STATS ──────────────────────────────────────────────────
function updateHero() {
  anim('h-total',    EVENTS.length);
  anim('h-inscrits', EVENTS.reduce((s,e)=>s+(parseInt(e.registered_count,10)||0),0));
  anim('h-complets', EVENTS.filter(e=>(parseInt(e.available_places,10)||0)<=0).length);
  // Déterminer les nouvelles inscriptions des 24h
  fetch('/MVPexam/api/stats')
    .then(r => r.json())
    .then(j => {
      if(j.success && j.summary) {
         anim('h-new24', j.summary.new_last_24h || 0);
      }
    }).catch(e => console.log('Hero daily count failed', e));
}

// ── DEBOUNCE ────────────────────────────────────────────────────
function debounceSearch() {
  clearTimeout(debTimer);
  debTimer = setTimeout(loadEvents, 400);
}

// ── UTILS ───────────────────────────────────────────────────────
function showGridError(message) {
  document.getElementById('events-grid').innerHTML = `<div class="col-span-3 text-center py-16">
    <div class="text-5xl mb-4">⚠️</div>
    <p class="font-display font-bold text-red-600">${escapeHtml(message)}</p>
    <button onclick="loadEvents()" class="mt-4 px-6 py-2 rounded-lg text-sm font-display font-bold text-white"
      style="background:#9f1239">Réessayer</button>
  </div>`;
}

function categoryColor(cat) {
  return {
    tech:'#9f1239',
    design:'#7e22ce',
    business:'#c2410c',
    science:'#0f766e'
  }[cat] || '#3b0714';
}

function applyRegistrationUpdate(eventId, data) {
  const event = EVENTS.find(e => parseInt(e.id, 10) === parseInt(eventId, 10));
  if (!event) return;

  event.registered_count = parseInt(data.registered_count, 10) || ((parseInt(event.registered_count, 10) || 0) + 1);
  event.available_places = parseInt(data.available_places, 10);
  event.fill_percentage  = parseInt(data.capacity_pct, 10) || 0;

  const pct = parseInt(event.fill_percentage, 10) || 0;
  const isFull = !!data.is_full || (parseInt(event.available_places, 10) <= 0);
  const bar = isFull ? '#dc2626' : pct >= 80 ? '#f59e0b' : categoryColor(event.category);
  const plEl  = document.getElementById(`pl-${event.id}`);
  const barEl = document.getElementById(`bar-${event.id}`);
  const btnEl = document.getElementById(`btn-${event.id}`);

  if (plEl) {
    plEl.textContent = `${event.registered_count} / ${event.capacity}`;
    plEl.style.color = bar;
  }
  if (barEl) {
    barEl.style.width = pct + '%';
    barEl.style.background = bar;
  }
  if (isFull && btnEl) {
    btnEl.disabled = true;
    btnEl.textContent = 'Complet';
    btnEl.style.background = '#94a3b8';
    btnEl.classList.add('opacity-40','cursor-not-allowed');
    toast(`${event.title} est maintenant complet !`, 'info');
  }
}

function showSkeletons() {
  document.getElementById('events-grid').innerHTML = Array(3).fill(`
    <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
      <div class="skeleton h-2 w-full mb-4 -mx-5 -mt-5" style="width:calc(100%+40px);border-radius:0"></div>
      <div class="skeleton h-5 w-3/4 mb-2 mt-2"></div>
      <div class="skeleton h-3 w-1/2 mb-1"></div>
      <div class="skeleton h-3 w-2/3 mb-4"></div>
      <div class="skeleton h-2 w-full mb-4"></div>
      <div class="skeleton h-9 w-28 rounded-xl"></div>
    </div>`).join('');
}

// fermer modals au clic overlay
document.getElementById('modal-reg').addEventListener('click', e=>{ if(e.target===e.currentTarget)closeReg(); });

// INIT
document.addEventListener('DOMContentLoaded', ()=>{ loadEvents(); });
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
