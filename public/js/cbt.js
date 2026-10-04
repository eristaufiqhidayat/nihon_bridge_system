/* CBT: navigasi soal, autosave ke server, antrean offline, timer, dan kirim otomatis. */
(function () {
  const root = document.getElementById('cbt'); if (!root) return;
  const $ = (s, r = document) => r.querySelector(s), $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = $('meta[name="csrf-token"]').content;
  const total = +root.dataset.total, storeKey = root.dataset.store;
  const panes = $$('.q-pane', root), grid = $$('#qGrid button');

  const st = {
    cur: +root.dataset.current || 0,
    answers: JSON.parse(root.dataset.answers || '{}'),
    flags: JSON.parse(root.dataset.flags || '{}'),
    left: +root.dataset.left,
    pending: { answers: {}, flags: {} },
    offline: false,
  };
  // Pulihkan jawaban yang belum terkirim (mis. halaman dimuat ulang saat offline)
  try {
    const saved = JSON.parse(localStorage.getItem(storeKey) || 'null');
    if (saved) {
      Object.assign(st.answers, saved.answers); Object.assign(st.flags, saved.flags);
      st.pending = { answers: saved.answers || {}, flags: saved.flags || {} };
    }
  } catch (_) {}

  const pendingCount = () => Object.keys(st.pending.answers).length + Object.keys(st.pending.flags).length;
  const persist = () => { try { pendingCount() ? localStorage.setItem(storeKey, JSON.stringify(st.pending)) : localStorage.removeItem(storeKey); } catch (_) {} };
  const fmt = s => [Math.floor(s / 3600), Math.floor(s % 3600 / 60), s % 60].map(x => String(x).padStart(2, '0')).join(':');

  function render() {
    panes.forEach((p, i) => { p.hidden = i !== st.cur; });
    const pane = panes[st.cur];
    $('#qSection').textContent = pane.dataset.section;
    $$('.opt', pane).forEach(b => { const on = String(st.answers[st.cur]) === b.dataset.opt; b.classList.toggle('sel', on); b.setAttribute('aria-checked', on); });
    $('[data-flag]', pane).classList.toggle('on', !!st.flags[st.cur]);
    grid.forEach((b, k) => {
      b.className = k === st.cur ? 'current' : st.flags[k] ? 'flagged' : st.answers[k] !== undefined ? 'answered' : '';
      if (st.pending.answers[k] !== undefined || st.pending.flags[k] !== undefined) b.classList.add('unsynced');
    });
    const ans = Object.keys(st.answers).length, fl = Object.values(st.flags).filter(Boolean).length;
    $('#qSummary').innerHTML = `Dijawab <b>${ans}</b> dari ${total} · Ragu-ragu <b>${fl}</b>`;
    $('#offlineBanner').hidden = !st.offline;
    $('#pendingCount').textContent = pendingCount();
  }

  let syncTimer = null, syncing = false;
  function queueSync(delay = 400) { clearTimeout(syncTimer); syncTimer = setTimeout(sync, delay); }
  async function sync() {
    if (syncing) return queueSync(500);
    syncing = true;
    const body = { answers: { ...st.pending.answers }, flags: { ...st.pending.flags }, current: st.cur };
    try {
      const r = await fetch(root.dataset.save, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf }, body: JSON.stringify(body) });
      if (r.status === 419) { persist(); onbeforeunload = null; location.reload(); return; }
      if (!r.ok) throw new Error(r.status);
      const j = await r.json();
      if (j.expired) { try { localStorage.removeItem(storeKey); } catch (_) {} location.href = j.redirect; return; }
      Object.keys(body.answers).forEach(k => { if (st.pending.answers[k] === body.answers[k]) delete st.pending.answers[k]; });
      Object.keys(body.flags).forEach(k => { if (st.pending.flags[k] === body.flags[k]) delete st.pending.flags[k]; });
      if (typeof j.left === 'number') st.left = j.left;
      if (st.offline) { st.offline = false; toast('Tersambung lagi. Jawaban tersinkron ke server.'); }
    } catch (e) {
      if (!st.offline) toast('Koneksi terputus. Jawaban disimpan di perangkat.');
      st.offline = true;
      queueSync(5000);
    } finally {
      syncing = false; persist(); render();
    }
  }
  window.addEventListener('online', () => queueSync(100));

  root.addEventListener('click', e => {
    const opt = e.target.closest('.opt');
    if (opt) { st.answers[st.cur] = +opt.dataset.opt; st.pending.answers[st.cur] = +opt.dataset.opt; persist(); render(); queueSync(); return; }
    const jump = e.target.closest('[data-jump]');
    if (jump) { const k = +jump.dataset.jump; if (k >= 0 && k < total) { st.cur = k; render(); queueSync(1500); $('#views').scrollTop = 0; } return; }
    if (e.target.closest('[data-flag]')) { st.flags[st.cur] = !st.flags[st.cur]; st.pending.flags[st.cur] = st.flags[st.cur]; if (!st.flags[st.cur]) delete st.flags[st.cur]; persist(); render(); queueSync(); return; }
    if (e.target.closest('[data-finish]')) { askFinish(); return; }
    const au = e.target.closest('[data-audio]');
    if (au) {
      if (!('speechSynthesis' in window)) { toast('Browser ini tidak mendukung audio sintetis. Transkrip ada di pembahasan.'); return; }
      speechSynthesis.cancel(); const u = new SpeechSynthesisUtterance(au.dataset.audio.replace(/　/g, ' ')); u.lang = 'ja-JP'; u.rate = .85; speechSynthesis.speak(u);
      toast('Memutar audio soal ' + (st.cur + 1));
    }
  });

  function askFinish() {
    const un = total - Object.keys(st.answers).length, fl = Object.values(st.flags).filter(Boolean).length;
    const warn = (un || fl) ? `Masih ada <b>${un} soal belum dijawab</b> dan <b>${fl} soal ragu-ragu</b>. Soal yang belum dijawab dihitung salah.` : 'Semua soal sudah dijawab.';
    $('#finishText').innerHTML = `${warn} Setelah dikirim, jawaban tidak bisa diubah.`;
    openModal('finishModal');
  }
  function submit(auto) {
    if ('speechSynthesis' in window) speechSynthesis.cancel();
    $('#payload').value = JSON.stringify({ answers: st.answers, flags: st.flags });
    $('#autoFlag').value = auto ? '1' : '0';
    try { localStorage.removeItem(storeKey); } catch (_) {}
    window.onbeforeunload = null;
    $('#submitForm').submit();
  }
  $('#finishOk').addEventListener('click', () => submit(false));

  // Timer: hitung mundur lokal, waktu sebenarnya dijaga server (expires_at)
  const chip = $('#timerChip');
  const tick = setInterval(() => {
    st.left = Math.max(0, st.left - 1);
    chip.textContent = '⏱ ' + fmt(st.left);
    chip.classList.toggle('warn', st.left <= 300);
    if (st.left === 0) { clearInterval(tick); toast('Waktu habis. Jawaban dikirim otomatis.'); submit(true); }
  }, 1000);

  window.onbeforeunload = () => (pendingCount() ? 'Masih ada jawaban yang belum terkirim.' : undefined);
  if (pendingCount()) queueSync(200);
  render();
})();
