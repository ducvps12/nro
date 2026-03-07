<?php
// /pages/topdonate1.php — BXH Nạp: dùng account.username; riêng tư; admin bật xem full/all
require_once('../core/config.php');
require_once('../core/head.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* =============== Helpers =============== */
function fmt_money($v){ return number_format((float)$v, 0, ',', '.'); }
function mask_name($s){
  $s = trim((string)$s);
  if ($s === '') return '—';
  $len = mb_strlen($s, 'UTF-8');
  if ($len <= 3) return mb_substr($s,0,1,'UTF-8') . '***';
  return mb_substr($s,0,3,'UTF-8') . '***' . mb_substr($s,-2,2,'UTF-8');
}
function hasCol(mysqli $db, string $table, string $col): bool {
  $res = $db->query("SHOW COLUMNS FROM `nro_root`.`$table` LIKE '$col'");
  return ($res && $res->num_rows > 0);
}

/* =============== Detect admin (chắc cú) =============== */
// Ưu tiên biến $isAdmin nếu head.php đã set
$detectedIsAdmin = 0;
if (isset($isAdmin)) {
  $detectedIsAdmin = (int)$isAdmin;
} else {
  $u = $_SESSION['logger']['username'] ?? null;
  $accId = $_SESSION['logger']['id'] ?? null;
  if ($u) {
    $uEsc = $config->real_escape_string($u);
    $rs = $config->query("SELECT isAdmin FROM `nro_root`.`account` WHERE username='$uEsc' LIMIT 1");
    if ($rs && $row = $rs->fetch_assoc()) $detectedIsAdmin = (int)$row['isAdmin'];
  } elseif ($accId) {
    $accId = (int)$accId;
    $rs = $config->query("SELECT isAdmin FROM `nro_root`.`account` WHERE id=$accId LIMIT 1");
    if ($rs && $row = $rs->fetch_assoc()) $detectedIsAdmin = (int)$row['isAdmin'];
  }
}

/* =============== Params =============== */
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = ($limit === 20) ? 20 : 10; // chỉ 10/20
$adminWantsFull = ($detectedIsAdmin === 1) && (isset($_GET['full']) && $_GET['full'] == '1'); // admin xem full username
$adminWantsAll  = ($detectedIsAdmin === 1) && (isset($_GET['all'])  && $_GET['all']  == '1'); // admin xem tất cả (bỏ opt-out)

/* =============== Cột thời gian phụ =============== */
$timeCol = null;
if (hasCol($config,'account','updated_at')) $timeCol = 'updated_at';
elseif (hasCol($config,'account','timestamp')) $timeCol = 'timestamp';

/* =============== Điều kiện opt-out (nếu có) =============== */
$hasOpt = hasCol($config,'account','show_in_leaderboard');
$whereOpt = '1';
if ($hasOpt && !$adminWantsAll) {
  $whereOpt = 'show_in_leaderboard = 1';
}

/* =============== Query (chỉ bảng account) =============== */
$sql = "
SELECT 
  username,
  tongnap
  ".($timeCol ? ", $timeCol AS lastPay" : "")."
FROM `nro_root`.`account`
WHERE $whereOpt
ORDER BY tongnap DESC".($timeCol ? ", lastPay ASC" : "")."
LIMIT {$limit}";
$data = mysqli_query($config, $sql);
$err  = ($data===false) ? mysqli_error($config) : null;
?>
<div class="p-1 mt-1 ibox-content" style="border-radius:7px;">
  <main>
    <div class="top-nav">
      <a class="top-btn" href="/pages/topnv.php">Top Nhiệm Vụ</a>
      <a class="top-btn" href="/pages/topsm.php">Top Sức Mạnh</a>
    </div>

    <h1 class="h3 mb-3 font-weight-normal" style="padding-top:24px;">
      <b><center>Bảng Xếp Hạng Top Đại Gia</center></b>
    </h1>

    <div class="table-responsive" style="color:black;">
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="line-height:15px;font-size:12px;padding:2px 5px 8px;gap:8px;">
        <span class="text-black" style="vertical-align: middle;">
          Cập nhật 5 phút 1 lần
          <?php if ($detectedIsAdmin === 1 && $adminWantsFull): ?>
            • (Admin) Đang hiển thị username đầy đủ
          <?php else: ?>
            • Username được che bớt để bảo vệ riêng tư
          <?php endif; ?>
          <?php if ($hasOpt && !$adminWantsAll): ?>
            • Chỉ hiển thị tài khoản đồng ý hiện BXH
          <?php elseif ($hasOpt && $adminWantsAll): ?>
            • (Admin) Đang xem tất cả, bỏ qua opt-out
          <?php endif; ?>
        </span>
        <div class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-dark<?= $limit===10?' active':'';?>" href="?limit=10<?= $adminWantsFull ? '&full=1':''; ?><?= $adminWantsAll ? '&all=1':''; ?>">Top 10</a>
          <a class="btn btn-sm btn-outline-dark<?= $limit===20?' active':'';?>" href="?limit=20<?= $adminWantsFull ? '&full=1':''; ?><?= $adminWantsAll ? '&all=1':''; ?>">Top 20</a>

          <?php if ($detectedIsAdmin === 1): ?>
            <?php if ($adminWantsFull): ?>
              <a class="btn btn-sm btn-outline-secondary" href="?limit=<?=$limit?><?= $adminWantsAll ? '&all=1':''; ?>">Ẩn bớt tên</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-danger" href="?limit=<?=$limit?>&full=1<?= $adminWantsAll ? '&all=1':''; ?>">Hiện tên đầy đủ (Admin)</a>
            <?php endif; ?>

            <?php if ($hasOpt): ?>
              <?php if ($adminWantsAll): ?>
                <a class="btn btn-sm btn-outline-secondary" href="?limit=<?=$limit?><?= $adminWantsFull ? '&full=1':''; ?>">Áp dụng opt-out</a>
              <?php else: ?>
                <a class="btn btn-sm btn-outline-primary" href="?limit=<?=$limit?>&all=1<?= $adminWantsFull ? '&full=1':''; ?>">Xem tất cả (Admin)</a>
              <?php endif; ?>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($err): ?>
        <div class="alert alert-danger" style="color:#b94a48;background:#f2dede;border:1px solid #eed3d7;">
          Lỗi truy vấn: <?php echo htmlspecialchars($err); ?>
        </div>
      <?php endif; ?>

      <table class="table table-hover table-nowrap" style="color:black!important;">
        <thead>
          <tr>
            <th>Top</th>
            <th>Tài khoản</th>
            <th>Tổng Nạp</th>
            <?php if ($timeCol): ?><th>Lần nạp gần nhất</th><?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php
          $rank = 1;
          if ($data && mysqli_num_rows($data)>0):
            while($row = mysqli_fetch_assoc($data)):
              $rawName = trim((string)($row['username'] ?? ''));
              // Admin + full => show nguyên; còn lại => mask
              $showName = ($detectedIsAdmin === 1 && $adminWantsFull)
                          ? ($rawName !== '' ? $rawName : '—')
                          : mask_name($rawName);
        ?>
          <tr class="top_<?php echo $rank; ?>">
            <td><b>#<?php echo $rank++; ?></b></td>
            <td><?php echo htmlspecialchars($showName); ?></td>
            <td><?php echo fmt_money($row['tongnap']); ?></td>
            <?php if ($timeCol): ?>
              <td><small><?php echo htmlspecialchars($row['lastPay']); ?></small></td>
            <?php endif; ?>
          </tr>
        <?php
            endwhile;
          else:
            echo '<tr><td colspan="'.($timeCol?4:3).'" class="text-center">Chưa có dữ liệu</td></tr>';
          endif;
        ?>
        </tbody>
      </table>

      <div class="mt-2" style="font-size:12px;color:#666;">
        * Mặc định chỉ hiển thị Top 10/20 và che bớt username để bảo vệ riêng tư.
        <?php if ($hasOpt): ?>
          Người chơi có thể ẩn khỏi BXH trong Cài đặt tài khoản (tắt “Hiển thị trong BXH”).
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<?php require_once('../core/end.php'); ?>
