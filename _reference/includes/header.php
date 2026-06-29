<?php
require_once __DIR__.'/../config.php';
$u = current_user();
$initials = $u ? strtoupper(substr($u['name'], 0, 1)) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Siddha Mudra Therapy') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{
    --primary:#0d9488;--primary-dark:#0f766e;--primary-light:#ccfbf1;
    --accent:#1e40af;--bg:#f8fafc;--surface:#fff;--border:#e2e8f0;
    --text:#0f172a;--text-muted:#64748b;
    --success:#16a34a;--success-light:#dcfce7;
    --warning:#ea580c;--warning-light:#ffedd5;
    --danger:#dc2626;--danger-light:#fee2e2;
    --shadow-sm:0 1px 2px rgba(15,23,42,.06);
    --shadow:0 1px 3px rgba(15,23,42,.08),0 1px 2px rgba(15,23,42,.04);
    --shadow-lg:0 10px 25px -5px rgba(15,23,42,.08),0 4px 10px -3px rgba(15,23,42,.04);
  }
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;font-size:14px;line-height:1.5;-webkit-font-smoothing:antialiased}
  a{color:var(--primary);text-decoration:none}
  a:hover{color:var(--primary-dark)}
  nav{background:var(--surface);border-bottom:1px solid var(--border);padding:12px 32px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;box-shadow:var(--shadow-sm);position:sticky;top:0;z-index:100}
  nav .brand{font-size:1.05em;font-weight:700;color:var(--text);display:flex;align-items:center;gap:10px}
  nav .brand .logo{width:34px;height:34px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:9px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.1em;box-shadow:var(--shadow)}
  nav .brand small{display:block;font-weight:500;color:var(--text-muted);font-size:.7em;letter-spacing:.3px;text-transform:uppercase}
  nav .links{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
  nav .links a{color:var(--text);padding:8px 14px;border-radius:6px;font-weight:500;font-size:.88em;transition:.15s}
  nav .links a:hover{background:var(--bg);color:var(--primary)}
  nav .links a.logout{color:var(--danger)}
  nav .user-chip{display:flex;align-items:center;gap:8px;padding:5px 12px 5px 5px;background:var(--bg);border:1px solid var(--border);border-radius:20px;font-size:.82em;color:var(--text);font-weight:500}
  nav .user-chip .avatar{width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,var(--primary),var(--accent));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:600;font-size:.78em}
  nav .user-chip .role{font-size:.75em;color:var(--text-muted);text-transform:capitalize}
  .container{max-width:1200px;margin:28px auto;padding:0 24px}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:24px;margin-bottom:18px;box-shadow:var(--shadow-sm)}
  .card-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;padding-bottom:14px;border-bottom:1px solid var(--border)}
  h1{font-size:1.6em;font-weight:700;color:var(--text);margin-bottom:6px}
  h2{font-size:1.1em;font-weight:600;color:var(--text);margin-bottom:14px}
  h3{font-size:1em;font-weight:600;color:var(--text);margin-bottom:10px}
  .page-title{margin-bottom:22px}
  .page-title h1{font-size:1.7em}
  .page-title .subtitle{color:var(--text-muted);font-size:.95em;margin-top:4px}
  label{display:block;margin:14px 0 6px;font-size:.82em;font-weight:500;color:var(--text);letter-spacing:.1px}
  input,select,textarea{width:100%;padding:10px 12px;border-radius:8px;border:1px solid var(--border);background:var(--surface);color:var(--text);font-size:.92em;font-family:inherit;transition:.15s}
  input:focus,select:focus,textarea:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px var(--primary-light)}
  textarea{resize:vertical;min-height:60px}
  button,.btn{background:var(--primary);color:#fff;border:none;padding:10px 22px;font-size:.9em;font-weight:600;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block;margin-top:12px;transition:.15s;font-family:inherit;line-height:1.4}
  button:hover,.btn:hover{background:var(--primary-dark);color:#fff;transform:translateY(-1px);box-shadow:var(--shadow)}
  .btn-secondary{background:var(--surface);color:var(--text);border:1px solid var(--border)}
  .btn-secondary:hover{background:var(--bg);color:var(--text);border-color:var(--primary)}
  .btn-sm{padding:6px 14px;font-size:.82em;margin-top:0}
  .btn-danger{background:var(--danger)}
  .btn-danger:hover{background:#b91c1c}
  .btn-success{background:var(--success)}
  .btn-success:hover{background:#15803d}
  .btn-block{display:block;width:100%;text-align:center}
  table{width:100%;border-collapse:collapse;margin-top:6px}
  th,td{padding:12px 14px;text-align:left;border-bottom:1px solid var(--border);font-size:.9em;vertical-align:middle}
  th{color:var(--text-muted);font-size:.72em;text-transform:uppercase;letter-spacing:.6px;font-weight:600;background:var(--bg)}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:#fafbfc}
  .badge{display:inline-block;padding:3px 10px;border-radius:12px;font-size:.72em;font-weight:600;background:var(--primary-light);color:var(--primary-dark);text-transform:uppercase;letter-spacing:.3px}
  .badge-done{background:var(--success-light);color:var(--success)}
  .badge-pending{background:var(--warning-light);color:var(--warning)}
  .badge-info{background:#dbeafe;color:#1e40af}
  .alert{padding:12px 16px;border-radius:8px;margin-bottom:16px;font-size:.9em;display:flex;align-items:center;gap:10px}
  .alert-error{background:var(--danger-light);border-left:3px solid var(--danger);color:#7f1d1d}
  .alert-success{background:var(--success-light);border-left:3px solid var(--success);color:#14532d}
  .alert-info{background:#dbeafe;border-left:3px solid var(--accent);color:#1e3a8a}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:16px}
  .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:18px}
  @media(max-width:720px){.grid-2{grid-template-columns:1fr}}
  .muted{color:var(--text-muted);font-size:.88em}
  .stat-card{background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:18px;box-shadow:var(--shadow-sm)}
  .stat-card .label{font-size:.78em;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;font-weight:600}
  .stat-card .value{font-size:1.8em;font-weight:700;color:var(--text);margin-top:6px}
  .stat-card .icon{float:right;width:38px;height:38px;border-radius:9px;background:var(--primary-light);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.1em}
  .empty-state{text-align:center;padding:40px 20px;color:var(--text-muted)}
  .empty-state .icon{font-size:2.5em;margin-bottom:10px;opacity:.5}
  .auth-wrapper{max-width:440px;margin:60px auto;padding:0 20px}
  .auth-wrapper .card{padding:32px}
  .auth-wrapper .auth-header{text-align:center;margin-bottom:24px}
  .auth-wrapper .auth-header .logo{width:54px;height:54px;background:linear-gradient(135deg,var(--primary),var(--primary-dark));border-radius:14px;display:inline-flex;align-items:center;justify-content:center;color:#fff;font-size:1.6em;margin-bottom:12px;box-shadow:var(--shadow-lg)}
  .auth-wrapper h1{font-size:1.4em;margin-bottom:4px}
  .auth-wrapper .subtitle{color:var(--text-muted);font-size:.9em}
  code{background:var(--bg);padding:2px 6px;border-radius:4px;font-size:.85em;border:1px solid var(--border)}
  details summary{cursor:pointer;color:var(--text-muted);font-size:.88em;padding:6px 0}
  details summary:hover{color:var(--primary)}
  pre{background:var(--bg);padding:12px;border-radius:6px;overflow-x:auto;font-size:.8em;max-height:200px;border:1px solid var(--border)}
</style>
</head>
<body>
<nav>
  <div class="brand">
    <div class="logo">🪔</div>
    <div>Siddha Mudra Therapy<small>Healing through movement</small></div>
  </div>
  <div class="links">
    <?php if ($u) { ?>
      <a href="<?= url($u['role'].'/dashboard.php') ?>">Dashboard</a>
      <div class="user-chip">
        <div class="avatar"><?= e($initials) ?></div>
        <div><?= e($u['name']) ?><div class="role"><?= e($u['role']) ?></div></div>
      </div>
      <a href="<?= url('logout.php') ?>" class="logout">Logout</a>
    <?php } else { ?>
      <a href="<?= url('login.php') ?>">Login</a>
      <a href="<?= url('register.php') ?>" class="btn btn-sm" style="margin-top:0;color:#fff">Sign Up</a>
    <?php } ?>
  </div>
</nav>
<div class="container">
