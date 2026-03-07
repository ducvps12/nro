<?php
require_once('../core/config.php');

$user_id = intval($_POST['user_id']);
$username = $config->real_escape_string($_POST['username']);

// Tìm đơn nạp mới nhất còn pending
$find = $config->query("SELECT * FROM money_bank WHERE user_id = '$user_id' AND status = 'pending' ORDER BY id DESC LIMIT 1");

if ($find->num_rows == 0) {
    die("❌ Không có đơn nào đang chờ xử lý.");
}

$don = $find->fetch_assoc();
$amount = $don['amount'];
$magd = $don['magd'];

// Tìm trong bảng giao dịch ngân hàng
$check = $config->query("SELECT * FROM bank_transactions 
                         WHERE content LIKE '%nap $username%' 
                         AND amount = '$amount' 
                         AND status = 'success' 
                         ORDER BY time DESC LIMIT 1");

if ($check->num_rows > 0) {
    // Đánh dấu đơn đã hoàn thành
    $config->query("UPDATE money_bank SET status = 'complete' WHERE id = '{$don['id']}'");

    die("✅ Giao dịch thành công. Tài khoản của bạn sẽ được cộng tiền sớm.");
} else {
    die("❌ Không tìm thấy giao dịch khớp. Vui lòng kiểm tra lại.");
}
