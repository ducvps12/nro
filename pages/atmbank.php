<?php
require_once('../core/config.php');
require_once('../core/head.php');
session_start();

if (!isset($_SESSION['logger']['username'])) {
    die("Bạn chưa đăng nhập.");
}

$username = $_SESSION['logger']['username'];
$stmt = $config->prepare("SELECT id FROM account WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    die("Không tìm thấy tài khoản.");
}
$user_id = $res->fetch_assoc()['id'];

// Cấu hình ngân hàng
$stkmbbank_config = '0855553269';
$chutaikhoan = 'LE KIEN AN';
$bankId = '970422'; // MB Bank
$csrf_token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrf_token;
?>

<main>
<script>
function copyText(inputId) {
    var inputElement = document.getElementById(inputId);
    inputElement.removeAttribute("readonly");
    inputElement.select();
    document.execCommand("copy");
    inputElement.setAttribute("readonly", "true");
    alert("Đã sao chép: " + inputElement.value);
}

function updateQR() {
    var amount = document.getElementById('amountSelect').value;
    var qrImage = document.getElementById('qrImage');
    var username = "<?php echo $username; ?>";
    var accountName = "<?php echo urlencode($chutaikhoan); ?>";
    var bankId = "<?php echo $bankId; ?>";
    var stk = "<?php echo $stkmbbank_config; ?>";
    var newSrc = `https://img.vietqr.io/image/${bankId}-${stk}-compact2.jpg?amount=${amount}&addInfo=nap%20${username}&accountName=${accountName}`;
    qrImage.src = newSrc;
}

function toggleQR() {
    updateQR();
    var qrDiv = document.getElementById('qrCodeDiv');
    qrDiv.style.display = (qrDiv.style.display === 'none' || qrDiv.style.display === '') ? 'block' : 'none';
}

var pollInterval = null;
var pollCount = 0;
var maxPolls = 30; // 30 lần x 10 giây = 5 phút

function confirmTransfer() {
    // Hiện overlay loading
    document.getElementById('loadingOverlay').style.display = 'flex';
    pollCount = 0;

    // Bắt đầu polling mỗi 10 giây
    checkBankNow();
    pollInterval = setInterval(checkBankNow, 10000);
}

function checkBankNow() {
    pollCount++;
    document.getElementById('pollStatus').textContent = 'Đang kiểm tra... (' + pollCount + '/' + maxPolls + ')';

    var xhr = new XMLHttpRequest();
    xhr.open("GET", "/api/check_bank_auto.php", true);
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var res = JSON.parse(xhr.responseText);
                if (res.success) {
                    // Thành công! Dừng polling
                    clearInterval(pollInterval);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    showSuccessNotification(res.amount);
                } else if (pollCount >= maxPolls) {
                    // Hết thời gian
                    clearInterval(pollInterval);
                    document.getElementById('loadingOverlay').style.display = 'none';
                    showErrorNotification('Chưa tìm thấy giao dịch. Vui lòng kiểm tra lại nội dung chuyển khoản và thử lại sau.');
                }
            } catch (e) {
                // JSON parse error, tiếp tục polling
            }
        }
    };
    xhr.send();
}

function showSuccessNotification(amount) {
    var formatted = new Intl.NumberFormat('vi-VN').format(amount);
    document.getElementById('notifAmount').textContent = '+' + formatted + ' VNĐ';
    document.getElementById('successNotification').style.display = 'flex';
}

function showErrorNotification(msg) {
    document.getElementById('errorMessage').textContent = msg;
    document.getElementById('errorNotification').style.display = 'flex';
}

function closeNotification(id) {
    document.getElementById(id).style.display = 'none';
    location.reload();
}

function cancelPolling() {
    clearInterval(pollInterval);
    document.getElementById('loadingOverlay').style.display = 'none';
}
</script>

