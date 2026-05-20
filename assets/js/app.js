/**
 * ╔══════════════════════════════════════════════════════════════╗
 * ║  EventHub Pro — assets/js/app.js                            ║
 * ║  JavaScript principal — Fetch API & interactions            ║
 * ║  ENSA Marrakech — Examen PHP Avancé                         ║
 * ╚══════════════════════════════════════════════════════════════╝
 *
 * STATUT : ✅ Partie 4 complétée
 *
 * CE QUI EST FOURNI :
 *   ✅  renderEventCards()     — rendu HTML des cartes
 *   ✅  showToast()            — notifications toast
 *   ✅  showSkeletons()        — squelettes de chargement
 *   ✅  setButtonLoading()     — état de chargement sur les boutons
 *   ✅  animateCounter()       — animation de compteurs
 *   ✅  formatDate()           — formatage de date en français
 *
 * COMPLÉTÉ (Partie 4.1) :
 *   ✅  loadEvents()          — chargement via fetch + filtres
 *   ✅  registerToEvent()     — inscription via POST fetch
 *   ✅  debounceSearch()      — recherche live avec délai 400ms
 *
 * COMPLÉTÉ (Partie 4.2) :
 *   ✅  startDashboard()      — polling toutes les 30s
 *   ✅  fetchDashboardStats() — appel api/stats.php
 *
 * CONTRAINTES :
 *   → JavaScript vanilla uniquement (pas de jQuery, pas d'Axios)
 *   → Tous les fetch() doivent gérer les erreurs réseau (try/catch)
 *   → L'interface ne doit jamais "casser" en cas d'erreur API
 */

'use strict';

// ══════════════════════════════════════════════════════════════════════════
// ÉTAT GLOBAL
// ══════════════════════════════════════════════════════════════════════════
const STATE = {
    currentTab:    'all',       // Onglet actif : 'all' | 'upcoming' | 'full'
    dashInterval:  null,        // Référence setInterval du dashboard
    debounceTimer: null,        // Référence setTimeout du debounce
    selectedEvent: null,        // Événement sélectionné pour inscription
    events:        [],          // Derniers événements reçus de l'API
    dashboard:     null,        // Dernières stats dashboard reçues
};

const CATEGORY_COLORS = {
    tech:     { bg: '#FFE4E6', text: '#9F1239', primary: '#9F1239' },
    design:   { bg: '#F3E8FF', text: '#7E22CE', primary: '#7E22CE' },
    business: { bg: '#FFEDD5', text: '#C2410C', primary: '#C2410C' },
    science:  { bg: '#DCFCE7', text: '#15803D', primary: '#0F766E' },
};


// ══════════════════════════════════════════════════════════════════════════
// PARTIE 4.1 — CHARGEMENT DES ÉVÉNEMENTS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Charge les événements depuis api/events.php et les affiche.
 *
 * PARAMÈTRES À ENVOYER EN POST (JSON) :
 *   keyword, category, has_places, tab (STATE.currentTab), page
 *
 * EN CAS DE SUCCÈS :
 *   → Appeler renderEventCards(data.data)
 *   → Mettre à jour la pagination si data.meta.pages > 1
 *
 * EN CAS D'ERREUR RÉSEAU :
 *   → showToast('Impossible de charger les événements.', 'error')
 *   → Afficher un message d'erreur dans la grille (pas de page blanche)
 *
 * LOADING STATE :
 *   → Appeler showSkeletons() avant le fetch
 *   → Les remplacer par les vraies cartes après réception
 *
 * @returns {Promise<void>}
 */
