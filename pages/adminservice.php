<?php
require_once('../core/config.php');
require_once('../core/head.php');
session_start(); // Khởi tạo session đầu tiên

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['logger']['username'])) {
    die("Bạn chưa đăng nhập.");
}

$username = $_SESSION['logger']['username'];

// Kiểm tra quyền admin của người dùng
$sql_admin = "SELECT isAdmin FROM account WHERE username = ?";
$stmt_admin = $config->prepare($sql_admin);
$stmt_admin->bind_param("s", $username);
$stmt_admin->execute();
$result_admin = $stmt_admin->get_result();
$row_admin = ($result_admin && $result_admin->num_rows > 0) ? $result_admin->fetch_assoc() : null;
$stmt_admin->close();

// Kiểm tra quyền admin
if ($row_admin['isAdmin'] != 1) {
    die("Dữ liệu quyền admin không tồn tại. Vui lòng đăng nhập lại.");
}

// Lấy thông tin người dùng
$sql = "SELECT id, tongnap FROM account WHERE username = ?";
$stmt = $config->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$row_hvd = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
$stmt->close();

if ($row_hvd) {
    $user_id = $row_hvd["id"];
    $user_pointNap = $row_hvd["tongnap"];
}

$thongbao = null;

// Xử lý yêu cầu cộng VND
if (isset($_POST['add_vnd'])) {
    $target_user = $_POST['target_user'];
    $amount = intval($_POST['amount']);

    if ($amount > 0) {
        // Cập nhật cả vnd, tongnap và thoi_vang
        $update_sql = "UPDATE account SET money = money + ? WHERE username = ?";
        $stmt_update = $config->prepare($update_sql);
        $stmt_update->bind_param("is", $amount,  $target_user);

        if ($stmt_update->execute()) {
            $thongbao = "<span style='color: White;'>Đã cộng $amount VND cho người dùng $target_user.</span>";
        } else {
            $thongbao = "<span style='color: red;'>Lỗi khi cập nhật số dư!</span>";
        }

        $stmt_update->close();
    } else {
        $thongbao = "<span style='color: red;'>Số tiền không hợp lệ.</span>";
    }
}
// Xử lý yêu cầu cộng VND và tổng nạp
if (isset($_POST['add_vnd_tongnap'])) {
    $target_user = $_POST['target_user'];
    $amount = intval($_POST['amount']);

    if ($amount > 0) {
        // Cập nhật vnd và pointNap trong cơ sở dữ liệu
        $update_sql = "UPDATE account SET money = money + ?, tongnap = tongnap + ? WHERE username = ?";
        $stmt_update = $config->prepare($update_sql);
        $stmt_update->bind_param("iis", $amount, $amount,$target_user);

        if ($stmt_update->execute()) {
            $thongbao = "<span style='color: White;'>Đã cộng $amount VND và cập nhật tổng nạp cho người dùng $target_user.</span>";
        } else {
            $thongbao = "<span style='color: red;'>Lỗi khi cập nhật số dư và tổng nạp!</span>";
        }

        $stmt_update->close();
    } else {
        $thongbao = "<span style='color: red;'>Số tiền không hợp lệ.</span>";
    }
}

// Lấy lịch sử nạp
$history_sql = "SELECT uid, sotien, tranid FROM naptien ORDER BY tranid DESC";
$history_result = $config->query($history_sql);

// Đếm tổng số account
$total_accounts_sql = "SELECT COUNT(*) as total_accounts FROM account";
$total_accounts_result = $config->query($total_accounts_sql);
$total_accounts = $total_accounts_result->fetch_assoc()['total_accounts'];
?>

