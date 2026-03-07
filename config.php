<?php
// Hãy tôn trọng bản quyền của tác giả, không nên chỉnh sửa nguồn! ChipDEV 0867893653
global $config;

$serverName = "localhost";
$userName   = "root";
$password   = "";
$dbName     = "nro_root";     // DB mặc định

// ===== KẾT NỐI DB =====
define('DB_ROOT',   'nro_root');
define('DB_PLAYER', 'nro_player');

// Kết nối mặc định (giữ nguyên biến $config để không phá code cũ)
$config = mysqli_connect($serverName, $userName, $password, DB_ROOT);
if (mysqli_connect_errno()) {
    echo "Sai hoặc Chưa kết nối Database (ROOT)!";
    exit();
}
mysqli_set_charset($config, 'utf8mb4');

// Kết nối sang DB nro_player (dùng chung user/pass)
$config_player = mysqli_connect($serverName, $userName, $password, DB_PLAYER);
if (!$config_player) {
    // Không die để web vẫn chạy được; chỉ cảnh báo khi cần dùng
    error_log('Không thể kết nối DB PLAYER: ' . mysqli_connect_error());
} else {
    mysqli_set_charset($config_player, 'utf8mb4');
}

# Cấu hình
$tieude = "Kỉ Niệm - Ngọc Rồng Lôi Lạc";
$server_name = "Ngọc Rồng Lôi Lạc";
$link_web = "http://loilac.online/";

// Dùng đường dẫn tuyệt đối từ gốc site
$logo = "/chipasset/images/logo/LOI_LAC_GIF.gif";
$logotb = "/chipasset/images/logo/loilac2.png";

$gia_mtv = 1; // giá sẽ trừ vào thỏi vàng để mtv

# Đường dẫn tải phiên bản và box zalo
$java = "/taive/NroLoiLac.jar";
$pc   = "/taive/LoiLac_Nro.zip"; // Đặt tên file tránh khoảng trắng
$adr  = "/taive/NroLoiLac.apk";
$ios  = "/taive/NroLoiLac.ipa";
$tf   = "https://testflight.apple.com/join/xxDGWjWZ";
$box_zalo = "https://zalo.me/g/rjddwm098";
$fanpage  = "https://www.facebook.com/ducvps123/?locale=vi_VN";
$Group    = "";
$tiktok   = "";

#DoiThe1s.VN API (Nếu có bán lại thì xoá key đi tránh lộ!)
$partner_id_config = '99841169691'; // TẠO Ở DOITHE1S
$partner_key_config = '4738d708aabbc162cba1569728bee9a0';  // TẠO Ở DOITHE1S
#Config API MBBank | STK

$phonembbank_config = '3'; 

$passmbbank_config = ''; 

$deviceIdCommon_goc_config = ''; // thay cái thông số mà bạn lấy đc từ F12 vào đây

$stkmbbank_config = '11'; // Số tài khoản MB Bank

#Config Web2m API (dùng cho auto cộng tiền)
$web2m_password_config = 'D';    // Password API web2m
$web2m_token_config = '9fb459298f42d065767fbd8df1b03f1e';    // Token API web2m

$chutaikhoan = "MAI C"; // Tên Tài khoản 

$urlQRmb_config = "../chipasset/images/qr/Mbbank.jpg";

$urllogonganhang_config = "https://static.wixstatic.com/media/9d8ed5_f9b52e11dd464b5c98da449bcafac435~mv2.png/v1/fill/w_900,h_437,al_c,q_90,enc_auto/9d8ed5_f9b52e11dd464b5c98da449bcafac435~mv2.png";
# Danh sách từ cấm
$censoredWords = array('sex', 'fuck', 'xxx', '.com', '.net', '.online', 'lồn', 'cặc', 'cc', 'seg', 'duma', 'đụ', 'chịch', 'má', 'địt', 'mẹ', 'cl', 'lm', 'mm');

$config = mysqli_connect($serverName, $userName, $password, $dbName);

if (mysqli_connect_errno()) {
    echo "Sai hoặc Chưa kết nối Database!";
    exit();
}

// Định nghĩa hàm escape string để tránh SQL Injection
function xss($data)
{
    global $config;
    return mysqli_real_escape_string($config, $data);
}

// Hàm tạo token (bạn có thể thay thế hàm này bằng cách tạo token phù hợp với yêu cầu của bạn)
function CreateToken()
{
    return md5(uniqid(rand(), true));
}

