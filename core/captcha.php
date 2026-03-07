<?php
// Khởi động session để lưu trữ câu trả lời captcha
session_start();

// Tạo câu trả lời captcha ngẫu nhiên (bao gồm các chữ cái và số)
$chars = '1234567890qwertyuiopasdfghjklzxcvbnm';
$captchaLength = 3;
$captchaAnswer = '';
for ($i = 0; $i < $captchaLength; $i++) {
    $captchaAnswer .= $chars[rand(0, strlen($chars) - 1)];
}

// Lưu câu trả lời captcha vào session
$_SESSION['captcha'] = $captchaAnswer;

// Tạo hình ảnh captcha
$width = 100;
$height = 40;
$image = imagecreate($width, $height);

// Màu nền
$bgColor = imagecolorallocate($image, 255, 255, 255);

// Màu chữ
$textColor = imagecolorallocate($image, 0, 0, 0);

// Vẽ chữ lên hình ảnh captcha
imagettftext($image, 20, 0, 10, 28, $textColor, '/chipasset/font/1.ttf', $captchaAnswer);

// Thiết lập header để hiển thị hình ảnh captcha
header('Content-Type: image/png');

// Xuất hình ảnh captcha
imagepng($image);

// Giải phóng bộ nhớ
imagedestroy($image);
?>
