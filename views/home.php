<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cave of Conspiracies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="assets/styles.css" rel="stylesheet">
  <link href="assets/animations.css" rel="stylesheet">
</head>
<body>
  <div class="container">
    <header class="header">
      <div class="brand">
        <div class="brand-badge">CoC</div>
        <div>
          <h1>Cave of Conspiracies</h1>
          <span class="tag">Post. Ponder. Poke holes.</span>
        </div>
      </div>
      <div class="header-right">
        <a href="?page=about" class="btn-outline">About</a>
        <a href="?page=creator" class="btn-outline">Creator</a>
        <a href="?page=links" class="btn-outline">Socials</a>
        <a href="?page=privacy" class="btn-outline">Privacy</a>
        <select id="themePicker" class="btn-outline">
          <option value="dark">Dark</option>
          <option value="light">Light</option>
          <option value="blue">Blue</option>
          <option value="midnight">Midnight</option>
          <option value="dusk">Dusk</option>
        </select>
      </div>
    </header>

    <?php if (!empty($_SESSION['last'])): ?>
      <div class="flash">Saved: “<?= htmlspecialchars($_SESSION['last']); $_SESSION['last'] = null; ?>”</div>
    <?php endif; ?>

    <section class="grid">
      <div class="col">
        <div class="card reveal">
          <h2>Share a conspiracy</h2>
          <p class="helper">Max 240 chars. Keep it wild yet civil.</p>
          <?php $composerCommunities = $communities ?? []; ?>
          <form method="post" action="" id="composerForm">
            <div class="row stretch" style="margin-bottom: 10px; gap: 12px;">
              <input type="text" name="nick" placeholder="Alias (e.g., MoonWatcher)" required>
              <select name="community">
                <?php
                  $seen = [];
                  foreach ($composerCommunities as $community):
                    $slug = htmlspecialchars($community['slug']);
                    if (isset($seen[$slug])) continue;
                    $seen[$slug] = true;
                    $label = htmlspecialchars($community['name']);
                    $selected = $slug === 'general' ? 'selected' : '';
                ?>
                  <option value="<?= $slug ?>" <?= $selected ?>>r/<?= $label ?></option>
                <?php endforeach; ?>
                <?php if (empty($seen['general'])): ?>
                  <option value="general" selected>r/General</option>
                <?php endif; ?>
              </select>
            </div>
            <textarea name="msg" id="msgBox" placeholder="Type your theory…" maxlength="240" required></textarea>
            <div class="row" style="justify-content: space-between; margin-top: 8px;">
              <small class="muted" id="countHint">0 / 240</small>
              <button class="btn">Post</button>
            </div>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
          </form>
        </div>

        <div class="card reveal" style="margin-top:16px;">
          <h2>Threads</h2>
          <p class="helper">
            <?= isset($total) ? (int)$total : 0 ?> total
            <?= !empty($q) ? ' · filtering for “' . htmlspecialchars($q) . '”' : '' ?>
          </p>

          <form method="get" action="" class="row" style="align-items:center; gap:12px; margin-bottom: 12px;">
            <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search conspiracies">
            <select name="sort" onchange="this.form.submit()">
              <option value="new" <?= ($sort ?? 'new') === 'new' ? 'selected' : '' ?>>Newest</option>
              <option value="old" <?= ($sort ?? '') === 'old' ? 'selected' : '' ?>>Oldest</option>
              <option value="top" <?= ($sort ?? '') === 'top' ? 'selected' : '' ?>>Top (votes)</option>
            </select>
            <button class="btn-outline">Apply</button>
          </form>

<<<<<<< HEAD
          <div id="messageList">
            <?php if (empty($messages)): ?>
              <div class="msg msg-empty"><p>No posts<?= !empty($q) ? ' for “' . htmlspecialchars($q) . '”' : '' ?> yet.</p></div>
            <?php else: foreach ($messages as $m): ?>
              <?php $comments = $m['comments'] ?? []; ?>
              <article class="msg" id="msg_<?= (int)$m['id'] ?>" data-community="<?= htmlspecialchars($m['community_slug'] ?? 'general') ?>">
                <div class="msg-head">
                  <span class="badge"><?= htmlspecialchars($m['nickname']) ?></span>
                  <span class="community-tag">r/<?= htmlspecialchars($m['community_name'] ?? 'General') ?></span>
                  <span class="time"><?= htmlspecialchars($m['created_at']) ?></span>
                  <form method="post" action="" class="actions" style="margin-left:auto;">
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
                  <button class="btn-danger" data-delete
                          data-id="<?= (int)$m['id'] ?>"
                          data-snippet="<?= htmlspecialchars(mb_strimwidth($m['body'], 0, 60, '…')) ?>">
                    Delete
                  </button>
                </div>
                <p><?= nl2br(htmlspecialchars($m['body'])) ?></p>
                <section class="comments" data-message="<?= (int)$m['id'] ?>">
                  <h3 class="comments-title">Comments</h3>
                  <div class="comments-list" id="comments_<?= (int)$m['id'] ?>">
                    <?php if (empty($comments)): ?>
                      <p class="comment-empty muted">No comments yet.</p>
                    <?php else: foreach ($comments as $c): ?>
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
          </div>

