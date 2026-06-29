<?php $pageTitle = 'Siddha Mudra Therapy';
require __DIR__.'/includes/header.php'; ?>
<div style="text-align:center;padding:50px 20px 30px">
  <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 16px;background:var(--primary-light);color:var(--primary-dark);border-radius:20px;font-size:.82em;font-weight:600;margin-bottom:20px">
    <span style="width:7px;height:7px;background:var(--success);border-radius:50%;display:inline-block"></span>
    AI-Powered Therapy Platform
  </div>
  <h1 style="font-size:2.4em;font-weight:700;margin-bottom:14px;line-height:1.2">Healing through<br><span style="color:var(--primary)">Siddha Mudras</span></h1>
  <p style="font-size:1.05em;color:var(--text-muted);max-width:580px;margin:0 auto 28px">A bridge between classical art and rehabilitation — doctors prescribe mudra therapy, patients practice with live AI guidance.</p>
  <div>
    <a href="<?= url('login.php') ?>" class="btn" style="margin-right:8px">Login to Account</a>
    <a href="<?= url('register.php') ?>" class="btn btn-secondary">Register as Patient</a>
  </div>
</div>

<div class="grid" style="margin-top:30px">
  <div class="card">
    <div style="width:42px;height:42px;background:#dbeafe;color:var(--accent);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-bottom:12px">👨‍⚕️</div>
    <h3>For Doctors</h3>
    <p class="muted">Review patient records, prescribe mudras with daily timings, and track adherence in one clean dashboard.</p>
  </div>
  <div class="card">
    <div style="width:42px;height:42px;background:var(--primary-light);color:var(--primary-dark);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-bottom:12px">🧘</div>
    <h3>For Patients</h3>
    <p class="muted">View your prescribed therapy schedule, receive reminders, and log completion at the end of each day.</p>
  </div>
  <div class="card">
    <div style="width:42px;height:42px;background:var(--success-light);color:var(--success);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.3em;margin-bottom:12px">📷</div>
    <h3>Live AI Coach</h3>
    <p class="muted">Open your camera — our trained model recognises Siddha mudras in real time and guides your practice.</p>
  </div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
