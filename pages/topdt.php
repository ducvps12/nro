<?php
// /pages/topdt.php — Top Sức Mạnh Đệ Tử: mask tên, admin bật xem full, 10/20, lọc active
require_once('../core/config.php');   // dùng chung kết nối/mật khẩu
require_once('../core/head.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/** ======= Helper ======= */
function fmt_power($v) {
    $v = (float)$v;
    if ($v >= 1000000000) return number_format($v/1000000000, 1, '.', '') . ' Tỷ';
    if ($v >= 1000000)    return number_format($v/1000000,    1, '.', '') . ' Tr';
    if ($v >= 1000)       return number_format($v/1000,       1, '.', '') . ' K';
    return number_format($v, 0, ',', '');
}
function planet_text($g) {
    return ($g==1) ? 'Namec' : (($g==2) ? 'Xayda' : 'Trái đất');
}
function mask_name($s){
  $s = trim((string)$s);
  if ($s === '') return '—';
  $len = mb_strlen($s, 'UTF-8');
  if ($len <= 3) return mb_substr($s,0,1,'UTF-8') . '***';
  return mb_substr($s,0,3,'UTF-8') . '***' . mb_substr($s,-2,2,'UTF-8');
}

/** ======= Tên DB ======= */
if (!defined('DB_ROOT'))   define('DB_ROOT',   'nro_root');
if (!defined('DB_PLAYER')) define('DB_PLAYER', 'nro_player');

/** ======= Detect admin + toggle xem full ======= */
$detectedIsAdmin = isset($isAdmin) ? (int)$isAdmin : 0;
if (!isset($isAdmin)) {
  $u = $_SESSION['logger']['username'] ?? null;
  if ($u) {
    $uEsc = $config->real_escape_string($u);
    $rs = $config->query("SELECT isAdmin FROM `".DB_ROOT."`.`account` WHERE username='$uEsc' LIMIT 1");
    if ($rs && $row = $rs->fetch_assoc()) $detectedIsAdmin = (int)$row['isAdmin'];
  }
}
$adminWantsFull = ($detectedIsAdmin === 1) && (isset($_GET['full']) && $_GET['full'] == '1');

/** ======= Config hiển thị ======= */
$limit      = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit      = ($limit === 20) ? 20 : 10; // chỉ cho 10/20
$onlyActive = isset($_GET['active']) ? (intval($_GET['active']) === 1) : false; // ?active=1 để lọc petStatus=1

/** ======= Thử JOIN chéo DB trên 1 kết nối ======= */
$where = $onlyActive ? "WHERE pz.petStatus = 1" : "";
$sqlJoin = "
SELECT 
    pz.name      AS petName,
    pz.cPower    AS petPower,
    pl.cName     AS ownerName,
    pl.cgender   AS ownerGender,
    pl.timestamp AS createdAt
FROM `".DB_PLAYER."`.`petzs` AS pz
JOIN `".DB_ROOT."`.`player`  AS pl
  ON pl.playerId = pz.playerId
{$where}
ORDER BY pz.cPower DESC, pl.timestamp ASC
LIMIT {$limit}
";
$data = mysqli_query($config, $sqlJoin);
$err  = $data === false ? mysqli_error($config) : null;

/** ======= Fallback: 2 bước nếu JOIN chéo bị chặn ======= */
$rows = [];
if ($data !== false) {
    while ($r = mysqli_fetch_assoc($data)) $rows[] = $r;
} else {
    // kết nối phụ tới DB_PLAYER nếu chưa có
    if (!isset($config_player) || !$config_player) {
        // cố gắng lấy lại thông số từ scope của config.php
        $server = isset($serverName) ? $serverName : '127.0.0.1';
        $user   = isset($userName)   ? $userName   : 'root';
        $pass   = isset($password)   ? $password   : '';
        $config_player = @mysqli_connect($server, $user, $pass, DB_PLAYER);
        if ($config_player) @mysqli_set_charset($config_player, 'utf8mb4');
    }

    if ($config_player) {
        $where2 = $onlyActive ? "WHERE petStatus = 1" : "";
        $pets = mysqli_query($config_player, "
            SELECT playerId, name AS petName, cPower AS petPower
            FROM petzs
            {$where2}
            ORDER BY cPower DESC
            LIMIT {$limit}
        ");
        if ($pets) {
            while ($pz = mysqli_fetch_assoc($pets)) {
                $pid = (int)$pz['playerId'];
                $pl  = mysqli_query($config, "SELECT cName AS ownerName, cgender AS ownerGender, timestamp AS createdAt FROM `".DB_ROOT."`.`player` WHERE playerId={$pid} LIMIT 1");
                $owner = $pl && mysqli_num_rows($pl) ? mysqli_fetch_assoc($pl) : ['ownerName'=>'—','ownerGender'=>0,'createdAt'=>0];
                $rows[] = [
                    'petName'     => $pz['petName'],
                    'petPower'    => $pz['petPower'],
                    'ownerName'   => $owner['ownerName'],
                    'ownerGender' => $owner['ownerGender'],
                    'createdAt'   => $owner['createdAt'],
                ];
            }
        } else {
            $err = 'Không thể truy vấn petzs trên DB PLAYER: ' . mysqli_error($config_player);
        }
    } else {
        if (!$err) $err = 'Không có quyền JOIN chéo và cũng không mở được kết nối DB PLAYER.';
    }
}
?>
<div class="p-1 mt-1 ibox-content" style="border-radius:7px;">
  <main>
    <div class="top-nav">
      <a class="top-btn" href="/pages/topdonate1.php">Top Đại Gia</a>
      <a class="top-btn" href="/pages/topnv.php">Top Nhiệm Vụ</a>
      <!-- <a class="top-btn active" href="/pages/topdt.php">Top Đệ Tử</a> -->
    </div>

    <h1 class="h3 mb-3 font-weight-normal" style="padding-top:24px;">
      <b><center>Top Sức Mạnh Đệ Tử</center></b>
    </h1>

    <div class="table-responsive">
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="line-height:15px;font-size:12px;padding:2px 5px 8px;gap:8px;">
        <span class="text-black">
          Cập nhật 5 phút 1 lần
          <?php if ($detectedIsAdmin === 1 && $adminWantsFull): ?>
            • (Admin) Đang hiển thị tên đầy đủ
          <?php else: ?>
            • Tên đệ & chủ được che bớt để hạn chế spam/đào bới
          <?php endif; ?>
        </span>
        <div class="d-flex gap-1">
          <a class="btn btn-sm btn-outline-dark<?= $limit===10?' active':'';?>" href="?limit=10<?= $onlyActive?'&active=1':''; ?><?= $adminWantsFull ? '&full=1':''; ?>">Top 10</a>
          <a class="btn btn-sm btn-outline-dark<?= $limit===20?' active':'';?>" href="?limit=20<?= $onlyActive?'&active=1':''; ?><?= $adminWantsFull ? '&full=1':''; ?>">Top 20</a>
          <a class="btn btn-sm btn-outline-dark<?= $onlyActive?' active':'';?>" href="?limit=<?=$limit?>&active=1<?= $adminWantsFull ? '&full=1':''; ?>">Chỉ đệ đang hoạt động</a>

          <?php if ($detectedIsAdmin === 1): ?>
            <?php if ($adminWantsFull): ?>
              <a class="btn btn-sm btn-outline-secondary" href="?limit=<?=$limit?><?= $onlyActive?'&active=1':''; ?>">Ẩn bớt tên</a>
            <?php else: ?>
              <a class="btn btn-sm btn-outline-danger" href="?limit=<?=$limit?><?= $onlyActive?'&active=1':''; ?>&full=1">Hiện tên đầy đủ (Admin)</a>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($err)): ?>
        <div class="alert alert-warning" style="color:#8a6d3b;background:#fcf8e3;border:1px solid #faebcc;">
          Thông báo: <?php echo htmlspecialchars($err); ?><br>
          <small>Nếu lỗi do quyền JOIN chéo, hãy cấp quyền:
          <code>GRANT SELECT ON <?php echo DB_PLAYER; ?>.* TO 'root'@'%'; GRANT SELECT ON <?php echo DB_ROOT; ?>.* TO 'root'@'%'; FLUSH PRIVILEGES;</code></small>
        </div>
      <?php endif; ?>

      <table class="table table-hover table-nowrap" style="color:black!important;">
        <thead>
          <tr>
            <th>Top</th>
            <th>Tên Đệ</th>
            <th>Sức Mạnh Đệ</th>
            <th>Chủ Nhân</th>
            <th>Hành Tinh</th>
          </tr>
        </thead>
        <tbody>
        <?php
        if (!empty($rows)):
          $rank=1;
          foreach ($rows as $row):
            $rawPet   = trim((string)($row['petName'] ?? ''));
            $rawOwner = trim((string)($row['ownerName'] ?? ''));
            // Admin + full => show nguyên; còn lại => mask
            if ($detectedIsAdmin === 1 && $adminWantsFull) {
              $showPet   = ($rawPet   !== '' ? $rawPet   : '—');
              $showOwner = ($rawOwner !== '' ? $rawOwner : '—');
            } else {
              $showPet   = mask_name($rawPet);
              $showOwner = mask_name($rawOwner);
            }
        ?>
          <tr class="top_<?php echo $rank; ?>">
            <td><b>#<?php echo $rank++; ?></b></td>
            <td><?php echo htmlspecialchars($showPet); ?></td>
            <td><?php echo fmt_power($row['petPower']); ?></td>
            <td><?php echo htmlspecialchars($showOwner); ?></td>
            <td><?php echo planet_text((int)$row['ownerGender']); ?></td>
          </tr>
        <?php
          endforeach;
        else:
          echo '<tr><td colspan="5" class="text-center">Chưa có dữ liệu</td></tr>';
        endif;
        ?>
        </tbody>
      </table>
    </div>
  </main>
</div>

<?php require_once('../core/end.php'); ?>