=======
          <!-- List -->
          <div id="messageList">
          <?php if (empty($messages)): ?>
            <div class="msg msg-empty"><p>No posts<?= !empty($q) ? ' for “'.htmlspecialchars($q).'”' : '' ?> yet.</p></div>
          <?php else: foreach ($messages as $m): ?>
            <article class="msg" id="msg_<?= (int)$m['id'] ?>">
              <div class="msg-head">
                <span class="badge"><?= htmlspecialchars($m['nickname']) ?></span>
                <span class="time"><?= htmlspecialchars($m['created_at']) ?></span>

                <!-- Votes -->
                <form method="post" action="" class="actions" style="margin-left:auto;">
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


                <!-- Delete trigger (opens modal) -->
                <button class="btn-danger" data-delete
                        data-id="<?= (int)$m['id'] ?>"
                        data-snippet="<?= htmlspecialchars(mb_strimwidth($m['body'],0,60,'…')) ?>">
                  Delete
                </button>
              </div>
              <p><?= nl2br(htmlspecialchars($m['body'])) ?></p>
              <?php $comments = $m['comments'] ?? []; ?>
              <section class="comments" data-message="<?= (int)$m['id'] ?>">
                <h3 class="comments-title">Comments</h3>
                <div class="comments-list" id="comments_<?= (int)$m['id'] ?>">
                  <?php if (empty($comments)): ?>
                    <p class="comment-empty muted">No comments yet.</p>
                  <?php else: foreach ($comments as $c): ?>
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
          </div>

          <!-- Pager -->
>>>>>>> origin/main
          <?php if (($pages ?? 1) > 1): ?>
            <nav class="pager">
              <?php
                $base = strtok($_SERVER['REQUEST_URI'], '?') ?: '';
                $qs = function ($p) use ($q, $sort) {
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
        </div>
      </div>

      <aside class="col">
        <div class="card reveal">
          <h2>Trending communities</h2>
          <p class="helper">Where the hive mind is buzzing this week.</p>
          <ul class="stats-list">
            <?php if (!empty($communities)): foreach ($communities as $community): ?>
              <li>
                <strong>r/<?= htmlspecialchars($community['name']) ?></strong>
                <span><?= htmlspecialchars($community['tagline'] ?: 'No tagline yet.') ?></span>
                <span class="muted">Posts (7d): <?= (int)$community['posts_7d'] ?> · Comments (7d): <?= (int)$community['comments_7d'] ?></span>
              </li>
            <?php endforeach; else: ?>
              <li><strong>r/General</strong><span class="muted">We just launched. Start the first thread!</span></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="card reveal" style="margin-top:16px;">
          <h2>Active conspirators</h2>
          <p class="helper">Fresh voices keeping the cave alive.</p>
          <ul class="stats-list">
            <?php if (!empty($activeUsers)): foreach ($activeUsers as $user): ?>
              <li>
                <strong><?= htmlspecialchars($user['nickname']) ?></strong>
                <span><?= (int)$user['messages'] ?> posts · <?= (int)$user['comments'] ?> comments</span>
                <span class="muted">Last seen <?= htmlspecialchars($user['last_seen']) ?></span>
              </li>
            <?php endforeach; else: ?>
              <li><strong>Be the first conspirator!</strong><span class="muted">Post a theory to appear here.</span></li>
            <?php endif; ?>
          </ul>
        </div>

        <div class="ticker-card reveal">
          <h2>Global pulse</h2>
          <p class="helper">Communities lighting up right now.</p>
          <ul data-community-ticker>
            <?php $tickerCommunities = array_slice($communities ?? [], 0, 5); ?>
            <?php if (!empty($tickerCommunities)): foreach ($tickerCommunities as $community): ?>
              <li>
                <span class="community-pill">
                  r/<?= htmlspecialchars($community['name']) ?>
                  <small><?= (int)$community['posts_7d'] ?> posts · <?= (int)$community['comments_7d'] ?> comments</small>
                </span>
              </li>
            <?php endforeach; else: ?>
              <li><span class="community-pill">r/General <small>Start something intriguing!</small></span></li>
            <?php endif; ?>
          </ul>
        </div>
      </aside>
    </section>
  </div>

  <div id="deleteModal" class="modal" aria-hidden="true">
    <div class="modal-panel">
      <h2 style="margin-top:0;">Delete message?</h2>
      <p class="helper">You're about to delete:</p>
      <div id="deletePreview" class="msg"></div>
      <form id="deleteForm" method="post" action="" class="row" style="margin-top:10px;">
        <input type="hidden" name="id" value="">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete">
        <button type="button" class="btn-outline" data-close>Cancel</button>
        <button class="btn-danger">Delete</button>
      </form>
    </div>
  </div>

  <script src="assets/app.js" defer></script>
  <script src="assets/dynamic-interface.js" defer></script>
  <script src="assets/composer.js" defer></script>
</body>
</html>
