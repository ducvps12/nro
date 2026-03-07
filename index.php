<?php
require_once('core/config.php');
require_once('core/head.php');
$thongbao = null;
$thongbao_admin = null;
// Kiểm tra xem người dùng có phải là quản trị viên hay không
$sql_admin = "SELECT isAdmin FROM account WHERE username = '$username'";
$result_admin = $config->query($sql_admin);
$row_admin = ($result_admin && $result_admin->num_rows > 0) ? $result_admin->fetch_assoc() : null;
// Kiểm tra xem người dùng có hoạt động hay không
$sql_active = "SELECT islock FROM account WHERE username = '$username'";
$result_active = $config->query($sql_active);
$row_active = ($result_active && $result_active->num_rows > 0) ? $result_active->fetch_assoc() : null;
// Lấy ID tài khoản
$sql = "SELECT id FROM account WHERE username = '$username'";
$result = $config->query($sql);
if ($result->num_rows > 0) {
  $row_hvd = $result->fetch_assoc();
  $accountId = $row_hvd["id"];
  // Kiểm tra sự tồn tại của ID tài khoản trong bảng player
  $sql_check_player = "SELECT COUNT(*) as player_count FROM player WHERE playerId = '$accountId'";
  $result_check_player = $config->query($sql_check_player);
  $row_check_player = ($result_check_player && $result_check_player->num_rows > 0) ? $result_check_player->fetch_assoc() : null;
  $player_exists = ($row_check_player && $row_check_player['player_count'] > 0);
  // Lấy giới tính từ bảng player
  $sql_gender = "SELECT head FROM player WHERE playerId = $accountId";
  $result_gender = $config->query($sql_gender);
  $row_gender = ($result_gender && $result_gender->num_rows > 0) ? $result_gender->fetch_assoc() : null;
}
// Xử lý khi người dùng gửi bình luận
if (isset($_POST['submit']) && isset($_POST['comment'])) {
  $comment = $_POST['comment'];
  $baiviet_id = $_GET['id'];
  $userCaptcha = $_POST['captcha']; // Lấy câu trả lời captcha nhập từ người dùng

  // Lấy câu trả lời captcha lưu trong session
  $captchaAnswer = $_SESSION['captcha'];

  // Kiểm tra xem câu trả lời captcha có đúng không
  if ($userCaptcha != $captchaAnswer) {
    $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Captcha không đúng. Vui lòng thử lại.</span>';
  } else {
    if (empty($comment)) {
      $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Vui lòng nhập nội dung bình luận!</span>';
    } else {
      // Kiểm tra từ cấm trong nội dung bình luận
      $containsCensoredWords = false;
      foreach ($censoredWords as $word) {
        if (stripos($comment, $word) !== false) {
          $containsCensoredWords = true;
          break;
        }
      }

      if ($containsCensoredWords) {
        $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Vui lòng không sử dụng từ cấm trong bình luận!</span>';
      } else {
        $sql_comment = "INSERT INTO comment_bai_viet (baiviet_id, khach_id, noidung, time) VALUES ('$baiviet_id', '$accountId', '$comment', " . time() . ")";
        $result_comment = $config->query($sql_comment);
        if ($result_comment) {
          $thongbao = '<span style="color: green; font-size: 12px; font-weight: bold;">Bình luận thành công!</span>';
        } else {
          $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Đã xảy ra lỗi!</span>';
        }
        if (!$player_exists) {
          $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Hãy tạo nhân vật trước khi đăng bài!</span>';
        }
      }
    }
  }
}
if (isset($_GET['id'])) {
  $id_delete = $_GET['id'];
  if ($row_admin['isAdmin'] == 1) {
    if (isset($_GET['delete'])) {
      $sql_delete = "DELETE FROM baiviet WHERE id = $id_delete";

      if ($config->query($sql_delete) === TRUE) {
        echo '<script>window.location.href = "/";</script>';
      } else {
        $thongbao_admin =  '<span style="color: red; font-size: 12px; font-weight: bold;">Đã xảy ra lỗi!</span>';
      }
    }
  }
} else {
  $thongbao_admin = '<span style="color: red; font-size: 12px; font-weight: bold;">Bạn không có quyền truy cập!</span>';
}
// Truy vấn để lấy danh sách bài viết
$sql = "SELECT b.id, b.tieude, b.top_baiviet, b.new, b.noidung, b.time, a.username, p.head
    FROM baiviet AS b
    INNER JOIN account AS a ON b.id = a.id
    LEFT JOIN player AS p ON p.playerId = a.id";
