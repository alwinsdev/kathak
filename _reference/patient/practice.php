<?php
require __DIR__.'/../config.php';
require_role('patient');

$uid = current_user()['id'];
$assignmentId = (int) ($_GET['assignment_id'] ?? 0);

// A valid, active prescription owned by THIS patient is required. No free practice.
$assignment = null;
if ($assignmentId) {
    $chk = db()->prepare('SELECT a.id, m.name AS mudra_name
                          FROM assignments a JOIN mudras m ON m.id = a.mudra_id
                          WHERE a.id = ? AND a.patient_id = ? AND a.active = 1');
    $chk->execute([$assignmentId, $uid]);
    $assignment = $chk->fetch();
}

$pageTitle = 'Live Practice';
require __DIR__.'/../includes/header.php';

if (! $assignment) {
    ?>
<a href="dashboard.php" class="muted">&larr; Back to dashboard</a>
<div class="card" style="max-width:520px;margin:40px auto;text-align:center">
  <div style="font-size:2.6em;margin-bottom:10px">🔒</div>
  <h2 style="margin-bottom:8px">No active prescription found</h2>
  <p class="muted" style="margin-bottom:20px">Please contact your doctor.</p>
  <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>
<?php
    require __DIR__.'/../includes/footer.php';
    exit;
}

$mudra = $assignment['mudra_name']; // verification target comes from the DB, never the URL
?>
<a href="dashboard.php" class="muted">&larr; Back to dashboard</a>
<div class="page-title" style="margin-top:10px">
  <h1>📷 Live AI Practice</h1>
  <p class="subtitle">Hold the <strong><?= e($mudra) ?></strong> mudra steady — the AI verifies your practice automatically.</p>
</div>

<style>
  .practice-grid{display:grid;grid-template-columns:2fr 1fr;gap:18px}
  @media(max-width:720px){.practice-grid{grid-template-columns:1fr}}
</style>
<div class="card">
  <div class="practice-grid">
    <div style="position:relative;background:#0f172a;border-radius:10px;overflow:hidden;min-height:320px">
      <video id="video" autoplay playsinline muted style="width:100%;display:block"></video>
      <canvas id="overlay" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none"></canvas>
    </div>
    <div>
      <h3>🎯 Target: <?= e($mudra) ?></h3>
      <div id="status" class="muted" style="padding:10px;background:var(--bg);border-radius:6px;font-size:.88em">Starting camera…</div>

      <div style="margin-top:14px">
        <div class="muted" style="font-size:.78em;text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Verification</div>
        <div id="matchInfo" style="font-size:.92em;min-height:22px;font-weight:500">Waiting for camera…</div>
        <div style="background:var(--border);height:10px;border-radius:5px;margin-top:8px;overflow:hidden">
          <div id="holdBar" style="background:var(--success);height:100%;width:0%;transition:.15s"></div>
        </div>
      </div>

      <div id="successCard" style="display:none;margin-top:16px"></div>

      <div id="results" style="margin-top:14px"></div>

      <div id="controls" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border)">
        <label>Detection Frequency</label>
        <select id="rate">
          <option value="1000">Every 1 second</option>
          <option value="500" selected>Every 0.5 seconds</option>
          <option value="2000">Every 2 seconds</option>
        </select>
        <button id="toggleBtn" class="btn-block">⏸ Pause Detection</button>
      </div>
    </div>
  </div>
</div>

<canvas id="captureCanvas" style="display:none"></canvas>

<script>
// Injected config — tune thresholds in config.php, not here.
window.PRACTICE_CONFIG = {
  assignmentId : <?= (int) $assignment['id'] ?>,
  target       : <?= json_encode($mudra) ?>,
  confThreshold: <?= json_encode((float) AI_CONF_THRESHOLD) ?>,
  holdSeconds  : <?= json_encode((float) AI_HOLD_SECONDS) ?>,
  detectUrl    : '../predict.php',
  completeUrl  : 'complete.php'
};
</script>
<script src="<?= url('assets/js/practice-ui.js') ?>"></script>
<script src="<?= url('assets/js/practice-camera.js') ?>"></script>
<script src="<?= url('assets/js/practice-verify.js') ?>"></script>
<?php require __DIR__.'/../includes/footer.php'; ?>
