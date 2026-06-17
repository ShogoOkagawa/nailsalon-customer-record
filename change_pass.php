<?php
/**
 * change_pass.php
 *
 * パスワード変更ツール
 *
 * 使い方:
 *   1. このファイルをサーバーにアップロード（ルートディレクトリに置く）
 *   2. ブラウザで https://elfin-customer.lamure.store/change_pass.php を開く
 *   3. 新しいパスワードを入力して「ハッシュを生成」ボタンを押す
 *   4. 表示されたハッシュ文字列を elfin_config.php の LOGIN_PASS_HASH にコピー
 *   5. elfin_config.php を保存したら、このファイルをサーバーから【必ず削除】する
 *
 * ⚠️ このファイルをサーバーに残したままにしないこと（誰でもPWを変更できてしまう）
 *
 * @create 2026/05/07
 **/

$hash = "";
$new_pass = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $new_pass = $_POST["new_pass"] ?? "";
  $new_pass2 = $_POST["new_pass2"] ?? "";

  if (empty($new_pass)) {
    $error = "パスワードを入力してください。";
  } elseif (mb_strlen($new_pass) < 8) {
    $error = "パスワードは8文字以上にしてください。";
  } elseif ($new_pass !== $new_pass2) {
    $error = "パスワードが一致しません。";
  } else {
    // bcrypt でハッシュ生成（cost=12 は十分に安全）
    $hash = password_hash($new_pass, PASSWORD_BCRYPT, ["cost" => 12]);
  }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>パスワード変更ツール</title>
  <style>
    body { font-family: sans-serif; max-width: 520px; margin: 60px auto; padding: 0 20px; }
    h2 { font-size: 1.3rem; }
    label { display: block; margin-top: 16px; font-size: .9rem; color: #444; }
    input[type=password], input[type=text] { width: 100%; padding: 8px 44px 8px 8px; font-size: 1rem; box-sizing: border-box; margin-top: 4px; }
    .pw-wrap { position: relative; }
    .pw-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; padding:0; color:#888; font-size:18px; margin-top:2px; }
    button { margin-top: 20px; padding: 10px 28px; background: #007bff; color: #fff; border: none; border-radius: 4px; font-size: 1rem; cursor: pointer; }
    .error  { color: #c00; margin-top: 12px; }
    .result { background: #f4f4f4; border: 1px solid #ccc; padding: 14px; margin-top: 20px; border-radius: 4px; word-break: break-all; }
    .result code { font-size: .95rem; }
    .step   { background: #fff8dc; border-left: 4px solid #f0ad4e; padding: 10px 14px; margin-top: 20px; font-size: .9rem; line-height: 1.8; }
    .warn   { background: #fde; border-left: 4px solid #c00; padding: 10px 14px; margin-top: 12px; font-size: .9rem; }
  </style>
</head>
<body>

<h2>🔑 パスワード変更ツール</h2>

<div class="warn">
  ⚠️ <strong>使い終わったらこのファイルをサーバーから削除してください。</strong>
</div>

<form method="POST" action="change_pass.php">
  <label>新しいパスワード（8文字以上）
    <div class="pw-wrap">
      <input type="password" name="new_pass" id="new_pass" required minlength="4">
      <button type="button" class="pw-toggle" onclick="togglePassword('new_pass', this)">👁</button>
    </div>
  </label>
  <label>新しいパスワード（確認）
    <div class="pw-wrap">
      <input type="password" name="new_pass2" id="new_pass2" required minlength="4">
      <button type="button" class="pw-toggle" onclick="togglePassword('new_pass2', this)">👁</button>
    </div>
  </label>
  <button type="submit">ハッシュを生成</button>
</form>
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

<?php if ($error): ?>
  <p class="error">⛔ <?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($hash): ?>
  <div class="result">
    <strong>✅ 生成されたハッシュ文字列（elfin_config.php にコピーする）:</strong><br><br>
    <code><?= htmlspecialchars($hash) ?></code>
  </div>

  <div class="step">
    <strong>次の手順:</strong><br>
    1. 上のハッシュ文字列を全選択してコピー<br>
    2. <code>elfin_config.php</code> を開く<br>
    3. <code>LOGIN_PASS_HASH</code> の値（<code>'$2y$...'</code> の部分）を貼り付けて保存<br>
    4. ログイン画面で新しいパスワードが使えるか確認<br>
    5. <strong>このファイル（change_pass.php）をサーバーから削除</strong>
  </div>
<?php endif; ?>

</body>
</html>
