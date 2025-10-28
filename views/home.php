<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cave of Conspiracies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="assets/styles.css" rel="stylesheet">
</head>
<body>
  <div class="container">
    <header class="header">
      <div class="brand">
        <div class="brand-badge">CoC</div>
        <div>
          <h1>Cave of Conspiracies</h1>
          <p class="tag">Post. Ponder. Poke holes.</p>
        </div>
      </div>
      <nav class="header-links">
        <a href="?page=about">About</a>
        <a href="?page=creator">Creator</a>
        <a href="?page=links">Socials</a>
        <a href="?page=privacy">Privacy</a>
      </nav>
    </header>

    <?php if (!empty($_SESSION['last'])): ?>
      <div class="flash">Saved: “<?= htmlspecialchars($_SESSION['last']); $_SESSION['last'] = null; ?>”</div>
    <?php endif; ?>

    <main class="layout">
      <section class="column main">
        <article class="card">
          <h2>Share a conspiracy</h2>
          <p class="helper">Max 240 characters. Keep it wild yet civil.</p>
          <form method="post" action="" id="composeForm">
            <div class="row">
              <input type="text" name="nick" placeholder="Alias" maxlength="60" required>
              <button class="btn">Post</button>
            </div>
            <textarea name="msg" id="msgBox" placeholder="Type your theory…" maxlength="240" required></textarea>
            <div class="row space-between">
              <small class="muted" id="countHint">0 / 240</small>
            </div>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
          </form>
        </article>

        <article class="card">
          <h2>Threads</h2>
          <p class="helper">
            <?= isset($total) ? (int)$total : 0 ?> total<?= !empty($q) ? ' · filtering for “'.htmlspecialchars($q).'”' : '' ?>
          </p>

          <form method="get" action="" class="row filter">
            <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search conspiracies">
            <select name="sort" onchange="this.form.submit()">
              <option value="new" <?= ($sort ?? 'new') === 'new' ? 'selected' : '' ?>>Newest</option>
              <option value="old" <?= ($sort ?? '') === 'old' ? 'selected' : '' ?>>Oldest</option>
              <option value="top" <?= ($sort ?? '') === 'top' ? 'selected' : '' ?>>Top (votes)</option>
            </select>
            <button class="btn-outline">Apply</button>
          </form>

          <?php if (empty($messages)): ?>
            <div class="msg msg-empty"><p>No posts<?= !empty($q) ? ' for “'.htmlspecialchars($q).'”' : '' ?> yet.</p></div>
          <?php else: foreach ($messages as $m): ?>
            <article class="msg" id="msg_<?= (int)$m['id'] ?>">
              <header class="msg-head">
                <span class="badge"><?= htmlspecialchars($m['nickname']) ?></span>
                <span class="time"><?= htmlspecialchars($m['created_at']) ?></span>
                <form method="post" action="" class="actions">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="type" value="up">
                  <input type="hidden" name="action" value="react">
                  <?= csrf_field() ?>
                  <button class="btn-outline" data-react="up" data-id="<?= (int)$m['id'] ?>">▲ <span id="up_<?= (int)$m['id'] ?>"><?= (int)($m['upvotes'] ?? 0) ?></span></button>
                </form>
                <form method="post" action="" class="actions">
                  <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="type" value="down">
                  <input type="hidden" name="action" value="react">
                  <?= csrf_field() ?>
                  <button class="btn-outline" data-react="down" data-id="<?= (int)$m['id'] ?>">▼ <span id="down_<?= (int)$m['id'] ?>"><?= (int)($m['downvotes'] ?? 0) ?></span></button>
                </form>
                <button class="btn-danger" data-delete data-id="<?= (int)$m['id'] ?>" data-snippet="<?= htmlspecialchars(mb_strimwidth($m['body'], 0, 60, '…')) ?>">Delete</button>
              </header>
              <p><?= nl2br(htmlspecialchars($m['body'])) ?></p>

              <section class="comments" data-message="<?= (int)$m['id'] ?>">
                <h3>Comments</h3>
                <div class="comments-list" id="comments_<?= (int)$m['id'] ?>">
                  <?php if (empty($m['comments'])): ?>
                    <p class="comment-empty muted">No comments yet.</p>
                  <?php else: foreach ($m['comments'] as $c): ?>
                    <article class="comment" data-comment-id="<?= (int)$c['id'] ?>">
                      <header class="comment-head">
                        <span class="badge badge-comment"><?= htmlspecialchars($c['nickname']) ?></span>
                        <span class="time"><?= htmlspecialchars($c['created_at']) ?></span>
                      </header>
                      <p><?= nl2br(htmlspecialchars($c['body'])) ?></p>
                    </article>
                  <?php endforeach; endif; ?>
                </div>
                <form method="post" action="" class="comment-form" data-message-id="<?= (int)$m['id'] ?>">
                  <div class="row">
                    <input type="text" name="nick" placeholder="Alias" maxlength="60" required>
                    <button class="btn-outline">Comment</button>
                  </div>
                  <textarea name="body" placeholder="Share your take…" maxlength="240" required rows="3"></textarea>
                  <?= csrf_field() ?>
                  <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                  <input type="hidden" name="action" value="comment">
                </form>
              </section>
            </article>
          <?php endforeach; endif; ?>

          <?php if (($pages ?? 1) > 1): ?>
            <nav class="pager">
              <?php
                $base = strtok($_SERVER['REQUEST_URI'], '?') ?: '';
                $qs = function($p) use ($q, $sort) {
                  $parts = ['page=' . $p];
                  if (!empty($q)) {
                    $parts[] = 'q=' . urlencode($q);
                  }
                  if (!empty($sort)) {
                    $parts[] = 'sort=' . urlencode($sort);
                  }
                  return '?' . implode('&', $parts);
                };
              ?>
              <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p === ($page ?? 1)): ?>
                  <span class="active"><?= $p ?></span>
                <?php else: ?>
                  <a href="<?= $base . $qs($p) ?>"><?= $p ?></a>
                <?php endif; ?>
              <?php endfor; ?>
            </nav>
          <?php endif; ?>
        </article>
      </section>

      <aside class="column side">
        <article class="card">
          <h2>Quick search</h2>
          <form method="get" action="" class="row">
            <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search conspiracies">
            <button class="btn-outline">Search</button>
          </form>
          <p class="helper">Clear the box to show everything.</p>
        </article>

        <article class="card">
          <h2>About</h2>
          <p class="helper">A tiny Reddit-style sandbox: PHP + PDO + MySQL, reactions, CSRF, pagination, and now threaded comments.</p>
        </article>
      </aside>
    </main>
  </div>

  <div id="deleteModal" class="modal" aria-hidden="true">
    <div class="modal-panel">
      <h2>Delete message?</h2>
      <p class="helper">You're about to delete:</p>
      <div id="deletePreview" class="msg"></div>
      <form id="deleteForm" method="post" action="" class="row">
        <input type="hidden" name="id" value="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button type="button" class="btn-outline" data-close>Cancel</button>
        <button class="btn-danger">Delete</button>
      </form>
    </div>
  </div>

  <script src="assets/app.js"></script>
  <script>
    const box = document.querySelector('#msgBox');
    const hint = document.querySelector('#countHint');
    if (box && hint) {
      const update = () => hint.textContent = `${box.value.length} / ${box.maxLength}`;
      box.addEventListener('input', update);
      update();
    }
  </script>
</body>
</html>
