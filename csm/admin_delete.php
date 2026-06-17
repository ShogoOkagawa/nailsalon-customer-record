<?php
/**
 * admin_delete.php
 *
 * リスト削除（論理削除）
 *
 * @create  2024/07/25
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

// -----------------------------------------------------------------------------
// IDチェック
// -----------------------------------------------------------------------------
if (empty($_GET["id"])) {
  die("ID不正");
}
$id = (int) $_GET["id"];

// -----------------------------------------------------------------------------
// DB更新処理（del_flg = 0 に更新）
// -----------------------------------------------------------------------------
try {
  date_default_timezone_set("Asia/Tokyo");
  $upd_date = date("Y-m-d H:i:s"); // ← 変数が未定義だったので追加

  $dbh = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("UPDATE customer_list SET
      del_flg  = ?,
      upd_date = ?
    WHERE no = ?");

  $stmt->bindValue(1, 0, PDO::PARAM_INT); // ← STR から INT に修正
  $stmt->bindValue(2, $upd_date, PDO::PARAM_STR);
  $stmt->bindValue(3, $id, PDO::PARAM_INT);
  $stmt->execute();

  $dbh = null;
} catch (Exception $e) {
  die("エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}

// ← redirectAfterAction() に変更（自動でローカル・本番切り替え）
redirectAfterAction('delete_customer', null, '削除が完了しました。');
