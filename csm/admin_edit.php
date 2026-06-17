<?php
/**
 * admin_edit.php
 * 詳細な内容の確認・変更
 * @create  2024/07/21
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php';

// -----------------------------------------------------------------------------
// IDチェック＆データ取得
// -----------------------------------------------------------------------------
try {
  if (empty($_GET['id'])) throw new Exception('ID不正');
  $id = (int) $_GET['id'];

  $dbh  = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("SELECT * FROM customer_list WHERE no = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbh = null;

  if (!$result) throw new Exception('該当する顧客データが見つかりません。');

} catch (Exception $e) {
  echo "エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
  die();
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>管理ページ</title>
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
        <p class="navbar-brand"><?= htmlspecialchars($result['member_name'], ENT_QUOTES, 'UTF-8') ?>様　　</p>
      </div>
      <div style="position:absolute; top:50%;left:50%;transform: translate(-50%, -50%);">
        <h1 class="text-white text-center">お客様情報</h1>
      </div>
    </div>
  </nav>

  <main role="main" class="container">
    <a class='return' href="admin_list.php?id=<?= htmlspecialchars($result['no'], ENT_QUOTES, 'UTF-8') ?>"> &larr;戻る</a>
    <div class="container" style="height:120px;"></div>
    <div class="container">
      <form method="post" action="admin_update.php?id=<?= htmlspecialchars($result['no'], ENT_QUOTES, 'UTF-8') ?>">
        <div class="row">

          <!-- 左カラム -->
          <div class="col-xs-12 col-md-6">
            <div class="d-flex mb-3">

              <!-- 作成者セレクト（buildStaffOptions）-->
              <div class="col pl-0">
                <label>作成者</label>
                <select id="author" class="custom-select" name="author" required>
                  <?= buildStaffOptions('author', (int)$result['author']) ?>
                </select>
              </div>

              <div class="col">
                <label>登録日</label>
                <input type="date" class="form-control" name="add_date"
                  value="<?= htmlspecialchars($result['add_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
              </div>
            </div>

            <div class="form-group">
              <label>会員番号</label>
              <?php $member_no = str_pad($result['no'], 5, 0, STR_PAD_LEFT); ?>
              <input type="text" class="form-control col-6" value="<?= $member_no ?>" disabled>
            </div>
            <div class="form-group">
              <label>氏名</label>
              <input type="text" class="form-control" name="member_name"
                value="<?= htmlspecialchars($result['member_name'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
              <label>フリガナ</label>
              <input type="text" class="form-control" name="member_kana"
                value="<?= htmlspecialchars($result['member_kana'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
              <label>メールアドレス</label>
              <input type="text" class="form-control" name="member_email"
                value="<?= htmlspecialchars($result['member_email'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
              <label>電話番号<span class="badge badge-pill badge-danger ml-2">必須</span></label>
              <input type="text" class="form-control" name="member_phone"
                value="<?= htmlspecialchars($result['member_phone'], ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            <div class="form-group">
              <label>住所</label>
              <input type="text" class="form-control" name="member_addr"
                value="<?= htmlspecialchars($result['member_addr'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
              <label>誕生日</label>
              <input type="date" class="form-control col-6" name="member_birthday"
                value="<?= htmlspecialchars($result['member_birthday'], ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <!-- きっかけ -->
            <div class="form-group">
              <label>きっかけ<span class="badge badge-pill badge-danger ml-2">必須</span></label>
              <select id="store_trigger" class="custom-select" name="store_trigger" required>
                <?php
                // きっかけの選択肢は functions.php の STORE_TRIGGERS で一元管理
                $triggers = STORE_TRIGGERS;
                foreach ($triggers as $val => $label):
                ?>
                  <option value="<?= $val ?>" <?= $result['store_trigger'] == $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>ご紹介者</label>
              <input type="text" class="form-control" name="introducer"
                value="<?= htmlspecialchars($result['introducer'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
          </div>

          <!-- 右カラム -->
          <div class="col-xs-12 col-md-6">

            <!-- 指名セレクト（buildStaffOptions）-->
            <div class="form-group pl-0">
              <label>指名</label>
              <select id="member_nomination" class="custom-select" name="member_nomination">
                <?= buildStaffOptions('member_nomination', (int)$result['member_nomination']) ?>
              </select>
            </div>

            <!-- ネイル経験 -->
            <div class="form-group">
              <label>ネイル経験<span class="badge badge-pill badge-danger ml-2">必須</span></label>
              <select id="nail_experience" class="custom-select" name="nail_experience" required>
                <?php
                $experiences = [0 => '----', 1 => '有り', 2 => '無し', 3 => '無回答'];
                foreach ($experiences as $val => $label):
                ?>
                  <option value="<?= $val ?>" <?= $result['nail_experience'] == $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- アレルギー -->
            <div class="form-group pl-0">
              <label>アレルギー<span class="badge badge-pill badge-danger ml-2">必須</span></label>
              <select id="allergy" class="custom-select" name="allergy" required>
                <?php
                $allergies = [0 => '----', 1 => 'アレルギーなどがある', 2 => 'とくにない', 3 => '無回答'];
                foreach ($allergies as $val => $label):
                ?>
                  <option value="<?= $val ?>" <?= $result['allergy'] == $val ? 'selected' : '' ?>>
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label>アレルギーの内容など</label>
              <input class="form-control" name="allergy_text" maxlength="600"
                value="<?= htmlspecialchars($result['allergy_text'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
              <label>好きな色</label>
              <input type="text" class="form-control" name="favorite_color"
                value="<?= htmlspecialchars($result['favorite_color'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
              <label>ご要望(デザイン・悩み)</label>
              <textarea class="form-control" name="request" cols="60" rows="6" maxlength="500"><?= htmlspecialchars($result['request'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
              <label>スタッフ記入欄</label>
              <textarea class="form-control" name="others" cols="60" rows="8" maxlength="600"><?= htmlspecialchars($result['others'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="d-grid gap-2 mb-5 d-md-flex justify-content-md-end">
              <input type="submit" class="btn btn-outline-info col-5 mx-2 me-md-2"
                value="<?= empty($result['member_phone']) ? '登録' : '変更' ?>">
              <a title='戻る' class='btn btn-outline-secondary col-2 mx-2 my-auto'
                href="admin_list.php?id=<?= $id ?>">戻る</a>
              <button type="button" class="btn btn-danger col-2 mx-2"
                data-toggle="modal" data-target="#exampleModal">削除</button>
            </div>

            <div class="form-group">
              <label>最終更新日時</label>
              <input type="datetime-local" class="form-control" name="upd_date"
                value="<?= htmlspecialchars($result['upd_date'], ENT_QUOTES, 'UTF-8') ?>" disabled>
            </div>
          </div>

        </div>
      </form>
    </div>
  </main>

  <!-- 削除モーダル -->
  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">顧客の削除</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          本当に『<?= htmlspecialchars($result['member_name'], ENT_QUOTES, 'UTF-8') ?>様』を削除してよろしいですか？
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
          <a title='削除する' class='btn btn-danger'
            href="admin_delete.php?id=<?= htmlspecialchars($result['no'], ENT_QUOTES, 'UTF-8') ?>">削除する</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="offcanvas.js"></script>
</body>
</html>