<?php
require_once('../core/config.php');

$amount = intval($_POST['amount']);
$user_id = intval($_POST['user_id']);
$username = $config->real_escape_string($_POST['username']);

// Kiểm tra có đơn chưa xử lý gần đây không (chống spam)
$check = $config->query("SELECT * FROM money_bank WHERE user_id = '$user_id' AND status = 'pending' AND TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 3");
if ($check->num_rows > 0) {
    die("Bạn đã tạo đơn gần đây. Vui lòng chờ xử lý.");
}

// Tạo mã giao dịch nội bộ (tạm)
$magd = 'MB' . time() . rand(100, 999);

// Insert đơn
$ok = $config->query("INSERT INTO money_bank (user_id, username, amount, status, magd, created_at) 
                      VALUES ('$user_id', '$username', '$amount', 'pending', '$magd', NOW())");

echo $ok ? "ok" : "Lỗi tạo đơn.";
