<?php
require __DIR__.'/../config.php';
require_role('doctor');

$patients = db()->query("
  SELECT u.id, u.name, u.email, p.age, p.gender, p.phone, p.condition_notes,
         (SELECT COUNT(*) FROM assignments a WHERE a.patient_id=u.id AND a.active=1) AS active_mudras
  FROM users u JOIN patients p ON p.user_id=u.id
  WHERE u.role='patient' ORDER BY u.created_at DESC
")->fetchAll();

$totalPatients = count($patients);
$totalAssignments = array_sum(array_column($patients, 'active_mudras'));

$pageTitle = 'Doctor Dashboard';
require __DIR__.'/../includes/header.php';
?>
<div class="page-title">
  <h1>Patient Records</h1>
  <p class="subtitle">Manage your patients and prescribe mudra therapy.</p>
</div>

<div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));margin-bottom:20px">
  <div class="stat-card">
    <div class="icon">👥</div>
    <div class="label">Total Patients</div>
    <div class="value"><?= $totalPatients ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:var(--success-light);color:var(--success)">📋</div>
    <div class="label">Active Prescriptions</div>
    <div class="value"><?= $totalAssignments ?></div>
  </div>
  <div class="stat-card">
    <div class="icon" style="background:#dbeafe;color:var(--accent)">📅</div>
    <div class="label">Today</div>
    <div class="value" style="font-size:1.1em;font-weight:600;margin-top:14px"><?= date('d M Y') ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2 style="margin-bottom:0">All Patients</h2>
    <span class="badge badge-info"><?= $totalPatients ?> registered</span>
  </div>
  <table>
    <tr><th>Patient</th><th>Age</th><th>Gender</th><th>Phone</th><th>Condition</th><th>Active</th><th></th></tr>
    <?php foreach ($patients as $p) { ?>
      <tr>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:.85em"><?= e(strtoupper(substr($p['name'], 0, 1))) ?></div>
            <div>
              <strong><?= e($p['name']) ?></strong>
              <div class="muted" style="font-size:.82em"><?= e($p['email']) ?></div>
            </div>
          </div>
        </td>
        <td><?= e($p['age']) ?></td>
        <td><?= e($p['gender']) ?></td>
        <td><?= e($p['phone']) ?></td>
        <td style="max-width:240px"><span class="muted"><?= e($p['condition_notes']) ?></span></td>
        <td><span class="badge"><?= $p['active_mudras'] ?> mudras</span></td>
        <td>
          <a href="adherence.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm btn-secondary" style="margin-right:4px">📊 Report</a>
          <a href="assign.php?patient_id=<?= $p['id'] ?>" class="btn btn-sm">Manage</a>
        </td>
      </tr>
    <?php } ?>
    <?php if (empty($patients)) { ?>
      <tr><td colspan="7"><div class="empty-state"><div class="icon">👥</div>No registered patients yet.</div></td></tr>
    <?php } ?>
  </table>
</div>
<?php require __DIR__.'/../includes/footer.php'; ?>
