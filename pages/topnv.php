<?php
// /pages/topnv.php — BXH Nhiệm Vụ: cName; user che tên, admin bật xem full
require_once('../core/config.php');
require_once('../core/head.php');

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ====== MAPPING NHIỆM VỤ ====== */
$missions = [
    0 => ['name' => 'Nhiệm vụ đầu tiên', 'details' => 'Chi tiết nhiệm vụ'],
    1 => ['name' => 'Nhiệm vụ tập luyện', 'details' => 'Mộc nhân được đặt nhiều tại %1, ngay trước nhà ông %2\nHãy đánh ngã 20 mộc nhân...'],
    2 => ['name' => 'Nhiệm vụ tìm thức ăn', 'details' => 'Tìm đến %3, tiêu diệt bọn quái %4 và nhặt về 10 đùi gà...'],
    3 => ['name' => 'Tìm kiếm sao băng', 'details' => 'Đi khám phá xem vật thể lạ vừa rơi xuống hành tinh...'],
    4 => ['name' => 'Nhiệm vụ khó khăn', 'details' => 'Đi tới %6, tiêu diệt lũ %4 mẹ...'],
    5 => ['name' => 'Nhiệm vụ gia tăng sức mạnh', 'details' => 'Hãy đi tập luyện để gia tăng sức mạnh\nThưởng 20000 sức mạnh\nThưởng 20000 tiềm năng'],
    6 => ['name' => 'Nhiệm vụ trò chuyện', 'details' => 'Đi tới trạm tàu vũ trụ, trò chuyện với %7'],
    7 => ['name' => 'Nhiệm vụ giải cứu', 'details' => 'Lên đường tiêu diệt bọn %9, giải cứu %8\nQuay về báo cáo với %7 để nhận thưởng'],
    8 => ['name' => 'Nhiệm vụ ân nhân xuất sắc', 'details' => 'Về %1, gặp và trò chuyện với %8\nSau đó về nhà kể lại mọi chuyện với ông %2'],
    9 => ['name' => 'Nhiệm vụ tiên học lễ', 'details' => 'Tìm đường tới %11, trò chuyện với %10 và xin làm đệ tử'],
    10 => ['name' => 'Nhiệm vụ học phí', 'details' => 'Tiêu diệt %12 thể hiện sức mạnh cho %10 thấy'],
    11 => ['name' => 'Nhiệm vụ kết giao', 'details' => 'Thể hiện thiện chí đoàn kết\nMở rộng các mối quan hệ\nKết bạn với người chưa có bang hội'],
    12 => ['name' => 'Nhiệm vụ xin phép', 'details' => 'Quay về nhà xin ông %2 cho phép tham gia bang hội cái bang'],
    13 => ['name' => 'Nhiệm vụ gia nhập bang hội', 'details' => 'Gia nhập 1 bang hội cùng những người đồng đội thiện chí\nCùng làm nhiệm với nhau không quản khó khăn'],
    14 => ['name' => 'Nhiệm vụ bang hội lần 1', 'details' => 'Cùng phối hợp với 2 người đồng đội lên đường làm nhiệm vụ\nGợi ý:\nHeo rừng xuất hiện tại rừng Bamboo...'],
    15 => ['name' => 'Tìm Truyện Đôrêmon Tập 2', 'details' => 'Tiêu diệt %12 Chúng đang giữ cuốn truyện'],
    16 => ['name' => 'Nhiệm vụ bang hội lần 2', 'details' => 'Hãy cùng 2 người đồng đội bang hội của mình để chiến đấu hết mình...'],
    17 => ['name' => 'Nhiệm vụ thách đấu', 'details' => 'Thách đấu 10 người hoặc bật cờ và tiêu diệt 10 người khác'],
    18 => ['name' => 'Nhiệm vụ tiêu diệt Boss Trùm', 'details' => 'Đạt 1.500.000 sức mạnh để trở thành Siêu nhân\nTiêu diệt Akkuman tại Thành phố Vegeta...'],
    19 => ['name' => 'Nhiệm vụ thử thách', 'details' => 'Đạt 15 triệu sức mạnh vào doanh trại Độc Nhãn tìm diệt Trung Úy Trắng...'],
    20 => ['name' => 'Nhiệm vụ cam go', 'details' => 'Đạt 15 triệu sức mạnh Vào doanh trại Độc Nhãn tìm diệt Trung Úy Trắng...'],
    21 => ['name' => 'Nhiệm vụ bất khả thi', 'details' => 'Đạt 50 triệu sức mạnh Tiêu diệt bọn tay sai của Fide tại Xayda...'],
    22 => ['name' => 'Nhiệm vụ chạm trán đệ tử', 'details' => 'Tiêu diệt bọn đệ tử Kuku, Mập Đầu Đinh, Rambo của Fide đại ca tại Xayda...'],
    23 => ['name' => 'Nhiệm vụ Tiểu Đội Sát Thủ', 'details' => 'Tiêu diệt Tiểu Đội Sát Thủ do Fide đại ca gọi đến tại Xayda...'],
    24 => ['name' => 'Nhiệm vụ chạm trán Fide đại ca', 'details' => 'Fide đã xuất hiện tại núi khỉ vàng...'],
    25 => ['name' => 'Nhiệm vụ Chú bé đến tương lai', 'details' => 'Đến trái đất, rừng Bamboo, rừng dương xỉ...'],
    26 => ['name' => 'Nhiệm vụ chạm trán Rôbốt sát thủ lần 1', 'details' => 'Hãy đến thành phố phía nam Đảo Balê hoặc cao nguyên...'],
    27 => ['name' => 'Nhiệm vụ chạm trán Rôbốt sát thủ lần 2', 'details' => 'Trở về quá khứ, đến sân sau siêu thị Tiêu diệt bọn Rôbốt sát thủ...'],
    28 => ['name' => 'Nhiệm vụ chạm trán Rôbốt sát thủ lần 3', 'details' => 'Đến thành phố, ngọn núi, thung lũng phía Bắc...'],
    29 => ['name' => 'Nhiệm vụ Chạm trán Xên bọ hung', 'details' => 'Đến thị trấn Ginder Tiêu diệt Xên Bọ Hung cấp 1...'],
    30 => ['name' => 'Nhiệm vụ Cuộc dạo chơi của Xên', 'details' => 'Nâng sức đánh gốc lên 10K, đến gặp Thần mèo Karin...'],
    31 => ['name' => 'Nhiệm vụ Cuộc đối đầu không cân sức', 'details' => 'Cẩn thận!!! Những vị khách không mời mà tới thường tỏ ra nguy hiểm...'],
    32 => ['name' => 'Nhiệm vụ Chạm trán người ngoài hành tinh', 'details' => 'Bảo vệ hành tinh thực vật, hạ những kẻ xâm lược...\nThưởng 10 Tr sức mạnh'],
    33 => ['name' => 'Cuộc đối đầu không cân sức', 'details' => 'Đi theo Ôsin,Hạ vua địa ngục Drabura,Hạ Pui Pui,Hạ Pui Pui lần 2,Hạ Yancôn,Hạ Drabura lần 2,Hạ Mabư,Báo cáo với Ôsin'],
    34 => ['name' => 'Truyền thuyết về Siêu Xayda Huyền Thoại', 'details' => 'Tìm nhẫn thời không thừ Super Black Goku,Sử dụng nhẫn thời không,Tìm người xayda đang bị thương,Hạ 5.000 Tobi và Cabira,Nói chuyện với Bardock,Tìm kiếm Berry đi lạc,Mang Bery về hang cho Bardock,Tìm 99 thức ăn cho Bardock tại bìa rừng,Hạ 10.000 Tobi và Cabira tại bìa rừng,Nói chuyện với Bardock'],
    35 => ['name' => 'Hoàn thành toàn bộ nhiệm vụ', 'details' => 'Vui lòng chờ nhiệm vụ mới']
];