async function loadEvents() {
    const keywordEl  = document.getElementById('search-input');
    const categoryEl = document.getElementById('filter-category') || document.getElementById('filter-cat');
    const placesEl   = document.getElementById('filter-places');

    const keyword   = keywordEl ? keywordEl.value.trim() : '';
    const category  = categoryEl ? categoryEl.value : '';
    const hasPlaces = placesEl ? placesEl.value === '1' : false;

    showSkeletons();

    try {
        const response = await fetch('api/events.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({
                keyword,
                category,
                has_places: hasPlaces,
                tab: STATE.currentTab,
                page: 1
            })
        });

        if (!response.ok) throw new Error('HTTP ' + response.status);

        const data = await response.json();

        if (data.success) {
            STATE.events = data.data || [];
            renderEventCards(STATE.events);
            if (typeof updateHero === 'function') updateHero();
        } else {
            showGridError(data.error ?? 'Erreur inconnue.');
        }

    } catch (err) {
        console.error('[loadEvents]', err);
        showToast('Impossible de charger les événements.', 'error');
        showGridError('Erreur de connexion au serveur.');
    }
}


// ══════════════════════════════════════════════════════════════════════════
// PARTIE 4.1 — INSCRIPTION EN TEMPS RÉEL
// ══════════════════════════════════════════════════════════════════════════

/**
 * Soumet l'inscription d'un participant à un événement.
 *
 * DONNÉES À ENVOYER (POST JSON) :
 *   { event_id, name, email }
 *
 * EN CAS DE SUCCÈS (data.success === true) :
 *   → Fermer la modale d'inscription
 *   → showToast('Inscription réussie ! Ticket envoyé par email.', 'success')
 *   → Mettre à jour la barre de capacité de la carte SANS rechargement :
 *       document.getElementById('bar-' + eventId).style.width = data.capacity_pct + '%'
 *       document.getElementById('places-' + eventId).textContent = ...
 *   → Si data.is_full === true : désactiver le bouton d'inscription
 *   → Si data.alert_sent === true : showToast('Alerte 80% envoyée à l\'organisateur', 'info')
 *
 * EN CAS D'ERREUR :
 *   → showToast(data.error, 'error')
 *   → Ne pas fermer la modale
 *
 * @param {number} eventId
 * @param {string} name
 * @param {string} email
 * @returns {Promise<void>}
 */
async function registerToEvent(eventId, name, email) {
    setButtonLoading('btn-register', true, 'Inscription en cours…');

    try {
        const response = await fetch('events/register.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ event_id: eventId, name, email })
        });

        const data = await response.json();

        if (data.success) {
            if (typeof closeRegisterModal === 'function') closeRegisterModal();
            if (typeof closeReg === 'function') closeReg();

            applyRealtimeRegistrationUpdate(eventId, data);
            showToast(
                data.email_sent
                    ? 'Inscription réussie ! Votre ticket PDF vous sera envoyé par email.'
                    : 'Inscription réussie. Email non envoyé : vérifiez la configuration SMTP.',
                data.email_sent ? 'success' : 'info'
            );

            if (data.alert_sent) {
                showToast("Alerte 80% envoyée à l'organisateur", 'info');
            }
        } else {
            showToast(data.error ?? 'Erreur lors de l\'inscription.', 'error');
        }

    } catch (err) {
        console.error('[registerToEvent]', err);
        showToast('Erreur réseau. Veuillez réessayer.', 'error');
    } finally {
        setButtonLoading('btn-register', false, "S'inscrire");
    }
}


// ══════════════════════════════════════════════════════════════════════════
// PARTIE 4.1 — RECHERCHE AVEC DEBOUNCE
// ══════════════════════════════════════════════════════════════════════════

/**
 * Déclenche loadEvents() après un délai de 400ms sans frappe.
 * Annule le timer précédent si l'utilisateur tape encore.
 *
 * APPELÉ PAR : oninput sur #search-input
 *
 * EXEMPLE D'IMPLÉMENTATION ATTENDUE :
 *   clearTimeout(STATE.debounceTimer);
 *   STATE.debounceTimer = setTimeout(loadEvents, 400);
 */
function debounceSearch() {
    clearTimeout(STATE.debounceTimer);
    STATE.debounceTimer = setTimeout(loadEvents, 400);
}


// ══════════════════════════════════════════════════════════════════════════
// PARTIE 4.2 — DASHBOARD TEMPS RÉEL
// ══════════════════════════════════════════════════════════════════════════

