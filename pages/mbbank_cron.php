<?php
require_once('../core/config.php'); 
include('api_mbbank.php');
$MBBANK = new MBBANK;

if (isset($_token)) {  
    $sql = "SELECT * FROM account_mbbank WHERE token = '" . xss($_token) . "' LIMIT 1";
    $result = $config->query($sql);
    $getData = $result->fetch_assoc();
    
    if ($getData) {
        $lichsu = json_decode($MBBANK->get_lsgd($getData['phone'], $getData['sessionId'], $getData['deviceId'], $getData['stk'], 2), true);

        if ($getData['time'] < time() - 60) {  
            if ($lichsu['result']['message'] == 'Session invalid') {  
                $MBBANK->deviceIdCommon_goc = $MBBANK->generateImei();  
                $MBBANK->user = $getData['phone'];  
                $MBBANK->pass = $getData['password'];  
                $text_captcha = $MBBANK->bypass_captcha_web2m('413145b2f6d981e32d0ee69a56b0e839');
                $login = json_decode($MBBANK->login($text_captcha), true);

                if($login['result']['message'] == "Capcha code is invalid") {  
                    exit(json_encode(array('status' => '1', 'msg' => 'Captcha không chính xác')));  
                }  
                else if($login['result']['message'] == 'Customer is invalid') {  
                    exit(json_encode(array('status' => '1', 'msg' => 'Thông tin không chính xác')));  
                }  
                else {  
                    $sql = "UPDATE account_mbbank SET name = '" . $login['cust']['nm'] . "', password = '" . $getData['password'] . "', sessionId = '" . $login['sessionId'] . "', deviceId = '" . $MBBANK->deviceIdCommon_goc . "', time = " . time() . " WHERE phone = '" . $getData['phone'] . "'";
                    $config->query($sql); 
                }          
            }  
        }  

        $tranList = array();

        if (!empty($lichsu['transactionHistoryList'])) {
            foreach ($lichsu['transactionHistoryList'] as $transaction) {  
                $noidung = $transaction['description'];
                $thoigian = $transaction['transactionDate'];
                $username = duxng_nap('donate ', $noidung);
                $sotien = $transaction['creditAmount'];
                $tranId = $transaction['refNo'];

                if ($sotien >= 5000) {
                    $sql_check_tranid = "SELECT tranid FROM atm_check WHERE tranid = '$tranId'";
                    $result_check = $config->query($sql_check_tranid);

                    if ($result_check->num_rows == 0) {
                        $sql_idaccount = "SELECT id FROM account WHERE id = '$username'";
                        $result = $config->query($sql_idaccount);

                        if ($result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            $accountId = $row["id"];
                        }

                        // #36: nap 10k = 30 thoi vang (bank transfer)
                        $sothoivang = intval($sotien * 30 / 10000);
                        $sql_account = "UPDATE account SET tongnap = tongnap + $sotien, vnd = vnd + $sotien, thoi_vang = thoi_vang + $sothoivang WHERE id = '$username'";
                        $config->query($sql_account);

                        $sql_atm_lichsu = "INSERT INTO napthe (user_nap, magiaodich, thoigian, sotien, status) VALUES ('$username', '$tranId', '$thoigian', '$sotien', 1)";
                        $config->query($sql_atm_lichsu);

                        $sql_tranId = "INSERT INTO atm_check (tranid) VALUES ('$tranId')";
                        $config->query($sql_tranId);
                    }
                } 
                $tranList[] = array(  
                    "tranId" => $transaction['refNo'],  
                    "postingDate" => $transaction['postingDate'],  
                    "transactionDate" => $transaction['transactionDate'],  
                    "accountNo" => $transaction['accountNo'],  
                    "creditAmount"=> $transaction['creditAmount'],  
                    "debitAmount"=> $transaction['debitAmount'],  
                    "currency"=> $transaction['currency'],  
                    "description"=> $transaction['description'],  
                    "availableBalance"=> $transaction['availableBalance'],  
                    "beneficiaryAccount"=> $transaction['beneficiaryAccount'],  
                );  
            }  
        } 

        echo json_encode(array(  
            "status"  => "success",  
            "message" => "Thành công",  
            "TranList" => $tranList  
        ));
    }
}

if (isset($_token)) {  
    $sql = "SELECT * FROM account_mbbank WHERE token = '" . xss($_token) . "' LIMIT 1";
    $result = $config->query($sql);
    $getData = $result->fetch_assoc();

    if ($getData) {  
        $balance = json_decode($MBBANK->get_balance($getData['phone'], $getData['sessionId'], $getData['deviceId']), true);  

        if ($getData['time'] < time() - 60) {  
            if ($balance['result']['message'] == 'Session invalid') {  
                $MBBANK->deviceIdCommon_goc = $MBBANK->generateImei();
                $MBBANK->user = $getData['phone']; 
                $MBBANK->pass = $getData['password'];  
                $text_captcha = $MBBANK->bypass_captcha_web2m('413145b2f6d981e32d0ee69a56b0e839');
                $login = json_decode($MBBANK->login($text_captcha), true);

                if($login['result']['message'] == "Capcha code is invalid") {  
                    echo json_encode(array('status' => '1', 'msg' => 'Captcha không chính xác'));
                    exit;
                }  
                else if($login['result']['message'] == 'Customer is invalid') {  
                    echo json_encode(array('status' => '1', 'msg' => 'Thông tin không chính xác'));
                    exit;
                }  
                else {  
                    $sql = "UPDATE account_mbbank SET name = '" . $login['cust']['nm'] . "', password = '" . $getData['password'] . "', sessionId = '" . $login['sessionId'] . "', deviceId = '" . $MBBANK->deviceIdCommon_goc . "', time = " . time() . " WHERE phone = '" . $getData['phone'] . "'";
                    $config->query($sql); 
                }  
            }  
        }  

        if ($balance['result']['message'] == 'OK') {  
            foreach ($balance['acct_list'] as $data) {  
                if ($data['acctNo'] == $getData['stk']) {  
                    $status = true;  
                    $message = 'Giao dịch thành công';  
                    echo json_encode(array('status' => '200', 'SoDu' => '' . $data['currentBalance'] . ''));
                    exit;
                }  
            }  
        } else {  
            echo json_encode(array('status' => '99', 'SoDu' => '0'));
            exit;
        }  
    }  
}
?>
