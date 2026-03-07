<?php
// pages/forum/index.php — Forum standalone (no head.php, custom UI + theme)
require_once(__DIR__ . '/../../core/config.php');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ========= Auth & Profile ========= */
$username  = $_SESSION['logger']['username'] ?? null;
$isLogin   = !empty($username);
$accountId = 0; 
$display   = $username ?: 'Khách';
$isAdmin   = 0; 
$tongnap   = 0;

if ($isLogin) {
  $u = $config->real_escape_string($username);
  $rs = $config->query("SELECT id, isAdmin, tongnap FROM account WHERE username='{$u}' LIMIT 1");
  if ($rs && $rs->num_rows) {
    $r         = $rs->fetch_assoc();
    $accountId = (int)$r['id'];
    $isAdmin   = (int)$r['isAdmin'];
    $tongnap   = (int)$r['tongnap'];
    $rp = $config->query("SELECT cName FROM player WHERE playerId={$accountId} LIMIT 1");
    if ($rp && $rp->num_rows) {
      $name = $rp->fetch_assoc()['cName'];
      if ($name) $display = $name;
    }
  }
}
$MIN_POST_TOPUP = 10000;
$canPost = $isLogin && ($tongnap >= $MIN_POST_TOPUP);

/* ========= Helpers ========= */
function hasCol(mysqli $db, string $table, string $col): bool {
  $res = $db->query("SHOW COLUMNS FROM `forum_threads` LIKE '{$col}'");
  return ($res && $res->num_rows > 0);
}

/* ========= Params ========= */
$catId = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$page  = max(1, (int)($_GET['page'] ?? 1));
$q     = trim($_GET['q'] ?? '');
$sort  = $_GET['sort'] ?? 'last'; // last|views|new
$mine  = isset($_GET['mine']) ? (int)$_GET['mine'] : 0;
$limit = 15; 
$offset= ($page-1)*$limit;

/* ========= Categories ========= */
$cats = $config->query("SELECT id,name,slug FROM forum_categories ORDER BY sort_order ASC, id ASC");

/* ========= Build WHERE ========= */
$where = [];
if ($catId) $where[] = "t.category_id={$catId}";
if ($q !== '') {
  $qEsc = $config->real_escape_string($q);
  $where[] = "(t.title LIKE '%{$qEsc}%' OR t.content LIKE '%{$qEsc}%')";
}
$hasAuthor = hasCol($config, 'forum_threads', 'author_id');
if ($mine === 1 && $isLogin && $hasAuthor) {
  $where[] = "t.author_id={$accountId}";
}
$whereSql = count($where) ? ('WHERE '.implode(' AND ',$where)) : "";

/* ========= Sort ========= */
$orderSql = "t.pinned DESC, t.last_post_at DESC, t.id DESC";
if ($sort === 'views') $orderSql = "t.pinned DESC, t.views DESC, t.last_post_at DESC";
elseif ($sort === 'new') $orderSql = "t.pinned DESC, t.created_at DESC";

/* ========= Count & Query ========= */
$sqlCount = "SELECT COUNT(*) c FROM forum_threads t {$whereSql}";
$total = 0;
if ($c = $config->query($sqlCount)) {
  $total = (int)$c->fetch_assoc()['c'];
}

$sql = "
SELECT t.id,t.title,t.pinned,t.locked,t.views,t.last_post_at,
       t.created_at,t.updated_at,c.name AS cat_name
FROM forum_threads t
JOIN forum_categories c ON c.id=t.category_id
{$whereSql}
ORDER BY {$orderSql}
LIMIT {$limit} OFFSET {$offset}";
$threads = $config->query($sql);

$pages = max(1, (int)ceil($total/$limit));
?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <title>Diễn đàn | Ngọc Rồng</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <!-- Inter font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- (Cho theme Original) CSS gốc của web nếu có -->
  <link id="sitecss" href="/chipasset/css/hoangvietdung.css" rel="stylesheet">
