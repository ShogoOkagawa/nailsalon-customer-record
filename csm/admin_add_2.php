<?php
/**
 * admin_add_2.php
 * 顧客情報の新規追加
 * @create  2024/07/22
 * @Update  2026/03/23
 **/

require_once __DIR__ . "/auth_check.php";
require_once __DIR__ . "/../config/elfin_config.php";
require_once __DIR__ . "/../config/functions.php";

// -----------------------------------------------------------------------------
// 利用規約への同意チェック（未同意の直POSTを拒否）
// -----------------------------------------------------------------------------
if (empty($_POST["agree"])) {
  die("ご利用規約への同意が必要です。前の画面に戻り、同意のうえ登録してください。");
}

// -----------------------------------------------------------------------------
// POST値を変数に代入
// -----------------------------------------------------------------------------
$member_name = $_POST["member_name"] ?? "";
$member_kana = $_POST["member_kana"] ?? "";
$author = (int) ($_POST["author"] ?? 0);

// -----------------------------------------------------------------------------
// ① customer_list に新規INSERT
// -----------------------------------------------------------------------------
try {
  $dbh = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("INSERT INTO customer_list (
      member_name, member_kana, author, del_flg, login_flg, add_date
    ) VALUES (?, ?, ?, 1, 1, NOW())");
  $stmt->bindValue(1, $member_name, PDO::PARAM_STR);
  $stmt->bindValue(2, $member_kana, PDO::PARAM_STR);
  $stmt->bindValue(3, $author, PDO::PARAM_INT);
  $stmt->execute();

  $new_id = (int) $dbh->lastInsertId();
  $dbh = null;
} catch (Exception $e) {
  die(
    "エラー発生（customer_list INSERT）: " .
      htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8")
  );
}

// -----------------------------------------------------------------------------
// ② 登録した顧客データをSELECTで取得
// -----------------------------------------------------------------------------
try {
  $dbh = getDbh();
  $stmt = $dbh->prepare("SELECT * FROM customer_list WHERE no = ?");
  $stmt->bindValue(1, $new_id, PDO::PARAM_INT);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_ASSOC);
  $dbh = null;

  if (!$result) {
    throw new Exception("登録データの取得に失敗しました。");
  }

  $member_name = $result["member_name"];
  $member_kana = $result["member_kana"];
  $member_id = $result["no"];
} catch (Exception $e) {
  die(
    "エラー発生（customer_list SELECT）: " .
      htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8")
  );
}

// -----------------------------------------------------------------------------
// ③ course_list にも同時INSERT
// -----------------------------------------------------------------------------
try {
  $dbh = getDbh();
  $stmt = $dbh->prepare("INSERT INTO course_list (
      member_id, member_name, member_kana, del_flg
    ) VALUES (?, ?, ?, 0)");
  $stmt->bindValue(1, $member_id, PDO::PARAM_INT);
  $stmt->bindValue(2, $member_name, PDO::PARAM_STR);
  $stmt->bindValue(3, $member_kana, PDO::PARAM_STR);
  $stmt->execute();
  $dbh = null;
} catch (Exception $e) {
  die(
    "エラー発生（course_list INSERT）: " .
      htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8")
  );
}

// ← redirectAfterAction() に変更
redirectAfterAction("add_customer", $member_id, "登録が完了しました。");
