<?php
session_start();
require_once('../core/config.php');
include('api_mbbank.php');
$mbbank = new MBBANK;
$phonembbank = $phonembbank_config;
$passmbbank = $passmbbank_config;
$stkmbbank = $stkmbbank_config;
if (empty($phonembbank)) {
    exit(json_encode(array('status' => '1', 'msg' => 'Vui lòng điền tài khoản đăng nhập')));
}

if (empty($passmbbank)) {
    exit(json_encode(array('status' => '1', 'msg' => 'Vui lòng điền mật khẩu')));
}

if (empty($stkmbbank)) {
    exit(json_encode(array('status' => '1', 'msg' => 'Vui lòng điền số tài khoản')));
}
$mbbank->user = $phonembbank;
$mbbank->pass = $passmbbank;
$time = time();
$text_captcha = $mbbank->bypass_captcha_web2m('413145b2f6d981e32d0ee69a56b0e839');
$login = json_decode($mbbank->login($text_captcha), true);

if ($login['result']['message'] == "Capcha code is invalid") {
    exit(json_encode(array('status' => '1', 'msg' => 'Captcha không chính xác')));
} else if ($login['result']['message'] == 'Customer is invalid') {
    exit(json_encode(array('status' => '1', 'msg' => 'Thông tin không chính xác')));
} else {
    // Check if the phone number already exists in the account_mbbank table
    $checkQuery = "SELECT phone FROM account_mbbank WHERE phone = '$phonembbank'";
    $checkResult = mysqli_query($config, $checkQuery);

    if (mysqli_num_rows($checkResult) > 0) {
        // Phone number already exists, don't insert
        exit(json_encode(array('status' => '1', 'msg' => 'Số điện thoại đã tồn tại trong hệ thống')));
    } else {
        $create = "INSERT INTO account_mbbank (phone, stk, name, password, sessionId, deviceId, token, time) 
                   VALUES ('$phonembbank', '$stkmbbank', '{$login['cust']['nm']}', '$passmbbank', '{$login['sessionId']}', '$mbbank->deviceIdCommon_goc', '" . CreateToken() . "', " . time() . ")";
        if (mysqli_query($config, $create)) {
            // Insert successful
            // You can display a success message if needed
            exit(json_encode(array('status' => '2', 'msg' => 'Thêm tài khoản thành công')));
        } else {
            // Insert failed
            // You can display an error message if needed
            exit(json_encode(array('status' => '1', 'msg' => 'Lỗi khi thêm tài khoản')));
        }
    }
}
?>
