/* practice-camera.js — webcam capture + detection loop + bounding-box overlay.
   Knows nothing about verification rules; it just delivers frames' predictions to a callback. */
window.Practice = window.Practice || {};

Practice.Camera = (function () {
  var cfg = window.PRACTICE_CONFIG;
  var video, overlay, octx, capCanvas, capCtx;
  var stream = null, running = true, timer = null, busy = false;
  var onResult = function () {};

  function rate() {
    var s = document.getElementById('rate');
    return s ? parseInt(s.value, 10) : 500;
  }

  function drawBoxes(preds) {
    octx.clearRect(0, 0, overlay.width, overlay.height);
    var nt = Practice.norm(cfg.target);
    preds.forEach(function (p) {
      if (p.x === undefined || p.width === undefined) return;
      var x = p.x - p.width / 2, y = p.y - p.height / 2;
      var isTarget = Practice.norm(p.class) === nt;
      octx.strokeStyle = isTarget ? '#16a34a' : '#ffb86b';
      octx.lineWidth = 3;
      octx.strokeRect(x, y, p.width, p.height);
      var label = p.class + ' ' + ((p.confidence || 0) * 100).toFixed(0) + '%';
      octx.font = '20px sans-serif';
      var tw = octx.measureText(label).width;
      octx.fillStyle = isTarget ? '#16a34a' : '#ffb86b';
      octx.fillRect(x, y - 26, tw + 12, 26);
      octx.fillStyle = '#fff';
      octx.fillText(label, x + 6, y - 7);
    });
  }

  function loop() {
    if (timer) clearTimeout(timer);
    if (!running) return;
    detect().finally(function () { timer = setTimeout(loop, rate()); });
  }

  async function detect() {
    if (busy) return;
    busy = true;
    try {
      capCtx.drawImage(video, 0, 0, capCanvas.width, capCanvas.height);
      var blob = await new Promise(function (r) { capCanvas.toBlob(r, 'image/jpeg', 0.7); });
      var fd = new FormData();
      fd.append('image', blob, 'frame.jpg');
      var res = await fetch(cfg.detectUrl, { method: 'POST', body: fd });
      var data = await res.json();
      drawBoxes(data.predictions || []);
      onResult(data);
    } catch (e) {
      Practice.UI.setStatus('Error: ' + e.message);
    } finally {
      busy = false;
    }
  }

  return {
    async start(cb) {
      onResult = cb || onResult;
      video = document.getElementById('video');
      overlay = document.getElementById('overlay');
      octx = overlay.getContext('2d');
      capCanvas = document.getElementById('captureCanvas');
      capCtx = capCanvas.getContext('2d');
      try {
        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        video.srcObject = stream;
        await new Promise(function (r) { video.onloadedmetadata = r; });
        overlay.width = video.videoWidth; overlay.height = video.videoHeight;
        capCanvas.width = video.videoWidth; capCanvas.height = video.videoHeight;
        Practice.UI.setStatus('<span style="color:#7fe5a4">● Live</span>');
        Practice.UI.setState('detecting');
        loop();
      } catch (e) {
        running = false;
        Practice.UI.setStatus('<span style="color:#ff8080">Camera error: ' + Practice.UI.escapeHtml(e.message) + '</span>');
        Practice.UI.setState('error', '— allow camera access, then reload.');
        var c = document.getElementById('controls');
        if (c) c.style.display = 'none';
      }
    },
    pause(p) {
      running = !p;
      if (running) loop();
      else if (timer) clearTimeout(timer);
    },
    isRunning() { return running; },
    stop() {
      running = false;
      if (timer) clearTimeout(timer);
      if (stream) { stream.getTracks().forEach(function (t) { t.stop(); }); stream = null; }
    }
  };
})();
