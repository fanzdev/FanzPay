/* FanzPay app.js — glassmorphism interactions: theme, reveal, nav, idempotency, polling. */
(function () {
  'use strict';
  /* Singleton guard: cegah double-binding bila script termuat/ dieksekusi dua kali.
     Tanpa ini, satu ketukan pada toggle menu memicu dua handler (buka lalu tutup) → terlihat kedip-kedip. */
  if (window.__fanzpayBooted) return;
  window.__fanzpayBooted = true;

  /* ---------- THEME: Dark/Light, default Light ---------- */
  var root = document.documentElement;
  function currentTheme() {
    try { return localStorage.getItem('fanzpay-theme') || 'light'; }
    catch (e) { return 'light'; }
  }
  function applyTheme(t) {
    root.setAttribute('data-theme', t === 'dark' ? 'dark' : 'light');
    try { localStorage.setItem('fanzpay-theme', t === 'dark' ? 'dark' : 'light'); } catch (e) {}
    document.querySelectorAll('[data-theme-icon]').forEach(function (el) {
      var isDark = t === 'dark';
      el.innerHTML = isDark
        ? '<svg class="ic" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2.5M12 19.5V22M2 12h2.5M19.5 12H22M4.9 4.9l1.8 1.8M17.3 17.3l1.8 1.8M19.1 4.9l-1.8 1.8M6.7 17.3l-1.8 1.8"/></svg>'
        : '<svg class="ic" width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 14.5A8.5 8.5 0 0 1 9.5 4 8.5 8.5 0 1 0 20 14.5z"/></svg>';
    });
    var meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', t === 'dark' ? '#070C1A' : '#F4F6FB');
  }
  applyTheme(currentTheme());
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-theme-toggle]');
    if (!b) return;
    ev.preventDefault();
    var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    applyTheme(next);
    toast(next === 'dark' ? 'Mode gelap aktif.' : 'Mode terang aktif.');
  });

  function toast(msg) {
    var t = document.querySelector('.toast');
    if (!t) {
      t = document.createElement('div');
      t.className = 'toast';
      document.body.appendChild(t);
    }
    t.textContent = msg;
    t.style.display = 'block';
    requestAnimationFrame(function(){ t.classList.add('show'); });
    clearTimeout(t._h);
    t._h = setTimeout(function () { t.classList.remove('show'); setTimeout(function(){ t.style.display='none'; }, 320); }, 2600);
  }

  /* ---------- NAV: shadow on scroll + mobile menu elegan (selalu di depan) ---------- */
  var nav = document.querySelector('.site-nav');
  function onScroll(){ if (nav) nav.classList.toggle('scrolled', window.scrollY > 8); }
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  var ICO_MENU = '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg>';
  var ICO_CLOSE = '<svg class="ic" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18"/></svg>';
  function setMenu(open) {
    var m = document.querySelector('#mmenu');
    var h = document.querySelector('[data-menu-toggle]');
    if (!m || !h) return;
    m.classList.toggle('on', !!open);
    h.classList.toggle('open', !!open);
    h.setAttribute('aria-expanded', open ? 'true' : 'false');
    h.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
    try { h.innerHTML = open ? ICO_CLOSE : ICO_MENU; } catch (e) {}
    if (nav) nav.classList.toggle('menu-open', !!open);
    document.body.classList.toggle('menu-open', !!open);
  }
  function isMenuOpen() {
    var m = document.querySelector('#mmenu');
    return !!(m && m.classList.contains('on'));
  }
  document.addEventListener('click', function (ev) {
    var h = ev.target.closest('[data-menu-toggle]');
    if (h) { ev.preventDefault(); setMenu(!isMenuOpen()); return; }
    if (isMenuOpen()) {
      var m = document.querySelector('#mmenu');
      // Klik tautan di dalam menu: tutup agar transisi terasa rapi.
      if (ev.target.closest('#mmenu a')) { setMenu(false); return; }
      // Klik di luar header saat menu terbuka (pada lapisan dim): tutup.
      if (m && nav && !ev.target.closest('.site-nav')) { setMenu(false); }
    }
  });
  document.addEventListener('keydown', function (ev) {
    if ((ev.key === 'Escape' || ev.key === 'Esc') && isMenuOpen()) setMenu(false);
  });
  window.addEventListener('resize', function () {
    if (window.innerWidth > 860 && isMenuOpen()) setMenu(false);
  });

  /* ---------- REVEAL on scroll ---------- */
  var io = null;
  try {
    io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    document.querySelectorAll('.reveal').forEach(function (el) { io.observe(el); });
  } catch (e) {
    document.querySelectorAll('.reveal').forEach(function (el) { el.classList.add('in'); });
  }

  /* ---------- Animated counters (real values only) ---------- */
  function animateCount(el) {
    var target = parseInt(el.getAttribute('data-count') || '0', 10);
    if (!target || target <= 0) return;
    var dur = 1200, t0 = null;
    function step(ts) {
      if (!t0) t0 = ts;
      var p = Math.min(1, (ts - t0) / dur);
      var eased = 1 - Math.pow(1 - p, 3);
      el.textContent = Math.round(target * eased).toLocaleString('id-ID');
      if (p < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  try {
    var cio = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { animateCount(en.target); cio.unobserve(en.target); } });
    }, { threshold: 0.4 });
    document.querySelectorAll('[data-count]').forEach(function (el) { cio.observe(el); });
  } catch (e) {}

  /* ---------- FAQ: satu terbuka dalam satu waktu (hemat ruang vertikal) ---------- */
  document.querySelectorAll('.faq').forEach(function (wrap) {
    wrap.querySelectorAll('details').forEach(function (d) {
      d.addEventListener('toggle', function () {
        if (!d.open) return;
        wrap.querySelectorAll('details').forEach(function (o) { if (o !== d && o.open) o.open = false; });
      });
    });
  });

  /* ---------- Password peek: ikon mata di dalam input ---------- */
  var EYE = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12z"/><circle cx="12" cy="12" r="2.8"/></svg>';
  var EYEOFF = '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.6 5.2A16.4 16.4 0 0 1 12 5.5c6.5 0 10 6.5 10 6.5a17.6 17.6 0 0 1-3.1 3.8M6.6 6.6A16.8 16.8 0 0 0 2 12s3.5 6.5 10 6.5c1.4 0 2.7-.3 3.9-.8"/><path d="m3 3 18 18"/></svg>';
  document.querySelectorAll('[data-peek]').forEach(function (btn) {
    if (!btn.dataset.eyeInit) { btn.innerHTML = EYE; btn.dataset.eyeInit = '1'; }
    btn.setAttribute('aria-label', 'Tampilkan kata sandi');
    btn.addEventListener('click', function () {
      var inp = document.querySelector(btn.getAttribute('data-peek'));
      if (!inp) return;
      var show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.innerHTML = show ? EYEOFF : EYE;
      btn.classList.toggle('on', show);
      btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
      inp.focus({ preventScroll: true });
    });
  });

  /* ---------- Meter kekuatan kata sandi ---------- */
  document.querySelectorAll('[data-strength]').forEach(function (inp) {
    var meter = document.querySelector(inp.getAttribute('data-strength'));
    if (!meter) return;
    var bars = meter.querySelectorAll('i');
    var label = meter.querySelector('[data-slabel]');
    var names = ['Isi kata sandi', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'];
    function score(v) {
      if (!v) return 0;
      var s = 0;
      if (v.length >= 8) s++;
      if (v.length >= 12) s++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
      if (/\d/.test(v)) s++;
      if (/[^A-Za-z0-9]/.test(v)) s++;
      return Math.max(1, Math.min(4, s));
    }
    function paint() {
      var lv = score(inp.value);
      meter.className = meter.className.replace(/\blv\d\b/g, '').trim() + ' lv' + lv;
      bars.forEach(function (b, i) { b.classList.toggle('f', i < lv); });
      if (label) label.textContent = names[lv];
    }
    inp.addEventListener('input', paint);
    paint();
  });

  /* ---------- Input HP: prefix +62 + auto-strip awalan ---------- */
  function stripID(v) {
    v = (v || '').replace(/[^\d]/g, '');
    while (/^62/.test(v)) v = v.slice(2);
    while (/^0/.test(v)) v = v.slice(1);
    return v.slice(0, 14);
  }
  document.querySelectorAll('.phone-field input').forEach(function (inp) {
    inp.value = stripID(inp.value);
    inp.setAttribute('placeholder', '812 xxxx xxxx');
    inp.addEventListener('input', function () {
      inp.value = stripID(inp.value);
      try { inp.setSelectionRange(inp.value.length, inp.value.length); } catch (e) {}
    });
  });
  // Klik strip +62 ikut memfokuskan kolom ketik.
  document.querySelectorAll('.phone-field').forEach(function (pf) {
    pf.addEventListener('click', function (ev) {
      if (ev.target.closest('input')) return;
      var inp = pf.querySelector('input');
      if (inp) { try { inp.focus({ preventScroll: true }); } catch (e) { inp.focus(); } }
    });
  });
  // Saat submit: kirim bentuk lengkap +62xxxxxxxxxx ke server.
  document.addEventListener('submit', function (ev) {
    var f = ev.target;
    if (!f || !f.querySelector) return;
    f.querySelectorAll('.phone-field input[name]').forEach(function (inp) {
      var d = stripID(inp.value);
      if (d !== '') inp.value = '+62' + d;
    });
  }, true);

  /* ---------- Wizard multi-langkah (mis. registrasi) ---------- */
  document.querySelectorAll('[data-wizard]').forEach(function (w) {
    var panels = Array.prototype.slice.call(w.querySelectorAll('[data-wpanel]'));
    var steps = Array.prototype.slice.call(w.querySelectorAll('[data-wstep]'));
    var cur = 0;
    function paint() {
      panels.forEach(function (p, i) { p.classList.toggle('on', i === cur); });
      steps.forEach(function (s, i) {
        s.classList.toggle('on', i === cur);
        s.classList.toggle('done', i < cur);
      });
      var first = panels[cur] && panels[cur].querySelector('input');
      if (first) { try { first.focus({ preventScroll: true }); } catch (e) {} }
    }
    function validPanel(p) {
      var ok = true, firstBad = null;
      p.querySelectorAll('[data-req]').forEach(function (el) {
        var v = (el.value || '').trim(), bad = '';
        el.closest('.field').classList.remove('invalid');
        if (v === '') bad = 'Wajib diisi.';
        else if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v)) bad = 'Format email tidak valid.';
        else if (el.dataset.min && v.length < parseInt(el.dataset.min, 10)) bad = 'Minimal ' + el.dataset.min + ' karakter.';
        else if (el.dataset.match) {
          var other = w.querySelector('[name="' + el.dataset.match + '"]');
          if (other && v !== other.value) bad = 'Tidak sama dengan kata sandi.';
        }
        else if (el.dataset.phone) {
          var nv = (function (x) { x = x.replace(/[^\d]/g, ''); while (/^62/.test(x)) x = x.slice(2); while (/^0/.test(x)) x = x.slice(1); return x; })(v);
          if (!/^8\d{7,11}$/.test(nv)) bad = 'Nomor HP tidak valid.';
        }
        var err = el.closest('.field').querySelector('[data-werr]');
        if (bad) {
          ok = false;
          el.closest('.field').classList.add('invalid');
          if (err) { err.textContent = bad; err.style.display = 'block'; }
          if (!firstBad) firstBad = el;
        } else if (err) { err.style.display = 'none'; }
      });
      if (firstBad) { try { firstBad.focus({ preventScroll: true }); } catch (e) {} }
      return ok;
    }
    w.querySelectorAll('[data-wnext]').forEach(function (b) {
      b.addEventListener('click', function () {
        if (!validPanel(panels[cur])) return;
        if (cur < panels.length - 1) { cur++; paint(); fillReview(); }
      });
    });
    w.querySelectorAll('[data-wback]').forEach(function (b) {
      b.addEventListener('click', function () { if (cur > 0) { cur--; paint(); } });
    });
    w.querySelectorAll('[data-wgoto]').forEach(function (b) {
      b.addEventListener('click', function () {
        var i = parseInt(b.getAttribute('data-wgoto'), 10);
        if (!isNaN(i) && i >= 0 && i < panels.length) { cur = i; paint(); }
      });
    });
    function fillReview() {
      w.querySelectorAll('[data-review]').forEach(function (el) {
        var src = w.querySelector('[name="' + el.getAttribute('data-review') + '"]');
        if (src) el.textContent = src.type === 'password' ? '••••••••' : (src.value || '—');
      });
    }
    w.addEventListener('input', function () { fillReview(); });
    paint();
  });

  // Idempotency key per form (hindari double-submit / double-click)
  document.querySelectorAll('form[data-idem]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var h = f.querySelector('input[name="_idem"]');
      if (h && !h.value) {
        var b = new Uint8Array(12);
        (window.crypto || {}).getRandomValues ? crypto.getRandomValues(b) : b.fill(Date.now() % 256);
        h.value = Array.from(b).map(function (x) { return x.toString(16).padStart(2, '0'); }).join('');
      }
      var btn = f.querySelector('[type="submit"]');
      if (btn) { btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = 'Memproses…'; }
    });
  });

  // Polling status deposit (hormati rate limit: 1x / 25 detik hanya saat tab terlihat)
  var depEl = document.querySelector('[data-dep-poll]');
  if (depEl) {
    var ref = depEl.getAttribute('data-dep-poll');
    var base = depEl.getAttribute('data-base') || '';
    var timer = setInterval(function () {
      if (document.hidden) return;
      fetch(base + '/api/deposit/' + encodeURIComponent(ref), { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.ok ? r.json() : null; })
        .then(function (j) {
          if (j && j.ok && j.data && (j.data.status === 'success' || j.data.status === 'expired' || j.data.status === 'failed')) {
            clearInterval(timer);
            window.location.reload();
          }
        })
        .catch(function () {});
    }, 25000);
  }

  // Bottom sheet generik
  document.querySelectorAll('[data-sheet-open]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var s = document.querySelector(btn.getAttribute('data-sheet-open'));
      if (s) s.classList.add('on');
    });
  });
  document.querySelectorAll('.sheet .dim, [data-sheet-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      el.closest('.sheet').classList.remove('on');
    });
  });

  // PIN pad → isi hidden input + dots
  document.querySelectorAll('[data-pinpad]').forEach(function (pad) {
    var target = document.querySelector(pad.getAttribute('data-pinpad'));
    var dots = pad.parentElement.querySelectorAll('.pin-dots i');
    var val = '';
    function paint() {
      dots.forEach(function (d, i) { d.classList.toggle('f', i < val.length); });
      if (target) target.value = val;
    }
    pad.querySelectorAll('button[data-k]').forEach(function (b) {
      b.addEventListener('click', function () {
        var k = b.getAttribute('data-k');
        if (k === 'del') val = val.slice(0, -1);
        else if (val.length < 6) val += k;
        paint();
      });
    });
    paint();
  });

  // Konfirmasi aksi sensitif
  document.querySelectorAll('[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (ev) {
      if (!window.confirm(f.getAttribute('data-confirm'))) ev.preventDefault();
    });
  });

  window.FanzPay = { toast: toast, applyTheme: applyTheme };
})();
