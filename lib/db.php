<?php
declare(strict_types=1);

function findOrCreateUser(PDO $pdo, string $nickname): int {
  $nickname = trim($nickname) ?: 'Anon';
  $stmt = $pdo->prepare('SELECT id FROM users WHERE nickname = ?');
  $stmt->execute([$nickname]);
  $row = $stmt->fetch();
  if ($row) {
    return (int)$row['id'];
  }
  $stmt = $pdo->prepare('INSERT INTO users(nickname) VALUES (?)');
  $stmt->execute([$nickname]);
  return (int)$pdo->lastInsertId();
}

function recordUserActivity(PDO $pdo, int $userId, string $kind): void {
  if ($userId <= 0) {
    return;
  }
  $messages = $kind === 'message' ? 1 : 0;
  $comments = $kind === 'comment' ? 1 : 0;
  $sql = 'INSERT INTO user_activity (user_id, last_seen, messages, comments)
          VALUES (:user_id, NOW(), :messages, :comments)
          ON DUPLICATE KEY UPDATE
            last_seen = VALUES(last_seen),
            messages = messages + VALUES(messages),
            comments = comments + VALUES(comments)';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':user_id' => $userId,
    ':messages' => $messages,
    ':comments' => $comments,
  ]);
}

function ensureCommunity(PDO $pdo, string $slug, string $name = '', string $tagline = ''): int {
  $slug = strtolower(trim($slug));
  if ($slug === '') {
    return 0;
  }
  $stmt = $pdo->prepare('SELECT id FROM communities WHERE slug = ?');
  $stmt->execute([$slug]);
  $id = $stmt->fetchColumn();
  if ($id) {
    return (int)$id;
  }
  $name = $name !== '' ? $name : ucfirst($slug);
  $insert = $pdo->prepare('INSERT INTO communities(slug, name, tagline) VALUES (?, ?, ?)');
  $insert->execute([$slug, $name, $tagline]);
  return (int)$pdo->lastInsertId();
}

function assignMessageToCommunity(PDO $pdo, int $messageId, ?string $slug = null): void {
  if ($messageId <= 0) {
    return;
  }
  $slug = $slug !== null ? strtolower(trim($slug)) : 'general';
  if ($slug === '') {
    $slug = 'general';
  }
  $defaults = [
    'general' => ['General', 'Open discussion for any conspiracy angle.'],
    'deepstate' => ['Deep State', 'Shadow governments, cover-ups, geopolitics.'],
    'cryptids' => ['Cryptids', 'Strange creatures and unexplained sightings.'],
  ];
  $meta = $defaults[$slug] ?? [ucfirst($slug), ''];
  $communityId = ensureCommunity($pdo, $slug, $meta[0], $meta[1]);
  if ($communityId <= 0) {
    return;
  }
  $sql = 'INSERT INTO message_topics (message_id, community_id)
          VALUES (:message_id, :community_id)
          ON DUPLICATE KEY UPDATE community_id = VALUES(community_id)';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':message_id' => $messageId,
    ':community_id' => $communityId,
  ]);
  recordCommunityActivity($pdo, $communityId, 'message');
}

function findCommunityIdForMessage(PDO $pdo, int $messageId): ?int {
  if ($messageId <= 0) {
    return null;
  }
  $stmt = $pdo->prepare('SELECT community_id FROM message_topics WHERE message_id = ?');
  $stmt->execute([$messageId]);
  $id = $stmt->fetchColumn();
  return $id ? (int)$id : null;
}

function recordCommunityActivity(PDO $pdo, int $communityId, string $kind): void {
  if ($communityId <= 0) {
    return;
  }
  $posts = $kind === 'message' ? 1 : 0;
  $comments = $kind === 'comment' ? 1 : 0;
  $sql = 'INSERT INTO community_trends(community_id, day, posts, comments)
          VALUES (:community_id, CURRENT_DATE(), :posts, :comments)
          ON DUPLICATE KEY UPDATE
            posts = posts + VALUES(posts),
            comments = comments + VALUES(comments)';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([
    ':community_id' => $communityId,
    ':posts' => $posts,
    ':comments' => $comments,
  ]);
}

