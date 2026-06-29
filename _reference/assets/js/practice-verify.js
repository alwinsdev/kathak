/* practice-verify.js — the verification rules + bootstrap (loaded last).
   Decides match/hold/verify and records completion via complete.php. */
window.Practice = window.Practice || {};

(function () {
  var cfg = window.PRACTICE_CONFIG;
  var normTarget = Practice.norm(cfg.target);
  var matchStart = null, bestConf = 0, verified = false;

  function reset() { matchStart = null; Practice.UI.setHold(0); }

  function onFrame(data) {
    if (verified) return;
    var preds = (data && data.predictions) || [];
    Practice.UI.showResults(preds);

    if (!preds.length) { Practice.UI.setState('detecting'); reset(); return; }

    // Best-confidence prediction whose class matches the prescribed target.
    var match = null;
    preds.forEach(function (p) {
      if (Practice.norm(p.class) === normTarget && (!match || (p.confidence || 0) > (match.confidence || 0))) match = p;
    });
    var conf = match ? (match.confidence || 0) : 0;

    if (match && conf >= cfg.confThreshold) {
      bestConf = Math.max(bestConf, conf);
      if (matchStart === null) {
        // First frame the correct mudra appears — show it before the hold begins.
        matchStart = performance.now();
        Practice.UI.setHold(0);
        Practice.UI.setState('correct', '(' + (conf * 100).toFixed(0) + '%)');
        return;
      }
      var held = (performance.now() - matchStart) / 1000;
      Practice.UI.setHold((held / cfg.holdSeconds) * 100);
      Practice.UI.setState('holding', '(' + held.toFixed(1) + ' / ' + cfg.holdSeconds + 's · ' + (conf * 100).toFixed(0) + '%)');
      if (held >= cfg.holdSeconds) verify();
    } else {
      reset();
      var top = preds[0] && preds[0].class ? preds[0].class : null;
      Practice.UI.setState('wrong', top ? '— detected ' + Practice.UI.escapeHtml(top) : '');
    }
  }

  async function verify() {
    verified = true;
    Practice.Camera.pause(true);
    Practice.UI.setHold(100);
    Practice.UI.setState('verifying');
    try {
      var fd = new FormData();
      fd.append('assignment_id', cfg.assignmentId);
      fd.append('confidence', bestConf.toFixed(3));
      var res = await fetch(cfg.completeUrl, { method: 'POST', body: fd });
      var out = await res.json();
      if (out.ok) { Practice.Camera.stop(); Practice.UI.showSuccess(out.already); }
      else fail(out.error || 'Could not record completion');
    } catch (e) {
      fail(e.message);
    }
  }

  function fail(msg) {
    // Stay incomplete and resume detection so the patient can retry.
    verified = false; bestConf = 0; reset();
    Practice.UI.showRetry(msg);
    Practice.Camera.pause(false);
  }

  Practice.Verify = { onFrame: onFrame };

  // Bootstrap (this file is included last, after UI + Camera).
  document.addEventListener('DOMContentLoaded', function () {
    Practice.UI.init();
    Practice.Camera.start(Practice.Verify.onFrame);

    var btn = document.getElementById('toggleBtn');
    if (btn) btn.onclick = function () {
      if (verified) return;
      var willRun = !Practice.Camera.isRunning();
      Practice.Camera.pause(!willRun);
      btn.textContent = willRun ? '⏸ Pause Detection' : '▶ Resume Detection';
    };
  });
})();
