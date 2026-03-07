<?php
require_once(__DIR__ . '/../../core/config.php');
session_start();

$username = $_SESSION['logger']['username'] ?? null;
if (!$username) die('Vui lòng đăng nhập');

$acc = $config->query("SELECT id,isAdmin,tongnap FROM account WHERE username='".xss($username)."' LIMIT 1");
if (!$acc || !$acc->num_rows) die('Không tìm thấy tài khoản');
$a = $acc->fetch_assoc();
$accountId = (int)$a['id'];
$isAdmin   = (int)$a['isAdmin'];
$tongnap   = (int)$a['tongnap'];

$dp = $config->query("SELECT cName FROM player WHERE playerId={$accountId} LIMIT 1");
$displayName = ($dp && $dp->num_rows) ? $dp->fetch_assoc()['cName'] : $username;

$MIN_POST_TOPUP = 10000;
$act = $_POST['act'] ?? '';
function safe($s){ return trim($s ?? ''); }

switch ($act) {
  case 'create_thread':
    if ($tongnap < $MIN_POST_TOPUP && !$isAdmin) {
      die('Tài khoản cần tổng nạp tối thiểu '.number_format($MIN_POST_TOPUP).' để đăng chủ đề mới.');
    }
    $cat  = (int)$_POST['category_id'];
    $tit  = safe($_POST['title']);
    $cont = safe($_POST['content']);
    if ($cat<=0 || $tit=='' || $cont=='') die('Thiếu dữ liệu');

    $sql = sprintf(
      "INSERT INTO forum_threads (category_id, author_id, author_name, title) VALUES (%d,%d,'%s','%s')",
      $cat, $accountId, xss($displayName), xss($tit)
    );
    if (!$config->query($sql)) die('Lỗi tạo chủ đề');
    $tid = (int)$config->insert_id;

    $sql2 = sprintf(
      "INSERT INTO forum_posts (thread_id, author_id, author_name, content) VALUES (%d,%d,'%s','%s')",
      $tid, $accountId, xss($displayName), xss($cont)
    );
    if (!$config->query($sql2)) die('Lỗi tạo bài viết đầu tiên');

    header("Location: /pages/forum/thread.php?id=".$tid);
    break;

  case 'reply':
    $tid  = (int)$_POST['thread_id'];
    $pid  = isset($_POST['parent_id']) && $_POST['parent_id']!=='' ? (int)$_POST['parent_id'] : null;
    $cont = safe($_POST['content']);
    if ($tid<=0 || $cont=='') die('Thiếu dữ liệu');

    $row = $config->query("SELECT locked FROM forum_threads WHERE id={$tid}")->fetch_assoc();
    if (!$row) die('Không tìm thấy chủ đề');
    if ((int)$row['locked']===1) die('Chủ đề đã khóa');

    $sql = sprintf(
      "INSERT INTO forum_posts (thread_id, author_id, author_name, content, parent_id) VALUES (%d,%d,'%s','%s',%s)",
      $tid, $accountId, xss($displayName), xss($cont), $pid ? $pid : 'NULL'
    );
    if (!$config->query($sql)) die('Lỗi tạo bài viết');

    $config->query("UPDATE forum_threads SET last_post_at=NOW() WHERE id={$tid}");
    header("Location: /pages/forum/thread.php?id=".$tid."#last");
    break;

  case 'like_post':
    $postId = (int)$_POST['post_id'];
    if ($postId<=0) die('Thiếu post_id');
    $ex = $config->query("SELECT id FROM forum_likes WHERE post_id={$postId} AND user_id={$accountId} LIMIT 1");
    if ($ex && $ex->num_rows) {
      $config->query("DELETE FROM forum_likes WHERE post_id={$postId} AND user_id={$accountId} LIMIT 1");
    } else {
      $config->query("INSERT IGNORE INTO forum_likes (post_id,user_id) VALUES ({$postId},{$accountId})");
    }
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/pages/forum/index.php'));
    break;

  case 'report_post':
    $postId = (int)$_POST['post_id'];
    $reason = safe($_POST['reason']);
    if ($postId<=0 || $reason=='') die('Thiếu dữ liệu');
    $config->query("INSERT INTO forum_reports (post_id, reporter_id, reason) VALUES ({$postId},{$accountId},'".xss($reason)."')");
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/pages/forum/index.php'));
    break;

  case 'delete_post':
    $postId = (int)$_POST['post_id'];
    if ($postId<=0) die('Thiếu post_id');
    $owner = $config->query("SELECT author_id,thread_id FROM forum_posts WHERE id={$postId} LIMIT 1");
    if (!$owner || !$owner->num_rows) die('Không tìm thấy bài viết');
    $ow = $owner->fetch_assoc();
    if (!$isAdmin && (int)$ow['author_id'] !== $accountId) die('Không có quyền');
    $config->query("UPDATE forum_posts SET is_deleted=1 WHERE id={$postId} LIMIT 1");
    header('Location: /pages/forum/thread.php?id='.(int)$ow['thread_id']);
    break;

  case 'delete_thread':
    if (!$isAdmin) die('Không có quyền');
    $tid = (int)($_POST['thread_id'] ?? 0);
    if ($tid <= 0) die('Thiếu thread_id');
    $config->query("DELETE FROM forum_threads WHERE id={$tid} LIMIT 1");
    header('Location: /pages/forum/index.php');
    break;

  case 'mod_thread':
    if (!$isAdmin) die('Không có quyền');
    $tid = (int)$_POST['thread_id'];
    if ($tid<=0) die('Thiếu thread_id');
    if (isset($_POST['toggle_pin']))  $config->query("UPDATE forum_threads SET pinned=1-pinned WHERE id={$tid} LIMIT 1");
    if (isset($_POST['toggle_lock'])) $config->query("UPDATE forum_threads SET locked=1-locked WHERE id={$tid} LIMIT 1");
    header('Location: /pages/forum/thread.php?id='.$tid);
    break;

  default:
    die('Hành động không hợp lệ');
}
