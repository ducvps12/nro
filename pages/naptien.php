<?php
require_once('../core/config.php');
require_once('../core/head.php');
$thongbao = null;
session_start();

if (!isset($_SESSION['logger']['username'])) {
    die("Bạn chưa đăng nhập.");
}

$username = $_SESSION['logger']['username'];
$sql = "SELECT id, tongnap FROM account WHERE username = '$username'";
$result = $config->query($sql);
if ($result->num_rows > 0) {
    $row_hvd = $result->fetch_assoc();
    $user_id = $row_hvd["id"];
    $user_pointNap = $row_hvd["tongnap"]; // Đảm bảo lấy giá trị điểm tích lũy
}
?>

<main>
    <!-- Phần Xem ưu đãi và Tiến độ -->
    <div class="d-inline d-sm-flex justify-content-center">
        <div class="col-md-8 mb-5 mb-sm-4">
            <div class="d-flex align-items-center justify-content-between">
                <a href="/ranking" style="color: white;">
                    <small class="fw-semibold">Xem ưu đãi</small>
                </a>
                <small class="fw-semibold" style="color: white;">
                    Tích lũy: <?php echo $user_pointNap;?>%
                </small>
            </div>
            <div class="recharge-progress">
                <div class="progress-container">
                    <div class="progress-main">
                        <div class="progress-bar" style="width: <?php echo $user_pointNap;?>%;"></div>
                        <div class="progress-bg"></div>
                    </div>
                </div>
                
                <!-- ƯU ĐÃI THEO MỐC NẠP -->

                <div class="_3Ne69qQgMJvF7eNZAIsp_D">
                    <div class="_38CkBz1hYpnEmyQwHHSmEJ">
                        <div class="NusvrwidhtE2W6NagO43R">
                            <div class="_1e8_XixJTleoS7HwwmyB-E">
                                <div class="_2kr5hlXQo0VVTYXPaqefA3 _2Nf9YEDFm2GHONqPnNHRWH" style="left: 1%;">
                                    <div class="_12VQKhFQP9a0Wy-denB6p6">
                                        <div>0</div>
                                        <div class="_3toQ_1IrcIyWvRGrIm2fHJ"></div>
                                    </div>
                                </div>
                                <div class="_2kr5hlXQo0VVTYXPaqefA3" style="left: 33.3333%;">
                                    <div class="_12VQKhFQP9a0Wy-denB6p6">
                                        <div class="_3KQP4x4OyaOj6NIpgE7cKm">
                                            <img alt="" class="_2KchEf_H4jouWwDFDPi5hm" src="/img/rank/silver.png">
                                        </div>
                                        <div>1Tr</div>
                                    </div>
                                    <div class="_3toQ_1IrcIyWvRGrIm2fHJ"></div>
                                </div>
                                <div class="_2kr5hlXQo0VVTYXPaqefA3" style="left: 66.6667%;">
                                    <div class="_12VQKhFQP9a0Wy-denB6p6">
                                        <div class="_3KQP4x4OyaOj6NIpgE7cKm">
                                            <img alt="" class="_2KchEf_H4jouWwDFDPi5hm" src="/img/rank/gold.png">
                                        </div>
                                        <div>2Tr</div>
                                    </div>
                                    <div class="_3toQ_1IrcIyWvRGrIm2fHJ"></div>
                                </div>
                                <div class="_2kr5hlXQo0VVTYXPaqefA3" style="left: 99%;">
                                    <div class="_12VQKhFQP9a0Wy-denB6p6">
                                        <div class="_3KQP4x4OyaOj6NIpgE7cKm">
                                            <img alt="" class="_2KchEf_H4jouWwDFDPi5hm" src="/img/rank/diamond.png">
                                        </div>
                                        <div>5Tr</div>
                                    </div>
                                    <div class="_3toQ_1IrcIyWvRGrIm2fHJ"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phần Chọn hình thức nạp -->
     <div>
        <div class="fs-5 fw-bold text-center text-white">Chọn hình thức nạp</div>
        <div class="row text-center justify-content-center row-cols-2 row-cols-lg-5 g-2 g-lg-2 my-1 mb-2">
            <div class="col">
                <a class="w-100 fw-semibold" href="/atm_bank">
                    <div class="recharge-method-item <?php echo ($tab != "atm") ? "active" : "false"; ?>">
                        <img alt="method" src="/img/atm.png" data-pin-no-hover="true">
                    </div>
                </a>
            </div>
            <!-- <div class="col">
                <a class="w-100 fw-semibold" href="/napthe">
                    <div class="recharge-method-item <?php echo ($tab == "card") ? "active" : "false"; ?>">
                        <img alt="method" src="/img/card.png" data-pin-no-hover="true">
                    </div>
                </a>
            </div> -->
        </div>
    </div>
  <!--  <div id="list_amt" class="row text-center justify-content-center row-cols-2 row-cols-lg-3 g-2 g-lg-2 my-1 mb-2">
            <?php
               foreach($list_recharge_price_momo as $item) {
                  if($item['bonus'] > 0) {
                     echo '<div>
                     <div class="col">
                        <div class="w-100 fw-semibold cursor-pointer">
                           <div class="recharge-method-item position-relative false" style="height: 90px;">
                              <div>'.number_format($item['amount']).' đ</div>
                              <div class="center-text text-danger"><span>____Nhận____</span></div>
                              <div class="text-primary">'.number_format($item['amount'] + ($item['amount'] * $item['bonus'] / 100)).' Coin </div>
                              <span class="text-white position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="z-index: 1;">+'.$item['bonus'].'%</span>
                           </div>
                        </div>
                     </div>
                  </div>';
                  } else {
                     echo '<div>
                     <div class="col">
                        <div class="w-100 fw-semibold cursor-pointer">
                           <div class="recharge-method-item position-relative false" style="height: 90px;">
                              <div>'.number_format($item['amount']).' đ</div>
                              <div class="center-text text-danger"><span>____Nhận____</span></div>
                              <div class="text-primary">'.number_format($item['amount']).' Coin </div>
                           </div>
                        </div>
                     </div>
                  </div>';
                  }
               }
            ?> 
         </div>-->
</main>

<?php require_once('../core/end.php'); ?>
