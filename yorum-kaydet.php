<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
if(!is_post()) redirect('index.php');
verify_csrf();
$ip=(string)($_SERVER['REMOTE_ADDR'] ?? ''); $hash=$ip!==''?hash('sha256',$ip.'|fikrimvar-v7'):'';
$name=trim((string)($_POST['name'] ?? '')); $message=trim((string)($_POST['message'] ?? '')); $website=trim((string)($_POST['website'] ?? ''));
$last=(int)($_SESSION['last_note_at'] ?? 0);
$recentFromIp=0;
if($hash!==''){
    $st=db()->prepare("SELECT COUNT(*) FROM notes WHERE ip_hash=? AND created_at >= datetime('now','-45 seconds')");
    $st->execute([$hash]);
    $recentFromIp=(int)$st->fetchColumn();
}
if($website!=='' || text_length($name)<2 || text_length($name)>60 || text_length($message)<3 || text_length($message)>300 || time()-$last<45 || $recentFromIp>0) redirect('index.php?note=error#notlar');
$st=db()->prepare("INSERT INTO notes(name,message,status,ip_hash) VALUES (?,?,'pending',?)"); $st->execute([$name,$message,$hash]); $_SESSION['last_note_at']=time(); redirect('index.php?note=ok#notlar');
