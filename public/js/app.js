/* Nihon Bridge — interaksi ringan di atas halaman Blade (modal, konfirmasi, toast, menu). */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = () => $('meta[name="csrf-token"]')?.content;

  /* ---------- toast ---------- */
  let toastTimer;
  window.toast = function (msg) {
    const t = $('#toast'); if (!t || !msg) return;
    t.textContent = msg; t.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
  };

  /* ---------- modal ---------- */
  window.openModal = function (id, fill) {
    const m = document.getElementById(id); if (!m) return;
    if (fill) fillForm(m, fill);
    m.hidden = false;
    const f = m.querySelector('input:not([type=hidden]),select,textarea,button.ok');
    if (f) setTimeout(() => f.focus(), 30);
  };
  window.closeModal = function (el) {
    const m = el ? el.closest('.modal-bg') : null;
    (m ? [m] : $$('.modal-bg')).forEach(x => { x.hidden = true; });
  };
  function fillForm(m, data) {
    const form = m.querySelector('form');
    Object.entries(data).forEach(([k, v]) => {
      if (k === '@action' && form) { form.action = v; return; }
      if (k === '@title') { const h = m.querySelector('[data-modal-title]'); if (h) h.textContent = v; return; }
      if (k === '@text') { const h = m.querySelector('[data-modal-text]'); if (h) h.textContent = v; return; }
      if (k === '@method' && form) { let i = form.querySelector('input[name=_method]'); if (!i) { i = document.createElement('input'); i.type = 'hidden'; i.name = '_method'; form.appendChild(i); } i.value = v; return; }
      if (k === '@submit') { const b = m.querySelector('button[type=submit]'); if (b) b.textContent = v; return; }
      const el = m.querySelector(`[name="${k}"]`);
      if (!el) return;
      if (el.type === 'checkbox') el.checked = !!v; else el.value = v ?? '';
    });
    m.querySelectorAll('.form-error[data-clear]').forEach(e => { e.hidden = true; });
    m.querySelectorAll('[data-amount-of]').forEach(syncAmount);
  }

  /* Field jumlah yang mengikuti opsi terpilih (data-amount pada <option>). */
  function syncAmount(out) {
    const sel = document.getElementById(out.dataset.amountOf);
    const opt = sel && sel.selectedOptions[0];
    if (opt && opt.dataset.amount !== undefined) out.value = opt.dataset.amount;
  }

  document.addEventListener('click', e => {
    const opener = e.target.closest('[data-modal-open]');
    if (opener) {
      e.preventDefault();
      let fill = null;
      if (opener.dataset.fill) { try { fill = JSON.parse(opener.dataset.fill); } catch (_) {} }
      openModal(opener.dataset.modalOpen, fill);
      return;
    }
    if (e.target.closest('[data-modal-close]')) { closeModal(e.target); return; }
    if (e.target.classList.contains('modal-bg')) { e.target.hidden = true; }
  });

  /* ---------- konfirmasi sebelum kirim form ---------- */
  let pendingForm = null;
  document.addEventListener('submit', e => {
    const f = e.target;
    if (f.dataset.confirm && !f.dataset.confirmed) {
      e.preventDefault();
      pendingForm = f;
      const m = $('#confirmModal');
      $('[data-modal-title]', m).textContent = f.dataset.confirmTitle || 'Lanjutkan?';
      $('[data-modal-text]', m).innerHTML = f.dataset.confirm;
      const ok = $('#confirmOk');
      ok.textContent = f.dataset.confirmOk || 'Ya, lanjutkan';
      ok.classList.toggle('danger', f.dataset.danger !== undefined);
      m.hidden = false; setTimeout(() => ok.focus(), 30);
    }
  });
  $('#confirmOk')?.addEventListener('click', () => {
    if (!pendingForm) return;
    pendingForm.dataset.confirmed = '1';
    $('#confirmModal').hidden = true;
    if (pendingForm.requestSubmit) pendingForm.requestSubmit(); else pendingForm.submit();
    pendingForm = null;
  });

  /* ---------- kontrol yang langsung mengirim form ---------- */
  document.addEventListener('change', e => {
    const el = e.target;
    if (el.matches('[data-autosubmit]')) { el.form.requestSubmit ? el.form.requestSubmit() : el.form.submit(); }
    if (el.id) $$(`[data-amount-of="${el.id}"]`).forEach(syncAmount);
  });

  /* ---------- baris berulang (mis. tahapan pembayaran angkatan) ---------- */
  document.addEventListener('click', e => {
    const add = e.target.closest('[data-repeat-add]');
    if (add) {
      const list = document.getElementById(add.dataset.repeatAdd);
      const tpl = document.getElementById(list.dataset.template);
      list.appendChild(tpl.content.cloneNode(true));
      renumber(list);
      return;
    }
    const del = e.target.closest('[data-repeat-remove]');
    if (del) {
      const list = del.closest('[data-template]');
      del.closest('[data-repeat-row]').remove();
      renumber(list);
    }
  });
  function renumber(list) {
    list.querySelectorAll('[data-repeat-row]').forEach((row, i) => {
      row.querySelectorAll('[data-repeat-no]').forEach(x => { x.textContent = i + 1; });
    });
    const count = document.querySelector(`[data-repeat-count="${list.id}"]`);
    if (count) count.textContent = list.querySelectorAll('[data-repeat-row]').length;
  }

  /* ---------- notifikasi, menu, keyboard ---------- */
  const bell = $('#bellBtn'), panel = $('#notifPanel');
  bell?.addEventListener('click', e => { e.stopPropagation(); panel.hidden = !panel.hidden; });
  document.addEventListener('click', e => { if (panel && !panel.hidden && !panel.contains(e.target)) panel.hidden = true; });
  $('#hamburger')?.addEventListener('click', () => $('#shell').classList.add('nav-open'));
  $('#scrim')?.addEventListener('click', () => $('#shell').classList.remove('nav-open'));
  document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    closeModal(); if (panel) panel.hidden = true; $('#shell')?.classList.remove('nav-open');
  });

  /* ---------- salin teks ---------- */
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-copy]'); if (!b) return;
    const t = b.dataset.copy, done = () => toast((b.dataset.copyMsg || 'Disalin: ') + t);
    try { navigator.clipboard.writeText(t).then(done, () => toast(t)); } catch (_) { toast(t); }
  });

  /* ---------- tampilkan/sembunyikan password ---------- */
  $$('[data-pw-toggle]').forEach(btn => btn.addEventListener('click', () => {
    const i = document.getElementById(btn.dataset.pwToggle), show = i.type === 'password';
    i.type = show ? 'text' : 'password';
    btn.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    btn.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  }));

  /* ---------- preferensi notifikasi (profil) ---------- */
  $$('[data-pref]').forEach(cb => cb.addEventListener('change', () => {
    fetch(cb.dataset.url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json' },
      body: JSON.stringify({ key: cb.dataset.pref, on: cb.checked }) })
      .then(r => r.ok ? toast(cb.dataset.label + ': ' + (cb.checked ? 'aktif' : 'nonaktif')) : Promise.reject())
      .catch(() => { cb.checked = !cb.checked; toast('Gagal menyimpan. Coba lagi.'); });
  }));

  /* ---------- segmented radio (kehadiran) ---------- */
  document.addEventListener('change', e => {
    const r = e.target; if (!r.matches('.seg input[type=radio]')) return;
    $$('label', r.closest('.seg')).forEach(l => { l.className = ''; });
    r.closest('label').className = 'on on-' + r.value;
    const sum = $('#attSummary'); if (!sum) return;
    const counts = { H: 0, I: 0, S: 0, A: 0 };
    $$('.seg input:checked').forEach(x => counts[x.value]++);
    sum.innerHTML = `<b class="tnum">${counts.H}</b> hadir · <b class="tnum">${counts.I}</b> izin · <b class="tnum">${counts.S}</b> sakit · <b class="tnum">${counts.A}</b> alpa`;
  });
  $('#markAllPresent')?.addEventListener('click', () => {
    $$('.seg input[value=H]').forEach(r => { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); });
  });

  /* ---------- buka modal dari server (validasi gagal) & toast flash ---------- */
  const boot = document.body.dataset;
  if (boot.openModal) openModal(boot.openModal);
  if (boot.toast) setTimeout(() => toast(boot.toast), 60);
})();
