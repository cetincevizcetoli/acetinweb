<?php
declare(strict_types=1);
require __DIR__ . '/../app/bootstrap.php';
if(!is_post()) redirect('index.php');
verify_csrf();
$name=trim((string)($_POST['name'] ?? '')); $message=trim((string)($_POST['message'] ?? '')); $website=trim((string)($_POST['website'] ?? ''));
$last=(int)($_SESSION['last_note_at'] ?? 0);
if($website!=='' || mb_strlen($name)<2 || mb_strlen($name)>60 || mb_strlen($message)<3 || mb_strlen($message)>300 || time()-$last<45) redirect('index.php?note=error#notlar');
$ip=(string)($_SERVER['REMOTE_ADDR'] ?? ''); $hash=$ip!==''?hash('sha256',$ip.'|fikrimvar-v7'):'';
$st=db()->prepare("INSERT INTO notes(name,message,status,ip_hash) VALUES (?,?,'pending',?)"); $st->execute([$name,$message,$hash]); $_SESSION['last_note_at']=time(); redirect('index.php?note=ok#notlar');
