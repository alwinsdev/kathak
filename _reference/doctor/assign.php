<?php
require __DIR__.'/../config.php';
require_role('doctor');

$patientId = (int) ($_GET['patient_id'] ?? 0);
$doctorId = current_user()['id'];

$stmt = db()->prepare('SELECT u.*, p.age, p.gender, p.phone, p.condition_notes FROM users u JOIN patients p ON p.user_id=u.id WHERE u.id=?');
$stmt->execute([$patientId]);
$patient = $stmt->fetch();
if (! $patient) {
    exit('Patient not found.');
}

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_id'])) {
        db()->prepare('UPDATE assignments SET active=0 WHERE id=? AND doctor_id=?')
            ->execute([(int) $_POST['delete_id'], $doctorId]);
        $msg = 'Assignment removed.';
    } else {
        $mid = (int) $_POST['mudra_id'];
        $time = $_POST['scheduled_time'];
        $dur = (int) $_POST['duration_min'];
        $note = trim($_POST['notes'] ?? '');
        db()->prepare('INSERT INTO assignments (patient_id,mudra_id,doctor_id,scheduled_time,duration_min,notes) VALUES (?,?,?,?,?,?)')
            ->execute([$patientId, $mid, $doctorId, $time, $dur, $note]);
        $msg = 'Mudra assigned successfully.';
    }
}

$mudras = db()->query('SELECT * FROM mudras ORDER BY name')->fetchAll();
$current = db()->prepare('
  SELECT a.*, m.name AS mudra_name FROM assignments a
  JOIN mudras m ON m.id=a.mudra_id
  WHERE a.patient_id=? AND a.active=1 ORDER BY a.scheduled_time
');
$current->execute([$patientId]);
$assignments = $current->fetchAll();

$pageTitle = 'Assign Mudras';
require __DIR__.'/../includes/header.php';
?>
<a href="dashboard.php" class="muted">&larr; Back to patients</a>
<div class="page-title" style="margin-top:10px;display:flex;justify-content:space-between;align-items:start;flex-wrap:wrap;gap:10px">
  <div>
    <h1>Prescribe Mudra Therapy</h1>
    <p class="subtitle">Managing therapy for <strong><?= e($patient['name']) ?></strong></p>
  </div>
  <a href="adherence.php?patient_id=<?= $patientId ?>" class="btn btn-secondary">📊 View Adherence Report</a>
</div>

<div class="card">
  <h2>Patient Information</h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px">
    <div><div class="muted" style="font-size:.75em;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Age</div><div style="font-weight:600;margin-top:4px"><?= e($patient['age']) ?></div></div>
    <div><div class="muted" style="font-size:.75em;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Gender</div><div style="font-weight:600;margin-top:4px"><?= e($patient['gender']) ?></div></div>
    <div><div class="muted" style="font-size:.75em;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Phone</div><div style="font-weight:600;margin-top:4px"><?= e($patient['phone']) ?></div></div>
    <div><div class="muted" style="font-size:.75em;text-transform:uppercase;letter-spacing:.5px;font-weight:600">Email</div><div style="font-weight:600;margin-top:4px;font-size:.85em"><?= e($patient['email']) ?></div></div>
  </div>
  <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border)">
    <div class="muted" style="font-size:.75em;text-transform:uppercase;letter-spacing:.5px;font-weight:600;margin-bottom:6px">Condition / Notes</div>
    <div><?= e($patient['condition_notes']) ?: '<span class="muted">No notes provided</span>' ?></div>
  </div>
</div>

<?php if ($msg) { ?><div class="alert alert-success">✓ <?= e($msg) ?></div><?php } ?>

<div class="grid-2">
  <div class="card">
    <h2>+ Add New Prescription</h2>
    <form method="post">
      <label>Mudra</label>
      <select name="mudra_id" required>
        <option value="">-- Choose a mudra --</option>
        <?php foreach ($mudras as $m) { ?>
          <option value="<?= $m['id'] ?>"><?= e($m['name']) ?> — <?= e(mb_strimwidth($m['description'], 0, 50, '…')) ?></option>
        <?php } ?>
      </select>
      <label>Scheduled Time (daily)</label>
      <input type="time" name="scheduled_time" required>
      <label>Duration (minutes)</label>
      <input type="number" name="duration_min" value="10" min="1" max="120" required>
      <label>Notes</label>
      <textarea name="notes" rows="2" placeholder="e.g. start slowly, 5 reps"></textarea>
      <button type="submit">Assign Mudra</button>
    </form>
  </div>

  <div class="card">
    <h2>Active Prescriptions</h2>
    <?php if (empty($assignments)) { ?>
      <div class="empty-state"><div class="icon">📋</div>No active prescriptions yet.</div>
    <?php } else { ?>
      <table>
        <tr><th>Mudra</th><th>Time</th><th>Min</th><th></th></tr>
        <?php foreach ($assignments as $a) { ?>
          <tr>
            <td><strong><?= e($a['mudra_name']) ?></strong><br><span class="muted" style="font-size:.8em"><?= e($a['notes']) ?></span></td>
            <td><?= e(substr($a['scheduled_time'], 0, 5)) ?></td>
            <td><?= e($a['duration_min']) ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Remove this prescription?')" style="display:inline">
                <input type="hidden" name="delete_id" value="<?= $a['id'] ?>">
                <button class="btn btn-sm btn-danger">Remove</button>
              </form>
            </td>
          </tr>
        <?php } ?>
      </table>
    <?php } ?>
  </div>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
