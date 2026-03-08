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

  <h2 style="font-size:22px;color:#664d03;">Hướng Dẫn Các Cơ Chế Trong Game</h2>
  <div style="padding: 0 16px; color: #343a40; font-size: 14px;">

    <!-- 1. Điểm Tiềm Năng -->
    <h3 style="font-size:17px;color:#664d03;margin-top:16px;">⭐ 1. Điểm Tiềm Năng (Phân Bổ Chỉ Số Gốc)</h3>
    <p>Điểm Tiềm Năng là điểm bạn kiếm được khi lên cấp hoặc tập luyện, dùng để tăng các chỉ số căn bản của nhân vật.</p>
    <p><b>Các chỉ số có thể nâng:</b></p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin-bottom:8px;">
      <tr style="background:#fff3cd;"><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Chỉ số</th><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Mô tả</th></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Máu (HP)</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng lượng máu tối đa</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Ki (MP)</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng lượng Ki tối đa</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Sức Đánh</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng sát thương vật lý</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Giáp (Defense)</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng khả năng giảm sát thương nhận vào</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Chí Mạng</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng chỉ số đánh chí mạng</td></tr>
    </table>
    <p><b>Chi phí nâng cấp:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - <b>Máu / Ki:</b> Chi phí tăng tuyến tính theo chỉ số hiện tại.<br>
      - <b>Sức Đánh:</b> Chi phí tăng theo tỉ lệ với sức đánh hiện tại.<br>
      - <b>Giáp:</b> Chi phí bắt đầu rất cao (500,000 điểm/lần), tăng dần mỗi cấp.<br>
      - <b>Chí Mạng:</b> Chi phí <b>tăng hàm mũ</b> (nhân 5 lần mỗi cấp). Cực kỳ đắt ở cấp cao — cần tích lũy rất nhiều tiềm năng.
    </p>
    <p style="font-size:12px;color:#856404;background:#fff8e1;border-radius:5px;padding:8px 12px;"><i>⚠ <b>Lưu ý:</b> Mỗi chỉ số có giới hạn tối đa dựa trên <b>lực chiến</b> của nhân vật. Khi đạt "Không đủ giới hạn lực chiến", bạn cần tăng lực chiến trước (lên cấp, trang bị tốt hơn) mới có thể tiếp tục nâng.</i></p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 2. Đồ Thần Linh -->
    <h3 style="font-size:17px;color:#664d03;">🛡️ 2. Đồ Thần Linh (Set Thần Linh)</h3>
    <p>Đây là cấp trang bị cao của game, gồm 5 bộ phận: <b>Áo, Quần, Găng, Giày, Nhẫn</b>.</p>
    <p><b>Cách có đồ Thần Linh:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - Nhận qua <b>Mốc Nạp</b> (xem mục 5 bên dưới)<br>
      - Nhận từ <b>Boss</b> hoặc sự kiện đặc biệt<br>
      - Mua/trao đổi trong game (nếu chưa khóa)
    </p>
    <p><b>Hệ hành tinh:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - <b>Trái Đất:</b> Áo 555, Quần 556, Găng 562, Giày 563, Nhẫn 561<br>
      - <b>Namek:</b> Áo 557, Quần 558, Găng 564, Giày 565, Nhẫn 561<br>
      - <b>Xayda:</b> Áo 559, Quần 560, Găng 566, Giày 567, Nhẫn 561
    </p>
    <p style="font-size:12px;color:#856404;background:#fff8e1;border-radius:5px;padding:8px 12px;"><i>⚠ Trang bị Thần Linh mang chỉ số cơ bản rất cao. Nó là <b>nguyên liệu</b> để nâng lên cấp cao hơn là <b>Set Kích Hoạt</b>.</i></p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 3. Set Kích Hoạt -->
    <h3 style="font-size:17px;color:#664d03;">⚔️ 3. Set Kích Hoạt (SKH)</h3>
    <p>Set Kích Hoạt là cấp trang bị <b>cao nhất</b> trong game, mạnh hơn nhiều so với Thần Linh. Khi mặc đủ <b>5 mảnh cùng set</b>, bạn sẽ nhận được <b>hiệu ứng đặc biệt cực mạnh</b>.</p>
    <p><b>Cách nâng Thần Linh → Set Kích Hoạt:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      1. Cần: <b>1 đồ Thần Linh</b> + <b>30 Thỏi Vàng</b><br>
      2. Tỉ lệ thành công: <b>80%</b> (thất bại mất 30 Thỏi Vàng, giữ lại đồ Thần Linh)<br>
      3. Thực hiện tại NPC tổ hợp đồ trong game
    </p>
    <p><b>Các Set Kích Hoạt và hiệu ứng khi mặc đủ 5 mảnh:</b></p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin:8px 0;">
      <tr style="background:#fff3cd;"><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Tên Set</th><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Hiệu ứng đặc biệt</th></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set XiHang</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Hiệu ứng đặc thù</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Kirin</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Kỹ năng <b>QCKK</b> (ID 10) gây <b>x2 sát thương</b></td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Songoku</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Kỹ năng <b>Kamejoko</b> (ID 1) gây <b>x2 sát thương</b></td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Picolo</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Kỹ năng <b>Makankosappo</b> (ID 11) tăng <b>+80% sát thương</b> quái, +50% PvP</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Ốc Tiêu</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Kỹ năng <b>Liên Hoàn</b> (Namek) gây <b>x2 sát thương</b></td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Pikkoro Đại Ma</b></td><td style="padding:5px 8px;border:1px solid #ddd;"><b>Đệ tử</b> gây <b>x2 sát thương</b></td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Kakarot</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Kỹ năng <b>đấm XD</b> (ID 4) gây <b>x2 sát thương</b></td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Ca Dic</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Hiệu ứng đặc thù</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Set Nappa</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Tăng <b>+80% HP tối đa</b></td></tr>
    </table>
    <p style="font-size:13px;padding-left:10px;">
      - Muốn có hiệu ứng, <b>phải mặc đủ 5 mảnh cùng tên set</b>.<br>
      - Đồ SKH sau khi nâng sẽ <b>không thể giao dịch</b> (bị khóa).
    </p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 4. Đệ Tử -->
    <h3 style="font-size:17px;color:#664d03;">🐾 4. Đệ Tử (Pet / Chim Chiến)</h3>
    <p>Đệ tử là người bạn đồng hành chiến đấu cùng nhân vật. Đệ tử sẽ tự động tấn công kẻ địch khi bạn chiến đấu.</p>
    <p><b>Phân loại đệ tử theo độ hiếm:</b></p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin:8px 0;">
      <tr style="background:#fff3cd;"><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Loại</th><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Cách kiếm</th></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Đệ tử thường</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Nhận tại Mốc Nạp 50k, mua tại Shop, hoặc từ sự kiện</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Đệ tử Mabu</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Hiếm hơn, thường từ sự kiện hoặc Mốc Nạp cao</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>Đệ tử Đen</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Hiếm nhất, thường từ Mốc Nạp hoặc boss đặc biệt</td></tr>
    </table>
    <p><b>Lưu ý chiến đấu với đệ tử:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - Nếu mặc <b>Set Pikkoro Đại Ma</b> (đủ 5 mảnh), đệ tử sẽ gây gấp đôi sát thương.<br>
      - Đệ tử cần được <b>nuôi cấp</b> để tăng sức mạnh.<br>
      - Mỗi người chỉ mang được <b>1 đệ tử</b> cùng lúc khi chiến đấu.
    </p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 5. Mốc Nạp -->
    <h3 style="font-size:17px;color:#664d03;">💰 5. Mốc Nạp (Milestone Thưởng Nạp Tích Lũy)</h3>
    <p>Đây là hệ thống thưởng theo tổng số tiền bạn đã nạp vào game. Mỗi mốc chỉ nhận được <b>1 lần duy nhất</b>.</p>
    <table style="width:100%;border-collapse:collapse;font-size:13px;margin:8px 0;">
      <tr style="background:#fff3cd;"><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Mốc Nạp</th><th style="padding:5px 8px;border:1px solid #ddd;text-align:left;">Phần Thưởng</th></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>50.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Đệ tử thường</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>100.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">20 Đá Bảo Vệ</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>200.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">Cải Trang Vô Hình VIP</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>500.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">1 bộ đồ Thần Linh + 30 Đá Bảo Vệ</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>1.000.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">CT Super Broly SSJ4 + Đồ Thần Linh + 50 Đá Bảo Vệ</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>2.000.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">CT Super Black Goku Rosé + Đồ Thần Linh + 100 Đá Bảo Vệ</td></tr>
      <tr><td style="padding:5px 8px;border:1px solid #ddd;"><b>5.000.000đ</b></td><td style="padding:5px 8px;border:1px solid #ddd;">CT Hit + Đồ Thần Linh + 999 Mảnh Găng Thiên Sứ + 500 Đá Bảo Vệ</td></tr>
    </table>
    <p><b>Chỉ số cải trang cao cấp (phần thưởng Mốc Nạp):</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - <b>CT Super Broly SSJ4:</b> SD+30%, HP+30%, KI+30%, Giáp+5%, Tránh+5%, Chí Mạng Kép+10%, Chí Mạng+5<br>
      - <b>CT Super Black Goku Rosé:</b> SD+35%, HP+35%, KI+35%, Giáp+10%, Tránh+10%, Chí Mạng Kép+15%, Chí Mạng+5<br>
      - <b>CT Hit:</b> Chí Mạng Kép+50%, SD+15%, Chí Mạng+10
    </p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 6. Viên Ngọc Rồng -->
    <h3 style="font-size:17px;color:#664d03;">🐉 6. Viên Ngọc Rồng (7 Viên Ngọc)</h3>
    <p>Khi thu thập đủ 7 viên ngọc rồng (từ 1 sao đến 7 sao), bạn có thể gọi Rồng Thần để ước 1 điều ước.</p>
    <p><b>Điều ước thường gặp:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - Nhận ngọc / vật phẩm<br>
      - Hồi phục nhân vật<br>
      - Các phần thưởng đặc biệt khác
    </p>
    <p><b>Cách kiếm ngọc rồng:</b></p>
    <p style="font-size:13px;padding-left:10px;">
      - <b>Hanabi rơi</b> từ quái khi tiêu diệt (tỉ lệ khá thấp ~0.08% cho ngọc cao cấp)<br>
      - <b>Sự kiện</b> do admin tổ chức<br>
      - <b>Quà tặng</b> từ hệ thống hoặc mốc nạp
    </p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- 7. Đá Bảo Vệ -->
    <h3 style="font-size:17px;color:#664d03;">💎 7. Đá Bảo Vệ (Tăng Sức Mạnh Trang Bị)</h3>
    <p>Đá Bảo Vệ được dùng khi <b>nâng cấp sao</b> cho trang bị để tránh mất đồ khi thất bại.</p>
    <p style="font-size:13px;padding-left:10px;">
      - Khi nâng sao trang bị mà <b>không dùng đá</b>: nếu fail → đồ có thể biến mất ❌<br>
      - Khi nâng sao trang bị mà <b>có dùng đá</b>: nếu fail → đồ vẫn giữ nguyên, chỉ mất đá ✅<br>
      - Đá bảo vệ có thể nhận từ <b>Mốc Nạp</b>, <b>sự kiện</b>, hoặc mua trong game.
    </p>

    <div class="border-danger border-top mt-3 mb-3"></div>

    <!-- Lộ trình tân thủ -->
    <h3 style="font-size:17px;color:#664d03;">🗺️ Tóm Tắt Lộ Trình Cho Tân Thủ</h3>
    <div style="background:#fff8e1;border-radius:7px;padding:10px 14px;font-size:13px;">
      <b>1.</b> Đăng ký → nhận gift code tân thủ<br>
      <b>2.</b> Lên cấp → phân bổ Điểm Tiềm Năng vào Máu &amp; Sức Đánh trước<br>
      <b>3.</b> Nhận Mốc Nạp để có đồ Thần Linh sớm<br>
      <b>4.</b> Dùng Thỏi Vàng để nâng Thần Linh → Set Kích Hoạt (SKH)<br>
      <b>5.</b> Mặc đủ 5 mảnh cùng Set để kích hoạt hiệu ứng đặc biệt<br>
      <b>6.</b> Nuôi đệ tử và săn 7 viên ngọc rồng để mạnh thêm
    </div>
  </div>
</div>
  <script type="text/javascript">
    $(document).ready(function() {
      $('#Noti_Home').modal('show');
    })
  </script>
<?php } ?>
<?php require_once('core/end.php'); ?>
