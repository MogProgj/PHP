<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

session_start();

require __DIR__.'/config.php';
require __DIR__.'/lib/db.php';
require __DIR__.'/lib/utils.php';

/** Safe PRG (redirect only after non-AJAX POST) */
function prg_redirect(): void {
  $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
  if ($xhr) return; // never redirect AJAX

  $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/\\'); // e.g. /myapp
  if ($base === '' || $base === '.') $base = '';
  $home = $base . '/';

  // keep existing GET params (optional)
  $qs = $_GET;
  $target = $home . ($qs ? ('?' . http_build_query($qs)) : '');

  header('Location: ' . $target, true, 303);
  exit;
}

// --------- simple page router (no redirects here) ---------
$pageName = $_GET['page'] ?? '';
if (in_array($pageName, ['about','creator','links','privacy'], true)) {
  render($pageName, []);
  exit;
}

// ----------------------- POST actions ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // CSRF check first
  $csrf = $_POST['csrf'] ?? '';
  if (!csrf_verify($csrf)) { http_response_code(403); exit('Invalid CSRF token'); }

  $action = $_POST['action'] ?? null;

  if ($action === 'add') {
    $nick = $_POST['nick'] ?? 'Anon';
    $msg  = $_POST['msg']  ?? '';
    $uid  = findOrCreateUser($pdo, $nick);
    addMessage($pdo, $uid, $msg);
    $_SESSION['last'] = $msg ?: null;

    // AJAX response
    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    if ($xhr) {
      $id  = (int)$pdo->lastInsertId();
      $row = getMessage($pdo, $id) ?: ['id'=>$id,'body'=>$msg,'created_at'=>date('c'),'nickname'=>$nick,'upvotes'=>0,'downvotes'=>0];
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true,'message'=>$row]);
      exit;
    }
    // Non-AJAX → PRG
    prg_redirect();
  }
  elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) deleteMessage($pdo, $id);

    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    if ($xhr) {
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true,'deleted'=>$id]);
      exit;
    }
    prg_redirect();
  }
  elseif ($action === 'react') {
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'up';
    reactMessage($pdo, $id, $type === 'down' ? 'down' : 'up');

    $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    if ($xhr) {
      $s = $pdo->prepare('SELECT upvotes, downvotes FROM messages WHERE id = ?');
      $s->execute([$id]);
      $row = $s->fetch() ?: ['upvotes'=>0,'downvotes'=>0];
      header('Content-Type: application/json');
      echo json_encode(['ok'=>true,'id'=>$id,'upvotes'=>(int)$row['upvotes'],'downvotes'=>(int)$row['downvotes']]);
      exit;
    }
    prg_redirect();
  }
  else {
    // Unknown action: just PRG back gracefully
    prg_redirect();
  }
}

// ------------------------ GET render -----------------------
$q     = trim($_GET['q'] ?? '');
$sort  = $_GET['sort'] ?? 'new';
$page  = max(1, (int)($_GET['page'] ?? 1));
$size  = 10;

$total = countMessages($pdo, $q);
$pages = max(1, (int)ceil($total / $size));
$page  = min($page, $pages);
$offset= ($page - 1) * $size;

$messages = listMessages($pdo, $q, $size, $offset, $sort);
render('home', compact('messages','q','page','pages','total','sort'));
