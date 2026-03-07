<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../core/config.php');

// Chỉ cho phép user đã login
if (!isset($_SESSION['logger']['username'])) {
    echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
    exit;
}

$username = strtolower($_SESSION['logger']['username']);
$token = $web2m_token_config;

// Hàm lấy dữ liệu từ URL bằng cURL
function curl_get_contents_auto($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    $data = curl_exec($curl);

    if (curl_errno($curl)) {
        curl_close($curl);
        return false;
    }

    curl_close($curl);
    return $data;
}

// Gọi API sieuthicode.net lấy lịch sử giao dịch MB Bank
$result = curl_get_contents_auto("https://api.sieuthicode.net/historyapimbbank/$token");
if ($result === false) {
    echo json_encode(['success' => false, 'message' => 'Không thể kết nối API ngân hàng']);
    exit;
}

$result = json_decode($result, true);
if (!$result || !isset($result['TranList'])) {
    echo json_encode(['success' => false, 'message' => 'Dữ liệu API không hợp lệ']);
    exit;
}

$found = false;
$totalAdded = 0;

// Duyệt danh sách giao dịch
foreach ((array)$result['TranList'] as $data) {
    $comment = $data['description'];        // Nội dung chuyển khoản
    $tranId = $data['tranId'];              // Mã giao dịch
    $amount = intval($data['creditAmount']); // Số tiền nhận

    // Bỏ qua giao dịch trừ tiền
    if ($amount <= 0) continue;

    // Tìm "nap <username>" trong nội dung
    if (preg_match('/nap\s+(\w+)/i', $comment, $matches)) {
        $txUsername = strtolower($matches[1]);

        // Chỉ xử lý giao dịch của chính user đang login
        if ($txUsername !== $username) continue;

        // Kiểm tra giao dịch đã tồn tại chưa (tránh cộng trùng)
        $stmt_check = $config->prepare("SELECT `magd` FROM `money_bank` WHERE `magd` = ?");
        $stmt_check->bind_param("s", $tranId);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows == 0) {
            // Tìm user_id từ username
            $stmt_user = $config->prepare("SELECT `id`, `username` FROM `account` WHERE `username` = ?");
            $stmt_user->bind_param("s", $username);
            $stmt_user->execute();
            $stmt_user->store_result();

            if ($stmt_user->num_rows > 0) {
                $stmt_user->bind_result($user_id, $username_db);
                $stmt_user->fetch();

                // Lưu giao dịch vào bảng money_bank
                $stmt_insert = $config->prepare("INSERT INTO `money_bank` (`user_id`, `username`, `amount`, `status`, `magd`) VALUES (?, ?, ?, 'complete', ?)");
                $stmt_insert->bind_param("isis", $user_id, $username_db, $amount, $tranId);
                $stmt_insert->execute();

                // Cộng tiền vào tài khoản
                $stmt_update = $config->prepare("UPDATE `account` SET `money` = `money` + ?, `tongnap` = `tongnap` + ? WHERE `id` = ?");
                $stmt_update->bind_param("iii", $amount, $amount, $user_id);
                $stmt_update->execute();

                $found = true;
                $totalAdded += $amount;
            }
        }

        $stmt_check->close();
    }
}

if ($found) {
    echo json_encode([
        'success' => true,
        'message' => 'Nạp tiền thành công!',
        'amount' => $totalAdded
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Chưa tìm thấy giao dịch mới'
    ]);
}
?>
