<?php
/**
 * admin_history_edit.php
 *
 * 顧客カルテ内容変更
 *
 * @create  2024/08/02
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

// -----------------------------------------------------------------------------
// IDチェック＆データ取得
// -----------------------------------------------------------------------------
try {
  if (empty($_GET["id"])) {
    throw new Exception("ID不正");
  }
  $id = (int) $_GET["id"];

  $dbh = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("SELECT * FROM course_list WHERE id = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbh = null;

  if (!$result) {
    throw new Exception("該当するカルテデータが見つかりません。");
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
  <title>顧客カルテ内容変更</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="offcanvas.css" rel="stylesheet">
  <style>
    .bd-placeholder-img {
      font-size: 1.125rem;
      text-anchor: middle;
      -webkit-member-select: none;
      -moz-member-select: none;
      -ms-member-select: none;
    }
    @media (min-width: 768px) {
      .bd-placeholder-img-lg { font-size: 3.5rem; }
    }
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

  <!-- ナビバー -->
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
        <h1 class="text-white text-center">内容変更</h1>
      </div>
    </div>
  </nav>

  <!-- メイン -->
  <main role="main" class="container">
    <div class="container" style="height:120px;"></div>
    <div class="container">
      <form method="post" action="admin_history_update.php?id=<?= htmlspecialchars(
        $result["id"],
        ENT_QUOTES,
        "UTF-8",
      ) ?>" enctype="multipart/form-data">
        <div class="col-md-10 mx-auto">

          <div class="form-group">
            <label>氏名</label>
            <input type="text" class="form-control" name="member_name"
              value="<?= htmlspecialchars(
                $result["member_name"],
                ENT_QUOTES,
                "UTF-8",
              ) ?>" disabled>
          </div>

          <div class="form-group">
            <label>フリガナ</label>
            <input type="text" class="form-control" name="member_kana"
              value="<?= htmlspecialchars(
                $result["member_kana"],
                ENT_QUOTES,
                "UTF-8",
              ) ?>" disabled>
          </div>

          <div class="d-flex form-group">
            <div class="col pl-0">
              <label>ご来店日</label>
              <input type="date" class="form-control" name="buy_date"
                value="<?= htmlspecialchars(
                  $result["buy_date"],
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>">
            </div>

            <!-- 対応スタッフセレクト（buildStaffOptions で退職者も維持） -->
            <div class="col">
              <label>対応スタッフ</label>
              <select id="practitioner" class="custom-select" name="buy_practitioner">
                <?= buildStaffOptions(
                  "buy_practitioner",
                  (int) $result["buy_practitioner"],
                ) ?>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>施術内容</label>
            <input type="text" class="form-control" name="buy_course"
              value="<?= htmlspecialchars(
                $result["buy_course"],
                ENT_QUOTES,
                "UTF-8",
              ) ?>">
          </div>

          <div class="d-flex form-group">
            <div class="col pl-0">
              <label>支払い価格</label>
              <input type="text" class="form-control" name="buy_price"
                value="<?= htmlspecialchars(
                  $result["buy_price"],
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>">
            </div>
            <div class="col">
              <label>色番</label>
              <input type="text" class="form-control" name="buy_color"
                value="<?= htmlspecialchars(
                  $result["buy_color"],
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>">
            </div>
          </div>

          <div class="form-group">
            <label>スタッフ記入欄</label>
            <textarea class="form-control" name="buy_text" cols="60" rows="8" maxlength="600"><?= htmlspecialchars(
              $result["buy_text"],
              ENT_QUOTES,
              "UTF-8",
            ) ?></textarea>
          </div>

          <!-- admin_history_new.php / admin_history_edit.php の画像フィールド -->
          <div class="form-group">
            <label>画像</label>
          
            <!-- 現在の画像を表示 -->
            <?php if (!empty($result['nail_image'])): ?>
              <div class="mb-2">
                <img src="<?= htmlspecialchars($result['nail_image'], ENT_QUOTES, 'UTF-8') ?>"
                  style="width:150px; height:150px; object-fit:cover; border-radius:6px;">
                <p class="text-muted small mt-1">現在アップされている画像</p>
              </div>
            <?php else: ?>
              <p class="text-muted small">現在画像はありません</p>
            <?php endif; ?>
          
            <!-- 新しい画像を選択（任意） -->
            <input type="file" name="nail_image" class="form-control"
              accept="image/jpeg,image/png,image/gif">
            <small class="text-muted">
              変更する場合のみ選択してください。選択しなければ現在の画像が維持されます。
            </small>
          
            <!-- 現在の画像URLをhiddenで保持（重要） -->
            <input type="hidden" name="current_nail_image"
              value="<?= htmlspecialchars($result['nail_image'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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

          <input type="hidden" name="id" value="<?= htmlspecialchars(
            $result["id"],
            ENT_QUOTES,
            "UTF-8",
          ) ?>">
            <div class="d-grid gap-2 mb-5 d-md-flex justify-content-md-end">
              <input type="submit" class="btn btn-danger col-5 mx-2 me-md-2" value="変更">
              <a title='戻る' class='btn btn-outline-secondary col-2 mx-2 my-auto'
                href="admin_history_list.php?id=<?= htmlspecialchars(
                  $result["member_id"],
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>">戻る</a>
              <!-- 削除ボタン（モーダル発火） -->
              <button type="button" class="btn btn-outline-danger col-2 mx-2"
                data-toggle="modal" data-target="#deleteModal">削除</button>
            </div>

            <!-- 削除確認モーダル -->
            <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">カルテの削除</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                  </div>
                  <div class="modal-body">
                    本当に『<?= htmlspecialchars(
                      $result["buy_date"],
                      ENT_QUOTES,
                      "UTF-8",
                    ) ?>』のカルテを削除してよろしいですか？
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                    <a class="btn btn-danger"
                      href="<?= BASE_URL ?>admin_history_delete.php?id=<?= htmlspecialchars(
  $result["id"],
  ENT_QUOTES,
  "UTF-8",
) ?>">削除する</a>
                  </div>
                </div>
              </div>
            </div>

        </div>
      </form>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="offcanvas.js"></script>
</body>
</html>
