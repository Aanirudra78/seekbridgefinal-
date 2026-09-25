// guard.js - Anti-cheating guard for SeekBridge tests
// - Detects tab/window switching and fullscreen exit (ESC)
// - 1st violation: blocks the test with a warning modal (Continue / Submit)
// - 2nd violation: auto-submits the test
// - Deadline-aware countdown timer (does NOT pause in background tabs)
;(function (w) {
  'use strict';

  var SeekGuard = {};

  // ================= warning modal (shared) =================
  var modal = null;
  function ensureModal() {
    if (modal) return modal;
    var wrap = document.createElement('div');
    wrap.id = 'seekGuardModal';
    wrap.className = 'seekguard-modal';
    wrap.style.display = 'none';
    wrap.innerHTML =
      '<div class="sgm-backdrop"></div>' +
      '<div class="sgm-card">' +
        '<div class="sgm-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>' +
        '<h5>Warning! You left the test window.</h5>' +
        '<p class="sgm-msg"></p>' +
        '<div class="sgm-btns">' +
          '<button type="button" class="btn btn-navy sgm-continue"><i class="fa-solid fa-arrow-rotate-left me-1"></i>Continue Test</button>' +
          '<button type="button" class="btn btn-danger sgm-submit"><i class="fa-solid fa-flag-checkered me-1"></i>Submit Test</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(wrap);
    modal = {
      el: wrap,
      msg: wrap.querySelector('.sgm-msg'),
      continueBtn: wrap.querySelector('.sgm-continue'),
      submitBtn: wrap.querySelector('.sgm-submit')
    };
    return modal;
  }
  function showWarn(opts, left) {
    var m = ensureModal();
    m.msg.textContent = 'You left the test window. ' +
      (left <= 0
        ? 'Continue with the test or submit it right now.'
        : 'If you leave again your test will be submitted automatically.');
    m.el.style.display = 'flex';
    m.continueBtn.onclick = function () {
      m.el.style.display = 'none';
      if (opts.onContinue) opts.onContinue();
    };
    m.submitBtn.onclick = function () {
      m.el.style.display = 'none';
      if (opts.onAutoSubmit) opts.onAutoSubmit();
    };
  }
  function hideWarn() {
    if (modal) modal.el.style.display = 'none';
  }

  // ================= violation monitor =================
  SeekGuard.start = function (opts) {
    opts = opts || {};
    var violations = 0;
    var max = opts.maxViolations || 2;
    var lastViolationAt = 0;
    var active = true;
    var stopped = false;

    function recordViolation() {
      if (!active) return;
      var now = Date.now();
      // dedupe: blur + visibilitychange fire for a single tab switch
      if (now - lastViolationAt < 1500) return;
      lastViolationAt = now;
      violations++;
      if (violations >= max) {
        active = false;
        stop(false);
        if (opts.onAutoSubmit) opts.onAutoSubmit();
      } else {
        if (opts.onViolation) opts.onViolation(violations, max - violations);
        showWarn(opts, max - violations);
      }
    }

    function onVisibility() { if (active && document.hidden) recordViolation(); }
    function onBlur() { if (active && !document.hidden) recordViolation(); }
    function onFsChange() {
      if (!active) return;
      var el = document.fullscreenElement || document.webkitFullscreenElement;
      if (!el) {
        // ESC pressed -> tried to leave the locked test
        recordViolation();
        if (opts.onFullscreenExit) opts.onFullscreenExit();
      }
    }
    function stop(force) {
      if (stopped) return;
      stopped = true;
      active = false;
      hideWarn();
    }

    document.addEventListener('visibilitychange', onVisibility);
    w.addEventListener('blur', onBlur);
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('webkitfullscreenchange', onFsChange);

    return {
      stop: stop,
      count: function () { return violations; }
    };
  };

  SeekGuard.enterFullscreen = function (el) {
    var d = el || document.documentElement;
    try {
      if (d.requestFullscreen) d.requestFullscreen();
      else if (d.webkitRequestFullscreen) d.webkitRequestFullscreen();
      else if (d.msRequestFullscreen) d.msRequestFullscreen();
    } catch (e) {}
  };

  SeekGuard.exitFullscreen = function () {
    try {
      if (document.exitFullscreen) document.exitFullscreen();
      else if (document.webkitExitFullscreen) document.webkitExitFullscreen();
      else if (document.msExitFullscreen) document.msExitFullscreen();
    } catch (e) {}
  };

  SeekGuard.secondsToText = function (s) {
    s = Math.max(0, s | 0);
    var m = Math.floor(s / 60), sec = s % 60;
    return String(m).padStart(2, '0') + ':' + String(sec).padStart(2, '0');
  };

  w.SeekGuard = SeekGuard;
})(window);