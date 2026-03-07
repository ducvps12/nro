<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
unset($_SESSION["errors"]);

$username = $_SESSION['logger']['username'] ?? '';

/* Số dư */
$vnd = 0;
if ($username !== '') {
  $sql_thoivang = "SELECT money FROM account WHERE username = '{$username}'";
  if ($result1 = $config->query($sql_thoivang)) {
    if ($result1->num_rows > 0) {
      $row1 = $result1->fetch_assoc();
      $vnd  = (int)$row1["money"];
    }
  }
}

/* Lấy head (avatar) */
$accountId = 0; 
$row = ['head' => null];

if ($username !== '') {
  $res = $config->query("SELECT id FROM account WHERE username = '{$username}'");
  if ($res && $res->num_rows > 0) {
    $r = $res->fetch_assoc();
    $accountId = (int)$r["id"];

    $res2 = $config->query("SELECT head FROM player WHERE playerId = {$accountId}");
    if ($res2 && $res2->num_rows > 0) {
      $row = $res2->fetch_assoc();
    }
  }
}

/* Quyền admin */
$isAdmin = 0;
if ($username !== '') {
  $resA = $config->query("SELECT id, isAdmin FROM account WHERE username = '{$username}'");
  if ($resA && $resA->num_rows > 0) {
    $rA      = $resA->fetch_assoc();
    $accountId = (int)$rA["id"];
    $isAdmin   = (int)$rA["isAdmin"];
  }
}
?>
<html lang="en">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title><?= $tieude; ?></title>
    <link rel="canonical" href="<?= $link_web; ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- favicon (đường dẫn tuyệt đối) -->
    <link rel="icon" type="image/x-icon" href="/chipasset/images/logo/LÔI LẠC GIF.gif">

    <!-- bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- jquery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>

    <!-- CSS nền site (đường dẫn tuyệt đối) -->
    <link rel="stylesheet" href="/chipasset/css/hoangvietdung.css?hoangvietdung=<?= rand(0,100000); ?>">

    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"></script>

    <style>
      #hoangvietdung { opacity: 1; }
      #hoangvietdung2{ padding:30px; background-color:rgba(0,0,0,0.3); }
      #custom-hr  { border:none; border-top:1px solid #000; margin:10px 0; }
      #custom-hr2 { border:none; border-top:1px solid #000; margin:2px 0; }
    </style>
  </head>
  <body class="girlkun-bg" id="hoangvietdung">
    <!-- nhạc 
    <audio autoplay>
      <source src="/music/noel.mp3" type="audio/mpeg">
    </audio>-->

    <div class="wrapper-site container-md p-1 col-sm-12 col-lg-6" style="border-radius: 7px;">
      <style>
        #snow { position: fixed; top: 0; left: 0; right: 0; bottom: 0; pointer-events: none; z-index: -70; }
      </style>
      <div id="snow"></div> 
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          var script = document.createElement('script');
          script.src = 'https://cdn.jsdelivr.net/particles.js/2.0.0/particles.min.js';
          script.onload = function () {
            particlesJS("snow", {
              "particles": {
                "number": {"value":75,"density":{"enable":true,"value_area":400}},
                "color": {"value":"#FFCC33"},
                "opacity":{"value":1,"random":true,"anim":{"enable":false}},
                "size":{"value":3,"random":true,"anim":{"enable":true}},
                "line_linked":{"enable":true},
                "move":{"enable":true,"speed":1,"direction":"top","random":true,"straight":false,"out_mode":"out","bounce":false,
                        "attract":{"enable":true,"rotateX":300,"rotateY":1200}}
              },
              "interactivity":{"events":{"onhover":{"enable":false},"onclick":{"enable":false},"resize":false}},
              "retina_detect": true
            });
          };
          document.head.append(script);
        });
      </script>

      <main>
        <!-- header -->
        <div class="header-site" style="background:#fab45c; border-radius:7px;">
          <!-- logo nhỏ 12+ -->
          <div class="text-center" style="line-height:15px;font-size:12px;padding:2px 5px 8px;">
            <img height="12" src="/chipasset/images/icon/12.png" style="vertical-align: middle;">
            <span class="text-black" style="vertical-align: middle;color:black;font-size:12px;">
              Dành cho người chơi trên 12 tuổi. Chơi quá 180 phút mỗi ngày sẽ có hại sức khỏe.
            </span>
          </div>

          <!-- LOGO / BANNER (có fallback) -->
          <div class="p-xs">
            <a href="/">
              <img src="<?= $logo; ?>"
                   onerror="this.onerror=null;this.src='<?= $logotb; ?>';"
                   style="display:block;margin-left:auto;margin-right:auto;max-width:350px;">
            </a>
          </div>

          <!-- download + forum (layout gọn, không bị lộn xộn) -->
          <div class="text-center mt-2">
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px;">
              <a href="<?= $java; ?>" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-download"></i> JAVA
              </a>
              <a href="<?= $pc; ?>" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-windows"></i> PC
              </a>
              <a href="<?= $adr; ?>" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-android"></i> APK
              </a>
              <!-- Bật nếu có file iOS -->
              <a href="<?= $ios; ?>" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-apple"></i> IPA
              </a>
              <a href="<?= $tf; ?>" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-apple"></i> TestFlight
              </a>
              <a href="https://zalo.me/g/ggqfai124" target="_blank" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-group"></i> Live
              </a>
              <a href="/pages/forum/index.php" class="btn btn-download text-white" style="border-radius:10px;width:120px;">
                <i class="fa fa-comments"></i> Diễn đàn
              </a>
            </div>

            <div class="text-center" style="line-height:15px;font-size:12px;padding:2px 5px 8px;">
              <span class="text-black" style="vertical-align:middle;line-height:36px;color:black;font-size:12px;">
                Tải phiên bản phù hợp để có trải nghiệm tốt.
              </span>
            </div>
          </div>
        </div>

        <!-- body: user info + nút nhanh -->
        <div class="col text-center mt-2" style="padding-bottom:36px;">
          <div class="user_name">
            <?php if (!empty($_SESSION['logger']['username'])) { ?>
              <center>
                <?php
                  $head = (int)($row["head"] ?? 0);
                  $known = [28,27,6,64,31,30,9,29,32];
                  if (in_array($head, $known)) {
                    echo '<img src="/chipasset/images/icon/'.$head.'.png" width="60" />';
                  } else {
                    echo '<img src="/chipasset/images/icon/13275.png" width="60" />';
                  }
                ?>
                <br>
              </center>
              <label><a style="color: White">Xin chào</a></label>
              <b style="color: red"><?= htmlspecialchars($_SESSION['logger']['username']) ?></b>
              <br><br> <span>Số dư : </span>
              <b style="color: red !important;"><?= number_format($vnd); ?></b> <i class="fa fa-database"></i>
              <br>
            <?php } ?>

            <?php if (!empty($isAdmin) && (int)$isAdmin === 1) { ?>
              <a href="/adminservice" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
                <i class="fa fa-credit-card"></i> Trang Quản Trị Admin
              </a>
            <?php } ?>
          </div>

          <?php if (!empty($_SESSION['logger']['username'])) { ?> 
            <a href="/naptien" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-credit-card"></i> Nạp tiền
            </a>
            <a href="/doimatkhau" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-address-card"></i> Đổi Password
            </a>
            <a href="/topsm" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-bar-chart"></i> Xếp Hạng
            </a>
            <a href="/dangxuat" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-sign-in"></i> Đăng Xuất
            </a> <br>
          <?php } else { ?> 
            <a href="/dangnhap" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-sign-in"></i> Đăng nhập
            </a>
            <a href="/dangky" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-user-plus"></i> Đăng ký
            </a>
            <a href="/topsm" class="btn btn-action m-1 text-white" style="border-radius: 10px;">
              <i class="fa fa-bar-chart"></i> Xếp Hạng
            </a>
            <a href="https://zalo.me/g/ggqfai124" target="_blank" class="btn btn-download text-white" style="border-radius: 10px;width: 100px;">
              <i class="fa fa-group"></i> Zalo
            </a>
          <?php } ?>
        </div>
