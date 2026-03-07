<?php
require_once('../core/config.php');

$taikhoanmb = $taikhoanmb_config;
$deviceIdCommon = $deviceIdCommon_config;
$sessionId = $sessionId_config;
$sotaikhoanmb = $sotaikhoanmb_config;

date_default_timezone_set('Asia/Ho_Chi_Minh');
$time1 = date("YmdHis", time() - 60*60*24*1) . '00';
$time2 = date("YmdHis") . '00';
$todate = date("d/m/Y");
$url = 'https://online.mbbank.com.vn/retail_web/common/getTransactionHistory';
$data = array(
    "accountNo" => "$sotaikhoanmb",
    "deviceIdCommon" => "$deviceIdCommon",
    "fromDate" => "$todate",
    "historyNumber" => "",
    "historyType" => "DATE_RANGE",
    "refNo" => "$taikhoanmb-$time2",
    "sessionId" => "$sessionId",
    "toDate" => "$todate",
    "type" => "ACCOUNT"
);

$postdata = json_encode($data);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Accept: application/json, text/plain, */*',
    'Accept-Encoding: gzip, deflate, br',
    'Accept-Language: vi-US,vi;q=0.9',
    'Authorization: Basic QURNSU46QURNSU4=',
    'Connection: keep-alive',
    'Host: online.mbbank.com.vn',
    'Origin: https://online.mbbank.com.vn',
    'Referer: https://online.mbbank.com.vn/information-account/source-account',
    'sec-ch-ua: "Google Chrome";v="105", "Not)A;Brand";v="8", "Chromium";v="105"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/105.0.0.0 Safari/537.36',
));

$result = curl_exec($ch);
curl_close($ch);

$result_array = json_decode($result, true);

if (isset($result_array['transactionHistoryList']) && is_array($result_array['transactionHistoryList'])) {
    foreach ($result_array['transactionHistoryList'] as $transaction) {
        $refNo = $transaction['refNo'];
        $creditAmount = (int)$transaction['creditAmount'];
        $transactionDate = $transaction['transactionDate'];
        $description = $transaction['description'];
        $status = 0;

        // Extract the part of refNo before the forward slash '/'
        $refNo_parts = explode('\\', $refNo);
        $refNo_before_slash = $refNo_parts[0];

        $sql_check = "SELECT id FROM transactionHistoryList WHERE refNo = '$refNo_before_slash'";
        $result_check = $config->query($sql_check);

        if ($result_check->num_rows == 0) {
            $sql_insert = "INSERT INTO transactionHistoryList (refNo, creditAmount, transactionDate, description, status) 
                VALUES ('$refNo_before_slash', $creditAmount, '$transactionDate', '$description', $status)";

            if ($config->query($sql_insert) === TRUE) {
                // Thực hiện hành động nếu insert thành công (nếu cần)
            }
        }
    }
    echo "Loading dữ liệu thành công! | Lê Công Tuấn <3";
} else {
    echo "Không có giao dịch trong danh sách hoặc dữ liệu không hợp lệ.";
}
?>