function addMessage(PDO $pdo, int $userId, string $body, ?string $communitySlug = null): ?int {
  $body = mb_substr(trim($body), 0, 240);
  if ($body === '') {
    return null;
  }
  $stmt = $pdo->prepare('INSERT INTO messages(user_id, body) VALUES (?, ?)');
  $stmt->execute([$userId, $body]);
  $messageId = (int)$pdo->lastInsertId();
  recordUserActivity($pdo, $userId, 'message');
  assignMessageToCommunity($pdo, $messageId, $communitySlug);
  return $messageId;
}

function countMessages(PDO $pdo, string $q): int {
  $q = trim($q);
  if ($q === '') {
    return (int)$pdo->query('SELECT COUNT(*) AS c FROM messages')->fetch()['c'];
  }
  $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM messages WHERE body LIKE ?');
  $stmt->execute(['%' . $q . '%']);
  return (int)$stmt->fetch()['c'];
}

function listMessagesBasic(PDO $pdo, string $q, int $limit, int $offset): array {
  $q = trim($q);
  if ($q === '') {
    $sql = 'SELECT m.id, m.body, m.created_at, u.nickname,
                   c.name AS community_name, c.slug AS community_slug
            FROM messages m
            JOIN users u ON u.id = m.user_id
            LEFT JOIN message_topics mt ON mt.message_id = m.id
            LEFT JOIN communities c ON c.id = mt.community_id
            ORDER BY m.id DESC
            LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
  }
  $sql = 'SELECT m.id, m.body, m.created_at, u.nickname,
                 c.name AS community_name, c.slug AS community_slug
          FROM messages m
          JOIN users u ON u.id = m.user_id
          LEFT JOIN message_topics mt ON mt.message_id = m.id
          LEFT JOIN communities c ON c.id = mt.community_id
          WHERE m.body LIKE :like
          ORDER BY m.id DESC
          LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':like', '%' . $q . '%', PDO::PARAM_STR);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  return $stmt->fetchAll();
}

function deleteMessage(PDO $pdo, int $id): void {
  $stmt = $pdo->prepare('DELETE FROM messages WHERE id = ?');
  $stmt->execute([$id]);
}

function reactMessage(PDO $pdo, int $id, string $type): void {
  if ($id <= 0) {
    return;
  }
  if ($type === 'up') {
    $pdo->prepare('UPDATE messages SET upvotes = upvotes + 1 WHERE id = ?')->execute([$id]);
  }
  if ($type === 'down') {
    $pdo->prepare('UPDATE messages SET downvotes = downvotes + 1 WHERE id = ?')->execute([$id]);
  }
}

function listMessages(PDO $pdo, string $q, int $limit, int $offset, string $sort = 'new'): array {
  $order = 'm.id DESC';
  if ($sort === 'old') {
    $order = 'm.id ASC';
  } elseif ($sort === 'top') {
    $order = ' (CAST(m.upvotes AS SIGNED) - CAST(m.downvotes AS SIGNED)) DESC, m.id DESC';
  }
  $base = 'SELECT m.id, m.body, m.created_at, m.upvotes, m.downvotes, u.nickname,
                  c.name AS community_name, c.slug AS community_slug
           FROM messages m
           JOIN users u ON u.id = m.user_id
           LEFT JOIN message_topics mt ON mt.message_id = m.id
           LEFT JOIN communities c ON c.id = mt.community_id';
  if (trim($q) === '') {
    $sql = $base . " ORDER BY $order LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return hydrateComments($pdo, $rows);
  }
  $sql = $base . ' WHERE m.body LIKE :like ORDER BY ' . $order . ' LIMIT :limit OFFSET :offset';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':like', '%' . trim($q) . '%', PDO::PARAM_STR);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  return hydrateComments($pdo, $rows);
}

function getMessage(PDO $pdo, int $id): ?array {
  $sql = 'SELECT m.id, m.body, m.created_at, m.upvotes, m.downvotes, u.nickname,
                 c.name AS community_name, c.slug AS community_slug
          FROM messages m
          JOIN users u ON u.id = m.user_id
          LEFT JOIN message_topics mt ON mt.message_id = m.id
          LEFT JOIN communities c ON c.id = mt.community_id
          WHERE m.id = ?';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) {
    return null;
  }
  $with = hydrateComments($pdo, [$row]);
  return $with[0] ?? $row;
}

