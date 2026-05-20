<!-- MODAL LOGIN -->
<div id="modal-login" class="modal-overlay hidden">
  <div class="modal-box" style="max-width:380px">
    <div class="p-6 border-b border-slate-100">
      <h3 class="font-display font-bold text-slate-900 text-lg">Connexion Organisateur</h3>
    </div>
    <div class="p-6 space-y-4">
      <div>
        <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Email</label>
        <input type="email" class="form-input" placeholder="admin@ensa.ma" />
      </div>
      <div>
        <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Mot de passe</label>
        <input type="password" class="form-input" placeholder="••••••••" />
      </div>
      <div class="p-3 rounded-lg bg-rose-50 border border-rose-200">
        <p class="text-xs text-rose-800 font-display">ℹ️ Auth via sessions PHP — à implémenter dans <b>auth/login.php</b></p>
      </div>
      <button onclick="fakeLogin()"
        class="w-full py-3 rounded-xl font-display font-bold text-sm text-white"
        style="background:#3b0714">Se connecter</button>
      <button onclick="document.getElementById('modal-login').classList.add('hidden')"
        class="w-full py-2 text-sm text-slate-400 hover:text-slate-600">Annuler</button>
    </div>
  </div>
</div>

<div id="toast-container"></div>

<!-- FOOTER -->
<footer class="mt-16 border-t border-slate-200 py-8 px-6 bg-white">
  <div class="max-w-6xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="flex items-center gap-2">
      <span class="font-display font-bold text-sm text-slate-900">EventHub Pro</span>
      <span class="text-slate-300">·</span>
      <span class="text-xs text-slate-400">MVP Examen PHP · 4ème Année · ENSA Marrakech · Univ. Cadi Ayyad</span>
    </div>
    <div class="flex gap-3 text-xs text-slate-400 font-display">
      <span>PDO</span><span>·</span>
      <span>PHPMailer</span><span>·</span>
      <span>TCPDF / Dompdf</span><span>·</span>
      <span>Fetch API</span>
    </div>
  </div>
</footer>

<script>
function toast(msg, type='info') {
  const c = document.getElementById('toast-container');
  if (!c) return;
  const t = document.createElement('div');
  t.className = `toast ${type}`; t.textContent = msg;
  c.appendChild(t);
  setTimeout(()=>{ t.style.cssText='opacity:0;transform:translateX(120%);transition:all .3s';
    setTimeout(()=>t.remove(),300); }, 3500);
}

function setLoad(btn,lbl,spn,on,txt) {
  const btnEl = document.getElementById(btn);
  const spnEl = document.getElementById(spn);
  const lblEl = document.getElementById(lbl);
  if (btnEl) btnEl.disabled = on;
  if (spnEl) spnEl.classList.toggle('hidden',!on);
  if (txt && lblEl) lblEl.textContent = txt;
}

function anim(id, target) {
  const el = document.getElementById(id); if(!el) return;
  const start = parseInt(el.textContent)||0, diff=target-start, steps=20; let i=0;
  const iv=setInterval(()=>{ i++; el.textContent=Math.round(start+diff*(i/steps));
    if(i>=steps){el.textContent=target;clearInterval(iv);} },20);
}

function openLogin() { document.getElementById('modal-login').classList.remove('hidden'); }
function fakeLogin() {
  document.getElementById('modal-login').classList.add('hidden');
  toast('Connecté en tant qu\'organisateur (Simulé)', 'success');
}

// fermer modals au clic overlay
document.getElementById('modal-login').addEventListener('click', e=>{ if(e.target===e.currentTarget)e.currentTarget.classList.add('hidden'); });

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, c => ({
    '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;'
  }[c]));
}
</script>
</body>
</html>