$result = $config->query($sql);
$rows = array();
$topPosts = array();
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $rows[] = $row;

    if ($row['top_baiviet'] == 1) {
      $topPosts[] = $row;
    }
  }
}
// Sắp xếp mảng $rows theo trường 'time' giảm dần
usort($rows, function ($a, $b) {
  return strtotime($b['time']) - strtotime($a['time']);
});
// Lấy giá trị trang hiện tại từ URL
$currentpage = isset($_GET['page']) ? $_GET['page'] : 1;

// Số bài viết hiển thị trên mỗi trang
$postsPerPage = 10;

// Tính toán giá trị startIndex và endIndex
$totalPosts = count($rows);
$totalPages = ceil($totalPosts / $postsPerPage);

$startIndex = ($currentpage - 1) * $postsPerPage;
$endIndex = $startIndex + $postsPerPage;

// Giới hạn giá trị startIndex và endIndex
$startIndex = max(0, $startIndex);
$endIndex = min($totalPosts, $endIndex);

// Lấy dữ liệu bài viết phù hợp với trang hiện tại
$displayedPosts = array_slice($rows, $startIndex, $endIndex - $startIndex);

$endIndex = $startIndex + $postsPerPage;
if (isset($_GET['id']) && !empty($rows)) {
  $id = $_GET['id'];

  foreach ($rows as $row) {
    if ($row['id'] == $id) {
?>
      <main>
      
        <div class="mt-1 alert alert-warning" style="background: #fab45c; border-radius: 7px; box-shadow: black 0px 0px 5px;">
          <div class="alert alert-danger" style="background: #fab45c; border-radius: 7px;">
            <div class="col">
              <center><?= $thongbao_admin; ?></center>
              <table cellpadding="0" cellspacing="0" width="100%" style="font-size: 13px;">
                <tbody>
                  <tr>
                  <audio autoplay>
                    <source src="/music/tet.mp3" type="audio/mpeg">
                </audio>
                    <td width="60px;" style="vertical-align: top;">
                      <div class="text-center" style="margin-left: -10px;">
                        <?php if ($row['top_baiviet'] == 1) { ?>
                          <img src="../chipasset/images/icon/admin.png" width="32" /><br>
                          <div style="font-size: 9px; padding-top: 5px">
                            <b style="color: red;">Admin</b>
                          </div>
                        <?php } else { ?>
                          <?php
                          if ($row["head"] == 28) {
                            echo '<img src="../chipasset/images/icon/28.png" width="32" />';
                          } elseif ($row["head"] == 27) {
                            echo '<img src="../chipasset/images/icon/27.png" width="32" />';
                          } elseif ($row["head"] == 6) {
                            echo '<img src="../chipasset/images/icon/6.png" width="32" />';
                          } elseif ($row["head"] == 64) {
                            echo '<img src="../chipasset/images/icon/64.png" width="32" />';
                          } elseif ($row["head"] == 31) {
                            echo '<img src="../chipasset/images/icon/31.png" width="32" />';
                          } elseif ($row["head"] == 30) {
                            echo '<img src="../chipasset/images/icon/30.png" width="32" />';
                          } elseif ($row["head"] == 9) {
                            echo '<img src="../chipasset/images/icon/9.png" width="32" />';
                          } elseif ($row["head"] == 29) {
                            echo '<img src="../chipasset/images/icon/29.png" width="32" />';
                          } elseif ($row["head"] == 32) {
                            echo '<img src="../chipasset/images/icon/32.png" width="32" />';
                          } else {
                            echo '<img src="../chipasset/images/icon/13275.png" width="32" />';
                          }
                          ?>
                          <br>
                          <div style="font-size: 9px; padding-top: 5px;">
                            <b style="color: blue;"><?= $row['username']; ?></b>
                          </div>
                        <?php } ?>
                      </div>
                    </td>
                    <td class="bg bg-light" style="background: #fab45c; border-radius: 7px;">
                      <div class="row" style="font-size: 9px; padding: 5px 7px;">
                        <div class="col">
                          <span><?= duxng_time($row['time']); ?></span>
                        </div>
                        <div class="col text-right">
                          <?php if ($row_admin['isAdmin'] == 1) { ?>
                            <span><b>[<a href="?id=<?= $id_delete; ?>&delete=1">Xoá Bài Viết</a>]</b></span>
                          <?php } ?>
                        </div>
                      </div>
                      <hr id="custom-hr2">
                      <div class="row" style="padding: 0px 7px 10px;">
                        <div class="col">
                          <?php if ($row['top_baiviet'] == 1) { ?>
                            <span><a style="color:orange" class="alert-link text-decoration-none"><?= $row['tieude']; ?><a></span>
                          <?php } else { ?>
                            <span><a style="color:blue" class="alert-link text-decoration-none"><?= $row['tieude']; ?><a></span>
                          <?php } ?>
                          <a>
                            <br>
                            <span><?=chip_bbcode($row['noidung']);?></span>
                          
                          </a>
                        </div>
                        <a></a>
                      </div>
                      <a></a>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <?php if ($_SESSION['logger']['username']) { ?>
            <hr>

          <?php } ?>
          <div class="d-flex justify-content-end">
            <?php if ($totalPages > 1) { ?>
              <ul class="pagination">
                <?php if ($currentpage > 1) { ?>
                  <a class="btn btn-action text-white" href="?page=<?php echo ($currentpage - 1); ?>" aria-label="Previous" style="border-radius: 15px 0px 0px 15px; pointer-events: none;"><span aria-hidden="true">«</span></a>
                  </a>
                <?php } ?>
                <?php
                $numAdjacent = 2; // Số trang số trung gian hiển thị xung quanh trang hiện tại

                $startPage = max(1, $currentpage - $numAdjacent);
                $endPage = min($totalPages, $currentpage + $numAdjacent);

                if ($startPage > 1) {
                  // Hiển thị trang đầu tiên và dấu "..."
                ?>
                  <li class=""><a href="?page=1" class="btn btn-action text-white">1</a></li>
                  <?php if ($startPage > 2) { ?>
                    <li class="disabled"><a class="btn btn-action text-white">...</a></li>
                  <?php }
                }

                for ($page = $startPage; $page <= $endPage; $page++) {
                  ?>
                  <li class=""><a href="?page=<?php echo $page; ?>" class="btn btn-<?php echo ($page == $currentpage) ? 'warning' : 'action'; ?> text-white"><?php echo $page; ?></a></li>
                  <?php
                }

                if ($endPage < $totalPages) {
                  // Hiển thị dấu "..." và trang cuối cùng
                  if ($endPage < ($totalPages - 1)) {
                  ?>
                    <li class="disabled"><a class="btn btn-action text-white">...</a></li>
                  <?php } ?>
                  <li class=""><a href="?page=<?php echo $totalPages; ?>" class="btn btn-action text-white"><?php echo $totalPages; ?></a></li>
                <?php }

                if ($currentpage < $totalPages) { ?>
                  <a class="btn btn-action text-white" href="?page=<?php echo ($currentpage + 1); ?>" aria-label="Next" style="border-radius: 0px 15px 15px 0px; "><span aria-hidden="true">»</span></a>
                <?php } ?>
              </ul>
            <?php } ?>
          </div>
          <hr>
        </div>
      </main>
  <?php }
  }
} else { ?>
 <main>
    <div class="p-1 pb-1 mt-1 alert alert-warning" style="background: #fab45c; border-radius: 7px;">
        <div class="alert" style="background: #fab45c; border-radius: 7px;">
            <h5><b>Thông Báo</b></h5>
         
            <?php foreach ($topPosts as $post) : ?>
                <div class="alert border" style="background: #b5f5f5; border-radius: 7px;">
                    <div class="topic_name">
                        <div style="width: 55px; float: left; margin-right: 10px;">
                            <img class="" src="../chipasset/images/icon/admin.png" style="border-color: red; width: 50px; height: 55px;">
                        </div>
                        <a class="alert-link text-danger url" href="?id=<?= $post['id']; ?>">
                            <span><?= $post['tieude']; ?></span>
                        </a>
                        <?php if ($post['new'] == 1) { ?><img width="35" src="https://bircu-journal.com/public/site/images/bircuadmin/new.gif"><?php } ?>
                        <div class="box_name_eman">bởi <b><font style="color: red;">Admin</font></b> - <span><?= duxng_time($post['time']); ?></span></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Additional Information Section -->
     <div class="intro" style="background: #fab45c; border-radius: 7px; padding: 20px;">
  <h2 style="font-size:22px;color:#664d03;">Thông Tin Cập Nhật</h2>
  <!-- <p style="padding: 0 24px; color: #343a40; font-size: 16px;">
    - Thời gian đi vào hoạt động: <b>20h Ngày 25/6</b>, Mọi chi tiết vui lòng theo dõi FanPage để cập nhật thông tin.<br>
    - <b>Giftcode:</b> atula1<br>
    - <b>Sever dame gốc:</b> Cày chay từ đồ thường, đồ max 8s.<br>
    - <b>Chỉ số gốc:</b> HP-MP 650k, Dame 31k.<br>
    - <b>Skh Up:</b> 3 map đầu, Vàng Up Quái.<br>
    - <b>Kick Super:</b> Săn đệ tử.<br>
    - <b>Linh Thú:</b> Bên trái siêu thị.<br>
    - <b>Tiền tệ:</b> Thỏi Vàng, Ngọc Xanh.<br>
    - Mỗi thứ 7 hàng tuần có rồng vô cực thực hiện cho anh em một điều ước toàn sever, chỉ cần có 5 tỉ sức mạnh trở lên là nhận được.<br>
  </p> -->

  <div class="border-danger border-top mt-4"></div>

  <!--<h2 style="font-size:22px;color:#664d03;">Chi Tiết Thêm</h2>-->
  <!--<p style="padding: 0 24px; color: #343a40; font-size: 16px;">-->
  <!--  - <b>Thời gian đi vào hoạt động:</b> 20h Ngày 25/6.<br>-->
  <!--  - <b>Hệ thống Sever:</b> Dame gốc, cày chay, đồ max 8s.<br>-->
  <!--  - Chỉ số cơ bản: HP-MP 650k, Dame 31k.<br>-->
  <!--  - Hỗ trợ nâng cấp map 3 đầu và Vàng Up Quái.<br>-->
  <!--  - Săn đệ tử có thể được kick khi tham gia Super.<br>-->
  <!--  - Linh Thú xuất hiện bên trái siêu thị.<br>-->
  <!--  - Tiền tệ chính: Thỏi Vàng và Ngọc Xanh.<br>-->
  <!--</p>-->

  <div class="text-center pt-3">
    <img style="height:90px; width:96px" src="img/skill-01.gif">
    <img style="height:90px; width:96px" src="img/skill-02.gif">
    <img style="height:90px; width:96px" src="img/skill-03.gif">
    <img style="height:90px; width:96px" src="img/skill-04.gif">
    <img style="height:90px; width:96px" src="img/skill-05.gif">
  </div>

  <div class="border-danger border-top mt-4"></div>
</div>


      <div class="d-flex justify-content-between">
        <?php if ($_SESSION['logger']['username'] && $row_active['active'] == 1 && $player_exists) { ?>
          <div>
            <a class="btn btn-action text-white" routerlink="post" href="/pages/dangbai_diendan.php" style="border-radius: 7px;">Đăng bài</a>
          </div>
        <?php } ?>
        <?php if ($totalPages > 1) { ?>
          <ul class="pagination">
            <?php if ($currentpage > 1) { ?>
              <a class="btn btn-action text-white" href="?page=<?php echo ($currentpage - 1); ?>" aria-label="Previous" style="border-radius: 15px 0px 0px 15px; pointer-events: none;"><span aria-hidden="true">«</span></a>
              </a>
            <?php } ?>
            <?php
            $numAdjacent = 2; // Số trang số trung gian hiển thị xung quanh trang hiện tại

            $startPage = max(1, $currentpage - $numAdjacent);
            $endPage = min($totalPages, $currentpage + $numAdjacent);

            if ($startPage > 1) {
              // Hiển thị trang đầu tiên và dấu "..."
            ?>
              <li class=""><a href="?page=1" class="btn btn-action text-white">1</a></li>
              <?php if ($startPage > 2) { ?>
                <li class="disabled"><a class="btn btn-action text-white">...</a></li>
              <?php }
            }

            for ($page = $startPage; $page <= $endPage; $page++) {
              ?>
              <li class=""><a href="?page=<?php echo $page; ?>" class="btn btn-<?php echo ($page == $currentpage) ? 'warning' : 'action'; ?> text-white"><?php echo $page; ?></a></li>
              <?php
            }

            if ($endPage < $totalPages) {
              // Hiển thị dấu "..." và trang cuối cùng
              if ($endPage < ($totalPages - 1)) {
              ?>
                <li class="disabled"><a class="btn btn-action text-white">...</a></li>
              <?php } ?>
              <li class=""><a href="?page=<?php echo $totalPages; ?>" class="btn btn-action text-white"><?php echo $totalPages; ?></a></li>
            <?php }

            if ($currentpage < $totalPages) { ?>
              <a class="btn btn-action text-white" href="?page=<?php echo ($currentpage + 1); ?>" aria-label="Next" style="border-radius: 0px 15px 15px 0px; "><span aria-hidden="true">»</span></a>
            <?php } ?>
          </ul>
        <?php } ?>
      </div>
    </div>
  </main>
  <div class="modal right fade" id="Noti_Home" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true" data-mdb-backdrop="static" data-mdb-keyboard="true">
    <div class="modal-dialog modal-side modal-bottom-right ">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #b5f5f5; color: #fab45c; text-align: center;">
          <img src=<?= $logotb; ?> style="display: block; margin-left: auto; margin-right: auto; max-width: 250px;">
        </div>
        <div class="modal-body">
          <center>
		  <h6 style="padding: 10px; color: red;">
            Cảnh báo: Hiện tại có tài khoản giả mạo admin!<br>
            Fake Zalo Admin Đi xin sỏ Rồi Làm ăn chung gì đó !
            Admin không có nhu cầu nhắn tin riêng hay hỏi xin ai 
            Vui lòng kiểm tra thông tin kỹ lưỡng trước khi giao dịch.
            </h6>
            <h6 style="padding: 10px">
              Tham gia các nền tảng mạng xã hội !<br>
            </h6>
            <a href=<?= $box_zalo; ?> class="btn btn-download" style="border-radius: 10px; color: #FFFFFF;" target="_blank"><b>Box Zalo</b></a>
            <a href=<?= $fanpage; ?> class="btn btn-download" style="border-radius: 10px; color: #FFFFFF;" target="_blank"><b>Fanpage</b></a>
      <a class="btn btn-download" style="border-radius: 10px; color: #FFFFFF;" data-bs-dismiss="modal" aria-label="Close"><b>Đóng</b></a>
          </center>
        </div>
      </div>
    </div>
  </div>
  <script>
    // Ngăn chặn mở Developer Tools bằng F12 hoặc chuột phải
    document.addEventListener('keydown', function(event) {
        if (event.key === 'F12' || (event.ctrlKey && event.shiftKey && event.key === 'I')) {
            event.preventDefault();
            window.location.href = 'f12.php'; // Điều hướng đến trang thông báo
        }
    });

    // Ngăn chặn mở Developer Tools bằng chuột phải
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
        window.location.href = 'f12.php';
    });

    // Kiểm tra nếu Developer Tools đang mở
    (function() {
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                window.location.href = 'f12.php';
            }
        });
        console.log(element);
    })();
