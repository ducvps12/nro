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
    // Use prepared statement to prevent SQL Injection
    $sql = "SELECT * FROM account WHERE username = ?";
    $stmt = mysqli_prepare($config, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result->num_rows > 0) {
        $row = mysqli_fetch_assoc($result);
        $storedPassword = $row['password'];

        // Compare passwords
        if ($password == $storedPassword) {
            $_SESSION['logger']['username'] = $username;
            echo '<script>window.location.href = "/";</script>';
            $thongbao = '<span style="color: green; font-size: 12px; font-weight: bold;">Đăng nhập thành công!</span>';
        } else {
            $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Sai tài khoản hoặc mật khẩu!</span>';
        }
    } else {
        $thongbao = '<span style="color: red; font-size: 12px; font-weight: bold;">Sai tài khoản hoặc mật khẩu!</span>';
    }

    mysqli_stmt_close($stmt);
}
?>
<main>
<div style="background:#fab45c; border-radius: 7px;height:400px;padding-top:30px;" class="pb-1">
    <form class="text-center col-lg-5 col-md-10" style="margin: auto;" method="post" action="">
        <h3 class="h3 mb-3 font-weight-light" style="padding-top:12px;text-wrap:nowrap;color:black;text-align:center;">Đăng Nhập Tài Khoản</h3>
        <?=$thongbao;?>
        <input style="margin:20px 0;height: 40px; border-radius: 15px; font-weight: bold;" name="username"
               type="text" class="form-control mt-1" placeholder="Tên tài khoản" autofocus="">
        <input style="margin:20px 0;height: 40px; border-radius: 15px; font-weight: bold;" name="password"
               type="password" class="form-control mt-1" placeholder="Mật khẩu">

        <div class="text-center mt-1">
            <button class="btn btn-lg btn-dark btn-block" style="border-radius: 10px;width: 100%; height: 50px;margin-top:20px"
                    type="submit" name="submit">Đăng nhập</button>
        </div>
    </form>
</div>
</main>

<?php require_once('../core/end.php'); ?>