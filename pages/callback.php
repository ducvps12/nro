<?php
require_once('../core/config.php');

$partner_id = $partner_id_config; // TẠO Ở DOITHE1S
$partner_key = $partner_key_config; // TẠO Ở DOITHE1S

// Hàm kiểm tra trạng thái thẻ
function checkCardStatus($request_id, $partner_id, $partner_key) {
    $dataPost = array(
        'request_id' => $request_id,
        'partner_id' => $partner_id,
        'command' => 'check',
        'sign' => md5($partner_key . $request_id)
    );

    $data = http_build_query($dataPost);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://doithe1s.vn/chargingws/v2');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result);
}

// Lấy tất cả thẻ có trạng thái chờ
$stmt = $config->prepare("SELECT id, request_id FROM napthe WHERE status = 99");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $card_id = $row['id'];
    $request_id = $row['request_id'];

    // Kiểm tra trạng thái thẻ
    $response = checkCardStatus($request_id, $partner_id, $partner_key);

    // Cập nhật trạng thái trong cơ sở dữ liệu
    $new_status = $response->status;
    $stmt = $config->prepare("UPDATE napthe SET status = ? WHERE id = ?");
    $stmt->bind_param("ii", $new_status, $card_id);
    $stmt->execute();
}
?>