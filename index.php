<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

session_start();

require __DIR__ . '/config.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/utils.php';

// Ensure the primary community exists so UI lists never come back empty.
ensureCommunity($pdo, 'general', 'General', 'Open discussion for any conspiracy angle.');

/** Safe PRG (redirect only after non-AJAX POST) */
function prg_redirect(): void {
  $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
  if ($xhr) {
    return; // never redirect AJAX
  }

  $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/\\');
  if ($base === '' || $base === '.') {
    $base = '';
  }
  $home = $base . '/';

  $qs = $_GET;
  $target = $home . ($qs ? ('?' . http_build_query($qs)) : '');

  header('Location: ' . $target, true, 303);
  exit;
}

// --------- simple page router (no redirects here) ---------
$pageName = $_GET['page'] ?? '';
if (in_array($pageName, ['about', 'creator', 'links', 'privacy'], true)) {
  render($pageName, []);
  exit;
}

// ----------------------- POST actions ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf'] ?? '';
  if (!csrf_verify($csrf)) {
    http_response_code(403);
    exit('Invalid CSRF token');
  }

  $action = $_POST['action'] ?? null;
  $xhr = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');

  if ($action === 'add') {
    $nick = $_POST['nick'] ?? 'Anon';
    $msg = $_POST['msg'] ?? '';
    $communitySlug = $_POST['community'] ?? null;
    $uid = findOrCreateUser($pdo, $nick);
    $messageId = addMessage($pdo, $uid, $msg, $communitySlug);
    $_SESSION['last'] = $msg ?: null;

    if ($xhr) {
      header('Content-Type: application/json');
      if ($messageId) {
        $row = getMessage($pdo, $messageId);
        echo json_encode(['ok' => true, 'message' => $row]);
      } else {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Unable to save message.']);
      }
      exit;
    }

    if ($messageId) {
      prg_redirect();
    }
    http_response_code(400);
    exit('Unable to save message.');
  } elseif ($action === 'comment') {
    $messageId = (int)($_POST['message_id'] ?? 0);
    $nick = $_POST['nick'] ?? 'Anon';
    $body = $_POST['body'] ?? '';

    $comment = addComment($pdo, $messageId, $nick, $body);

    if ($xhr) {
      header('Content-Type: application/json');
      if ($comment) {
        echo json_encode(['ok' => true, 'comment' => $comment]);
      } else {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Unable to save comment.']);
      }
      exit;
    }

    if ($comment) {
      prg_redirect();
    }

    http_response_code(400);
    exit('Unable to save comment.');
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
      deleteMessage($pdo, $id);
    }

    if ($xhr) {
      header('Content-Type: application/json');
      echo json_encode(['ok' => true, 'deleted' => $id]);
      exit;
    }
    prg_redirect();
  } elseif ($action === 'react') {
    $id = (int)($_POST['id'] ?? 0);
    $type = $_POST['type'] ?? 'up';
    reactMessage($pdo, $id, $type === 'down' ? 'down' : 'up');

    if ($xhr) {
      $stmt = $pdo->prepare('SELECT upvotes, downvotes FROM messages WHERE id = ?');
      $stmt->execute([$id]);
      $row = $stmt->fetch() ?: ['upvotes' => 0, 'downvotes' => 0];
      header('Content-Type: application/json');
      echo json_encode([
        'ok' => true,
        'id' => $id,
        'upvotes' => (int)$row['upvotes'],
        'downvotes' => (int)$row['downvotes'],
      ]);
      exit;
    }
    prg_redirect();
  } else {
    prg_redirect();
  }
}

// ------------------------ GET render -----------------------
$q = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'new';
$page = max(1, (int)($_GET['page'] ?? 1));
$size = 10;

$total = countMessages($pdo, $q);
$pages = max(1, (int)ceil($total / $size));
$page = min($page, $pages);
$offset = ($page - 1) * $size;

$messages = listMessages($pdo, $q, $size, $offset, $sort);
$communities = listCommunities($pdo, 8);
$activeUsers = listActiveUsers($pdo, 6);

render('home', compact('messages', 'q', 'page', 'pages', 'total', 'sort', 'communities', 'activeUsers'));
