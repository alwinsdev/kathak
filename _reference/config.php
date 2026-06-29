<?php

// config.php — central settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kathak_therapy');

define('ROBOFLOW_API_KEY', 'st5Dxl7ulBE7YAdyq8vc');
define('ROBOFLOW_MODEL_URL', 'https://serverless.roboflow.com/kathak-trainer/8');

// ── AI verification settings (adjust freely for demos — no business-logic changes needed) ──
define('AI_CONF_THRESHOLD', 0.60); // minimum detection confidence (0–1) to count as a match
define('AI_HOLD_SECONDS', 3);      // seconds the correct mudra must be held continuously to verify

session_start();

// Auto-detect base URL (works in any subfolder)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$scriptDir = preg_replace('#/(patient|doctor|includes)$#', '', $scriptDir);
if ($scriptDir === '/' || $scriptDir === '.') {
    $scriptDir = '';
}
define('BASE_URL', rtrim($scriptDir, '/'));

function url($path)
{
    return BASE_URL.'/'.ltrim($path, '/');
}

function db()
{
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }

    return $pdo;
}

function current_user()
{
    return $_SESSION['user'] ?? null;
}

function require_role($role)
{
    $u = current_user();
    if (! $u || $u['role'] !== $role) {
        header('Location: '.url('login.php'));
        exit;
    }
}

function e($s)
{
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
