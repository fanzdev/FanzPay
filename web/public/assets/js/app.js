/* FanzPay app.js — glassmorphism interactions: theme, reveal, nav, idempotency, polling. */
(function () {
  'use strict';

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
      el.textContent = t === 'dark' ? '☀️' : '🌙';
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
    toast(next === 'dark' ? 'Mode gelap aktif — mata aman, gaya tetap.' : 'Mode terang aktif — bersih dan fokus.');
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

  /* ---------- NAV: shadow on scroll + mobile menu ---------- */
  var nav = document.querySelector('.site-nav');
  function onScroll(){ if (nav) nav.classList.toggle('scrolled', window.scrollY > 8); }
  window.addEventListener('scroll', onScroll, { passive: true }); onScroll();
  document.addEventListener('click', function (ev) {
    var h = ev.target.closest('[data-menu-toggle]');
    if (h) {
      var m = document.querySelector(h.getAttribute('data-menu-toggle'));
      if (m) m.classList.toggle('on');
    }
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

  /* ---------- Password peek ---------- */
  document.querySelectorAll('[data-peek]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var inp = document.querySelector(btn.getAttribute('data-peek'));
      if (!inp) return;
      inp.type = inp.type === 'password' ? 'text' : 'password';
      btn.textContent = inp.type === 'password' ? 'Lihat' : 'Sembunyikan';
    });
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