function duxng_time($timestamp)
{
    $currentTime = time();
    $diffInSeconds = $currentTime - $timestamp;

    $MINUTE = 60;
    $HOUR = 60 * $MINUTE;
    $DAY = 24 * $HOUR;
    $WEEK = 7 * $DAY;
    $MONTH = 30 * $DAY;
    $YEAR = 365 * $DAY;

    if ($diffInSeconds < $MINUTE) {
        return 'Vừa mới đây';
    } elseif ($diffInSeconds < $HOUR) {
        $minutesAgo = floor($diffInSeconds / $MINUTE);
        return $minutesAgo . ' phút trước';
    } elseif ($diffInSeconds < $DAY) {
        $hoursAgo = floor($diffInSeconds / $HOUR);
        return $hoursAgo . ' giờ trước';
    } elseif ($diffInSeconds < $WEEK) {
        $daysAgo = floor($diffInSeconds / $DAY);
        return $daysAgo . ' ngày trước';
    } elseif ($diffInSeconds < $MONTH) {
        $weeksAgo = floor($diffInSeconds / $WEEK);
        return $weeksAgo . ' tuần trước';
    } elseif ($diffInSeconds < $YEAR) {
        $monthsAgo = floor($diffInSeconds / $MONTH);
        return $monthsAgo . ' tháng trước';
    } else {
        $yearsAgo = floor($diffInSeconds / $YEAR);
        return $yearsAgo . ' năm trước';
    }
}

function duxng_nap($MEMO_PREFIX, $des)
{
    $re = '/' . $MEMO_PREFIX . '\d+/im';
    preg_match_all($re, $des, $matches, PREG_SET_ORDER, 0);
    if (count($matches) == 0)
        return null;
    // Print the entire match result 
    $orderCode = $matches[0][0];
    $prefixLength = strlen($MEMO_PREFIX);
    $orderId = intval(substr($orderCode, $prefixLength));
    return $orderId;
}

