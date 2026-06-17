<?php
/**
 * admin_new.php
 * 新規登録
 * @create  2024/07/24
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>新規お客様登録画面</title>
  <link rel="stylesheet" href="css/floating-labels.css">
  <link rel="stylesheet" href="css/bootstrap.css">
</head>
<body>
  <form action="admin_add_2.php" method="POST" class="form-signin">
    <div class="text-center mb-4">
      <h1 class="h3 mb-3 font-weight-normal">お客様情報登録</h1>
    </div>
    <div class="form-label-group">
      <input type="text" name="member_name" id="inputId" class="form-control" required autofocus>
      <label for="inputId">お名前</label>
    </div>
    <div class="form-label-group">
      <input type="text" name="member_kana" class="form-control" required>
      <label>フリガナ</label>
    </div>
    <button class="btn btn-lg btn-primary btn-block" type="submit">登録</button>
    <div class="row">
      <div class="col-md-3 offset-md-3"></div>
      <div class="col-md-3 offset-md-3">
        <a title="もどる" class="btn btn-outline-secondary mr-auto mt-3" href="admin_list.php">もどる</a>
      </div>
    </div>

    <!-- 作成者セレクト（getActiveStaffs）-->
    <div class="form-group mt-5">
      <label>作成者 / 担当者</label>
      <select id="author" class="col-6 custom-select" name="author" required>
        <?php foreach (getActiveStaffs('author') as $id_s => $staff): ?>
          <option value="<?= $id_s ?>">
            <?= htmlspecialchars($staff['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <p class="mt-5 mb-3 text-muted text-center">&copy; Nail Salon elfin / le ciel 2024-</p>
  </form>
</body>
</html>