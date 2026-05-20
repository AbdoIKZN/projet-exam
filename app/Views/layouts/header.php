<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EventHub Pro — MVP · ENSA Marrakech</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <style>
    :root {
      --navy:  #3b0714;
      --blue:  #9f1239;
      --amber: #f59e0b;
      --teal:  #0d9488;
      --slate: #64748b;
    }
    * { box-sizing: border-box; }
    body { font-family: 'DM Sans', sans-serif; background: #fff7f8; color: #1e293b; }
    .font-display, h1,h2,h3,h4 { font-family: 'Syne', sans-serif; }

    /* gradient hero */
    .hero-bg {
      background: linear-gradient(135deg, #3b0714 0%, #7f1d1d 48%, #9f1239 100%);
      background-size: 200% 200%;
      animation: grad 10s ease infinite;
    }
    @keyframes grad { 0%,100%{background-position:0% 50%} 50%{background-position:100% 50%} }

    .dot-bg {
      background-image: radial-gradient(circle, rgba(255,255,255,.07) 1px, transparent 1px);
      background-size: 26px 26px;
    }

    /* cards */
    .event-card { transition: transform .22s ease, box-shadow .22s ease; }
    .event-card:hover { transform: translateY(-5px); box-shadow: 0 18px 40px rgba(127,29,29,.16); }

    /* badge */
    .badge {
      font-family: 'Syne', sans-serif;
      font-size: .64rem; letter-spacing: .07em;
      text-transform: uppercase; font-weight: 700;
      padding: 3px 10px; border-radius: 99px;
    }

    /* capacity bar */
    .cap-bar { height: 5px; border-radius: 99px; background: #e2e8f0; overflow: hidden; }
    .cap-bar-fill { height: 100%; border-radius: 99px; transition: width .6s ease; }

    /* skeleton */
    @keyframes shimmer {
      0%   { background-position: -400px 0; }
      100% { background-position:  400px 0; }
    }
    .skeleton {
      background: linear-gradient(90deg, #e2e8f0 25%, #f8fafc 50%, #e2e8f0 75%);
      background-size: 800px 100%;
      animation: shimmer 1.4s infinite;
      border-radius: 6px;
    }

    /* toast */
    #toast-container { position:fixed; bottom:24px; right:24px; z-index:999; display:flex; flex-direction:column; gap:10px; }
    .toast {
      min-width:280px; padding:14px 18px; border-radius:10px;
      color:#fff; font-size:.9rem; font-family:'DM Sans',sans-serif;
      box-shadow: 0 8px 24px rgba(0,0,0,.2);
      animation: slideIn .3s ease;
    }
    @keyframes slideIn { from{transform:translateX(120%);opacity:0} to{transform:translateX(0);opacity:1} }
    .toast.success { background:#16a34a; }
    .toast.error   { background:#dc2626; }
    .toast.info    { background:#9f1239; }

    /* modal */
    .modal-overlay {
      position:fixed; inset:0; z-index:50;
      background:rgba(59,7,20,.58); backdrop-filter:blur(5px);
      display:flex; align-items:center; justify-content:center;
      animation:fadeIn .2s ease;
    }
    @keyframes fadeIn { from{opacity:0} to{opacity:1} }
    .modal-box {
      background:#fff; border-radius:16px;
      width:min(560px,94vw); max-height:90vh; overflow-y:auto;
      box-shadow:0 32px 64px rgba(59,7,20,.25);
      animation:popIn .25s ease;
    }
    @keyframes popIn { from{transform:scale(.92);opacity:0} to{transform:scale(1);opacity:1} }

    /* form */
    .form-input {
      width:100%; padding:10px 14px;
      border:1.5px solid #cbd5e1; border-radius:8px;
      font-family:'DM Sans',sans-serif; font-size:.95rem;
      background:#fff; color:#1e293b;
      transition:border-color .2s, box-shadow .2s; outline:none;
    }
    .form-input:focus { border-color:#9f1239; box-shadow:0 0 0 3px rgba(159,18,57,.12); }

    /* spinner */
    .spinner {
      width:17px; height:17px;
      border:2.5px solid rgba(255,255,255,.35); border-top-color:#fff;
      border-radius:50%; animation:spin .7s linear infinite; display:inline-block;
    }
    @keyframes spin { to{transform:rotate(360deg)} }

    /* tabs */
    .tab-btn {
      font-family:'Syne',sans-serif; font-size:.75rem; font-weight:700;
      letter-spacing:.06em; text-transform:uppercase;
      padding:8px 18px; border-radius:99px; cursor:pointer; transition:all .2s;
    }
    .tab-btn.active { background:#9f1239; color:#fff; }
    .tab-btn:not(.active) { background:transparent; color:#64748b; }
    .tab-btn:not(.active):hover { background:#e2e8f0; }

    /* nav */
    .nav-link {
      font-family:'Syne',sans-serif; font-size:.78rem; font-weight:600;
      letter-spacing:.07em; text-transform:uppercase;
      color:rgba(255,255,255,.65); transition:color .2s;
      padding-bottom:2px; border-bottom:2px solid transparent;
    }
    .nav-link:hover { color:#fff; }
    .nav-link.active { color:#fecdd3; border-bottom-color:#fecdd3; }

    /* stat */
    .stat-num { font-family:'Syne',sans-serif; font-size:2.4rem; font-weight:800; line-height:1; }

    /* todo */
    .todo-block {
      background:#fff7ed; border-left:4px solid #f59e0b;
      padding:10px 14px; border-radius:0 8px 8px 0;
      font-family:'Courier New',monospace; font-size:.8rem; color:#92400e;
      margin:6px 0; white-space:pre-wrap; line-height:1.5;
    }

    /* kpi card */
    .kpi { background:#fff; border-radius:16px; padding:20px; border:1px solid #e2e8f0; }
  </style>
</head>
<body>

<?php
// Page active fournie par le controleur.
$activePage = $activePage ?? 'events';
?>

<!-- NAV -->
<nav class="hero-bg dot-bg sticky top-0 z-40 shadow-lg">
  <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3 cursor-pointer" onclick="window.location.href='/MVPexam/'">
      <div class="w-8 h-8 rounded-lg flex items-center justify-center" style="background:#be123c">
        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
      </div>
      <span class="font-display text-white text-lg font-bold">EventHub <span style="color:#fecdd3">Pro</span></span>
    </div>
    <div class="hidden md:flex items-center gap-7">
      <a href="/MVPexam/" class="nav-link <?php echo $activePage === 'events' ? 'active' : ''; ?>">Événements</a>
      <a href="/MVPexam/dashboard" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
      <a href="/MVPexam/events/create" class="nav-link <?php echo $activePage === 'create' ? 'active' : ''; ?>">Créer</a>
    </div>
    <div class="flex items-center gap-3">
      <div class="w-2 h-2 rounded-full bg-green-400 animate-pulse"></div>
      <span class="text-white/50 text-xs font-display">Live</span>
      <button onclick="openLogin()"
        class="ml-3 px-4 py-2 rounded-lg text-xs font-display font-bold tracking-widest uppercase"
        style="background:#fff1f2; color:#881337">Connexion</button>
    </div>
  </div>
</nav>
