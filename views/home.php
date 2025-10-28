<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Cave of Conspiracies</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Update these paths if your folder name isn't /myApp -->
  <link href="assets/styles.css" rel="stylesheet">
</head>
<body>
  <div class="container">

    <!-- Header -->
    <header class="header">
      <div class="brand">
        <div class="brand-badge" style="width:44px;height:44px;font-size:16px;">CoC</div>
        <h1 style="font-size:28px; letter-spacing:.5px; margin:0;">Cave of Conspiracies</h1>
        <span class="tag">Post. Ponder. Poke holes.</span>
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

    <!-- Flash -->
    <?php if (!empty($_SESSION['last'])): ?>
      <div class="flash">Saved: “<?= htmlspecialchars($_SESSION['last']); $_SESSION['last']=null; ?>”</div>
    <?php endif; ?>

    <!-- Layout -->
    <section class="grid">
      <!-- Left column: composer + threads -->
      <div class="col">

        <!-- Create Post -->
        <div class="card reveal">
          <h2>Share a conspiracy</h2>
          <p class="helper">Max 240 chars. Keep it wild yet civil.</p>
          <form method="post" action="" id = "composerForm">
            <div class="row">
              <input type="text" name="nick" placeholder="Alias (e.g., MoonWatcher)" required>
              <button class="btn">Post</button>
            </div>
            <textarea name="msg" id="msgBox" placeholder="Type your theory…" maxlength="240" required></textarea>
            <div class="row" style="justify-content: space-between;">
              <small class="muted" id="countHint">0 / 240</small>
            </div>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
          </form>
        </div>

        <!-- Threads -->
        <div class="card reveal" style="margin-top:16px;">
          <h2>Threads</h2>
          <p class="helper">
            <?= isset($total) ? (int)$total : 0 ?> total
            <?= !empty($q) ? ' · filtering for “'.htmlspecialchars($q).'”' : '' ?>
          </p>

          <!-- Search + Sort -->
          <form method="get" action="" class="row" style="align-items:center; gap:12px;">
            <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search conspiracies">
            <select name="sort" onchange="this.form.submit()">
              <option value="new" <?= ($sort ?? 'new')==='new'?'selected':'' ?>>Newest</option>
              <option value="old" <?= ($sort ?? '')==='old'?'selected':'' ?>>Oldest</option>
              <option value="top" <?= ($sort ?? '')==='top'?'selected':'' ?>>Top (votes)</option>
            </select>
            <button class="btn-outline">Apply</button>
          </form>

          <!-- List -->
          <?php if (empty($messages)): ?>
            <div class="msg"><p>No posts<?= !empty($q) ? ' for “'.htmlspecialchars($q).'”' : '' ?> yet.</p></div>
          <?php else: foreach ($messages as $m): ?>
            <article class="msg">
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
            </article>
          <?php endforeach; endif; ?>

          <!-- Pager -->
          <?php if (($pages ?? 1) > 1): ?>
            <nav class="pager">
              <?php
                $base = strtok($_SERVER['REQUEST_URI'], '?') ?: '';
                $qs = function($p) use($q,$sort){
                  $parts = ['page='.$p];
                  if (!empty($q))   $parts[] = 'q='.urlencode($q);
                  if (!empty($sort))$parts[] = 'sort='.urlencode($sort);
                  return '?'.implode('&',$parts);
                };
              ?>
              <?php for ($p = 1; $p <= $pages; $p++): ?>
                <?php if ($p == ($page ?? 1)): ?>
                  <span class="active"><?= $p ?></span>
                <?php else: ?>
                  <a href="<?= $base.$qs($p) ?>"><?= $p ?></a>
                <?php endif; ?>
              <?php endfor; ?>
            </nav>
          <?php endif; ?>
        </div>
      </div>

      <!-- Right column: search panel + about -->
      <aside class="col">
        <div class="card reveal">
          <h2>Quick search</h2>
          <form method="get" action="" class="row">
            <input type="text" name="q" value="<?= htmlspecialchars($q ?? '') ?>" placeholder="Search conspiracies">
            <button class="btn-outline">Search</button>
          </form>
          <p class="helper">Clear the box to instantly show everything.</p>
        </div>

        <div class="card reveal" style="margin-top:16px;">
          <h2>About</h2>
          <p class="helper">
            A tiny Reddit-style sandbox: PHP + PDO + MySQL, reactions, CSRF, pagination,
            dark/light themes. Coming soon: comments, edit-in-place, full-text search, tags.
          </p>
        </div>
      </aside>
    </section>
  </div>

  <!-- Delete Modal -->
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

  <!-- Scripts (update path prefix if needed) -->
  <script src="assets/app.js"></script>

  <!-- Page-local scripts -->
  <script>
    // Live character counter for composer
    const box = document.querySelector('#msgBox');
    const hint = document.querySelector('#countHint');
    if (box && hint){
      const update = () => hint.textContent = `${box.value.length} / ${box.maxLength}`;
      box.addEventListener('input', update); update();
    }
  </script>
</body>
<body>
  <!-- Floating background blobs (decor) -->
  <div class="blob one"></div>
  <div class="blob two"></div>

  <div class="container">
    ...
  </div>
</html>
