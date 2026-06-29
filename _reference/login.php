<?php
require __DIR__.'/config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'patient';
    $stmt = db()->prepare('SELECT * FROM users WHERE email=? AND role=?');
    $stmt->execute([$email, $role]);
    $u = $stmt->fetch();
    if ($u && password_verify($pass, $u['password'])) {
        $_SESSION['user'] = ['id' => $u['id'], 'name' => $u['name'], 'role' => $u['role'], 'email' => $u['email']];
        header('Location: '.url($u['role'].'/dashboard.php').'');
        exit;
    } else {
        $err = 'Invalid credentials for this role.';
    }
}
$pageTitle = 'Login';
require __DIR__.'/includes/header.php';
?>
<div class="auth-wrapper">
<div class="card">
  <div class="auth-header">
    <div class="logo">🪔</div>
    <h1>Welcome back</h1>
    <div class="subtitle">Sign in to continue your therapy</div>
  </div>
  <?php if ($err) { ?><div class="alert alert-error">⚠️ <?= e($err) ?></div><?php } ?>
  <form method="post">
    <label>Login as</label>
    <select name="role"><option value="patient">Patient</option><option value="doctor">Doctor</option></select>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <button type="submit" class="btn-block">Sign In</button>
    <p class="muted" style="margin-top:18px;text-align:center">No patient account? <a href="<?= url('register.php') ?>">Create one</a></p>
    <div class="alert alert-info" style="margin-top:16px;font-size:.82em">Demo doctor: <code>doctor@kathak.com</code> / <code>doctor123</code></div>
  </form>
</div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
