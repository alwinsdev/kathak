<?php
require __DIR__.'/../config.php';
require_role('doctor');

$patientId = (int) ($_GET['patient_id'] ?? 0);
$stmt = db()->prepare('SELECT u.*, p.age, p.gender, p.condition_notes FROM users u JOIN patients p ON p.user_id=u.id WHERE u.id=?');
$stmt->execute([$patientId]);
$patient = $stmt->fetch();
if (! $patient) {
    exit('Patient not found.');
}

$DAYS = 14;
$start = date('Y-m-d', strtotime('-'.($DAYS - 1).' days'));

// Active assignments
$stmt = db()->prepare('SELECT a.id, a.mudra_id, m.name FROM assignments a JOIN mudras m ON m.id=a.mudra_id WHERE a.patient_id=? AND a.active=1');
$stmt->execute([$patientId]);
$assignments = $stmt->fetchAll();
$totalActive = count($assignments);

// Daily completion count
$stmt = db()->prepare('
  SELECT c.completed_date AS d, COUNT(*) AS done
  FROM completions c JOIN assignments a ON a.id=c.assignment_id
  WHERE a.patient_id=? AND c.completed_date >= ?
  GROUP BY c.completed_date
');
$stmt->execute([$patientId, $start]);
$byDate = [];
foreach ($stmt->fetchAll() as $r) {
    $byDate[$r['d']] = (int) $r['done'];
}

$labels = [];
$doneVals = [];
$missedVals = [];
for ($i = 0; $i < $DAYS; $i++) {
    $d = date('Y-m-d', strtotime("$start +$i days"));
    $labels[] = date('d M', strtotime($d));
    $done = $byDate[$d] ?? 0;
    $doneVals[] = $done;
    $missedVals[] = max(0, $totalActive - $done);
}

// Per-mudra adherence over period
$stmt = db()->prepare('
  SELECT m.name, a.id AS aid,
         (SELECT COUNT(*) FROM completions c WHERE c.assignment_id=a.id AND c.completed_date >= ?) AS done
  FROM assignments a JOIN mudras m ON m.id=a.mudra_id
  WHERE a.patient_id=? AND a.active=1
');
$stmt->execute([$start, $patientId]);
$perMudra = $stmt->fetchAll();
foreach ($perMudra as &$pm) {
    $pm['percent'] = $DAYS > 0 ? round(($pm['done'] / $DAYS) * 100) : 0;
}
unset($pm);

$totalDone = array_sum($doneVals);
$totalExpected = $totalActive * $DAYS;
$overallPct = $totalExpected > 0 ? round(($totalDone / $totalExpected) * 100) : 0;

$pageTitle = 'Adherence — '.$patient['name'];
require __DIR__.'/../includes/header.php';
?>
<a href="dashboard.php" class="muted">&larr; Back to patients</a>
<div class="page-title" style="margin-top:10px">
  <h1>📊 Adherence Report</h1>
  <p class="subtitle">Patient: <strong><?= e($patient['name']) ?></strong> · Last <?= $DAYS ?> days</p>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px">
  <div class="stat-card">
    <div class="icon" style="background:<?= $overallPct >= 80 ? 'var(--success-light);color:var(--success)' : ($overallPct >= 50 ? 'var(--warning-light);color:var(--warning)' : 'var(--danger-light);color:var(--danger)') ?>">
      <?= $overallPct >= 80 ? '🌟' : ($overallPct >= 50 ? '⚠️' : '🚨') ?>
    </div>
    <div class="label">Overall Adherence</div>
    <div class="value"><?= $overallPct ?>%</div>
    <div style="background:var(--border);height:6px;border-radius:3px;margin-top:8px;overflow:hidden">
      <div style="background:<?= $overallPct >= 80 ? 'var(--success)' : ($overallPct >= 50 ? 'var(--warning)' : 'var(--danger)') ?>;height:100%;width:<?= $overallPct ?>%"></div>
    </div>
  </div>
  <div class="stat-card">
    <div class="icon">✓</div>
    <div class="label">Sessions Completed</div>
    <div class="value"><?= $totalDone ?><span style="font-size:.5em;color:var(--text-muted);font-weight:500"> / <?= $totalExpected ?></span></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:#dbeafe;color:var(--accent)">🧘</div>
    <div class="label">Active Mudras</div>
    <div class="value"><?= $totalActive ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:var(--warning-light);color:var(--warning)">📅</div>
    <div class="label">Period</div>
    <div class="value" style="font-size:1.1em;font-weight:600;margin-top:14px"><?= $DAYS ?> days</div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 style="margin-bottom:0">Daily Completion Trend</h2>
    <a href="assign.php?patient_id=<?= $patientId ?>" class="btn btn-sm btn-secondary">Manage Prescriptions</a>
  </div>
<div style="position:relative;height:260px;width:100%">
  <canvas id="trendChart"></canvas>
</div>
</div>

<div class="card">
  <h2>Per-Mudra Adherence</h2>
  <?php if (empty($perMudra)) { ?>
    <div class="empty-state"><div class="icon">📋</div>No active prescriptions.</div>
  <?php } else { ?>
    <?php foreach ($perMudra as $pm) { ?>
      <?php $col = $pm['percent'] >= 80 ? 'var(--success)' : ($pm['percent'] >= 50 ? 'var(--warning)' : 'var(--danger)'); ?>
      <div style="padding:12px 0;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px">
          <strong><?= e($pm['name']) ?></strong>
          <span style="font-weight:600;color:<?= $col ?>"><?= $pm['percent'] ?>%</span>
        </div>
        <div style="background:var(--bg);height:8px;border-radius:4px;overflow:hidden">
          <div style="background:<?= $col ?>;height:100%;width:<?= $pm['percent'] ?>%;transition:.3s"></div>
        </div>
        <div class="muted" style="font-size:.8em;margin-top:4px"><?= $pm['done'] ?> of <?= $DAYS ?> days completed</div>
      </div>
    <?php } ?>
  <?php } ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('trendChart').getContext('2d');
new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [
      { label: 'Completed', data: <?= json_encode($doneVals) ?>, backgroundColor: '#16a34a', borderRadius: 4 },
      { label: 'Missed', data: <?= json_encode($missedVals) ?>, backgroundColor: '#fecaca', borderRadius: 4 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'bottom', labels: { font: { family: 'Inter', size: 12 } } } },
    scales: {
      x: { stacked: true, grid: { display: false }, ticks: { font: { family: 'Inter' } } },
      y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { family: 'Inter' } }, grid: { color: '#f1f5f9' } }
    }
  }
});
</script>
<?php require __DIR__.'/../includes/footer.php'; ?>
