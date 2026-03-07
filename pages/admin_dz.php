<?php
echo "test";
$ketnoi = mysqli_connect("localhost", "root", "", "nro_root");  //hết r đkyes
if ($ketnoi->connect_error) {
    die("Connection failed: " . $ketnoi->connect_error);
}
// mặc định khi cấu hình api post ở azcard.vn callback gọi về post json
//https://api.ipfw.site/cron/cardsea.php?status=1&message=Th%C3%A0nh%20c%C3%B4ng&request_id=480809218&declared_value=10000&card_value=10000&value=10000&amount=10000&code=77119521351846&serial=59000023435191&telco=VINAPHONE&trans_id=343424&callback_sign=17b118fe86852c52ea126c9537617f6d
function check_string($data)
{
    return trim(htmlspecialchars(addslashes($data)));
    //return str_replace(array('<',"'",'>','?','/',"\\",'--','eval(','<php'),array('','','','','','','','',''),htmlspecialchars(addslashes(strip_tags($data))));
}
/** CALLBACK */
if (isset($_GET['request_id']) && isset($_GET['callback_sign'])) {
    $status = check_string($_GET['status']);
    $message = check_string($_GET['message']);
    $requestid = check_string($_GET['request_id']); // request id
    $declared_value = check_string($_GET['declared_value']); //Giá trị khai báo
    $read_receive = check_string($_GET['value']); //Giá trị thực của thẻ
    $value_receive = check_string($_GET['amount']); //Số tiền nhận được
    $code = check_string($_GET['code']);
    $serial = check_string($_GET['serial']);
    $telco = check_string($_GET['telco']);
    $trans_id = check_string($_GET['trans_id']); //Mã giao dịch bên chúng tôi
    $callback_sign = check_string($_GET['callback_sign']);
    $truyvan = mysqli_query($ketnoi, "SELECT * FROM `napthe` where `code` = '$code' and `status` = '99' ");
    $truyvan1 = mysqli_fetch_array($truyvan);
    $aiduoctien = $truyvan1['user_nap'];
    $baonhieu = mysqli_num_rows($truyvan);
    if ($baonhieu == '1') {
        mysqli_query($ketnoi, "insert into napthe(status) values('$status')");
    }

    if ($status == 1) {
        // #36: nap 10k = 30 thoi vang
        $soxudc = $read_receive * 1; // VND amount for vnd/tongnap tracking
        $sothoivang = intval($read_receive * 30 / 10000); // 30 thoi vang per 10k VND
        mysqli_query($ketnoi, "UPDATE `account` SET `vnd` = `vnd` + '$soxudc',`thoi_vang` = `thoi_vang` + '$sothoivang',`tongnap` = `tongnap` + '$soxudc' where `id` = '$aiduoctien' ") or ('Ko cong tien');

        // tình trạng = đúng
        mysqli_query($ketnoi, "update  `napthe` set `status` = '1'  where `code` = '$code' ");
        die('Thẻ đúng !');
    } else {
        mysqli_query($ketnoi, "update `napthe` set `status` = '2' where `code` = '$code' ");
        exit('Thẻ không hợp lệ !');
    }
}