/* ====== Helpers ====== */
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
function missionName($id, $map)    { return $map[$id]['name']    ?? 'Nhiệm vụ không xác định'; }
function missionDetails($id, $map) { return $map[$id]['details'] ?? 'Không có chi tiết nhiệm vụ'; }

/* ====== Detect admin + toggle xem full ====== */
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

/* ====== Limit chỉ 10/20 ====== */
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$limit = ($limit === 20) ? 20 : 10;

/* ====== Có cột timestamp hay không ====== */
$hasTs = hasCol($config, 'player', 'timestamp');

/* ====== Cache 5 phút (file-based) ====== */
$cacheDir = sys_get_temp_dir() . '/topnv_cache';
if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
$cacheFile = $cacheDir . "/topnv_limit_{$limit}.json";
$cacheTTL = 300; // 5 phút

$rows = null;
$err = null;

// Kiểm tra cache còn hạn không
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTTL) {
    $cached = @json_decode(file_get_contents($cacheFile), true);
    if (is_array($cached)) {
        $rows = $cached;
    }
}

// Nếu không có cache hoặc hết hạn → query DB
if ($rows === null) {
    $sql = "
    SELECT cName, ctaskId".($hasTs ? ", timestamp" : "")."
    FROM `nro_root`.`player`
    ORDER BY CAST(ctaskId AS UNSIGNED) DESC".($hasTs ? ", timestamp ASC" : "")."
    LIMIT {$limit}";
    $data = mysqli_query($config, $sql);
    $err  = ($data === false) ? mysqli_error($config) : null;

    if ($data && !$err) {
        $rows = [];
        while ($r = mysqli_fetch_assoc($data)) {
            $rows[] = $r;
        }
        // Lưu cache
        @file_put_contents($cacheFile, json_encode($rows, JSON_UNESCAPED_UNICODE));
    }
}
?>
<div class="p-1 mt-1 ibox-content" style="border-radius:7px;">
  <main>
    <div class="top-nav">
      <a class="top-btn" href="/pages/topsm.php">Top Sức Mạnh</a>
      <a class="top-btn" href="/pages/topdonate1.php">Top Đại Gia</a>
    </div>

    <h1 class="h3 mb-3 font-weight-normal" style="padding-top:36px;">
      <b><center>Bảng Xếp Hạng Nhiệm Vụ</center></b>
    </h1>

    <div class="table-responsive">
      <div class="d-flex justify-content-between align-items-center flex-wrap" style="line-height:15px;font-size:12px;padding:2px 5px 8px;gap:8px;">
        <span class="text-black" style="vertical-align: middle;">
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

      <?php if ($err): ?>
        <div class="alert alert-danger" style="color:#b94a48;background:#f2dede;border:1px solid #eed3d7;">
          Lỗi truy vấn: <?php echo htmlspecialchars($err); ?>
        </div>
      <?php endif; ?>

      <table class="table table-hover table-nowrap" style="color:black!important;">
        <thead>
          <tr>
            <th scope="col">Top</th>
            <th scope="col">Nhân vật</th>
            <th scope="col">Nhiệm Vụ</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $rank = 1;
        if (!empty($rows)):
          foreach ($rows as $row):
            $rawName = trim((string)($row['cName'] ?? ''));
            // Admin + full => show nguyên; còn lại => mask
            $showName = ($detectedIsAdmin === 1 && $adminWantsFull)
                        ? ($rawName !== '' ? $rawName : '—')
                        : mask_name($rawName);
            $task_id      = (int)$row['ctaskId'];
            $task_name    = missionName($task_id, $missions);
            $task_details = missionDetails($task_id, $missions);
        ?>
          <tr class="top_<?php echo $rank; ?>">
            <td><b>#<?php echo $rank++; ?></b></td>
            <td><?php echo htmlspecialchars($showName); ?></td>
            <td>
              <b><?php echo htmlspecialchars($task_name); ?></b><br>
              <small><?php echo nl2br(htmlspecialchars($task_details)); ?></small>
            </td>
          </tr>
        <?php
          endforeach;
        else:
          echo '<tr><td colspan="3" class="text-center">Chưa có dữ liệu</td></tr>';
        endif;
        ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
<?php require_once('../core/end.php'); ?>
