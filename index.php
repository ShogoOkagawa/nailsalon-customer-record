<?php
/**
 * index.php
 *
 * 顧客台帳TOP ログインページ
 *
 * @create  2024/07/22
 * @Update  2026/05/07
 *
 * 変更点:
 *  - form action を index.php 自身に修正（index_login.php は不要）
 *  - パスワードをハッシュ化して比較するよう変更
 *  - ID/PWは elfin_config.php の定数で管理
 **/

session_start();

// すでにログイン済みなら管理画面へ
if (!empty($_SESSION["login_id"])) {
  header("Location: ./csm/admin_list.php");
  exit();
}

require_once __DIR__ . "/config/elfin_config.php";

$error_message = "";

if (isset($_POST["login"])) {
  if (!empty($_POST["login_id"]) && !empty($_POST["login_pass"])) {
    // ① ID一致 かつ ② パスワードのハッシュが一致するか確認
    if (
      $_POST["login_id"] === LOGIN_ID &&
      password_verify($_POST["login_pass"], LOGIN_PASS_HASH)
    ) {
      $_SESSION["login_id"] = $_POST["login_id"];
      $_SESSION["session_token"] = SESSION_TOKEN; // トークンを記録
      header("Location: ./csm/admin_list.php");
      exit();
    }

    // ID/パスワード不一致
    $error_message =
      "※ID、もしくはパスワードが間違っています。<br>もう1度入力して下さい。";
  } else {
    // 未入力
    $error_message = "※IDとパスワードを入力して下さい。";
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>顧客管理データベース</title>
  <link rel="stylesheet" href="css/floating-labels.css">
  <link rel="stylesheet" href="css/bootstrap.css">
  <link rel="icon" href="/favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="/favicon.ico">
</head>
<body>
  <!-- ★ action を index.php 自身に修正（元の index_login.php は削除してOK） -->
  <form action="index.php" method="POST" class="form-signin">
    <div class="text-center mb-4">
      <img class="mb-4" src="images/IMG_4700.JPG" alt="elfin logo" width="478" height="205">
      <h1 class="h3 mb-3 font-weight-normal">お客様管理画面</h1>
      <?php if ($error_message): ?>
        <p class="text-danger">
          <?= $error_message ?>
        </p>
      <?php endif; ?>
    </div>
    <div class="form-label-group">
      <input type="text" name="login_id" id="inputId" class="form-control" placeholder="id" required autofocus>
      <label for="inputId">ログインID</label>
    </div>
    <div class="form-label-group" style="position: relative;">
      <input type="password" name="login_pass" id="inputPassword" class="form-control" placeholder="Password" required style="padding-right: 48px;">
      <label for="inputPassword">パスワード</label>
      <button type="button" onclick="togglePassword('inputPassword', this)"
        style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:0; color:#888; font-size:18px;">
        👁
      </button>
    </div>
    <button name="login" class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
    <script>
      function togglePassword(inputId, btn) {
        var input = document.getElementById(inputId);
        if (input.type === "password") {
          input.type = "text";
          btn.textContent = "🙈";
        } else {
          input.type = "password";
          btn.textContent = "👁";
        }
      }
    </script>
    <p class="mt-5 mb-3 text-muted text-center">&copy; Nail Salon elfin 2024-</p>
  </form>
</body>
</html>
