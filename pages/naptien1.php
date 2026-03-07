<?php 
require_once('head.php'); 
// Kiểm tra session username tồn tại và không rỗng
if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
    header("Location: /dang-nhap.html"); // Chuyển hướng đến trang đăng nhập
    exit; // Đảm bảo dừng các xử lý tiếp theo
}
$username = $_SESSION['username'];
$thongbao = '';

// Kiểm tra xem có phải là phương thức POST hay không
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy thông tin từ form
    $telco = $_POST['telco'];
    $amount = $_POST['amount'];
    $serial = $_POST['serial'];
    $code = $_POST['code'];
    $captcha_input = $_POST['captcha']; // Lấy giá trị captcha nhập từ form

    // Kiểm tra mã thẻ và seri không được để trống
    if (empty($code) || empty($serial)) {
        $thongbao = '<div class="alert alert-danger">Vui lòng nhập đầy đủ mã thẻ và seri.</div>';
    } else {
        // Kiểm tra Captcha
        if (!isset($_SESSION['captcha_code']) || empty($_SESSION['captcha_code']) || strtolower($captcha_input) !== strtolower($_SESSION['captcha_code'])) {
            $thongbao = '<div class="alert alert-danger">Mã xác minh không chính xác. Vui lòng thử lại.</div>';
        } else {
            // Xóa session Captcha sau khi kiểm tra thành công
            unset($_SESSION['captcha_code']);

            // Tiếp tục xử lý nạp thẻ như thông thường
            // Tạo chữ ký
            $sign = md5($partner_key . $code . $serial);

            // Gọi API để nạp thẻ
            $api_url = 'https://doithe1s.vn/chargingws/v2';
            $data = [
                'telco' => $telco,
                'code' => $code,
                'serial' => $serial,
                'amount' => $amount,
                'request_id' => uniqid(), // Tạo request_id duy nhất
                'partner_id' => $partner_id,
                'sign' => $sign,
                'command' => 'charging'
            ];

            // Gửi request
            $ch = curl_init($api_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);

            // Xử lý response từ API
            $result = json_decode($response, true);
            if ($result && isset($result['status'])) {
                if ($result['status'] == 99) {
                    // Nạp thẻ đang chờ xử lý
                    $thongbao = '<div class="alert alert-info">Nạp thẻ đang chờ xử lý. Vui lòng chờ và kiểm tra sau.</div>';

                    // Thêm vào bảng naptien
                    $user_nap = $username; // Sử dụng biến $username đã có từ head.php

                    $sql = "INSERT INTO naptien (user_nap, telco, amount, serial, code, time, status)
                            VALUES ('$user_nap', '$telco', '$amount', '$serial', '$code', current_timestamp(), '99')";

                    if ($conn->query($sql) === TRUE) {
                        // Thành công, không cần thông báo
                    }

                    $conn->close();
                } else {
                    // Xử lý khi không phải status = 99 (có thể là lỗi hoặc trạng thái khác)
                    $thongbao = '<div class="alert alert-danger">Có lỗi xảy ra khi nạp thẻ. Vui lòng thử lại sau.</div>';
                }
            } else {
                // Xử lý khi không có response hoặc không thể decode JSON
                $thongbao = '<div class="alert alert-danger">Có lỗi xảy ra khi gọi API. Vui lòng thử lại sau.</div>';
            }
        }
    }
}
?>
<div id="content" class="app-content">
  <ul class="breadcrumb">
    <li class="breadcrumb-item">
      <a href="/"> <?=$name_server;?> </a>
    </li>
    <li class="breadcrumb-item active">ADD MONEY</li>
  </ul>
  <h1 class="page-header"> Nạp Tiền </h1>
  <hr class="mb-4">
  <div class="card">
    <div class="card-body pb-2">
      <form method="POST" action="nap-tien.html">
        <div class="row">
          <div class="col-xl-6">
            <label class="form-label" for="telco">Nhà Mạng:</label>
            <select class="form-select mb-3" id="telco" name="telco">
                <option value="VIETTEL">VIETTEL</option>
                <option value="MOBIFONE">MOBIFONE</option>
                <option value="VINAPHONE">VINAPHONE</option>
            </select>
            <label class="form-label" for="amount">Mệnh Giá:</label>
            <select class="form-select mb-3" id="amount" name="amount">
                <option value="10000">10,000 VNĐ</option>
                <option value="20000">20,000 VNĐ</option>
                <option value="30000">30,000 VNĐ</option>
                <option value="50000">50,000 VNĐ</option>
                <option value="100000">100,000 VNĐ</option>
                <option value="200000">200,000 VNĐ</option>
                <option value="300000">300,000 VNĐ</option>
                <option value="500000">500,000 VNĐ</option>
                <option value="1000000">1,000,000 VNĐ</option>
            </select>
            <div class="form-group mb-3">
                <label class="form-label" for="serial">Seri Thẻ:</label>
                <input type="text" class="form-control" id="serial" name="serial" placeholder="Ở mặt sau dòng nhỏ ở thẻ cào...">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="code">Mã Thẻ:</label>
                <input type="text" class="form-control" id="code" name="code" placeholder="Ở sau lớp màng bạc...">
            </div>
            <div class="form-group mb-3">
                <label class="form-label" for="captcha">Xác Minh:</label>
                <!-- Đổi tên id từ code sang captcha -->
                <img src="/core/captcha.php" alt="Captcha"><input type="text" class="form-control" id="captcha" name="captcha" placeholder="Nhập mã xác minh ở đây...">
            </div>
            <?=$thongbao;?>
            <div class="form-group mb-3">
                <button type="submit" class="btn btn-outline-theme">
                    <i class="far fa-credit-card"></i> Nạp Tiền
                </button>
            </div>
          </div>
          <div class="col-xl-6">
            <div class="form-group mb-3">
              <div class="card">
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table mb-0">
                      <thead>
                        <tr>
                          <th scope="col">#</th>
                          <th scope="col">Nhà Mạng</th>
                          <th scope="col">Mệnh Giá</th>
                          <th scope="col">Seri Thẻ</th>
                          <th scope="col">Mã Thẻ</th>
                          <th scope="col">Thời Gian</th>
                          <th scope="col">Trạng Thái</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                          $sql = "SELECT * FROM naptien WHERE user_nap = '$username' ORDER BY time DESC LIMIT 5";
                          $result = $conn->query($sql);

                          if ($result->num_rows > 0) {
                              // Hiển thị các dòng dữ liệu từ bảng naptien
                              $count = 1;
                              while ($row = $result->fetch_assoc()) {
                        ?>
                        <tr>
                          <th scope="row"><?=$count;?></th>
                          <td><?=$row['telco'];?></td>
                          <td><?=number_format($row['amount']);?> VNĐ</td>
                          <td><?=$row['serial'];?></td>
                          <td><?=$row['code'];?></td>
                          <td><?=$row['time'];?></td>
                          <td><?php
                              if ($row['status'] == 99) {
                                  echo "<span class='badge bg-warning'>Chờ Duyệt</span>";
                              } elseif ($row['status'] == 1) {
                                  echo "<span class='badge bg-success'>Thành Công</span>";
                              } elseif ($row['status'] == 3) {
                                  echo "<span class='badge bg-info'>Thành Công Nhưng Sai Mệnh Giá</span>";
                              } elseif ($row['status'] == 2) {
                                  echo "<span class='badge bg-danger'>Thẻ Sai</span>";
                              } else {
                                  echo "<span class='badge bg-danger'>Bảo Trì</span>";
                              }
                          ?></td>
                        </tr>
                        <?php
                              $count++;
                              }
                          } else {
                              echo "<tr><td colspan='7'>Không có dữ liệu.</td></tr>";
                          }

                          $conn->close();
                        ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="card-arrow">
                  <div class="card-arrow-top-left"></div>
                  <div class="card-arrow-top-right"></div>
                  <div class="card-arrow-bottom-left"></div>
                  <div class="card-arrow-bottom-right"></div>
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <div class="card">
                <div class="card-header fw-bold small">HƯỚNG DẪN VÀ LƯU Ý</div>
                <div class="card-body">
                  <p class="card-text mb-3">Thẻ được duyệt tự động 5s - 1p, nếu quá thời gian đó hoặc bị lỗi thì có thể liên hệ <b>Admin</b> để hỗ trợ. </p>
                  <a href="#" class="card-link">Group Zalo</a>
                  <a href="#" class="card-link">Admin</a>
                </div>
                <div class="card-arrow">
                  <div class="card-arrow-top-left"></div>
                  <div class="card-arrow-top-right"></div>
                  <div class="card-arrow-bottom-left"></div>
                  <div class="card-arrow-bottom-right"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
    <div class="card-arrow">
      <div class="card-arrow-top-left"></div>
      <div class="card-arrow-top-right"></div>
      <div class="card-arrow-bottom-left"></div>
      <div class="card-arrow-bottom-right"></div>
    </div>
  </div>
  <a href="#" data-toggle="scroll-to-top" class="btn-scroll-top fade">
    <i class="fa fa-arrow-up"></i>
  </a>
</div>
<?php require_once('end.php'); ?>