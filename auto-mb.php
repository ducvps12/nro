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
    // Ngân hàng thường tự thêm dấu cách vào giữa username (vd: "cter20 04" thay vì "cter2004")
    if (preg_match('/nap\s+([a-zA-Z0-9]+(?:\s+[a-zA-Z0-9]+)*?)(?:\.|,|-|CT\s|Ma\s|CHUYEN|TU:|FT\d|\s{2,}|$)/i', $comment, $matches)) {
        // Thử ghép username bằng cách xóa khoảng trắng (fix lỗi ngân hàng ngắt ký tự)
        $username_full = strtolower(preg_replace('/\s+/', '', $matches[1]));
        // Lấy chỉ từ đầu tiên (fallback)
        preg_match('/(\w+)/', $matches[1], $first_word);
        $username_short = strtolower($first_word[1]);

        // Kiểm tra xem giao dịch đã tồn tại chưa (tránh cộng trùng)
        $stmt_check = $config->prepare("SELECT `magd` FROM `money_bank` WHERE `magd` = ?");
        $stmt_check->bind_param("s", $tranId);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows == 0) {
            // Bước 1: Thử tìm username đầy đủ (đã ghép lại)
            $stmt_user = $config->prepare("SELECT `id`, `username` FROM `account` WHERE LOWER(`username`) = ?");
            $stmt_user->bind_param("s", $username_full);
            $stmt_user->execute();
            $stmt_user->store_result();

            // Bước 2: Nếu không tìm thấy, thử với từ đầu tiên
            if ($stmt_user->num_rows == 0 && $username_full !== $username_short) {
                $stmt_user->close();
                $stmt_user = $config->prepare("SELECT `id`, `username` FROM `account` WHERE LOWER(`username`) = ?");
                $stmt_user->bind_param("s", $username_short);
                $stmt_user->execute();
                $stmt_user->store_result();
                // Cập nhật username để hiển thị đúng
                $username_full = $username_short;
            }

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
                echo "[!] Username '$username_full' không tồn tại<br>";
            }
        } else {
            echo "[=] Giao dịch '$tranId' đã xử lý rồi<br>";
        }
    }
}
?>
