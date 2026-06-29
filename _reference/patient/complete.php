<?php

// patient/complete.php — records an AI-verified completion (JSON endpoint)
require __DIR__.'/../config.php';
require_role('patient');
header('Content-Type: application/json');

$uid = current_user()['id'];
$today = date('Y-m-d');
$aid = (int) ($_POST['assignment_id'] ?? 0);

$conf = isset($_POST['confidence']) ? (float) $_POST['confidence'] : null;
if ($conf !== null) {
    $conf = max(0, min(1, $conf));
}

// Ownership + active check (same rule used on the dashboard)
$chk = db()->prepare('SELECT id FROM assignments WHERE id=? AND patient_id=? AND active=1');
$chk->execute([$aid, $uid]);
if (! $chk->fetch()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid or unauthorized assignment']);
    exit;
}

// Was it already completed today? (the unique key makes the insert idempotent regardless)
$done = db()->prepare('SELECT id FROM completions WHERE assignment_id=? AND completed_date=?');
$done->execute([$aid, $today]);
$already = (bool) $done->fetch();

db()->prepare('INSERT IGNORE INTO completions (assignment_id, completed_date, confidence) VALUES (?,?,?)')
    ->execute([$aid, $today, $conf]);

echo json_encode(['ok' => true, 'already' => $already]);