/**
 * Démarre le polling automatique du dashboard (toutes les 30s).
 * Appelle fetchDashboardStats() immédiatement puis toutes les 30 secondes.
 * Arrête le polling précédent si la fonction est rappelée.
 */
function startDashboard() {
    if (STATE.dashInterval) clearInterval(STATE.dashInterval);
    fetchDashboardStats();
    STATE.dashInterval = setInterval(fetchDashboardStats, 30000);
}

/**
 * Récupère les statistiques depuis api/stats.php et met à jour le dashboard.
 *
 * EN CAS DE SUCCÈS :
 *   → Mettre à jour les KPI (animateCounter pour les nombres)
 *   → Mettre à jour le Top 3
 *   → Mettre à jour l'heure de dernière mise à jour
 *   → Si un événement vient de passer à 100% → showToast(..., 'info')
 *
 * EN CAS D'ERREUR :
 *   → Afficher un message discret (ne pas casser l'interface)
 *   → Réessayer automatiquement dans 10 secondes (clearInterval + setTimeout)
 *
 * @returns {Promise<void>}
 */
async function fetchDashboardStats() {
    try {
        const response = await fetch('api/stats.php');
        if (!response.ok) throw new Error('HTTP ' + response.status);

        const data = await response.json();
        if (!data.success) throw new Error(data.error || 'Erreur stats');

        const previousFullIds = new Set((STATE.dashboard?.per_event || [])
            .filter(e => e.is_full)
            .map(e => e.id));
        STATE.dashboard = data;

        animateCounter('kpi-total', data.summary.total_registered);
        animateCounter('d-total', data.summary.total_registered);
        animateCounter('kpi-new-24h', data.summary.new_last_24h);
        animateCounter('d-new', data.summary.new_last_24h);
        animateCounter('kpi-alertes', data.summary.alert_count);

        const tauxEl = document.getElementById('kpi-taux') || document.getElementById('d-taux');
        if (tauxEl) tauxEl.textContent = data.summary.avg_fill_pct + '%';
        const alertEl = document.getElementById('d-alert');
        if (alertEl) alertEl.textContent = data.summary.alert_count;

        renderTop3(data.top3 || [], previousFullIds);

        const updateEl = document.getElementById('last-update');
        if (updateEl) updateEl.textContent = 'Mis à jour à ' + new Date().toLocaleTimeString('fr-FR');

    } catch (err) {
        console.error('[fetchDashboardStats]', err);
        clearInterval(STATE.dashInterval);
        setTimeout(startDashboard, 10000);
        showToast('Erreur de chargement du dashboard. Nouvelle tentative dans 10s.', 'error');
    }
}


// ══════════════════════════════════════════════════════════════════════════
// FOURNI — RENDU DES CARTES D'ÉVÉNEMENTS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Génère et injecte les cartes d'événements dans #events-grid.
 *
 * @param {Array} events  Tableau d'objets événement (depuis api/events.php)
 */
