<?php

require_once(__DIR__ . '/core/config.php');

// Token API sieuthicode.net
$token = $web2m_token_config;

// Hàm lấy dữ liệu từ URL bằng cURL
function curl_get_contents($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
    $data = curl_exec($curl);

    if(curl_errno($curl)) {
        echo 'Curl error: ' . curl_error($curl);
        return false;
    }

    curl_close($curl);
    return $data;
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ERROR);

// Lấy dữ liệu từ API MB Bank (sieuthicode.net)
$result = curl_get_contents("https://api.sieuthicode.net/historyapimbbank/$token");
if ($result === false) {
    die("Không thể lấy dữ liệu từ API");
}

$result = json_decode($result, true);

// Duyệt danh sách giao dịch
foreach ((array)$result['TranList'] as $data) {
    $comment = $data['description'];        // Nội dung chuyển khoản
    $tranId = $data['tranId'];              // Mã giao dịch (FT...)
    $amount = intval($data['creditAmount']); // Số tiền nhận
    
    // Bỏ qua giao dịch trừ tiền (debit)
    if ($amount <= 0) continue;

    // Tìm "nap <username>" trong nội dung chuyển khoản
    // MB Bank hay chèn khoảng trắng vào giữa username (vd: "nap cter20 04" thay vì "nap cter2004")
    // Bước 1: Tách phần sau "nap " cho đến dấu phân cách (. - , hoặc từ khóa ngân hàng)
    // Bước 2: Xóa khoảng trắng thừa trong phần đó để ghép lại username đúng
    if (!preg_match('/nap\s+(.+)/i', $comment, $raw_match)) continue;
    
    $raw = $raw_match[1];
    // Cắt tại dấu phân cách: . - , hoặc các từ khóa ngân hàng
    $raw = preg_split('/[.\-,]|\b(?:CT\s+tu|Ma\s+(?:GD|giao\s*dich)|TU:|FT\d|CHUYEN\s+TIEN|ACSP|Trace\d|MOMO)\b/i', $raw)[0];
    // Xóa khoảng trắng thừa rồi lấy phần chữ+số đầu tiên
    $raw = preg_replace('/\s+/', '', trim($raw));
    if (!preg_match('/^([a-zA-Z][a-zA-Z0-9_]+)/', $raw, $matches)) continue;
    
    $username = strtolower($matches[1]);

    // Kiểm tra xem giao dịch đã tồn tại chưa (tránh cộng trùng)
    $stmt_check = $config->prepare("SELECT `magd` FROM `money_bank` WHERE `magd` = ?");
    $stmt_check->bind_param("s", $tranId);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows == 0) {
        // Tìm user_id từ username
        $stmt_user = $config->prepare("SELECT `id`, `username` FROM `account` WHERE LOWER(`username`) = ?");
        $stmt_user->bind_param("s", $username);
        $stmt_user->execute();
        $stmt_user->store_result();

        if ($stmt_user->num_rows > 0) {
            $stmt_user->bind_result($user_id, $username_db);
            $stmt_user->fetch();

            // 1. Lưu giao dịch vào bảng money_bank
            $stmt_insert = $config->prepare("INSERT INTO `money_bank` (`user_id`, `username`, `amount`, `status`, `magd`) VALUES (?, ?, ?, 'complete', ?)");
            $stmt_insert->bind_param("isis", $user_id, $username_db, $amount, $tranId);
            $stmt_insert->execute();

            // 2. Cộng tiền vào tài khoản
            $stmt_update = $config->prepare("UPDATE `account` SET `money` = `money` + ?, `tongnap` = `tongnap` + ? WHERE `id` = ?");
            $stmt_update->bind_param("iii", $amount, $amount, $user_id);
            $stmt_update->execute();

            echo "[✓] $username_db +$amount VND (cộng tiền thành công)<br>";
        } else {
            echo "[!] Username '$username' không tồn tại<br>";
        }
    } else {
        echo "[=] Giao dịch '$tranId' đã xử lý rồi<br>";
    }
}
?>
