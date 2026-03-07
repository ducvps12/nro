<?php
require_once('../core/config.php'); // Kết nối database

// Cấu hình hiển thị lỗi & thời gian thực thi
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 60);

// Bắt lỗi chết script (timeout, memory leak...)
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error) {
        echo "❌ Script chết với lỗi: ";
        print_r($error);
    }
});

// Hàm gọi API qua proxy có timeout
function curl_get_contents($url) {
    $headers = [
        'Accept: */*',
        'Connection: keep-alive',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36'
    ];

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_TIMEOUT, 20);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($curl, CURLOPT_USERAGENT, $headers[2]);

    // ✅ Cấu hình proxy
    $proxy_ip_port = '42.116.47.244:41275';
    $proxy_auth    = 'auUhlL:bjUlwA';

    curl_setopt($curl, CURLOPT_PROXY, $proxy_ip_port);
    curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
    curl_setopt($curl, CURLOPT_PROXYUSERPWD, $proxy_auth);

    $data = curl_exec($curl);

    if (curl_errno($curl)) {
        echo "❌ CURL lỗi: " . curl_error($curl) . "<br>";
    }

    curl_close($curl);
    return $data;
}

// Thông tin API Web2M
$stk      = '11';
$password = '';
$token    = '0717';

$url = "https://api.web2m.com/historyapiacbv3/$password/$stk/$token";
$response = curl_get_contents($url);
$result = json_decode($response, true);

// Ghi log nếu cần
file_put_contents("log_api.txt", json_encode($result, JSON_PRETTY_PRINT));

if (!isset($result['transactions'])) {
    echo "⚠️ Không lấy được dữ liệu giao dịch từ API<br>";
    exit;
}

// Xử lý tối đa 5 giao dịch gần nhất
$count = 0;
foreach ((array)$result['transactions'] as $data) {
    if ($count++ >= 5) break;

    $comment = $data['description'];
    $tranId  = $data['transactionID'];
    $amount  = $data['amount'];
    $type    = $data['type'];

    echo "🔍 Giao dịch $tranId ($amount VND) - Nội dung: $comment<br>";

    // Lọc từ khóa nạp
    if (preg_match('/nap\s+(\w+)/i', strtolower($comment), $matches)) {
        $username = $matches[1];

        // Kiểm tra giao dịch đã xử lý chưa
        $check = mysqli_query($config, "SELECT `magd` FROM `money_bank` WHERE `magd` = '$tranId'");
        if (mysqli_num_rows($check) > 0) {
            echo "⏩ Giao dịch `$tranId` đã xử lý trước đó<br>";
            continue;
        }

        // Tìm user
        $auser_arr = mysqli_query($config, "SELECT `id`, `username` FROM `account` WHERE `username` = '$username'");
        if ($row = mysqli_fetch_assoc($auser_arr)) {
            $user_id     = $row["id"];
            $username_db = $row["username"];

            // ✅ Tính bonus
            $bonus = 0;
            if ($amount >= 1000000) {
                $bonus = $amount * 0.20;
            } elseif ($amount >= 500000) {
                $bonus = $amount * 0.15;
            } elseif ($amount >= 200000) {
                $bonus = $amount * 0.10;
            } elseif ($amount >= 50000) {
                $bonus = $amount * 0.05;
            }
            $bonus = round($bonus);
            $totalAdd = $amount + $bonus;

            // Thêm vào bảng giao dịch
            $stmt = $config->prepare("INSERT INTO `money_bank` (`user_id`, `username`, `amount`, `status`, `magd`) VALUES (?, ?, ?, 'complete', ?)");
            $stmt->bind_param("isis", $user_id, $username_db, $amount, $tranId);
            if (!$stmt->execute()) {
                echo "❌ Lỗi khi lưu giao dịch: " . $stmt->error . "<br>";
                continue;
            }
            $stmt->close();

            // Cập nhật tiền trong tài khoản
            $update = mysqli_query($config, "
                UPDATE `account` 
                SET `money` = `money` + $totalAdd,
                    `tongnap` = `tongnap` + $amount
                WHERE `id` = $user_id
            ");

            if ($update) {
                echo "✅ [$username_db] đã nạp +$amount VND";
                if ($bonus > 0) {
                    echo " (tặng thêm +$bonus VND) → Tổng: $totalAdd VND";
                }
                echo "<br>";
            } else {
                echo "❌ Lỗi cộng tiền vào tài khoản [$user_id]<br>";
            }
        } else {
            echo "⚠️ Username `$username` không tồn tại trong hệ thống<br>";
        }
    } else {
        echo "⚠️ Giao dịch `$tranId` không chứa từ khóa 'nap [username]'<br>";
    }
}
?>
