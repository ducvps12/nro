<?php 
require_once('../core/config.php'); 
require_once('../core/head.php'); 
$thongbao = null;
session_start();

if (isset($_SESSION['logger']['username'])) {
    echo '<script>window.location.href = "/";</script>';
    exit();
}

if (isset($_POST['submit']) && $_POST['username'] != '' && $_POST['password'] != '') {
    $username = $_POST['username'];                
    $password = $_POST['password'];
    // Kiểm tra ký tự đặc biệt
    if (!preg_match('/^[a-z\d_]{4,20}$/i', $username) || !preg_match('/^[a-z\d_]{1,20}$/i', $password)) {
        $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Tài khoản hoặc mật khẩu không hợp lệ.</span>';
    } else {
        // Kiểm tra tài khoản đã tồn tại
        $sql = "SELECT * FROM account WHERE username = '$username'";
        $old = mysqli_query($config, $sql);
        if (mysqli_num_rows($old) > 0) {
            $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Tài khoản đã tồn tại!</span>';
        } else {
            // Thực hiện đăng ký
            $sql = "INSERT INTO account (username, password) VALUES ('$username', '$password')";
            mysqli_query($config, $sql);
            $thongbao = '<span style="color: green; font-size: 12px; font-weight: bold;">Đăng ký thành công!</span>';
        }
    }
}
?>
<main>
    <div style="background: #fab45c;padding:30px 0;height:400px;color: white; border-radius: 7px;" class="pb-1">
        <form class="text-center col-lg-5 col-md-10" style="margin: auto;" method="post" action="">
            <h1 class="h3 mb-3 font-weight-normal" style="padding-top:12px;text-wrap:nowrap;color:black;">Đăng Ký Tài Khoản</h1>
            <?=$thongbao;?>
            <input style="margin:20px 0;height: 40px; border-radius: 15px; font-weight: bold;" name="username" required="" autofocus=""
                   type="text" class="form-control mt-1" placeholder="Tên tài khoản">
            <input style="margin:20px 0;height: 40px; border-radius: 15px; font-weight: bold;" name="password" required=""
                   type="password" class="form-control mt-1" placeholder="Mật khẩu">

            <div class="text-center mt-1">
                <button class="btn btn-lg btn-dark btn-block" style="border-radius: 10px;width: 100%; height: 50px;margin-top:20px;"
                        type="submit" name="submit">Đăng ký</button>
            </div>
        </form>
    </div>
</main>

<?php require_once('../core/end.php'); ?>