function addComment(PDO $pdo, int $messageId, string $nickname, string $body): ?array {
  $messageId = max(0, $messageId);
  $body = mb_substr(trim($body), 0, 240);
  $nickname = mb_substr(trim($nickname), 0, 60) ?: 'Anon';
  if ($messageId === 0 || $body === '') {
    return null;
  }

  $stmt = $pdo->prepare('SELECT user_id FROM messages WHERE id = ?');
  $stmt->execute([$messageId]);
  $messageOwner = $stmt->fetch();
  if (!$messageOwner) {
    return null;
  }

  $commenterId = findOrCreateUser($pdo, $nickname);
  recordUserActivity($pdo, $commenterId, 'comment');
  $communityId = findCommunityIdForMessage($pdo, $messageId);
  if ($communityId) {
    recordCommunityActivity($pdo, $communityId, 'comment');
  }

  $insert = $pdo->prepare('INSERT INTO comments(message_id, nickname, body) VALUES (?, ?, ?)');
  $insert->execute([$messageId, $nickname, $body]);
  $commentId = (int)$pdo->lastInsertId();

  return getComment($pdo, $commentId);
}

function getComment(PDO $pdo, int $id): ?array {
  $stmt = $pdo->prepare('SELECT id, message_id, nickname, body, created_at FROM comments WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row) {
    return null;
  }
  $row['id'] = (int)$row['id'];
  $row['message_id'] = (int)$row['message_id'];
  return $row;
}

function listCommentsForMessages(PDO $pdo, array $messageIds): array {
  $ids = array_values(array_unique(array_filter(array_map('intval', $messageIds), fn ($v) => $v > 0)));
  if (!$ids) {
    return [];
  }
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $sql = "SELECT id, message_id, nickname, body, created_at
          FROM comments
          WHERE message_id IN ($placeholders)
          ORDER BY created_at ASC, id ASC";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($ids);
  $grouped = [];
  while ($row = $stmt->fetch()) {
    $mid = (int)$row['message_id'];
    $grouped[$mid][] = [
      'id' => (int)$row['id'],
      'message_id' => $mid,
      'nickname' => $row['nickname'],
      'body' => $row['body'],
      'created_at' => $row['created_at'],
    ];
  }
  return $grouped;
}

function hydrateComments(PDO $pdo, array $messages): array {
  if (!$messages) {
    return [];
  }
  $ids = array_map(fn ($row) => (int)($row['id'] ?? 0), $messages);
  $comments = listCommentsForMessages($pdo, $ids);
  foreach ($messages as &$row) {
    $id = (int)($row['id'] ?? 0);
    $row['comments'] = $comments[$id] ?? [];
  }
  unset($row);
  return $messages;
}

function listCommunities(PDO $pdo, int $limit = 6): array {
  $sql = 'SELECT c.id, c.slug, c.name, c.tagline,
                 COALESCE(SUM(ct.posts), 0) AS posts_7d,
                 COALESCE(SUM(ct.comments), 0) AS comments_7d
          FROM communities c
          LEFT JOIN community_trends ct
            ON ct.community_id = c.id AND ct.day >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY)
          GROUP BY c.id, c.slug, c.name, c.tagline
          ORDER BY posts_7d DESC, comments_7d DESC, c.created_at DESC
          LIMIT :limit';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  return array_map(static function ($row) {
    return [
      'slug' => $row['slug'],
      'name' => $row['name'],
      'tagline' => $row['tagline'],
      'posts_7d' => (int)$row['posts_7d'],
      'comments_7d' => (int)$row['comments_7d'],
    ];
  }, $rows ?: []);
}

function listActiveUsers(PDO $pdo, int $limit = 5): array {
  $sql = 'SELECT u.nickname, ua.messages, ua.comments, ua.last_seen
          FROM user_activity ua
          JOIN users u ON u.id = ua.user_id
          ORDER BY (ua.messages + ua.comments) DESC, ua.last_seen DESC
          LIMIT :limit';
  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->execute();
  $rows = $stmt->fetchAll();
  return array_map(static function ($row) {
    return [
      'nickname' => $row['nickname'],
      'messages' => (int)$row['messages'],
      'comments' => (int)$row['comments'],
      'last_seen' => $row['last_seen'],
    ];
  }, $rows ?: []);
}
