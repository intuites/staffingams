/* Staffing Accounting — vanilla JS. No dependencies.
   Handles: mobile nav, delete-confirm modal, transaction-form conditional
   sections, cascading selects (company → candidate → type, candidate → project). */
(function () {
  'use strict';

  /* ---------- Mobile nav ---------- */
  var toggle = document.querySelector('[data-nav-toggle]');
  var nav = document.querySelector('[data-nav]');
  if (toggle && nav) {
    toggle.addEventListener('click', function () { nav.classList.toggle('open'); });
  }

  /* ---------- Nav dropdowns (click for touch; hover handled by CSS) ---------- */
  document.querySelectorAll('[data-drop]').forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var drop = btn.closest('.navdrop');
      var wasOpen = drop.classList.contains('open');
      document.querySelectorAll('.navdrop.open').forEach(function (d) { d.classList.remove('open'); });
      if (!wasOpen) drop.classList.add('open');
    });
  });
  document.addEventListener('click', function () {
    document.querySelectorAll('.navdrop.open').forEach(function (d) { d.classList.remove('open'); });
  });

  /* ---------- Delete-confirmation modal ---------- */
  var modal = document.getElementById('confirm-modal');
  if (modal) {
    var form = document.getElementById('confirm-form');
    var msg = document.getElementById('confirm-msg');
    document.addEventListener('click', function (e) {
      var opener = e.target.closest('[data-confirm-action]');
      if (opener) {
        e.preventDefault();
        form.action = opener.getAttribute('data-confirm-action');
        msg.textContent = opener.getAttribute('data-confirm-msg') || 'Are you sure? This cannot be undone.';
        modal.classList.add('open');
        return;
      }
      if (e.target.closest('[data-modal-close]') || e.target === modal) {
        modal.classList.remove('open');
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') modal.classList.remove('open');
    });
  }

  /* ---------- Transaction form: conditional sections by type ----------
     Sections carry data-types="Earnings,Company Payment"; the type <select>
     carries data-txn-type. Required-ness inside hidden sections is dropped so
     the browser doesn't block submit on invisible fields. */
  var typeSel = document.querySelector('[data-txn-type]');
  if (typeSel) {
    var sections = document.querySelectorAll('[data-types]');
    var applyType = function () {
      var t = typeSel.value;
      sections.forEach(function (sec) {
        var show = sec.getAttribute('data-types').split(',').indexOf(t) !== -1;
        sec.style.display = show ? '' : 'none';
        sec.querySelectorAll('input,select,textarea').forEach(function (el) {
          if (el.hasAttribute('data-req')) el.required = show;
          if (!show) el.disabled = false; // keep values posted; server ignores irrelevant ones
        });
      });
    };
    typeSel.addEventListener('change', applyType);
    applyType();

    /* Live auto-calculated amount for Earnings */
    var hours = document.querySelector('[name="hours_worked"]');
    var rate = document.querySelector('[name="rate_applied"]');
    var autoOut = document.querySelector('[data-auto-amount]');
    var recalc = function () {
      if (!autoOut) return;
      var h = parseFloat(hours && hours.value) || 0;
      var r = parseFloat(rate && rate.value) || 0;
      autoOut.value = '$' + (h * r).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };
    if (hours) hours.addEventListener('input', recalc);
    if (rate) rate.addEventListener('input', recalc);
    recalc();
  }

  /* ---------- Project form: live rate preview ---------- */
  var rc = document.querySelector('[data-rate-client]');
  var ri = document.querySelector('[data-rate-informed]');
  var pct = document.querySelector('[data-rate-pct]');
  var ovr = document.querySelector('[data-rate-override]');
  var autoRate = document.querySelector('[data-auto-rate]');
  var finalRate = document.querySelector('[data-final-rate]');
  if (autoRate) {
    var recalcRate = function () {
      var a = Math.min(parseFloat(rc.value) || 0, parseFloat(ri.value) || 0) * (parseFloat(pct.value) || 0);
      a = Math.round(a * 100) / 100;
      var o = parseFloat(ovr && ovr.value) || 0;
      var fmt = function (n) { return '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
      autoRate.value = fmt(a);
      if (finalRate) finalRate.value = fmt(o > 0 ? o : a);
    };
    [rc, ri, pct, ovr].forEach(function (el) { if (el) el.addEventListener('input', recalcRate); });
    recalcRate();
  }

  /* ---------- Cascading selects ----------
     A select with data-cascade-src="/companies/{id}/candidates.json" and
     data-cascade-target="#candidate-select" repopulates the target when it
     changes. {id} is replaced by the chosen value. Optional
     data-cascade-keep keeps the current selection when still present. */
  document.querySelectorAll('[data-cascade-src]').forEach(function (src) {
    var target = document.querySelector(src.getAttribute('data-cascade-target'));
    if (!target) return;
    var placeholder = target.getAttribute('data-placeholder') || '— Select —';
    var repopulate = function (submitAfter) {
      var v = src.value;
      var keep = target.value;
      target.innerHTML = '';
      var opt0 = document.createElement('option');
      opt0.value = '';
      opt0.textContent = placeholder;
      target.appendChild(opt0);
      if (!v) { target.dispatchEvent(new Event('cascade-done')); return; }
      fetch(src.getAttribute('data-cascade-src').replace('{id}', encodeURIComponent(v)))
        .then(function (r) { return r.json(); })
        .then(function (rows) {
          rows.forEach(function (row) {
            var o = document.createElement('option');
            o.value = row.id !== undefined ? row.id : row.value;
            o.textContent = row.label;
            target.appendChild(o);
          });
          if (keep && target.querySelector('option[value="' + keep + '"]')) target.value = keep;
          target.dispatchEvent(new Event('cascade-done'));
          if (submitAfter && src.hasAttribute('data-cascade-submit')) src.form.submit();
        });
    };
    src.addEventListener('change', function () { repopulate(false); });
    // Populate on load when the source already has a value but the target has
    // only its server-rendered options (edit forms handle this server-side).
    if (src.hasAttribute('data-cascade-init') && src.value) repopulate(false);
  });

  /* ---------- Auto-submit filters ---------- */
  document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
    el.addEventListener('change', function () { el.form.submit(); });
  });
})();
