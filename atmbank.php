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

function confirmTransfer() {
    var csrfToken = "<?php echo $csrf_token; ?>";
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "/api/confirm_money_bank.php", true);
    xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            alert(xhr.responseText);
            location.reload();
        }
    };
    xhr.send(`user_id=<?php echo $user_id; ?>&username=<?php echo $username; ?>&csrf_token=${csrfToken}`);
}
</script>

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

                        <!-- <div class="mt-3">
                            <button onclick="confirmTransfer()" class="btn btn-warning">Xác Nhận Đã Chuyển Khoản</button>
                        </div> -->

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
