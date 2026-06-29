<?php
require __DIR__.'/../config.php';
require_role('patient');

$uid = current_user()['id'];
$days = 35; // ~5 weeks
$startDate = date('Y-m-d', strtotime('-'.($days - 1).' days'));

// Per-day completion stats
$stmt = db()->prepare('
  SELECT c.completed_date AS d, COUNT(*) AS done
  FROM completions c
  JOIN assignments a ON a.id=c.assignment_id
  WHERE a.patient_id=? AND c.completed_date >= ?
  GROUP BY c.completed_date
');
$stmt->execute([$uid, $startDate]);
$doneByDate = [];
foreach ($stmt->fetchAll() as $r) {
    $doneByDate[$r['d']] = (int) $r['done'];
}

// Total prescriptions active per day (we approximate: total active assignments now)
$totalActive = (int) db()->prepare('SELECT COUNT(*) c FROM assignments WHERE patient_id=? AND active=1')
    ->execute([$uid]) ?: 0;
$stmt = db()->prepare('SELECT COUNT(*) c FROM assignments WHERE patient_id=? AND active=1');
$stmt->execute([$uid]);
$totalActive = (int) $stmt->fetchColumn();

// Streak: consecutive days ending today with full completion
$streak = 0;
for ($i = 0; $i < 60; $i++) {
    $d = date('Y-m-d', strtotime("-$i days"));
    if (! isset($doneByDate[$d]) || $doneByDate[$d] < max(1, $totalActive)) {
        break;
    }
    $streak++;
}

// Total completions all-time
$stmt = db()->prepare('
  SELECT COUNT(*) FROM completions c JOIN assignments a ON a.id=c.assignment_id WHERE a.patient_id=?
');
$stmt->execute([$uid]);
$totalEver = (int) $stmt->fetchColumn();

// Recent completion log (last 20)
$stmt = db()->prepare('
  SELECT c.completed_date, m.name AS mudra_name, c.notes
  FROM completions c
  JOIN assignments a ON a.id=c.assignment_id
  JOIN mudras m ON m.id=a.mudra_id
  WHERE a.patient_id=?
  ORDER BY c.completed_date DESC, c.created_at DESC
  LIMIT 20
');
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$pageTitle = 'Therapy History';
require __DIR__.'/../includes/header.php';
?>
<a href="dashboard.php" class="muted">&larr; Back to dashboard</a>
<div class="page-title" style="margin-top:10px">
  <h1>📅 Therapy History</h1>
  <p class="subtitle">Track your consistency and celebrate your progress</p>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px">
  <div class="stat-card">
    <div class="icon" style="background:var(--success-light);color:var(--success)">🔥</div>
    <div class="label">Current Streak</div>
    <div class="value"><?= $streak ?> <span style="font-size:.5em;color:var(--text-muted);font-weight:500">days</span></div>
  </div>
  <div class="stat-card">
    <div class="icon">📊</div>
    <div class="label">Total Sessions</div>
    <div class="value"><?= $totalEver ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:#dbeafe;color:var(--accent)">🧘</div>
    <div class="label">Active Mudras</div>
    <div class="value"><?= $totalActive ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:var(--warning-light);color:var(--warning)">📆</div>
    <div class="label">Days Tracked</div>
    <div class="value"><?= count($doneByDate) ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 style="margin-bottom:0">Last 5 Weeks</h2>
    <div style="display:flex;gap:14px;font-size:.78em;color:var(--text-muted)">
      <span><span style="display:inline-block;width:12px;height:12px;background:var(--success);border-radius:3px;vertical-align:middle"></span> All done</span>
      <span><span style="display:inline-block;width:12px;height:12px;background:var(--warning);border-radius:3px;vertical-align:middle"></span> Partial</span>
      <span><span style="display:inline-block;width:12px;height:12px;background:var(--border);border-radius:3px;vertical-align:middle"></span> Missed</span>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:6px">
    <?php
    $labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
foreach ($labels as $l) {
    echo '<div class="muted" style="font-size:.72em;text-align:center;font-weight:600;text-transform:uppercase;letter-spacing:.5px;padding-bottom:4px">'.$l.'</div>';
}

// Find starting Monday of the period
$start = new DateTime($startDate);
$startWeekday = (int) $start->format('N'); // 1=Mon..7=Sun
// pad with empty cells
for ($i = 1; $i < $startWeekday; $i++) {
    echo '<div></div>';
}

for ($i = 0; $i < $days; $i++) {
    $d = date('Y-m-d', strtotime("$startDate +$i days"));
    $done = $doneByDate[$d] ?? 0;
    $isToday = $d === date('Y-m-d');
    $isFuture = $d > date('Y-m-d');

    if ($isFuture) {
        $bg = 'var(--bg)';
        $color = 'var(--text-muted)';
        $opacity = '.4';
    } elseif ($done === 0) {
        $bg = 'var(--bg)';
        $color = 'var(--text-muted)';
        $opacity = '1';
    } elseif ($totalActive > 0 && $done >= $totalActive) {
        $bg = 'var(--success)';
        $color = '#fff';
        $opacity = '1';
    } else {
        $bg = 'var(--warning)';
        $color = '#fff';
        $opacity = '1';
    }
    $border = $isToday ? '2px solid var(--accent)' : '1px solid var(--border)';
    $dayNum = (int) date('j', strtotime($d));
    $mLabel = $dayNum === 1 ? '<div style="font-size:.62em;opacity:.8">'.date('M', strtotime($d)).'</div>' : '';
    echo '<div title="'.$d.': '.$done.' completed" style="aspect-ratio:1;background:'.$bg.';color:'.$color.';opacity:'.$opacity.';border:'.$border.';border-radius:6px;display:flex;flex-direction:column;align-items:center;justify-content:center;font-size:.85em;font-weight:600">'.$dayNum.$mLabel.'</div>';
}
?>
  </div>
</div>

<div class="card">
  <h2>Recent Completions</h2>
  <?php if (empty($recent)) { ?>
    <div class="empty-state"><div class="icon">📝</div>No completions logged yet. Start practising and mark sessions complete!</div>
  <?php } else { ?>
    <table>
      <tr><th>Date</th><th>Mudra</th><th>Notes</th></tr>
      <?php foreach ($recent as $r) { ?>
        <tr>
          <td><strong><?= date('d M', strtotime($r['completed_date'])) ?></strong><div class="muted" style="font-size:.78em"><?= date('l', strtotime($r['completed_date'])) ?></div></td>
          <td><strong><?= e($r['mudra_name']) ?></strong></td>
          <td><span class="muted"><?= e($r['notes']) ?: '—' ?></span></td>
        </tr>
      <?php } ?>
    </table>
  <?php } ?>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
