<?php
declare(strict_types=1);

function findOrCreateUser(PDO $pdo, string $nickname): int {
  $nickname = trim($nickname) ?: 'Anon';
  $s = $pdo->prepare('SELECT id FROM users WHERE nickname = ?');
  $s->execute([$nickname]);
  $row = $s->fetch();
  if ($row) return (int)$row['id'];
  $s = $pdo->prepare('INSERT INTO users(nickname) VALUES (?)');
  $s->execute([$nickname]);
  return (int)$pdo->lastInsertId();
}

function addMessage(PDO $pdo, int $userId, string $body): void {
  $body = mb_substr(trim($body), 0, 240);
  if ($body === '') return;
  $s = $pdo->prepare('INSERT INTO messages(user_id, body) VALUES (?, ?)');
  $s->execute([$userId, $body]);
}

function countMessages(PDO $pdo, string $q): int {
  $q = trim($q);
  if ($q === '') {
    return (int)$pdo->query('SELECT COUNT(*) AS c FROM messages')->fetch()['c'];
  }
  $s = $pdo->prepare('SELECT COUNT(*) AS c FROM messages WHERE body LIKE ?');
  $s->execute(['%'.$q.'%']);
  return (int)$s->fetch()['c'];
}

function listMessagesBasic(PDO $pdo, string $q, int $limit, int $offset): array {
  $q = trim($q);
  if ($q === '') {
    $s = $pdo->prepare(
      'SELECT m.id, m.body, m.created_at, u.nickname
       FROM messages m JOIN users u ON u.id = m.user_id
       ORDER BY m.id DESC
       LIMIT :limit OFFSET :offset'
    );
    $s->bindValue(':limit', $limit, PDO::PARAM_INT);
    $s->bindValue(':offset', $offset, PDO::PARAM_INT);
    $s->execute();
    return $s->fetchAll();
  }
  $s = $pdo->prepare(
    'SELECT m.id, m.body, m.created_at, u.nickname
     FROM messages m JOIN users u ON u.id = m.user_id
     WHERE m.body LIKE :like
     ORDER BY m.id DESC
     LIMIT :limit OFFSET :offset'
  );
  $s->bindValue(':like', '%'.$q.'%', PDO::PARAM_STR);
  $s->bindValue(':limit', $limit, PDO::PARAM_INT);
  $s->bindValue(':offset', $offset, PDO::PARAM_INT);
  $s->execute();
  return $s->fetchAll();
}

function deleteMessage(PDO $pdo, int $id): void {
  $s = $pdo->prepare('DELETE FROM messages WHERE id = ?');
  $s->execute([$id]);
}
function reactMessage(PDO $pdo, int $id, string $type): void {
  if ($id <= 0) return;
  if ($type === 'up')  $pdo->prepare('UPDATE messages SET upvotes = upvotes + 1 WHERE id = ?')->execute([$id]);
  if ($type === 'down')$pdo->prepare('UPDATE messages SET downvotes = downvotes + 1 WHERE id = ?')->execute([$id]);
}

function listMessages(PDO $pdo, string $q, int $limit, int $offset, string $sort='new'): array {
  $order = 'm.id DESC';
  if ($sort === 'old') $order = 'm.id ASC';
 if ($sort === 'top') {
  $order = ' (CAST(m.upvotes AS SIGNED) - CAST(m.downvotes AS SIGNED)) DESC, m.id DESC';
}
  if (trim($q) === '') {
    $s = $pdo->prepare(
      "SELECT m.id, m.body, m.created_at, m.upvotes, m.downvotes, u.nickname
       FROM messages m JOIN users u ON u.id = m.user_id
       ORDER BY $order
       LIMIT :limit OFFSET :offset"
    );
    $s->bindValue(':limit', $limit, PDO::PARAM_INT);
    $s->bindValue(':offset', $offset, PDO::PARAM_INT);
    $s->execute();
    return $s->fetchAll();
  }
  $s = $pdo->prepare(
    "SELECT m.id, m.body, m.created_at, m.upvotes, m.downvotes, u.nickname
     FROM messages m JOIN users u ON u.id = m.user_id
     WHERE m.body LIKE :like
     ORDER BY $order
     LIMIT :limit OFFSET :offset"
  );
  $s->bindValue(':like', '%'.trim($q).'%', PDO::PARAM_STR);
  $s->bindValue(':limit', $limit, PDO::PARAM_INT);
  $s->bindValue(':offset', $offset, PDO::PARAM_INT);
  $s->execute();
  return $s->fetchAll();
}
function getMessage(PDO $pdo, int $id): ?array {
  $s = $pdo->prepare('SELECT m.id, m.body, m.created_at, m.upvotes, m.downvotes, u.nickname
                      FROM messages m JOIN users u ON u.id = m.user_id
                      WHERE m.id = ?');
  $s->execute([$id]);
  $row = $s->fetch();
  return $row ?: null;
}
