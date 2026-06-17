<?php
/**
 * admin_history_detail.php
 *
 * カルテ情報の詳細
 *
 * @create  2024/08/01
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php'; // ← 追加

// -----------------------------------------------------------------------------
// IDチェック＆データ取得
// -----------------------------------------------------------------------------
try {
  if (empty($_GET['id'])) throw new Exception('ID不正');
  $id = (int) $_GET['id'];

  $dbh  = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("SELECT * FROM course_list WHERE id = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbh    = null;

  if (!$result) throw new Exception('該当するカルテデータが見つかりません。');

} catch (Exception $e) {
  echo "エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "<br>";
  die();
}

// getStaffName() で担当者名を取得（if/elseif を削除）
$buy_practitioner = getStaffName('buy_practitioner', (int)$result['buy_practitioner']);
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>詳細</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="./css/offcanvas.css" rel="stylesheet">
  <link href="./css/reserve_date.css" rel="stylesheet">
</head>

<body class="bg-light">

  <!-- ナビバー -->
  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" style="background-color: rgb(120,83,178);">
    <div class="d-flex" style="width:100%;">
      <div class="">
        <a href="<?= BASE_URL ?>admin_history_list.php?id=<?= htmlspecialchars($result['member_id'], ENT_QUOTES, 'UTF-8') ?>">
          <p class="navbar-brand"><?= htmlspecialchars($result['member_name'], ENT_QUOTES, 'UTF-8') ?>様　　</p>
        </a>
      </div>
      <div style="position:absolute; top:50%;left:50%;transform: translate(-50%, -50%);">
        <h1 class="text-white text-center">お客様カルテ</h1>
      </div>
    </div>
  </nav>

  <!-- メイン -->
  <main role="main" class="container mb-3">
    <div class="container mb-0" style="height:50px;"></div>
    <div class="row">
      <div class="col-12">

        <div class="col bg-white card text-center pr-0 pl-0 mx-auto my-auto">
          <div class="card-header text-white bg-info">
            <?= htmlspecialchars($result['member_name'], ENT_QUOTES, 'UTF-8') ?>様　
            <?= htmlspecialchars($result['buy_date'], ENT_QUOTES, 'UTF-8') ?>　内容詳細
          </div>
          <div class="px-0 py-0">
            <div class="table-responsive-lg customers">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th scope="row">項目</th>
                    <th scope="col">内容</th>
                  </tr>
                </thead>
                <tbody>

                  <!-- 担当者（getStaffName() で変換済み） -->
                  <tr>
                    <th scope="row">担当者</th>
                    <td class="text-center">
                      <?= htmlspecialchars($buy_practitioner, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">施術コース</th>
                    <td class="text-center">
                      <?= htmlspecialchars($result['buy_course'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">料金</th>
                    <td class="text-center">
                      <?= !empty($result['buy_price'])
                        ? '¥' . htmlspecialchars($result['buy_price'], ENT_QUOTES, 'UTF-8') . '円'
                        : '記載なし' ?>
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">色番</th>
                    <td class="text-center">
                      <?= !empty($result['buy_color'])
                        ? nl2br(htmlspecialchars($result['buy_color'], ENT_QUOTES, 'UTF-8'))
                        : '記載なし' ?>
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">メモ</th>
                    <td <?= empty($result['buy_text']) ? 'class="text-center"' : '' ?>>
                      <?= !empty($result['buy_text'])
                        ? nl2br(htmlspecialchars($result['buy_text'], ENT_QUOTES, 'UTF-8'))
                        : '記載なし' ?>
                    </td>
                  </tr>

                  <tr>
                    <th scope="row">画像</th>
                    <td>
                      <div class="text-center">
                        <?php if (!empty($result['nail_image'])): ?>
                          <?php $nail_img = htmlspecialchars($result['nail_image'], ENT_QUOTES, 'UTF-8'); ?>
                          <figure class="figure">
                            <a href="<?= $nail_img ?>" target="_blank" rel="noopener noreferrer">
                              <img src="<?= $nail_img ?>" class="rounded" style="width:300px;">
                              <figcaption class="figure-caption text-right">
                                <?= htmlspecialchars($result['buy_date'], ENT_QUOTES, 'UTF-8') ?>の画像
                              </figcaption>
                            </a>
                          </figure>
                        <?php else: ?>
                          画像なし
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>

                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ボタン -->
        <div class="d-flex flex-row-reverse bd-highlight">
          <!-- 削除ボタン（モーダル発火） -->
          <div class="p-2 bd-highlight ml-1">
            <button type="button" class="btn btn-danger"
              data-toggle="modal" data-target="#deleteModal">削除する</button>
          </div>
          <div class="p-2 bd-highlight ml-1">
            <a title='内容を変更する' class='btn btn-warning'
              href="<?= BASE_URL ?>admin_history_edit.php?id=<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">変更する</a>
          </div>
          <div class="p-2 bd-highlight ml-auto">
            <a title='リストに戻る' class='btn btn-outline-info'
              href="<?= BASE_URL ?>admin_history_list.php?id=<?= htmlspecialchars($result['member_id'], ENT_QUOTES, 'UTF-8') ?>">リストに戻る</a>
          </div>
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
                本当に『<?= htmlspecialchars($result['buy_date'], ENT_QUOTES, 'UTF-8') ?>』のカルテを削除してよろしいですか？
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
                <a class="btn btn-danger"
                  href="<?= BASE_URL ?>admin_history_delete.php?id=<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">削除する</a>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="offcanvas.js"></script>
</body>
</html>