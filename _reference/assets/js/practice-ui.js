/* practice-ui.js — all DOM/visual concerns for the practice screen.
   Exposes Practice.UI (state machine + rendering) and Practice.norm (shared helper). */
window.Practice = window.Practice || {};

Practice.norm = function (s) {
  return (s || '').toLowerCase().replace(/[^a-z0-9]/g, '');
};

Practice.UI = (function () {
  var $ = function (id) { return document.getElementById(id); };

  // Verification state machine — the patient always knows what the AI is doing.
  var STATES = {
    waiting_camera: { text: '⏳ Waiting for camera…',     color: 'muted'   },
    detecting:      { text: '🔍 Detecting mudra…',         color: 'muted'   },
    wrong:          { text: '↻ Show the target mudra',     color: 'warning' },
    correct:        { text: '✋ Correct mudra detected',    color: 'success' },
    holding:        { text: '⏱ Hold steady…',              color: 'success' },
    verifying:      { text: '🔐 Verifying…',                color: 'success' },
    verified:       { text: '✅ Practice Verified',         color: 'success' },
    error:          { text: '⚠ Error',                     color: 'danger'  }
  };

  function color(c) {
    return c === 'success' ? 'var(--success)'
         : c === 'warning' ? 'var(--warning)'
         : c === 'danger'  ? '#ff8080'
         : 'var(--text-muted)';
  }

  function escapeHtml(s) {
    return String(s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  return {
    escapeHtml: escapeHtml,

    init: function () {
      this.setState('waiting_camera');
      this.setHold(0);
    },

    setStatus: function (html) { $('status').innerHTML = html; },

    // key = state name; detail = optional extra text already HTML-safe
    setState: function (key, detail) {
      var s = STATES[key] || STATES.detecting;
      $('matchInfo').innerHTML =
        '<span style="color:' + color(s.color) + '">' + s.text + (detail ? ' ' + detail : '') + '</span>';
    },

    setHold: function (pct) {
      $('holdBar').style.width = Math.max(0, Math.min(100, pct)) + '%';
    },

    showResults: function (preds) {
      if (!preds || !preds.length) { $('results').innerHTML = '<p class="muted">No mudra detected</p>'; return; }
      $('results').innerHTML = preds.map(function (p) {
        return '<div style="background:var(--bg);padding:8px 10px;border-radius:6px;margin-bottom:6px;font-size:.82em">' +
               '<strong>' + escapeHtml(p.class) + '</strong> · ' + ((p.confidence || 0) * 100).toFixed(1) + '%</div>';
      }).join('');
    },

    showSuccess: function (already) {
      $('controls').style.display = 'none';
      this.setStatus('<span style="color:#7fe5a4">✓ Verified</span>');
      this.setState('verified');
      $('successCard').style.display = 'block';
      $('successCard').innerHTML =
        '<div class="alert alert-success" style="margin-bottom:12px">✅ ' +
        (already ? 'Already completed today — well done!' : 'Verified! Your session has been logged.') +
        '</div><a href="dashboard.php" class="btn btn-block">Back to Dashboard</a>';
    },

    showRetry: function (msg) {
      // Verification failed → session stays incomplete; prompt to retry.
      this.setHold(0);
      $('matchInfo').innerHTML =
        '<span style="color:#ff8080">' + escapeHtml(msg) +
        ' — session not recorded. Hold the target mudra again.</span>';
    }
  };
})();