function chip_bbcode($var = '') {
    $var = str_replace("\n", "<br>", $var);
  $var = preg_replace('#\[hr\]#si', '<hr/>', $var);
  $var = str_replace('[HR][/HR]', '<hr/>', $var);
  $var = preg_replace_callback('#@([\w\d]{2,})#s', function($matches) {
          return tagtv($matches[1]);
      }, str_replace("]\n", "]", $var));
  $var = preg_replace('#\[code\](.*?)\[/code\]#si', '<div class="phdr"><b>Mã</b></div><div class="gmenu">\1</div>', $var);
  $var = preg_replace('#\[CODE\](.*?)\[/CODE\]#si', '<div class="phdr"><b>Mã</b></div><div class="gmenu">\1</div>', $var);
  $var = preg_replace('#\[text\](.*?)\[/text\]#si', '<b>TEXT:</b><br><textarea>\1</textarea><br>', $var);
  $var = preg_replace('#\[TEXT\](.*?)\[/TEXT\]#si', '<b>TEXT:</b><br><textarea>\1</textarea><br>', $var);
  $var = str_replace('[br]', '<br/>', $var);
  $var = preg_replace('#\[list\](.*?)\[/list\]#si', '<div style="border-top: 1px dashed #CECFCE; border-bottom: 1px dashed #CECFCE;">$1</div>', $var);
  $var = preg_replace('#\[LIST\](.*?)\[/LIST\]#si', '<div style="border-top: 1px dashed #CECFCE; border-bottom: 1px dashed #CECFCE;">$1</div>', $var);
  $var = preg_replace('#\[center\](.+?)\[/center\]#is', '<div align="center">\1</div>', $var );
  $var = preg_replace('#\[CENTER\](.+?)\[/CENTER\]#is', '<div align="center">\1</div>', $var );
  $var = preg_replace('#\[LEFT\](.+?)\[/LEFT\]#is', '<div align="left">\1</div>', $var );
  $var = preg_replace('#\[left\](.+?)\[/left\]#is', '<div align="left">\1</div>', $var );
  $var = preg_replace('#\[right\](.+?)\[/right\]#is', '<div align="right">\1</div>', $var );
  $var = preg_replace('#\[RIGHT\](.+?)\[/RIGHT\]#is', '<div align="right">\1</div>', $var );
  $var = preg_replace('#\[trichten\](.+?)\[/trichten\]#is', '<div class="user1">Trích dẫn bài của \1</div>', $var );
  $var = preg_replace('#\[c\](.*?)\[/c\]#si', '<span class="quote" style="display:block"> \1</span>', $var);
  $var = preg_replace('#\[trichnd\](.+?)\[/trichnd\]#is', '<div class="quote2"> \1</div>', $var );
  $var = preg_replace('#\[quote=(.*?)\](.*?)\[/quote\]#si', '<div class="phdr">\1 đã viết</div><div class="gmenu">\2</div>', $var);
  $var = preg_replace('#\[img](.+?)\[/img]#is', '<img src="\1" border="0" />', $var);
  $var = preg_replace('#\[COLOR=(.+?)\](.+?)\[/COLOR\]#is', '<font style="color:\1;">\2</font>', $var );
  $var = preg_replace('#\[color=(.+?)\](.+?)\[/color\]#is', '<font style="color:\1;">\2</font>', $var );
  $var = preg_replace('#\[SIZE=(.+?)\](.+?)\[/SIZE\]#is', '<font style="font-size:\1;">\2</font>', $var );
  $var = preg_replace('#\[size=(.+?)\](.+?)\[/size\]#is', '<font style="font-size:\1;">\2</font>', $var );
  $var = preg_replace('#\[FONT=(.+?)\](.+?)\[/FONT\]#is', '<font face="\1">\2</font>', $var );
  $var = preg_replace('#\[font=(.+?)\](.+?)\[/font\]#is', '<font face="\1">\2</font>', $var );
  $var = highlight_bb($var);
  //////////////
  
  return $var;
  }
   function highlight_bb($var)
      {
          // Список поиска
          $search = array(
  '#\[img](.+?)\[/img]#is', // images 
  '#\[d](.+?)\[/d]#is', // link download
  
              '#\[b](.+?)\[/b]#is', // Жирный
              '#\[i](.+?)\[/i]#is', // Курсив
              '#\[u](.+?)\[/u]#is', // Подчеркнутый
              '#\[s](.+?)\[/s]#is', // Зачеркнутый
              '#\[small](.+?)\[/small]#is', // Маленький шрифт
              '#\[big](.+?)\[/big]#is', // Большой шрифт
              '#\[red](.+?)\[/red]#is', // Красный
              '#\[green](.+?)\[/green]#is', // Зеленый
              '#\[blue](.+?)\[/blue]#is', // Синий
              '!\[color=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/color]!is', // Цвет шрифта
              '!\[bg=(#[0-9a-f]{3}|#[0-9a-f]{6}|[a-z\-]+)](.+?)\[/bg]!is', // Цвет фона
              '#\[(quote|c)](.+?)\[/(quote|c)]#is', // Цитата
              '#\[\*](.+?)\[/\*]#is', // Список
              '#\[spoiler=(.+?)](.+?)\[/spoiler]#is' // Спойлер
          );
          // Список замены
          $replace = array(
              '<a href="$1" title="Click to view image" target="_blank"><img src="$1" border="0" style="max-width: 200px;"></img></a>', //images 
              '<img src="/images/bb/download.gif" border="0"></img><a href="$1" title="Click to download">[Download]</a>',// link download
  
              '<span style="font-weight: bold">$1</span>', // Жирный
              '<span style="font-style:italic">$1</span>', // Курсив
              '<span style="text-decoration:underline">$1</span>', // Подчеркнутый
              '<span style="text-decoration:line-through">$1</span>', // Зачеркнутый
              '<span style="font-size:x-small">$1</span>', // Маленький шрифт
              '<span style="font-size:large">$1</span>', // Большой шрифт
              '<span style="color:red">$1</span>', // Красный
              '<span style="color:green">$1</span>', // Зеленый
              '<span style="color:blue">$1</span>', // Синий
              '<span style="color:$1">$2</span>', // Цвет шрифта
              '<span style="background-color:$1">$2</span>', // Цвет фона
              '<div class="bbcode_container"><div class="bbcode_quote"><div class="quote_container"><div class="bbcode_quote_container"></div>$2</div></div></div>', // Цитата
              '<span class="bblist">$1</span>', // Список
              '<div><div class="spoilerhead" style="cursor:pointer;" onclick="var _n=this.parentNode.getElementsByTagName(\'div\')[1];if(_n.style.display==\'none\'){_n.style.display=\'\';}else{_n.style.display=\'none\';}">$1 (+/-)</div><div class="spoilerbody" style="display:none">$2</div></div>' // Спойлер
          );
          return preg_replace($search, $replace, $var);
      }

$list_recharge_price_momo = [
    [
        "amount" => 10000,
        "bonus" =>  0//25
    ],
    [
        "amount" => 50000,
        "bonus" => 0 // 25
    ],
    [
        "amount" => 100000,
        "bonus" => 0 // 25
    ],
    [
        "amount" => 200000,
        "bonus" => 0 // 25
    ],
    [
        "amount" => 500000,
        "bonus" => 0 // 25
    ],
    [
        "amount" => 1000000,
        "bonus" => 5//27
    ],
    [
        "amount" => 2000000,
        "bonus" => 10//30
    ],
    [
        "amount" => 5000000,
        "bonus" => 20//36
    ],
    [
        "amount" => 10000000,
        "bonus" => 30//45
    ],
];
