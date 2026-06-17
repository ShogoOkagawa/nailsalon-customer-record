<?php
/**
 * admin_history_list.php
 * カルテ情報の履歴一覧
 * @create  2024/07/20
 * @Update  2026/03/26
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

if (empty($_GET["id"])) {
  die("ID不正");
}
$id = (int) $_GET["id"];

try {
  $dbh = getDbh();

  // ① del_flg = 1 のみ取得
  $stmt = $dbh->prepare("SELECT * FROM course_list
    WHERE  member_id = ?
    AND del_flg = 1
    ORDER BY buy_date DESC");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // ② 件数カウント（del_flg = 1 のみ）
  $count_r = count($result);

  // ③ 顧客名取得
  $stmt = $dbh->prepare("SELECT member_name FROM customer_list WHERE no = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $result_n = $stmt->fetch(PDO::FETCH_ASSOC);

  $dbh = null;

  if (!$result_n) {
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
  <title>お客様カルテ</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="./css/offcanvas.css" rel="stylesheet">
  <link href="./css/reserve_date.css" rel="stylesheet">
</head>

<body class="bg-light">
  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" style="background-color: rgb(120,83,178);">
    <div class="d-flex" style="width:100%;">
      <div class="">
        <a href="<?= BASE_URL ?>admin_list.php?id=<?= htmlspecialchars(
  $id,
  ENT_QUOTES,
  "UTF-8",
) ?>">
          <p class="navbar-brand"><?= htmlspecialchars(
            $result_n["member_name"],
            ENT_QUOTES,
            "UTF-8",
          ) ?>様　　</p>
        </a>
      </div>
      <div style="position:absolute; top:50%;left:50%;transform: translate(-50%, -50%);">
        <h1 class="text-white text-center">お客様カルテ</h1>
      </div>
    </div>
  </nav>

  <main role="main" class="container mb-3">
    <div class="container mb-0" style="height:50px;"></div>
    <div class="row">
      <div class="col-12">

        <div class="mr-auto p-2 bd-highlight">
          <a title='新規登録' class='btn btn-success btn-lg mx-2 my-auto'
            href="<?= BASE_URL ?>admin_history_new.php?id=<?= htmlspecialchars(
  $id,
  ENT_QUOTES,
  "UTF-8",
) ?>">新規登録</a>
        </div>

        <div class="col bg-white card text-center pr-0 pl-0 mx-auto my-auto">
          <div class="card-header text-white bg-info">施術履歴</div>
          <div class="container px-0 py-0">
            <div class="table-responsive-lg customers">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th scope="col">利用日</th>
                    <th scope="col">担当者</th>
                    <th scope="col">コース内容</th>
                    <th scope="col">金額</th>
                    <th scope="col">画像</th>
                    <th scope="col">詳細</th>
                    <th scope="col">削除</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if ($count_r === 0): ?>
                    <tr>
                      <td class="text-center" colspan="6">利用履歴はありません</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($result as $key): ?>
                      <tr>
                        <th scope="row"><?= htmlspecialchars(
                          $key["buy_date"],
                          ENT_QUOTES,
                          "UTF-8",
                        ) ?></th>
                        <td><?= htmlspecialchars(
                          getStaffName(
                            "buy_practitioner",
                            (int) $key["buy_practitioner"],
                          ),
                          ENT_QUOTES,
                          "UTF-8",
                        ) ?></td>
                        <td><?= htmlspecialchars(
                          $key["buy_course"],
                          ENT_QUOTES,
                          "UTF-8",
                        ) ?></td>
                        <td><?= htmlspecialchars(
                          $key["buy_price"],
                          ENT_QUOTES,
                          "UTF-8",
                        ) ?>円</td>
                        <td class="text-center">
                            <?php if (!empty($key["nail_image"])): ?>
                              <a href="<?= BASE_URL ?>admin_history_detail.php?id=<?= htmlspecialchars(
  $key["id"],
  ENT_QUOTES,
  "UTF-8",
) ?>"
                                target="_blank" rel="noopener noreferrer">
                                <img src="<?= htmlspecialchars(
                                  $key["nail_image"],
                                  ENT_QUOTES,
                                  "UTF-8",
                                ) ?>"
                                  style="width:60px; height:60px; object-fit:cover; border-radius:4px;">
                              </a>
                            <?php else: ?>
                              <span class="text-muted small">なし</span>
                            <?php endif; ?>
                        </td>
                        <td>
                          <a class="btn btn-primary btn-sm"
                            href="<?= BASE_URL ?>admin_history_detail.php?id=<?= htmlspecialchars(
  $key["id"],
  ENT_QUOTES,
  "UTF-8",
) ?>"
                            role="button">詳細　＞</a>
                        </td>
                        <!-- 削除ボタン（モーダル発火） -->
                        <td>
                          <button type="button" class="btn btn-danger btn-sm"
                            data-toggle="modal"
                            data-target="#deleteModal"
                            data-karte-id="<?= htmlspecialchars(
                              $key["id"],
                              ENT_QUOTES,
                              "UTF-8",
                            ) ?>"
                            data-karte-date="<?= htmlspecialchars(
                              $key["buy_date"],
                              ENT_QUOTES,
                              "UTF-8",
                            ) ?>">
                            削除
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="container-fluid">
          <div class="row mt-2">
            <div class="col-3 ml-auto">
              <a title='顧客台帳に戻る' class='btn btn-outline-info mx-3'
                href="<?= BASE_URL ?>admin_list.php?id=<?= htmlspecialchars(
  $id,
  ENT_QUOTES,
  "UTF-8",
) ?>">台帳に戻る</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- 削除確認モーダル -->
  <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">カルテの削除</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          本当に『<span id="modal-karte-date"></span>』のカルテを削除してよろしいですか？
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
          <a id="modal-delete-btn" class="btn btn-danger" href="<?= BASE_URL ?>admin_history_delete.php?id=<?= htmlspecialchars(
  $id,
  ENT_QUOTES,
  "UTF-8",
) ?>"">削除する</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/gh/alfrcr/paginathing/dist/paginathing.min.js"></script>
  <script type="text/javascript" src="./js/paginathing.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="./js/offcanvas.js"></script>
  <script>
    // ページネーション
    $('.table tbody').paginathing({
      perPage: 5,
      insertAfter: '.table',
      pageNumbers: true,
      ulClass: 'pagination flex-wrap justify-content-center'
    });

    // モーダルにカルテ情報をセット
    $('#deleteModal').on('show.bs.modal', function(e) {
      const btn       = $(e.relatedTarget);
      const karteId   = btn.data('karte-id');
      const karteDate = btn.data('karte-date');
      $('#modal-karte-date').text(karteDate);
      $('#modal-delete-btn').attr('href', '<?= BASE_URL ?>admin_history_delete.php?id=' + karteId);
    });
  </script>
</body>
</html>