<style>
    /* Các kiểu CSS cho giao diện admin */
    .admin-container nav ul {
        list-style-type: none;
        padding: 0;
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-bottom: 20px;
    }

    .admin-container nav ul li {
        border: 2px solid #007bff;
        border-radius: 10px;
        background-color: #007bff;
    }

    .admin-container nav ul li a {
        display: inline-block;
        color: white;
        font-weight: bold;
        padding: 10px 20px;
        text-decoration: none;
        text-align: center;
    }

    .admin-container nav ul li:hover {
        background-color: #0056b3;
    }

    .admin-container nav ul li:hover a {
        color: white;
    }

    form {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
        margin-top: 20px;
    }

    form label {
        font-weight: bold;
    }

    form input {
        padding: 10px;
        width: 300px;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    form button {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
    }

    form button:hover {
        background-color: #0056b3;
    }
	.admin-container nav ul li.active-tab {
    border: 2px solid red;
    background-color: #ffcccc;
	}

</style>

<main>
<script>
    // Ngăn chặn mở Developer Tools bằng F12 hoặc chuột phải
    document.addEventListener('keydown', function(event) {
        if (event.key === 'F12' || (event.ctrlKey && event.shiftKey && event.key === 'I')) {
            event.preventDefault();
            window.location.href = 'f12.php'; // Điều hướng đến trang thông báo
        }
    });

    // Ngăn chặn mở Developer Tools bằng chuột phải
    document.addEventListener('contextmenu', function(event) {
        event.preventDefault();
        window.location.href = 'f12.php';
    });

    // Kiểm tra nếu Developer Tools đang mở
    (function() {
        const element = new Image();
        Object.defineProperty(element, 'id', {
            get: function() {
                window.location.href = 'f12.php';
            }
        });
        console.log(element);
    })();
</script>
    <div class="admin-container">
        <?php if ($thongbao) { echo "<p>$thongbao</p>"; } ?>

        <nav>
    <ul>
        <li class="<?= (isset($_GET['menu']) && $_GET['menu'] == 'add_vnd') ? 'active-tab' : '' ?>">
            <a href="?menu=add_vnd"><i class="fa fa-plus-circle"></i> Cộng VND</a>
        </li>
        <li class="<?= (isset($_GET['menu']) && $_GET['menu'] == 'add_vnd_tongnap') ? 'active-tab' : '' ?>">
            <a href="?menu=add_vnd_tongnap"><i class="fa fa-plus-circle"></i> Cộng VND + Tổng Nạp</a>
        </li>
        <li class="<?= (isset($_GET['menu']) && $_GET['menu'] == 'history') ? 'active-tab' : '' ?>">
            <a href="?menu=history"><i class="fa fa-history"></i> Xem Lịch Sử Nạp</a>
        </li>
        <li class="<?= (isset($_GET['menu']) && $_GET['menu'] == 'total_accounts') ? 'active-tab' : '' ?>">
            <a href="?menu=total_accounts"><i class="fa fa-users"></i> Xem Tổng Tài Khoản</a>
        </li>
    </ul>
</nav>


        <?php
        if (isset($_GET['menu'])) {
            $menu = $_GET['menu'];

            if ($menu == 'add_vnd') {
                echo '<h2>Cộng VND</h2>
                <form method="POST">
                    <label for="target_user">Tên người dùng:</label>
                    <input type="text" id="target_user" name="target_user" required>

                    <label for="amount">Số tiền (VND):</label>
                    <input type="number" id="amount" name="amount" required>

                    <button type="submit" name="add_vnd" class="btn btn-action m-1 text-white" style="border-radius: 10px;"><i class="fa fa-plus"></i> Cộng VND</button>
                </form>';
            }elseif ($menu == 'add_vnd_tongnap') {
                echo '<h2>Cộng VND + Tổng Nạp</h2>
                <form method="POST">
                    <label for="target_user">Tên người dùng:</label>
                    <input type="text" id="target_user" name="target_user" required>
            
                    <label for="amount">Số tiền (VND):</label>
                    <input type="number" id="amount" name="amount" required>
            
                    <button type="submit" name="add_vnd_tongnap" class="btn btn-action m-1 text-white" style="border-radius: 10px;"><i class="fa fa-plus"></i> Cộng VND + Tổng Nạp</button>
                </form>';
            } elseif ($menu == 'history') {
                echo '<h2>Lịch Sử Nạp</h2>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Tên người dùng</th>
                            <th>Số tiền</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>';

                if ($history_result->num_rows > 0) {
                    while ($row = $history_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>{$row['username']}</td>";
                        echo "<td>{$row['amount']} VND</td>";
                        echo "<td>{$row['created_at']}</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>Không có lịch sử nạp.</td></tr>";
                }

                echo '</tbody>
                </table>';
            } elseif ($menu == 'total_accounts') {
                echo '<h2>Tổng Số Tài Khoản</h2>
                <p>Hiện tại có tổng cộng <strong>' . $total_accounts . '</strong> tài khoản.</p>';
            } else {
                echo '<p>Menu không hợp lệ.</p>';
            }
        } else {
            echo '<p>Chọn một mục từ menu để bắt đầu.</p>';
        }
        ?>
    </div>
</main>

<?php require_once('../core/end.php'); ?>
