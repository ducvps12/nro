<?php
// pages/forum/thread.php — full page, standalone UI
require_once(__DIR__ . '/../../core/config.php');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ====== Input ====== */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die('Thiếu id');

/* ====== View + data ====== */
$config->query("UPDATE forum_threads SET views = views + 1 WHERE id = {$id}");

$thread = $config->query("
  SELECT t.*, c.name AS cat_name
  FROM forum_threads t
  JOIN forum_categories c ON c.id = t.category_id
  WHERE t.id = {$id}
  LIMIT 1
");
if (!$thread || !$thread->num_rows) die('Không tìm thấy chủ đề');
$t = $thread->fetch_assoc();

/* ====== User ====== */
$username   = $_SESSION['logger']['username'] ?? null;
$isLogin    = !empty($username);
$isAdmin    = 0;
$accountId  = 0;
$display    = $username ?: 'Khách';

if ($isLogin) {
  $u  = $config->real_escape_string($username);
  $ra = $config->query("SELECT id,isAdmin FROM account WHERE username='{$u}' LIMIT 1");
  if ($ra && $ra->num_rows) {
    $a         = $ra->fetch_assoc();
    $accountId = (int)$a['id'];
    $isAdmin   = (int)$a['isAdmin'];
    $rp = $config->query("SELECT cName FROM player WHERE playerId={$accountId} LIMIT 1");
    if ($rp && $rp->num_rows) {
      $name = $rp->fetch_assoc()['cName'];
      if ($name) $display = $name;
    }
  }
}

/* ====== Posts ====== */
$posts = $config->query("
  SELECT p.*,
    (SELECT COUNT(*) FROM forum_likes l WHERE l.post_id = p.id) AS likes
  FROM forum_posts p
  WHERE p.thread_id = {$id} AND p.is_deleted = 0
  ORDER BY p.created_at ASC
");
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title><?php echo htmlspecialchars($t['title']); ?> | Diễn đàn</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Site CSS (nền mặc định) -->
  <link rel="stylesheet" href="/chipasset/css/hoangvietdung.css">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

  <style>
    /* Default = Normal (sử dụng nền gốc site, card đục dễ đọc) */
    :root{
      --bg: transparent;          /* xem nền từ .girlkun-bg */
      --card:#ffffff; --text:#1f2937; --muted:#64748b;
      --accent:#0ea5e9; --danger:#ef4444; --border:rgba(2,6,23,.08);
      --row:#ffffff; --row-alt:#fafafa; --row-hover:#f1f5f9; --head:#f8fafc;
    }
    body{background:var(--bg); color:var(--text); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;}

    /* Black (nền tối riêng, không dùng nền site) */
    body[data-theme="black"]{
      --bg:#0e111a; --card:#151a24; --text:#e6ebff; --muted:#9aa4c7;
      --accent:#66b2ff; --danger:#ff6b6b; --border:rgba(255,255,255,.08);
      --row:#161c28; --row-alt:#141927; --row-hover:#1b2232; --head:#131926;
    }

    /* Original: cũng dùng nền site, giữ card trắng đục */
    body[data-theme="original"]{
      --bg: transparent;
      --card:#ffffff; --text:#1f2937; --muted:#64748b; --border:rgba(2,6,23,.08);
      --row:#ffffff; --row-alt:#fafafa; --row-hover:#f1f5f9; --head:#f8fafc;
    }

    .wrap{max-width:1000px;margin:20px auto;padding:0 12px}
    .header{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center;gap:12px}
    .theme-switch{display:flex;gap:6px;background:var(--head);padding:6px;border-radius:10px;border:1px solid var(--border)}
    .tbtn{padding:6px 10px;border-radius:8px;border:1px solid transparent;cursor:pointer;font-size:12px;color:var(--text)}
    .tbtn.active{background:rgba(14,165,233,.12);border-color:rgba(14,165,233,.4)}
    .crumb a{text-decoration:none}
    .title-row{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
    .badge-pin{background:linear-gradient(90deg,#ffd56b,#ffb86b);color:#2b1d00;font-weight:700}
    .badge-lock{background:rgba(127,127,127,.2);color:#555}
    .info{color:var(--muted);font-size:13px}

    .card{background:var(--card);border:1px solid var(--border);border-radius:12px}
    .post{border:1px solid var(--border);border-radius:12px;background:var(--row)}
    .post:nth-child(even){background:var(--row-alt)}
    .post:hover{background:var(--row-hover)}
    .post .meta{display:flex;justify-content:space-between;align-items:center;color:var(--muted);font-size:13px}
    .post .author{font-weight:700;color:var(--text)}
    .replybox .form-control{background:var(--card);border:1px solid var(--border);color:var(--text)}
    .btn-primary{background:linear-gradient(90deg,#0ea5e9,#38bdf8);border:0}
    .btn-primary:hover{filter:brightness(1.05)}
    .btn-outline-danger{border-color:rgba(239,68,68,.5);color:#ef4444}
    .btn-outline-danger:hover{background:rgba(239,68,68,.1)}
  </style>
</head>
<!-- mặc định bật nền site -->
<body class="girlkun-bg">
  <div class="wrap">
    <!-- Header: Home + breadcrumb + theme -->
    <div class="header">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="/" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-house"></i> Home</a>
        <a href="/pages/forum/index.php" class="btn btn-sm btn-outline-info"><i class="fa-regular fa-comments"></i> Diễn đàn</a>
        <span class="crumb ms-1">
          <i class="fa-solid fa-angle-right"></i>
          <a href="/pages/forum/index.php?cat=<?php echo (int)$t['category_id']; ?>">
            <?php echo htmlspecialchars($t['cat_name']); ?>
          </a>
        </span>
      </div>

      <div class="d-flex align-items-center gap-2">
        <div class="theme-switch" id="themeSwitch">
          <button class="tbtn" data-theme="black">Black</button>
          <button class="tbtn" data-theme="normal">Normal</button>
          <button class="tbtn" data-theme="original">Original</button>
        </div>
        <?php if ($isLogin): ?>
          <span class="ms-2 text-muted small">Xin chào, <b><?php echo htmlspecialchars($display); ?></b></span>
        <?php else: ?>
          <a class="btn btn-sm btn-outline-primary" href="/dangnhap"><i class="fa fa-sign-in"></i> Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Thread head -->
    <div class="card mt-3 p-3">
      <div class="title-row">
        <?php if ($t['pinned']): ?><span class="badge badge-pin">Ghim</span><?php endif; ?>
        <?php if ($t['locked']): ?><span class="badge badge-lock">Khóa</span><?php endif; ?>
        <h3 class="m-0"><?php echo htmlspecialchars($t['title']); ?></h3>
      </div>
      <div class="info mt-1">
        Đăng bởi <b><?php echo htmlspecialchars($t['author_name']); ?></b>
        • Tạo: <?php echo htmlspecialchars($t['created_at']); ?>
        • Cập nhật: <?php echo htmlspecialchars($t['last_post_at']); ?>
        • Lượt xem: <?php echo number_format((int)$t['views']); ?>
      </div>

      <?php if ($isAdmin): ?>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <form method="post" action="/pages/forum/action.php" class="d-flex gap-2">
            <input type="hidden" name="act" value="mod_thread">
            <input type="hidden" name="thread_id" value="<?php echo (int)$t['id']; ?>">
            <button name="toggle_pin"  value="1" class="btn btn-sm btn-outline-warning"  type="submit"><?php echo $t['pinned']?'Bỏ ghim':'Ghim'; ?></button>
            <button name="toggle_lock" value="1" class="btn btn-sm btn-outline-secondary" type="submit"><?php echo $t['locked']?'Mở khóa':'Khóa'; ?></button>
          </form>
          <form method="post" action="/pages/forum/action.php"
                onsubmit="return confirm('Xóa chủ đề này? Hành động không thể hoàn tác.');">
            <input type="hidden" name="act" value="delete_thread">
            <input type="hidden" name="thread_id" value="<?php echo (int)$t['id']; ?>">
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="fa fa-trash"></i> Xóa chủ đề</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <!-- Posts -->
    <div class="mt-3">
      <?php if ($posts && $posts->num_rows): while($p = $posts->fetch_assoc()): ?>
        <div class="post p-3 mb-2">
          <div class="meta">
            <div class="author"><i class="fa-regular fa-user me-1"></i><?php echo htmlspecialchars($p['author_name']); ?></div>
            <div class="time"><i class="fa-regular fa-clock me-1"></i><?php echo htmlspecialchars($p['created_at']); ?></div>
          </div>
          <div class="content mt-2">
            <?php
              // Nội dung hỗ trợ BBCode (đã có chip_bbcode trong config.php)
              echo chip_bbcode(htmlspecialchars_decode($p['content']));
            ?>
          </div>
          <div class="actions mt-3 d-flex gap-2 align-items-center">
            <?php if ($isLogin): ?>
              <form method="post" action="/pages/forum/action.php" class="d-inline">
                <input type="hidden" name="act" value="like_post">
                <input type="hidden" name="post_id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit">
                  <i class="fa-regular fa-thumbs-up"></i> Thích (<?php echo (int)$p['likes']; ?>)
                </button>
              </form>
              <button class="btn btn-sm btn-outline-secondary" type="button"
                      onclick="replyTo(<?php echo (int)$p['id']; ?>,'<?php echo htmlspecialchars($p['author_name'],ENT_QUOTES); ?>')">
                <i class="fa-regular fa-comment-dots"></i> Trả lời
              </button>
              <button class="btn btn-sm btn-outline-warning" type="button" onclick="reportPost(<?php echo (int)$p['id']; ?>)">
                <i class="fa-regular fa-flag"></i> Báo cáo
              </button>
            <?php else: ?>
              <span class="text-muted">Đăng nhập để tương tác</span>
            <?php endif; ?>

            <?php if ($isAdmin || ($isLogin && (int)$p['author_id'] === $accountId)): ?>
              <form method="post" action="/pages/forum/action.php" class="ms-auto">
                <input type="hidden" name="act" value="delete_post">
                <input type="hidden" name="post_id" value="<?php echo (int)$p['id']; ?>">
                <button class="btn btn-sm btn-outline-danger" type="submit"
                        onclick="return confirm('Xóa bài viết này?')">
                  <i class="fa fa-trash"></i> Xóa
                </button>
              </form>
            <?php endif; ?>
          </div>
        </div>
      <?php endwhile; else: ?>
        <div class="alert alert-light">Chưa có bài viết.</div>
      <?php endif; ?>
    </div>

    <!-- Reply box -->
    <?php if ($isLogin && !$t['locked']): ?>
      <div class="replybox card mt-3">
        <div class="card-body">
          <form method="post" action="/pages/forum/action.php">
            <input type="hidden" name="act" value="reply">
            <input type="hidden" name="thread_id" value="<?php echo (int)$t['id']; ?>">
            <input type="hidden" name="parent_id" id="parent_id" value="">
            <label class="form-label">Nội dung</label>
            <textarea name="content" id="reply_content" class="form-control" rows="6"
                      placeholder="Nhập nội dung, hỗ trợ BBCode như [b]...[/b], [img]URL[/img]" required></textarea>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-primary" type="submit"><i class="fa fa-paper-plane"></i> Gửi trả lời</button>
              <button class="btn btn-link" type="button" id="cancel_reply" style="display:none" onclick="cancelReply()">Hủy trả lời</button>
            </div>
          </form>
        </div>
      </div>
    <?php elseif ($t['locked']): ?>
      <div class="alert alert-warning mt-3"><i class="fa-solid fa-lock"></i> Chủ đề đã bị khóa.</div>
    <?php endif; ?>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Theme switcher: normal & original dùng nền site; black dùng nền tối riêng
    (function(){
      const body = document.body;
      const key  = 'forumTheme';
      const ts   = document.getElementById('themeSwitch');
      function setTheme(val){
        if(!['normal','black','original'].includes(val)) val = 'original';
        localStorage.setItem(key,val);
        body.setAttribute('data-theme',val);
        // dùng nền site cho normal/original
        const useSiteBg = (val === 'normal' || val === 'original');
        body.classList.toggle('girlkun-bg', useSiteBg);
        ts.querySelectorAll('.tbtn').forEach(b=>b.classList.toggle('active', b.dataset.theme===val));
      }
      setTheme(localStorage.getItem(key) || 'original');
      ts.addEventListener('click', e=>{
        const btn = e.target.closest('.tbtn'); if(!btn) return;
        setTheme(btn.dataset.theme);
      });
    })();

    // Reply helpers
    function replyTo(id, author){
      document.getElementById('parent_id').value = id;
      const box = document.getElementById('reply_content');
      if(box && author){
        const cur = box.value.trim();
        const ins = `[quote=${author}]\\n\\n[/quote]\\n`;
        box.value = cur ? (cur + "\\n" + ins) : ins;
      }
      document.getElementById('cancel_reply').style.display='inline-block';
      box?.focus();
      window.scrollTo({top: document.body.scrollHeight, behavior:'smooth'});
    }
    function cancelReply(){
      document.getElementById('parent_id').value = '';
      document.getElementById('cancel_reply').style.display='none';
    }
    function reportPost(postId){
      const reason = prompt('Lý do báo cáo:');
      if(!reason) return;
      const f = document.createElement('form');
      f.method='post'; f.action='/pages/forum/action.php';
      f.innerHTML = `
        <input type="hidden" name="act" value="report_post">
        <input type="hidden" name="post_id" value="${postId}">
        <input type="hidden" name="reason" value="${reason.replace(/"/g,'&quot;')}">
      `;
      document.body.appendChild(f); f.submit();
    }
  </script>
</body>
</html>
