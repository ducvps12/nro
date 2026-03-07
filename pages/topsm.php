<?php
// /pages/topsm.php — BXH Sức Mạnh: cName; user che tên, admin bật xem full
require_once('../core/config.php');
require_once('../core/head.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ===== Helpers ===== */
function fmt_power($v){
    $v=(float)$v;
    if($v>=1000000000) return number_format($v/1000000000,1,'.','').' Tỷ';
    if($v>=1000000)    return number_format($v/1000000,1,'.','').' Tr';
    if($v>=1000)       return number_format($v/1000,1,'.','').' K';
    return number_format($v,0,',','');
}
function planet($g){ return $g==1?'Namec':($g==2?'Xayda':'Trái đất'); }
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

/* ===== Detect admin + toggle xem full ===== */
$detectedIsAdmin = isset($isAdmin) ? (int)$isAdmin : 0;
if (!isset($isAdmin)) {
  $u = $_SESSION['logger']['username'] ?? null;
  if ($u) {
    $uEsc = $config->real_escape_string($u);
    $rs = $config->query("SELECT isAdmin FROM `nro_root`.`account` WHERE username='$uEsc' LIMIT 1");
    if ($rs && $row = $rs->fetch_assoc()) $detectedIsAdmin = (int)$row['isAdmin'];
  }
}
$adminWantsFull = ($detectedIsAdmin === 1) && (isset($_GET['full']) && $_GET['full'] == '1');

/* ===== Config nhẹ ===== */
if(!defined('DB_ROOT')) define('DB_ROOT','nro_root');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = ($limit === 20) ? 20 : 10; // chỉ 10/20

/* ===== Query ===== */
$hasTs = hasCol($config,'player','timestamp');
$sql = "
SELECT cName, cgender, cPower".($hasTs ? ", timestamp" : "")."
FROM `".DB_ROOT."`.`player`
ORDER BY cPower DESC".($hasTs ? ", timestamp ASC" : "")."
LIMIT {$limit}";
$data = mysqli_query($config,$sql);
$err  = ($data===false) ? mysqli_error($config) : null;
?>
<div class="p-1 mt-1 ibox-content" style="border-radius:7px;">
  <main>
    <div class="top-nav">
      <a class="top-btn" href="/pages/topdonate1.php">Top Đại Gia</a>
      <a class="top-btn" href="/pages/topnv.php">Top Nhiệm Vụ</a>
      <!-- <a class="top-btn" href="/pages/topdt.php">Top Đệ Tử</a> -->
    </div>

    <h1 class="h3 mb-3 font-weight-normal" style="padding-top:24px;">
      <b><center>Bảng Xếp Hạng Sức Mạnh</center></b>
    </h1>

    <div class="table-responsive">
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="line-height:15px;font-size:12px;padding:2px 5px 8px;gap:8px;">
        <span class="text-black">
          Cập nhật 5 phút 1 lần
          <?php if ($detectedIsAdmin === 1 && $adminWantsFull): ?>
            • (Admin) Đang hiển thị tên đầy đủ
          <?php else: ?>
            • Tên nhân vật được che bớt để hạn chế spam/đào bới
          <?php endif; ?>
        </span>
        <div class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-dark<?= $limit===10?' active':'';?>" href="?limit=10<?= $adminWantsFull ? '&full=1':''; ?>">Top 10</a>
          <a class="btn btn-sm btn-outline-dark<?= $limit===20?' active':'';?>" href="?limit=20<?= $adminWantsFull ? '&full=1':''; ?>">Top 20</a>

          <?php if ($detectedIsAdmin === 1): ?>
            <?php if ($adminWantsFull): ?>
              <a class="btn btn-sm btn-outline-secondary" href="?limit=<?=$limit?>">Ẩn bớt tên</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-danger" href="?limit=<?=$limit?>&full=1">Hiện tên đầy đủ (Admin)</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if($err): ?>
        <div class="alert alert-danger" style="color:#b94a48;background:#f2dede;border:1px solid #eed3d7;">
          Lỗi truy vấn: <?php echo htmlspecialchars($err); ?>
        </div>
      <?php endif; ?>

      <table class="table table-hover table-nowrap" style="color:black!important;">
        <thead>
          <tr>
            <th scope="col">Top</th>
            <th scope="col">Nhân vật</th>
            <th scope="col">Sức Mạnh</th>
            <th scope="col">Hành Tinh</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        if($data && mysqli_num_rows($data)>0):
          while($row = mysqli_fetch_assoc($data)):
            $rawName = trim((string)($row['cName'] ?? ''));
            $showName = ($detectedIsAdmin === 1 && $adminWantsFull)
                        ? ($rawName !== '' ? $rawName : '—')
                        : mask_name($rawName);
        ?>
          <tr class="top_<?php echo $rank; ?>">
            <td><b>#<?php echo $rank++; ?></b></td>
            <td><?php echo htmlspecialchars($showName); ?></td>
            <td><?php echo fmt_power($row['cPower']); ?></td>
            <td><?php echo planet((int)$row['cgender']); ?></td>
          </tr>
        <?php
          endwhile;
        else:
          echo '<tr><td colspan="4" class="text-center">Chưa có dữ liệu</td></tr>';
        endif;
        ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php require_once('../core/end.php'); ?>
