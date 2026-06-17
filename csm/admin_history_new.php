<?php
/**
 * admin_history_new.php
 * 新規顧客カルテ作成
 * @create  2024/07/30
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

try {
  if (empty($_GET["id"])) {
    throw new Exception("ID不正");
  }
  $id = (int) $_GET["id"];

  $dbh = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("SELECT * FROM customer_list WHERE no = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbh = null;

  if (!$result) {
    throw new Exception("該当する顧客データが見つかりません。");
  }
} catch (Exception $e) {
  echo "エラー発生: " .
    htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8") .
    "<br>";
  die();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>新規顧客カルテ作成</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="offcanvas.css" rel="stylesheet">
  <style>
    .return {
      color: gray;
      position: absolute;
      top: 80px;
      left: 20px;
      text-decoration: none;
    }
    .return:before {
      content: '';
      display: inline-block;
      width: 30px;
      height: 30px;
      background-image: url(/images/left_arrow.png);
      background-size: contain;
      vertical-align: middle;
    }
  </style>
</head>

<body class="bg-light">
  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" style="background-color: rgb(120,83,178);">
    <div class="d-flex" style="width:100%;">
      <div class="">
        <p class="navbar-brand"><?= htmlspecialchars(
          $result["member_name"],
          ENT_QUOTES,
          "UTF-8",
        ) ?>様　　</p>
      </div>
      <div style="position:absolute; top:50%;left:50%;transform: translate(-50%, -50%);">
        <h1 class="text-white text-center">新規顧客カルテ作成</h1>
      </div>
    </div>
  </nav>

  <main role="main" class="container">
    <a class='return' href="admin_history_list.php?id=<?= htmlspecialchars(
      $result["no"],
      ENT_QUOTES,
      "UTF-8",
    ) ?>"> &larr;戻る</a>
    <div class="container" style="height:120px;"></div>
    <div class="container">
      <form method="post" action="admin_history_add.php?id=<?= htmlspecialchars(
        $result["no"],
        ENT_QUOTES,
        "UTF-8",
      ) ?>" enctype="multipart/form-data">
        <div class="col-md-10 mx-auto">

          <div class="form-group">
            <label>会員番号</label>
            <?php $member_no = str_pad($result["no"], 5, 0, STR_PAD_LEFT); ?>
            <input type="text" class="form-control col-6" value="<?= $member_no ?>" disabled>
            <input type="hidden" name="member_id" value="<?= htmlspecialchars(
              $result["no"],
              ENT_QUOTES,
              "UTF-8",
            ) ?>">
          </div>
          <div class="form-group">
            <label>氏名</label>
            <input type="text" class="form-control" name="member_name"
              value="<?= htmlspecialchars(
                $result["member_name"],
                ENT_QUOTES,
                "UTF-8",
              ) ?>">
          </div>
          <div class="form-group">
            <label>フリガナ</label>
            <input type="text" class="form-control" name="member_kana"
              value="<?= htmlspecialchars(
                $result["member_kana"],
                ENT_QUOTES,
                "UTF-8",
              ) ?>">
          </div>

          <div class="d-flex form-group">
            <div class="col pl-0">
              <label>ご来店日<span class="badge badge-pill badge-danger ml-2">必須</span></label>
              <input type="date" class="form-control" name="buy_date" value="" required>
            </div>
            <!-- 対応スタッフセレクト（getActiveStaffs）-->
            <div class="col">
              <label>対応スタッフ</label>
              <select id="practitioner" class="custom-select" name="buy_practitioner" required>
                <?php foreach (
                  getActiveStaffs("buy_practitioner")
                  as $id_s => $staff
                ): ?>
                  <option value="<?= $id_s ?>">
                    <?= htmlspecialchars($staff["name"], ENT_QUOTES, "UTF-8") ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>施術内容</label>
            <input type="text" class="form-control" name="buy_course" value="">
          </div>
          <div class="d-flex form-group">
            <div class="col pl-0">
              <label>支払い価格</label>
              <input type="text" class="form-control" name="buy_price" value="" placeholder="数字のみ記入、円は不要">
            </div>
            <div class="col">
              <label>色番</label>
              <input type="text" class="form-control" name="buy_color" value="">
            </div>
          </div>
          <div class="form-group">
            <label>スタッフ記入欄</label>
            <textarea class="form-control" name="buy_text" cols="60" rows="8" maxlength="600"
              placeholder="割引の内容やお客様のオーダー内容などがあれば記入する。"></textarea>
          </div>
          <!-- 画像フィールド -->
          <div class="form-group">
              <label>画像</label>
              <input type="file" name="nail_image"
                class="form-control"
                accept="image/jpeg,image/png,image/gif">

              <!-- iPhoneユーザーへの案内 -->
              <div class="alert alert-info mt-2 p-2" style="font-size:13px; line-height:1.8;">
                📱 <strong>iPhoneをお使いの方へ</strong><br>
                以下のどちらかの方法でアップロードしてください。<br><br>
                <strong>【方法① おすすめ・1回設定するだけ】</strong><br>
                設定アプリ → カメラ → フォーマット →
                <strong style="color:rgb(120,83,178);">「互換性優先」</strong> を選択<br><br>
                <strong>【方法② その都度変換する】</strong><br>
                写真アプリで画像を長押し → 共有 →
                <strong style="color:rgb(120,83,178);">「画像を書き出す」→「JPG」</strong>
                を選択してアップロード
              </div>
            </div>
          </div>

          <div class="d-grid gap-2 mb-5 d-md-flex justify-content-md-end">
            <input type="submit" class="btn btn-outline-info col-5 mx-2 me-md-2" value="登録">
            <a title='戻る' class='btn btn-outline-secondary col-2 mx-2 my-auto'
              href="admin_history_list.php?id=<?= htmlspecialchars(
                $id,
                ENT_QUOTES,
                "UTF-8",
              ) ?>">戻る</a>
          </div>

        </div>
      </form>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="offcanvas.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/heic2any@0.0.4/dist/heic2any.min.js"></script>
</body>
</html>
