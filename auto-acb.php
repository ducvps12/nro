<?php

$local_db = "1626";
$user_db = "root";
$pass_db = "n";
$name_db = "nro_root";
$nd_nap = "nap";


// Hàm lấy dữ liệu từ URL bằng cURL
function curl_get_contents($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 1);  // Bật SSL verification
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);   // Bật SSL verification
    $data = curl_exec($curl);

    // Kiểm tra lỗi cURL
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

// Kết nối database
$config = new mysqli($local_db, $user_db, $pass_db, $name_db);
if ($config->connect_error) {
    die("Kết nối MySQLi thất bại: " . $config->connect_error);
}

$stk = '1';
$password = 'D';
$token = '0917';

// Lấy dữ liệu từ API
$result = curl_get_contents("https://api.web2m.com/historyapiacbv3/$password/$stk/$token");
if ($result === false) {
    die("Không thể lấy dữ liệu từ API");
}

$result = json_decode($result, true);

foreach ((array)$result['transactions'] as $data) {
    $comment = $data['description'];
    $tranId = $data['transactionID'];
    $amount = $data['amount'];
    $type = $data['type'];

    if (preg_match('/nap/', strtolower($comment))) {
        $re = '/nap\s+(\w+)/m';
        preg_match($re, strtolower($comment), $matches);

        if (isset($matches[1])) {
            $username = $matches[1];

            // Kiểm tra xem giao dịch đã tồn tại chưa
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

                    // 1. Thêm giao dịch vào bảng money_bank
                    $stmt_insert = $config->prepare("INSERT INTO `money_bank` (`user_id`, `username`, `amount`, `status`, `magd`) VALUES (?, ?, ?, 'complete', ?)");
                    $stmt_insert->bind_param("isis", $user_id, $username_db, $amount, $tranId);
                    $stmt_insert->execute();

                    // 2. Cập nhật tiền vào bảng account
                    $stmt_update = $config->prepare("UPDATE `account` SET `money` = `money` + ?, `tongnap` = `tongnap` + ? WHERE `id` = ?");
                    $stmt_update->bind_param("iii", $amount, $amount, $user_id);
                    $stmt_update->execute();

                    echo "[✓] $username_db +$amount VND (cộng vào tài khoản + lưu giao dịch)<br>";
                } else {
                    echo "[!] Username `$username` không tồn tại trong bảng account<br>";
                }
            } else {
                echo "[=] Giao dịch `$tranId` đã tồn tại<br>";
            }
        }
    }
}
?>