<style>
  /* Default = Normal (sáng) */
  :root{
    --bg:#f6f7fb;      /* nền trang */
    --card:#ffffff;    /* mặt thẻ luôn đục */
    --text:#1f2937; --muted:#64748b;
    --accent:#0ea5e9; --accent-2:#fb923c; --danger:#ef4444;
    --border:rgba(2,6,23,.08);
    --row:#ffffff; --row-alt:#fafafa; --row-hover:#f1f5f9;
    --head:#f8fafc;
  }
  body{background:var(--bg); color:var(--text); font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,"Helvetica Neue",Arial,sans-serif;}

  /* Black */
  body[data-theme="black"]{
    --bg:#0e111a;
    --card:#151a24;            /* thẻ đục, tối */
    --text:#e6ebff; --muted:#9aa4c7;
    --accent:#66b2ff; --accent-2:#ffb86b; --danger:#ff6b6b;
    --border:rgba(255,255,255,.08);
    --row:#161c28; --row-alt:#141927; --row-hover:#1b2232; --head:#131926;
  }

  /* Original: dùng BG gốc (girlkun-bg), thẻ vẫn đục trắng để dễ đọc */
  body[data-theme="original"]{
    --bg:transparent;
    --card:#ffffff;            /* thẻ trắng đục */
    --text:#1f2937; --muted:#64748b;
    --border:rgba(2,6,23,.08);
    --row:#ffffff; --row-alt:#fafafa; --row-hover:#f1f5f9; --head:#f8fafc;
  }

  .forum-wrap{max-width:1200px;margin:20px auto;padding:0 12px;}
  .forum-header{display:flex;justify-content:space-between;align-items:center;gap:12px;
    background:var(--card); border:1px solid var(--border); border-radius:14px; padding:14px 16px;}
  .forum-title{font-weight:700;letter-spacing:.2px}
  .pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;
        background:rgba(14,165,233,.1);border:1px solid rgba(14,165,233,.25);color:var(--accent);font-size:14px;}

  .layout{display:grid;grid-template-columns:260px 1fr;gap:16px;margin-top:16px}
  @media (max-width: 992px){.layout{grid-template-columns:1fr}}

  .card{background:var(--card);border:1px solid var(--border);border-radius:14px}
  .sidebar{position:sticky;top:12px;height:fit-content}

  .list-group .list-group-item{background:transparent;color:var(--text);border:1px solid var(--border);
    margin-bottom:8px;border-radius:12px;transition:.15s}
  .list-group .list-group-item:hover{transform:translateY(-1px);border-color:rgba(14,165,233,.4)}
  .list-group .active{background:rgba(14,165,233,.12);border-color:rgba(14,165,233,.6);font-weight:600}

  .toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;padding:10px 14px;border-bottom:1px solid var(--border)}
  .search{flex:1;min-width:240px;display:flex;align-items:center;gap:8px;background:var(--card);
          border:1px solid var(--border);border-radius:10px;padding:8px 12px}
  .search input{background:transparent;border:0;outline:0;color:var(--text);width:100%}

  .table-forum thead th{background:var(--head); color:var(--muted); font-weight:700; text-transform:uppercase; letter-spacing:.4px; font-size:12px;}
  .table-forum tbody tr{background:var(--row)}
  .table-forum tbody tr:nth-child(even){background:var(--row-alt)}
  .table-forum tbody tr:hover{background:var(--row-hover)}
  .thread-title{color:var(--text);text-decoration:none;font-weight:700}
  .thread-title:hover{color:var(--accent)}
  .badge-pin{background:linear-gradient(90deg,#ffd56b,#ffb86b);color:#2b1d00;font-weight:700}
  .badge-lock{background:rgba(127,127,127,.2);color:#555}

  .btn-primary{background:linear-gradient(90deg,#0ea5e9,#38bdf8);border:0}
  .btn-primary:hover{filter:brightness(1.05)}
  .btn-outline-danger{border-color:rgba(239,68,68,.5);color:#ef4444}
  .btn-outline-danger:hover{background:rgba(239,68,68,.1)}

  .pagination .page-link{background:transparent;border-color:var(--border);color:var(--text)}
  .pagination .active .page-link{background:var(--accent);border-color:var(--accent);color:#fff}

  .theme-switch{display:flex;gap:6px;background:var(--head);padding:6px;border-radius:10px;border:1px solid var(--border)}
  .theme-switch .tbtn{padding:6px 10px;border-radius:8px;border:1px solid transparent;cursor:pointer;font-size:12px;color:var(--text)}
  .theme-switch .tbtn.active{background:rgba(14,165,233,.12);border-color:rgba(14,165,233,.4)}
  .muted{color:var(--muted)} .cap-cta{font-size:12px;color:var(--muted)}
</style>


</head>
<body class="">
  <div class="forum-wrap">
    <div class="forum-header card">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <a href="/" class="btn btn-sm btn-outline-light">
          <i class="fa-solid fa-house"></i> Home
        </a>
        <span class="pill"><i class="fa-solid fa-comments"></i> Diễn đàn</span>
        <h3 class="forum-title m-0">Cộng đồng Ngọc Rồng</h3>
      </div>

      <div class="d-flex align-items-center gap-3 flex-wrap">
        <!-- Theme switch -->
        <div class="theme-switch" id="themeSwitch">
          <button class="tbtn" data-theme="black"    title="Nền đen">Black</button>
          <button class="tbtn" data-theme="normal"   title="Bình thường">Normal</button>
          <button class="tbtn" data-theme="original" title="Nền gốc web">Original</button>
        </div>

        <?php if ($isLogin): ?>
          <span class="muted">Xin chào, <strong><?php echo htmlspecialchars($display); ?></strong></span>
          <?php if ($canPost): ?>
            <button class="btn btn-primary glow" data-bs-toggle="modal" data-bs-target="#newThreadModal">
              <i class="fa fa-plus"></i> Chủ đề mới
            </button>
          <?php else: ?>
            <button class="btn btn-secondary" disabled
              title="Cần tổng nạp tối thiểu <?php echo number_format($MIN_POST_TOPUP); ?>">
              <i class="fa fa-lock"></i> Chủ đề mới
            </button>
          <?php endif; ?>
        <?php else: ?>
          <a class="btn btn-outline-light" href="/dangnhap"><i class="fa fa-sign-in"></i> Đăng nhập</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="layout">
      <!-- Sidebar -->
      <aside class="sidebar">
        <div class="card p-3">
          <div class="mb-2 d-flex align-items-center justify-content-between">
            <strong>Danh mục</strong>
            <a class="small text-decoration-none" href="?cat=0" style="color:#9fd0ff">Tất cả</a>
          </div>
          <div class="list-group">
            <a href="?cat=0" class="list-group-item list-group-item-action<?php echo $catId==0?' active':'';?>">
              <i class="fa-regular fa-compass me-2"></i> Tất cả bài
            </a>
            <?php if ($cats) while($c=$cats->fetch_assoc()): ?>
              <a href="?cat=<?php echo $c['id']; ?>"
                 class="list-group-item list-group-item-action<?php echo $catId==$c['id']?' active':'';?>">
                <i class="fa-regular fa-folder-closed me-2"></i> <?php echo htmlspecialchars($c['name']); ?>
              </a>
            <?php endwhile; ?>
          </div>
        </div>

        <div class="card p-3 mt-3">
          <strong class="mb-2">Mẹo nhỏ</strong>
          <div class="cap-cta">
            • Dùng ô tìm kiếm để lọc nhanh theo <b>tiêu đề</b> hoặc <b>nội dung</b>.<br>
            • Chủ đề được <b>ghim</b> luôn ở trên cùng.<br>
            • Admin có thể <b>xóa</b> bài không phù hợp ngay tại danh sách.<br>
            • (Gợi ý thêm) tính năng <b>theo dõi chủ đề</b>, <b>thông báo trả lời</b>, <b>like</b>/<b>report</b> cần bổ sung cột & bảng — nếu muốn mình sẽ gửi migration.
          </div>
        </div>
      </aside>

      <!-- Content -->
      <section class="card">
        <div class="toolbar">
          <form class="search" method="get" action="">
            <i class="fa fa-search"></i>
            <input type="hidden" name="cat" value="<?php echo (int)$catId; ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
            <?php if ($mine && $isLogin && $hasAuthor): ?>
              <input type="hidden" name="mine" value="1">
            <?php endif; ?>
            <input name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Tìm tiêu đề, nội dung..." autocomplete="off">
          </form>

          <!-- Sort -->
          <div class="dropdown">
            <button class="btn btn-sm btn-outline-light dropdown-toggle" data-bs-toggle="dropdown">
              Sắp xếp
            </button>
            <ul class="dropdown-menu dropdown-menu-dark">
              <li><a class="dropdown-item <?php echo $sort==='last'?'active':'';?>" href="?cat=<?php echo $catId;?>&q=<?php echo urlencode($q);?>&sort=last">Mới cập nhật</a></li>
              <li><a class="dropdown-item <?php echo $sort==='views'?'active':'';?>" href="?cat=<?php echo $catId;?>&q=<?php echo urlencode($q);?>&sort=views">Nhiều lượt xem</a></li>
              <li><a class="dropdown-item <?php echo $sort==='new'?'active':'';?>" href="?cat=<?php echo $catId;?>&q=<?php echo urlencode($q);?>&sort=new">Mới tạo</a></li>
            </ul>
          </div>

          <!-- Mine -->
          <?php if ($isLogin && $hasAuthor): ?>
            <?php if ($mine): ?>
              <a class="btn btn-sm btn-outline-secondary" href="?cat=<?php echo $catId;?>&q=<?php echo urlencode($q);?>&sort=<?php echo $sort;?>">Tất cả bài</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-info" href="?cat=<?php echo $catId;?>&q=<?php echo urlencode($q);?>&sort=<?php echo $sort;?>&mine=1">
                <i class="fa fa-user"></i> Bài của tôi
              </a>
            <?php endif; ?>
          <?php endif; ?>

          <div class="muted ms-auto">
            <?php echo number_format($total); ?> chủ đề • Trang <?php echo $page; ?>/<?php echo $pages; ?>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover table-nowrap table-forum align-middle mb-0">
            <thead>
              <tr>
                <th>Tiêu đề</th>
                <th>Danh mục</th>
                <th>Lượt xem</th>
                <th>Bài mới nhất</th>
                <?php if ($isAdmin): ?><th class="text-end">Quản trị</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if ($threads && $threads->num_rows): while($t=$threads->fetch_assoc()): ?>
                <tr>
                  <td>
                    <?php if ($t['pinned']): ?>
                      <span class="badge badge-pin me-1">Ghim</span>
                    <?php endif; ?>
                    <?php if ($t['locked']): ?>
                      <span class="badge badge-lock me-1">Khóa</span>
                    <?php endif; ?>
                    <a class="thread-title" href="/pages/forum/thread.php?id=<?php echo $t['id']; ?>">
                      <?php echo htmlspecialchars($t['title']); ?>
                    </a>
                    <div class="small muted mt-1">
                      Tạo: <?php echo htmlspecialchars($t['created_at']); ?>
                      • Cập nhật: <?php echo htmlspecialchars($t['last_post_at']); ?>
                    </div>
                  </td>
                  <td class="text-nowrap"><?php echo htmlspecialchars($t['cat_name']); ?></td>
                  <td class="text-nowrap"><?php echo number_format((int)$t['views']); ?></td>
                  <td class="text-nowrap"><small><?php echo htmlspecialchars($t['last_post_at']); ?></small></td>

                  <?php if ($isAdmin): ?>
                    <td class="text-end">
                      <form method="post" action="/pages/forum/action.php"
                            onsubmit="return confirm('Xóa chủ đề này? Hành động không thể hoàn tác.');">
                        <input type="hidden" name="act" value="delete_thread">
                        <input type="hidden" name="thread_id" value="<?php echo (int)$t['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Xóa">
                          <i class="fa fa-trash"></i>
                        </button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endwhile; else: ?>
                <tr><td colspan="<?php echo $isAdmin?5:4; ?>" class="text-center py-5">Chưa có chủ đề</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="p-3">
          <nav aria-label="pagination">
            <ul class="pagination justify-content-center gap-1 m-0">
              <?php 
                $base = '?cat='.$catId.'&q='.urlencode($q).'&sort='.$sort.($mine&&$isLogin&&$hasAuthor?'&mine=1':'').'&page=';
                for($i=1;$i<=$pages;$i++): 
              ?>
                <li class="page-item <?php echo $i==$page?'active':''; ?>">
                  <a class="page-link" href="<?php echo $base.$i; ?>"><?php echo $i; ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>
        </div>
        <?php endif; ?>

      </section>
    </div>
  </div>

  <?php if ($canPost): ?>
  <!-- Modal tạo chủ đề -->
  <div class="modal fade" id="newThreadModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <form class="modal-content" method="post" action="/pages/forum/action.php">
        <input type="hidden" name="act" value="create_thread">
        <div class="modal-header">
          <h5 class="modal-title"><i class="fa fa-pen-to-square me-2"></i>Tạo chủ đề mới</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="row g-2">
            <div class="col-md-5">
              <label class="form-label">Danh mục</label>
              <select class="form-select" name="category_id" required>
                <?php
                  $cats2 = $config->query("SELECT id,name FROM forum_categories ORDER BY sort_order ASC,id ASC");
                  while($c=$cats2->fetch_assoc()):
                ?>
                  <option value="<?php echo (int)$c['id']; ?>" <?php echo $catId==$c['id']?'selected':''; ?>>
                    <?php echo htmlspecialchars($c['name']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
            </div>
            <div class="col-md-7">
              <label class="form-label">Tiêu đề</label>
              <input type="text" class="form-control" name="title" maxlength="200" required placeholder="Nhập tiêu đề...">
            </div>
          </div>

          <div class="mt-2">
            <label class="form-label">Nội dung (hỗ trợ BBCode)</label>
            <textarea class="form-control" name="content" rows="8" required placeholder="[b]Chữ đậm[/b], [img]URL[/img]..."></textarea>
            <div class="cap-cta mt-1">Gợi ý: dùng <code>[b]...[/b]</code>, <code>[i]...[/i]</code>, <code>[img]URL[/img]</code>…</div>
          </div>
        </div>
        <div class="modal-footer">
          <span class="me-auto cap-cta">Yêu cầu tổng nạp ≥ <?php echo number_format($MIN_POST_TOPUP); ?></span>
          <button class="btn btn-primary" type="submit"><i class="fa fa-paper-plane"></i> Đăng</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
  // Theme: normal / black / original (original dùng BG gốc, card vẫn đục)
  (function(){
    const body = document.body;
    const key  = 'forumTheme';
    const ts   = document.getElementById('themeSwitch');

    function setTheme(val){
      if(!['normal','black','original'].includes(val)) val = 'normal';
      localStorage.setItem(key, val);
      body.setAttribute('data-theme', val);
      // Nền gốc web: thêm class girlkun-bg, còn lại bỏ
      body.classList.toggle('girlkun-bg', val === 'original');
      // Active button
      ts.querySelectorAll('.tbtn').forEach(b => b.classList.toggle('active', b.dataset.theme === val));
    }

    setTheme(localStorage.getItem(key) || 'normal');
    ts.addEventListener('click', e => {
      const btn = e.target.closest('.tbtn'); if(!btn) return;
      setTheme(btn.dataset.theme);
    });
  })();

  // Nhấn ghim: highlight nhẹ
  document.addEventListener('DOMContentLoaded',()=>{
    document.querySelectorAll('.badge-pin').forEach(b=>{
      const tr = b.closest('tr'); if(!tr) return;
      tr.style.transition='box-shadow .25s'; tr.style.boxShadow='0 0 0 0 rgba(255,184,107,.0)';
      setTimeout(()=>{ tr.style.boxShadow='0 0 0 6px rgba(255,184,107,.18)'; },80);
      setTimeout(()=>{ tr.style.boxShadow='none'; },900);
    });
  });
</script>


</body>
</html>
