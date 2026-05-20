<?php
require_once __DIR__ . '/../layouts/header.php';
?>

<!-- MAIN -->
<main class="max-w-6xl mx-auto px-6 py-10">
  <!-- ── CREATE ── -->
  <section id="sec-create">
    <div class="max-w-2xl mx-auto">
      <div class="mb-8">
        <h2 class="font-display text-2xl font-bold text-slate-900">Créer un événement</h2>
        <p class="text-slate-500 text-sm mt-1">Les champs marqués * sont obligatoires.</p>
      </div>

      <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-sm">
        <div class="space-y-5">
          <div>
            <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Titre *</label>
            <input type="text" id="f-title" class="form-input" placeholder="Ex : DevFest Marrakech 2025" />
          </div>
          <div>
            <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Description *</label>
            <textarea id="f-desc" rows="3" class="form-input resize-none" placeholder="Décrivez votre événement…"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Date *</label>
              <input type="datetime-local" id="f-date" class="form-input" />
            </div>
            <div>
              <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Lieu *</label>
              <input type="text" id="f-lieu" class="form-input" placeholder="Ex : ENSA Marrakech" />
            </div>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Capacité *</label>
              <input type="number" id="f-cap" min="1" class="form-input" placeholder="50" />
            </div>
            <div>
              <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Catégorie *</label>
              <select id="f-cat" class="form-input">
                <option value="">— Choisir —</option>
                <option value="tech">Tech</option>
                <option value="design">Design</option>
                <option value="business">Business</option>
                <option value="science">Science</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-xs font-display font-bold text-slate-600 uppercase tracking-widest mb-1">Email organisateur *</label>
            <input type="email" id="f-email" class="form-input" placeholder="organisateur@example.ma" />
          </div>

          <div class="p-4 rounded-xl border-2 border-dashed border-green-300 bg-green-50">
            <p class="font-display font-bold text-green-900 text-xs mb-1">✅ Création connectée</p>
            <div class="todo-block">// POST /events/create
// Redirection ou notification de succès immédiate</div>
          </div>

          <button onclick="submitCreate()"
            class="w-full py-3 rounded-xl font-display font-bold text-sm text-white flex items-center justify-center gap-2"
            style="background:#9f1239" id="btn-create">
            <span id="lbl-create">Créer l'événement</span>
            <span id="spn-create" class="spinner hidden"></span>
          </button>
        </div>
      </div>
    </div>
  </section>
</main>

<script>
async function submitCreate() {
  const payload = {
    title: document.getElementById('f-title').value.trim(),
    description: document.getElementById('f-desc').value.trim(),
    date: document.getElementById('f-date').value,
    location: document.getElementById('f-lieu').value.trim(),
    capacity: document.getElementById('f-cap').value,
    category: document.getElementById('f-cat').value,
    organizer_email: document.getElementById('f-email').value.trim()
  };
  
  if (!payload.title || !payload.description || !payload.date || !payload.location ||
      !payload.capacity || !payload.category || !payload.organizer_email) {
    toast('Remplissez tous les champs obligatoires', 'error');
    return;
  }

  setLoad('btn-create','lbl-create','spn-create', true, 'Création…');
  
  try {
    const res = await fetch('/MVPexam/events/create', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Création impossible');

    toast('Événement créé avec succès !', 'success');
    
    // Clear inputs
    ['f-title','f-desc','f-date','f-lieu','f-cap','f-cat','f-email'].forEach(id => document.getElementById(id).value='');
    
    // Redirect to home page
    setTimeout(() => {
        window.location.href = '/MVPexam/';
    }, 1500);

  } catch (err) {
    console.error('[submitCreate]', err);
    toast(err.message || 'Erreur pendant la création.', 'error');
  } finally {
    setLoad('btn-create','lbl-create','spn-create', false, "Créer l'événement");
  }
}
</script>

<?php
require_once __DIR__ . '/../layouts/footer.php';
?>
