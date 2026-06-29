<?php
require __DIR__.'/config.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $age = (int) ($_POST['age'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $cond = trim($_POST['condition_notes'] ?? '');
    if (! $name || ! $email || ! $pass) {
        $err = 'Name, email and password are required.';
    } else {
        try {
            $pdo = db();
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'patient')");
            $stmt->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            $uid = $pdo->lastInsertId();
            $pdo->prepare('INSERT INTO patients (user_id,age,gender,phone,condition_notes) VALUES (?,?,?,?,?)')
                ->execute([$uid, $age, $gender, $phone, $cond]);
            $pdo->commit();
            $_SESSION['user'] = ['id' => $uid, 'name' => $name, 'role' => 'patient', 'email' => $email];
            header('Location: '.url('patient/dashboard.php').'');
            exit;
        } catch (PDOException $ex) {
            $err = $ex->getCode() === '23000' ? 'Email already registered.' : 'DB error: '.$ex->getMessage();
        }
    }
}
$pageTitle = 'Register';
require __DIR__.'/includes/header.php';
?>
<div class="auth-wrapper" style="max-width:520px">
<div class="card">
  <div class="auth-header">
    <div class="logo">🪔</div>
    <h1>Create your account</h1>
    <div class="subtitle">Join Siddha Mudra Therapy</div>
  </div>
  <?php if ($err) { ?><div class="alert alert-error">⚠️ <?= e($err) ?></div><?php } ?>
  <form method="post">
    <label>Full Name</label><input name="name" required>
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <div class="grid" style="grid-template-columns:1fr 1fr">
      <div><label>Age</label><input type="number" name="age" min="1" max="120"></div>
      <div><label>Gender</label>
        <select name="gender"><option value="">--</option><option>Male</option><option>Female</option><option>Other</option></select>
      </div>
    </div>
    <label>Phone</label><input name="phone">
    <label>Condition / Reason for therapy</label><textarea name="condition_notes" rows="3" placeholder="e.g. post-stroke finger stiffness, arthritis…"></textarea>
    <button type="submit" class="btn-block">Create Account</button>
    <p class="muted" style="margin-top:18px;text-align:center">Already registered? <a href="<?= url('login.php') ?>">Sign in</a></p>
  </form>
</div>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
