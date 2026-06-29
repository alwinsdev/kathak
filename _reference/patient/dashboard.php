<?php
require __DIR__.'/../config.php';
require_role('patient');

$uid = current_user()['id'];
$today = date('Y-m-d');

// Completion is recorded only via AI verification (patient/practice.php → patient/complete.php).
// There is intentionally no manual "Mark Done" path in this POC.

$stmt = db()->prepare('
  SELECT a.*, m.name AS mudra_name, m.description, m.benefits,
         (SELECT id FROM completions c WHERE c.assignment_id=a.id AND c.completed_date=?) AS done_today
  FROM assignments a JOIN mudras m ON m.id=a.mudra_id
  WHERE a.patient_id=? AND a.active=1
  ORDER BY a.scheduled_time
');
$stmt->execute([$today, $uid]);
$assignments = $stmt->fetchAll();

// Suggested mudras (not yet assigned)
$sugg = db()->prepare('
  SELECT * FROM mudras
  WHERE id NOT IN (SELECT mudra_id FROM assignments WHERE patient_id=? AND active=1)
  LIMIT 4
');
$sugg->execute([$uid]);
$suggested = $sugg->fetchAll();

$total = count($assignments);
$done = count(array_filter($assignments, fn ($a) => $a['done_today']));
$pending = $total - $done;
$progress = $total > 0 ? round(($done / $total) * 100) : 0;

$pageTitle = 'My Therapy';
require __DIR__.'/../includes/header.php';
?>
<div class="page-title">
  <h1>My Therapy Schedule</h1>
  <p class="subtitle"><?= date('l, d M Y') ?> · Welcome back, <?= e(current_user()['name']) ?></p>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px">
  <div class="stat-card">
    <div class="icon">🧘</div>
    <div class="label">Today's Mudras</div>
    <div class="value"><?= $total ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:var(--success-light);color:var(--success)">✓</div>
    <div class="label">Completed</div>
    <div class="value"><?= $done ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:var(--warning-light);color:var(--warning)">⏳</div>
    <div class="label">Pending</div>
    <div class="value"><?= $pending ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:#dbeafe;color:var(--accent)">📊</div>
    <div class="label">Progress</div>
    <div class="value"><?= $progress ?>%</div>
    <div style="background:var(--border);height:6px;border-radius:3px;margin-top:8px;overflow:hidden">
      <div style="background:var(--success);height:100%;width:<?= $progress ?>%;transition:.3s"></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2 style="margin-bottom:0">Today's Prescriptions</h2><a href="history.php" class="btn btn-sm btn-secondary">📅 View History</a></div>
  <?php if (empty($assignments)) { ?>
    <div class="empty-state"><div class="icon">📋</div>No mudras assigned yet.<div style="font-size:.85em;margin-top:6px">Your doctor will prescribe a routine soon.</div></div>
  <?php } else { ?>
    <table>
      <tr><th>Time</th><th>Mudra</th><th>Duration</th><th>Status</th><th>Action</th></tr>
      <?php foreach ($assignments as $a) { ?>
        <tr data-time="<?= e(substr($a['scheduled_time'], 0, 5)) ?>" data-name="<?= e($a['mudra_name']) ?>">
          <td><strong style="font-size:1.05em;color:var(--primary)"><?= e(substr($a['scheduled_time'], 0, 5)) ?></strong></td>
          <td>
            <strong><?= e($a['mudra_name']) ?></strong>
            <div class="muted" style="font-size:.83em;margin-top:2px"><?= e($a['description']) ?></div>
            <?php if ($a['notes']) { ?><div class="muted" style="font-size:.8em;margin-top:4px;color:var(--accent)">📝 <?= e($a['notes']) ?></div><?php } ?>
          </td>
          <td><?= e($a['duration_min']) ?> min</td>
          <td>
            <?php if ($a['done_today']) { ?>
              <span class="badge badge-done">✓ AI-Verified</span>
            <?php } else { ?>
              <span class="badge badge-pending">Pending</span>
            <?php } ?>
          </td>
          <td>
            <?php if (! $a['done_today']) { ?>
              <a href="practice.php?assignment_id=<?= $a['id'] ?>&mudra=<?= urlencode($a['mudra_name']) ?>" class="btn btn-sm">📷 Practice &amp; Verify</a>
            <?php } else { ?>
              <a href="practice.php?assignment_id=<?= $a['id'] ?>&mudra=<?= urlencode($a['mudra_name']) ?>" class="btn btn-sm btn-secondary">📷 Practice again</a>
            <?php } ?>
          </td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>
</div>

<?php if (! empty($suggested)) { ?>
<div class="card">
  <div class="card-header"><h2 style="margin-bottom:0">💡 Suggested Mudras</h2><span class="muted">Discuss with your doctor</span></div>
  <div class="grid">
    <?php foreach ($suggested as $m) { ?>
      <div style="background:var(--bg);padding:16px;border-radius:10px;border:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:6px">
          <strong style="font-size:1em"><?= e($m['name']) ?></strong>
          <span style="font-size:1.3em">🧘</span>
        </div>
        <p class="muted" style="font-size:.83em;line-height:1.5"><?= e($m['description']) ?></p>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--border);font-size:.8em;color:var(--success);display:flex;align-items:center;gap:6px">
          <span>✓</span><span><?= e($m['benefits']) ?></span>
        </div>
      </div>
    <?php } ?>
  </div>
</div>
<?php } ?>

<script>
// Browser-side alerts at scheduled times
const rows = document.querySelectorAll('tr[data-time]');
const alerted = new Set();
function checkAlerts(){
  const now = new Date();
  const hhmm = String(now.getHours()).padStart(2,'0')+':'+String(now.getMinutes()).padStart(2,'0');
  rows.forEach(r => {
    const t = r.dataset.time, n = r.dataset.name;
    if (t === hhmm && !alerted.has(t)) {
      alerted.add(t);
      if (Notification.permission === 'granted') {
        new Notification('🪔 Kathak Therapy', { body: `Time for ${n} practice!` });
      } else {
        alert(`Time for ${n} practice!`);
      }
      r.style.background = 'rgba(255,184,107,0.15)';
    }
  });
}
if ('Notification' in window && Notification.permission === 'default') {
  Notification.requestPermission();
}
setInterval(checkAlerts, 30000);
checkAlerts();
</script>
<?php require __DIR__.'/../includes/footer.php'; ?>
