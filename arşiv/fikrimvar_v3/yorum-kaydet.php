<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php#notlar'); exit; }
$token = (string) ($_POST['csrf'] ?? '');
$name = trim((string) ($_POST['name'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
$website = trim((string) ($_POST['website'] ?? ''));
$last = (int) ($_SESSION['last_note_at'] ?? 0);
$valid = hash_equals((string) ($_SESSION['csrf_token'] ?? ''), $token)
    && $website === ''
    && mb_strlen($name) >= 2 && mb_strlen($name) <= 60
    && mb_strlen($message) >= 3 && mb_strlen($message) <= 300
    && (time() - $last) >= 45;

if (!$valid) { header('Location: index.php?note=error#notlar'); exit; }
$path = DATA_DIR . '/notes-pending.json';
$pending = load_json('notes-pending.json');
$pending[] = ['name'=>$name,'message'=>$message,'date'=>date('Y-m-d H:i:s'),'published'=>false];
$ok = file_put_contents($path, json_encode($pending, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE), LOCK_EX) !== false;
if ($ok) { $_SESSION['last_note_at'] = time(); $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); }
header('Location: index.php?note=' . ($ok ? 'ok' : 'error') . '#notlar');
