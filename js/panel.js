/* ============================================
   js/panel.js  -  copiar enlace + refrescar progreso
   ============================================ */
(function () {
  'use strict';

  var btn = document.getElementById('copiar');
  var inp = document.getElementById('enlace');
  if (btn && inp) {
    btn.addEventListener('click', function () {
      var ok = function () {
        var t = btn.textContent;
        btn.textContent = 'Copiado';
        setTimeout(function () { btn.textContent = t; }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(inp.value).then(ok, function () {
          inp.select(); document.execCommand('copy'); ok();
        });
      } else {
        inp.select(); document.execCommand('copy'); ok();
      }
    });
  }

  var fill  = document.getElementById('barFill');
  var pctEl = document.getElementById('barPct');
  var api   = window.PANEL_API;

  function setPct(p) {
    p = Math.max(0, Math.min(100, Math.round(p)));
    if (fill)  fill.style.width = p + '%';
    if (pctEl) pctEl.textContent = p + '%';
  }

  function refrescar() {
    if (!api) return;
    fetch(api, { credentials: 'same-origin', cache: 'no-store' })
      .then(function (r) { return r.json(); })
      .then(function (d) { if (d && typeof d.pct === 'number') setPct(d.pct); })
      .catch(function () {});
  }

  setInterval(refrescar, 15000);
})();