function renderEventCards(events) {
    const grid = document.getElementById('events-grid');

    if (!events || events.length === 0) {
        grid.innerHTML = `
            <div class="col-span-3 text-center py-16">
                <div class="text-5xl mb-4">🔍</div>
                <p class="font-display font-bold text-slate-600 text-lg">Aucun événement trouvé</p>
                <p class="text-slate-400 text-sm mt-2">Modifiez vos critères de recherche</p>
            </div>`;
        return;
    }

    grid.innerHTML = events.map(e => {
        const pct      = parseInt(e.fill_percentage) || 0;
        const isFull   = e.available_places <= 0;
        const isWarn   = pct >= 80 && !isFull;
        const colors   = CATEGORY_COLORS[e.category] || { bg: '#F1F5F9', text: '#334155', primary: '#64748B' };
        const barColor = isFull ? '#DC2626' : isWarn ? '#F59E0B' : colors.primary;

        return `
        <div class="event-card bg-white rounded-2xl border border-slate-200 overflow-hidden flex flex-col shadow-sm"
             data-event-id="${e.id}">
            <div class="h-2" style="background:${colors.primary}"></div>
            <div class="p-5 flex flex-col flex-1">
                <div class="flex items-start gap-2 mb-3 flex-wrap">
                    <span class="badge" style="background:${colors.bg};color:${colors.text}">${e.category}</span>
                    ${isFull  ? '<span class="badge" style="background:#FEE2E2;color:#DC2626">Complet</span>' : ''}
                    ${isWarn  ? '<span class="badge" style="background:#FEF3C7;color:#B45309">🔥 Quasi plein</span>' : ''}
                </div>
                <h3 class="font-display font-bold text-base text-slate-900 mb-1 leading-snug">${e.title}</h3>
                <p class="text-xs text-slate-500 mb-1">📅 ${formatDate(e.event_date)}</p>
                <p class="text-xs text-slate-500 mb-3">📍 ${e.location}</p>
                <p class="text-xs text-slate-600 leading-relaxed flex-1">${e.description}</p>
                <div class="mt-4">
                    <div class="flex justify-between text-xs font-display font-bold mb-1">
                        <span class="text-slate-400">Capacité</span>
                        <span style="color:${barColor}" id="places-${e.id}">
                            ${e.registered_count} / ${e.capacity}
                        </span>
                    </div>
                    <div class="cap-bar">
                        <div class="cap-bar-fill" id="bar-${e.id}"
                             style="width:${pct}%; background:${barColor}"></div>
                    </div>
                    ${!isFull ? `<p class="text-xs text-slate-400 mt-1">${e.available_places} place(s) restante(s)</p>` : ''}
                </div>
                <button
                    id="btn-${e.id}"
                    ${isFull ? 'disabled' : `onclick="openRegisterModal(${e.id})"`}
                    class="mt-4 w-full py-2.5 rounded-xl font-display font-bold text-xs text-white tracking-wide
                           ${isFull ? 'opacity-40 cursor-not-allowed' : 'hover:opacity-90 transition'}"
                    style="background:${isFull ? '#94A3B8' : colors.primary}">
                    ${isFull ? 'Complet' : "S'inscrire →"}
                </button>
            </div>
        </div>`;
    }).join('');
}

function openRegisterModal(eventId) {
    if (typeof openReg === 'function') {
        openReg(eventId);
        return;
    }

    STATE.selectedEvent = STATE.events.find(e => parseInt(e.id, 10) === parseInt(eventId, 10));
}

function applyRealtimeRegistrationUpdate(eventId, data) {
    const event = STATE.events.find(e => parseInt(e.id, 10) === parseInt(eventId, 10));
    if (event) {
        event.registered_count = parseInt(data.registered_count, 10) || ((parseInt(event.registered_count, 10) || 0) + 1);
        event.available_places = parseInt(data.available_places, 10);
        event.fill_percentage  = parseInt(data.capacity_pct, 10) || 0;
    }

    const placesEl = document.getElementById('places-' + eventId) || document.getElementById('pl-' + eventId);
    const barEl    = document.getElementById('bar-' + eventId);
    const btnEl    = document.getElementById('btn-' + eventId);

    if (placesEl && event) {
        placesEl.textContent = event.registered_count + ' / ' + event.capacity;
    }

    if (barEl) {
        barEl.style.width = (parseInt(data.capacity_pct, 10) || 0) + '%';
    }

    if (data.is_full && btnEl) {
        btnEl.disabled = true;
        btnEl.textContent = 'Complet';
        btnEl.style.background = '#94A3B8';
        btnEl.classList.add('opacity-40', 'cursor-not-allowed');
    }
}