<!-- Loading Overlay -->
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; justify-content:center; align-items:center; flex-direction:column;">
    <div style="background:#fff; border-radius:16px; padding:30px 40px; text-align:center; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.3);">
        <div style="margin-bottom:20px;">
            <div class="spinner-bank"></div>
        </div>
        <h5 style="margin:0 0 8px; color:#333;">Đang kiểm tra giao dịch</h5>
        <p id="pollStatus" style="margin:0 0 15px; color:#666; font-size:14px;">Đang kiểm tra... (0/30)</p>
        <p style="margin:0 0 15px; color:#999; font-size:12px;">Hệ thống tự kiểm tra mỗi 10 giây.<br>Vui lòng đợi...</p>
        <button onclick="cancelPolling()" class="btn btn-outline-secondary btn-sm">Hủy</button>
    </div>
</div>

<!-- Success Notification -->
<div id="successNotification" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:16px; padding:35px 40px; text-align:center; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.3);">
        <div style="font-size:60px; margin-bottom:10px;">🎉</div>
        <h4 style="color:#28a745; margin:0 0 8px;">Nạp tiền thành công!</h4>
        <p id="notifAmount" style="font-size:24px; font-weight:bold; color:#333; margin:0 0 15px;">+0 VNĐ</p>
        <p style="color:#666; font-size:14px; margin:0 0 20px;">Số tiền đã được cộng vào tài khoản của bạn.</p>
        <button onclick="closeNotification('successNotification')" class="btn btn-success">OK</button>
    </div>
</div>

<!-- Error Notification -->
<div id="errorNotification" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:10000; justify-content:center; align-items:center;">
    <div style="background:#fff; border-radius:16px; padding:35px 40px; text-align:center; max-width:360px; width:90%; box-shadow:0 8px 32px rgba(0,0,0,0.3);">
        <div style="font-size:60px; margin-bottom:10px;">⚠️</div>
        <h4 style="color:#dc3545; margin:0 0 8px;">Chưa tìm thấy</h4>
        <p id="errorMessage" style="color:#666; font-size:14px; margin:0 0 20px;"></p>
        <button onclick="closeNotification('errorNotification')" class="btn btn-danger">Đóng</button>
    </div>
</div>

<style>
.spinner-bank {
    width: 48px;
    height: 48px;
    border: 5px solid #e0e0e0;
    border-top: 5px solid #007bff;
    border-radius: 50%;
    animation: spin-bank 1s linear infinite;
    margin: 0 auto;
}
@keyframes spin-bank {
    to { transform: rotate(360deg); }
}
</style>