</script>

<div class="intro" style="background: #fab45c; border-radius: 7px; padding: 20px;">
  <h2 style="font-size:22px;color:#664d03;">Nổi Bật</h2>
  <p style="padding: 0 24px; color: #343a40; font-size: 16px;">
    - Mở thành viên miễn phí, cày sức mạnh, đua top dễ dàng<br>
    - Đăng ký tài khoản, tải game tất cả đều miễn phí<br>
    - Game hướng tới sự lâu dài, đặt cảm giác trải nghiệm của người chơi lên hàng đầu<br>
    - Game được quảng bá tiếp cận rộng rãi, thị trường đông đảo<br>
    - Admin không can thiệp vào game
  </p>

  <div class="border-danger border-top mt-4"></div>

  <h2 style="font-size:22px;color:#664d03;">Giới thiệu</h2>
  <p style="padding: 0 24px; color: #343a40; font-size: 16px;">
    - Thể loại hành động, nhập vai. Trực tiếp điều khiển nhân vật hành động. Dễ chơi, dễ điều khiển nhân vật. Đồ họa sắc nét. Có phiên bản đồ họa cao cho điện thoại mạnh và phiên bản pixel cho máy cấu hình thấp.<br>
    - Cốt truyện bám sát nguyên tác. Người chơi sẽ gặp tất cả nhân vật từ Bunma, Quy lão kame, Jacky-chun, Tàu Pảy Pảy... cho đến Fide, Pic, Poc, Xên, Broly, đội Bojack.<br>
    - Đặc điểm nổi bật nhất: Tham gia đánh doanh trại độc nhãn. Tham gia đại hội võ thuật. Tham gia săn lùng ngọc rồng để mang lại điều ước cho bản thân.<br>
    - Tương thích tất cả các dòng máy trên thị trường hiện nay: Máy tính PC Windows, Điện thoại di động Nokia Java, Android và máy tính bảng Android.
  </p>

  <div class="text-center pt-3">
    <img style="height:90px; width:96px" src="img/skill-01.gif">
    <img style="height:90px; width:96px" src="img/skill-02.gif">
    <img style="height:90px; width:96px" src="img/skill-03.gif">
    <img style="height:90px; width:96px" src="img/skill-04.gif">
    <img style="height:90px; width:96px" src="img/skill-05.gif">
  </div>

  <div class="border-danger border-top mt-4"></div>

  <h2 style="font-size:22px;color:#664d03;">Hướng Dẫn Tân Thủ</h2>
  <p style="padding: 0 24px; color: #343a40; font-size: 16px;">
    - 1. Đăng ký tài khoản<br>
    Chú Bé Rồng sử dụng Tài Khoản riêng, không chung với bất kỳ Trò Chơi nào khác.<br>
    Bạn có thể đăng ký tài khoản miễn phí ngay trong game, hoặc trên trang Diễn Đàn.<br>
    Khi đăng ký, bạn nên sử dụng đúng số điện thoại hoặc email thật của mình.<br>
    Nếu sử dụng thông tin sai, người có số điện thoại hoặc email thật sẽ có thể lấy mật khẩu của bạn.<br>
    Số điện thoại và email của bạn sẽ không hiện ra cho người khác thấy. Admin không bao giờ hỏi mật khẩu của bạn.<br><br>

    - 2. Hướng dẫn điều khiển<br>
    Đối với máy bàn phím: Dùng phím mũi tên, phím số, để điều khiển nhân vật. Phím chọn giữa để tương tác.<br>
    Đối với máy cảm ứng: Dùng tay chạm vào màn hình cảm ứng để di chuyển. Chạm nhanh 2 lần vào 1 đối tượng để tương tác.<br>
    Đối với PC: Dùng chuột, click chuột phải để di chuyển, click chuột trái để chọn, click đôi vào đối tượng để tương tác.<br><br>

    - 3. Một số thông tin căn bản<br>
    - Đậu thần dùng để tăng KI và HP ngay lập tức.<br>
    - Bạn chỉ mang theo người được 10 hạt đậu. Nếu muốn mang nhiều hơn, hãy xin từ bạn bè trong Bang.<br>
    - Tất cả các sách kỹ năng đều có thể học miễn phí tại Quy Lão Kame, khi bạn có đủ điểm tiềm năng.<br>
    - Bạn không thể bay, dùng kỹ năng, nếu hết KI.<br>
    - Tấn công quái vật cùng bạn bè trong Bang sẽ mang lại nhiều điểm tiềm năng hơn đánh một mình.<br>
    - Tập luyện với bạn bè tại khu vực thích hợp sẽ mang lại nhiều điểm tiềm năng hơn đánh quái vật.<br>
    - Khi được nâng cấp, đậu thần sẽ phục hồi nhiều HP và KI hơn.<br>
    - Vào trò chơi đều đặn mỗi ngày để nhận được Ngọc miễn phí.<br>
    - Đùi gà sẽ phục hồi 100% HP, KI. Cà chua phục hồi 100% KI. Cà rốt phục hồi 100% HP.<br>
    - Cây đậu thần kết một hạt sau một thời gian, cho dù bạn đang offline.<br>
    - Sau 3 ngày không tham gia trò chơi, bạn sẽ bị giảm sức mạnh do lười luyện tập.<br>
    - Bạn sẽ giảm thể lực khi đánh quái, nhưng sẽ tăng lại thể lực khi không đánh nữa.<br>
    - Ngoài ra, bạn có thể tham khảo những thông tin sau để có thể dễ dàng tham gia trò chơi hơn.
  </p>
</div>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#Noti_Home').modal('show');
    })
  </script>
<?php } ?>
<?php require_once('core/end.php'); ?>