function renderTop3(top3, previousFullIds = new Set()) {
    const list = document.getElementById('top-list');
    if (!list) return;

    if (!top3.length) {
        list.innerHTML = '<p class="text-sm text-slate-400">Aucune donnée disponible.</p>';
        return;
    }

    list.innerHTML = top3.map((event, index) => {
        const pct = parseInt(event.fill_pct, 10) || 0;
        const bar = pct >= 80 ? '#F59E0B' : '#9F1239';
        if (event.is_full && !previousFullIds.has(event.id)) {
            showToast(`${event.title} vient de passer à 100%`, 'info');
        }

        return `<div class="flex items-center gap-4 p-3 rounded-xl bg-slate-50">
            <span class="font-display font-black text-2xl text-slate-200">0${index + 1}</span>
            <div class="flex-1">
                <p class="font-display font-bold text-sm text-slate-900 mb-1">${escapeHtml(event.title)}</p>
                <div class="cap-bar"><div class="cap-bar-fill" style="width:${pct}%;background:${bar}"></div></div>
            </div>
            <span class="badge font-display" style="background:${pct >= 100 ? '#FEE2E2' : pct >= 80 ? '#FEF3C7' : '#DBEAFE'};color:${pct >= 100 ? '#DC2626' : pct >= 80 ? '#B45309' : '#1D4ED8'}">${pct}%</span>
        </div>`;
    }).join('');
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    }[char]));
}


// ══════════════════════════════════════════════════════════════════════════
// FOURNI — UTILITAIRES
// ══════════════════════════════════════════════════════════════════════════

/** Affiche les squelettes de chargement dans la grille. */
function showSkeletons(count = 3) {
    const grid = document.getElementById('events-grid');
    grid.innerHTML = Array.from({ length: count }, () => `
        <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
            <div class="skeleton h-2 w-full mb-4 -mx-5 -mt-5" style="width:calc(100% + 40px); border-radius:0"></div>
            <div class="skeleton h-5 w-3/4 mb-2 mt-2"></div>
            <div class="skeleton h-3 w-1/2 mb-1"></div>
            <div class="skeleton h-3 w-2/3 mb-4"></div>
            <div class="skeleton h-2 w-full mb-4"></div>
            <div class="skeleton h-9 w-28 rounded-xl"></div>
        </div>`).join('');
}

/** Affiche un message d'erreur dans la grille. */
function showGridError(message) {
    document.getElementById('events-grid').innerHTML = `
        <div class="col-span-3 text-center py-16">
            <div class="text-5xl mb-4">⚠️</div>
            <p class="font-display font-bold text-red-600">${message}</p>
            <button onclick="loadEvents()"
                    class="mt-4 px-6 py-2 rounded-lg text-sm font-display font-bold text-white"
                    style="background:#9f1239">Réessayer</button>
        </div>`;
}

/**
 * Affiche un toast de notification.
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function showToast(message, type = 'info') {
    const container = document.getElementById('toast-container');
    const toast     = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.cssText = 'opacity:0; transform:translateX(120%); transition:all .3s ease;';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

/**
 * Met un bouton en état de chargement (spinner).
 * @param {string} buttonId
 * @param {boolean} loading
 * @param {string} loadingText
 */
function setButtonLoading(buttonId, loading, loadingText = 'Chargement…') {
    const btn = document.getElementById(buttonId);
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.originalText = btn.textContent;
        btn.innerHTML = `<span class="spinner"></span> ${loadingText}`;
    } else {
        btn.innerHTML = btn.dataset.originalText || loadingText;
    }
}

/**
 * Anime un compteur de 0 à target.
 * @param {string} elementId
 * @param {number} target
 */
function animateCounter(elementId, target) {
    const el = document.getElementById(elementId);
    if (!el) return;
    const start = parseInt(el.textContent) || 0;
    const diff  = target - start;
    const steps = 24;
    let   step  = 0;
    const timer = setInterval(() => {
        step++;
        el.textContent = Math.round(start + diff * (step / steps));
        if (step >= steps) { el.textContent = target; clearInterval(timer); }
    }, 20);
}

/**
 * Formate une date ISO en français lisible.
 * @param {string} dateStr  Format: '2025-09-20T09:00:00'
 * @returns {string}        Format: 'sam. 20 sept. 2025 à 09h00'
 */
function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        weekday: 'short', day: 'numeric', month: 'short',
        year: 'numeric', hour: '2-digit', minute: '2-digit'
    }).replace(':', 'h');
}


// ══════════════════════════════════════════════════════════════════════════
// INITIALISATION
// ══════════════════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
    loadEvents();
});