<div class="p-1 mt-1 ibox-content" style="border-radius: 7px; box-shadow: 0px 0px 5px black;">
    <div class="card" style="background-color: transparent;">
        <div class="card-body">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center" style="color:black;">
                        <div class="form-group mt-2">
                            <label><b>Chủ Tài Khoản:</b></label>
                            <input id="chutaikhoanInput" class="form-control mt-1" type="text" value="<?php echo htmlspecialchars($chutaikhoan); ?>" readonly>
                        </div>
                        <div class="form-group mt-2">
                            <label><b>Số Tài Khoản:</b></label>
                            <input id="stkInput" class="form-control mt-1" type="text" value="<?php echo htmlspecialchars($stkmbbank_config); ?>" readonly>
                        </div>

                        <div class="form-group mt-3">
                            <label><b>Chọn Mệnh Giá:</b></label>
                            <select id="amountSelect" class="form-control" onchange="updateQR()">
                                <option value="10000">10,000 VNĐ</option>
                                <option value="20000">20,000 VNĐ</option>
                                <option value="30000">30,000 VNĐ</option>
                                <option value="50000">50,000 VNĐ</option>
                                <option value="70000">70,000 VNĐ</option>
                                <option value="100000">100,000 VNĐ</option>
                                <option value="200000">200,000 VNĐ</option>
                                <option value="300000">300,000 VNĐ</option>
                                <option value="500000">500,000 VNĐ</option>
                                <option value="1000000">1,000,000 VNĐ</option>
                                <option value="2000000">2,000,000 VNĐ</option>
                                <option value="5000000">5,000,000 VNĐ</option>
                            </select>
                        </div>

                        <button onclick="toggleQR()" class="btn btn-primary mt-2">Hiện Mã QR</button>

                        <div id="qrCodeDiv" class="text-center" style="display: none; margin-top: 10px;">
                            <img id="qrImage" src="" class="img-fluid mx-auto d-block" style="border-radius: 7px;">
                            <small class="text-muted">Dùng app ngân hàng quét mã để chuyển khoản</small>
                        </div>

                        <div class="form-group mt-3">
                            <label><b>Nội Dung Chuyển Khoản:</b></label>
                            <input id="ndInput" class="form-control" type="text" value="nap <?php echo htmlspecialchars($username); ?>" readonly>
                        </div>

                        <div class="mt-3">
                            <button onclick="copyText('stkInput')" class="btn btn-success">Copy STK</button>
                            <button onclick="copyText('ndInput')" class="btn btn-info">Copy ND</button>
                        </div>

                        <div class="mt-3">
                            <button onclick="confirmTransfer()" class="btn btn-warning w-100 fw-bold" style="font-size:16px; padding:12px;">✅ Đã Chuyển Khoản — Kiểm Tra Ngay</button>
                        </div>

                        <div class="mt-4">
                            <b class="badge bg-info p-2 rounded">Nạp Tối Thiểu: 10.000 VND</b>
                        </div>

                        <!-- <div class="mt-3">
                            <label><b>Mốc Thưởng:</b></label>
                            <ul class="list-group">
                                <li class="list-group-item">Nạp ≥ 50K: Tặng 5%</li>
                                <li class="list-group-item">Nạp ≥ 200K: Tặng 10%</li>
                                <li class="list-group-item">Nạp ≥ 500K: Tặng 15%</li>
                                <li class="list-group-item">Nạp ≥ 1 triệu: Tặng 20%</li>
                            </ul>
                        </div> -->

                        <p class="text-danger mt-3">⚠️ Vui lòng kiểm tra kỹ nội dung và số tài khoản trước khi chuyển khoản</p>
                    </div>
                </div>

                <hr>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Thời Gian</th>
                                <th>Mã Giao Dịch</th>
                                <th>Số Tiền</th>
                                <th>Trạng Thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
                            $limit = 5;
                            $offset = ($page - 1) * $limit;
                            $q = $config->query("SELECT * FROM money_bank WHERE user_id = $user_id ORDER BY id DESC LIMIT $offset, $limit");
                            if ($q->num_rows === 0) {
                                echo '<tr><td colspan="5" class="text-center"><< Lịch Sử Nạp Trống >></td></tr>';
                            } else {
                                while ($row = $q->fetch_assoc()) {
                                    echo '<tr>';
                                    echo '<td>#' . $row['id'] . '</td>';
                                    echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                                    echo '<td><b>' . htmlspecialchars($row['magd']) . '</b></td>';
                                    echo '<td>' . number_format($row['amount']) . ' VNĐ</td>';
                                    echo '<td>' . ($row['status'] === 'complete' ? '<span class="text-success">Thành Công</span>' : '<span class="text-warning">Đang xử lý</span>') . '</td>';
                                    echo '</tr>';
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                    <?php
                    $totalRows = $config->query("SELECT COUNT(*) as total FROM money_bank WHERE user_id = $user_id")->fetch_assoc()['total'];
                    $totalPages = ceil($totalRows / $limit);
                    ?>
                    <div class="pagination mt-3 text-center">
                        <?php
                        if ($page > 1) echo '<a href="?page=' . ($page - 1) . '"><< Trước</a>';
                        for ($i = 1; $i <= $totalPages; $i++) {
                            echo '<a href="?page=' . $i . '"' . ($i == $page ? ' class="active"' : '') . '>' . $i . '</a>';
                        }
                        if ($page < $totalPages) echo '<a href="?page=' . ($page + 1) . '">Sau >></a>';
                        ?>
                    </div>
                    <style>
                        .pagination a {
                            color: black;
                            padding: 8px 12px;
                            text-decoration: none;
                            border: 1px solid #ddd;
                            margin: 0 4px;
                        }
                        .pagination a.active {
                            background-color: #4CAF50;
                            color: white;
                        }
                        .pagination a:hover:not(.active) {
                            background-color: #ddd;
                        }
                    </style>
                </div>
            </div>
        </div>
    </div>
</div>
</main>

<?php require_once('../core/end.php'); ?>
