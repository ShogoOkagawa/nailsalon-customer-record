<?php
/**
 * admin_history_add.php
 * カルテ情報の新規追加
 * @create  2024/07/31
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

if (empty($_GET["id"])) {
  die("ID不正");
}
$id = (int) $_GET["id"];

// POST値を変数に代入
$member_id = (int) ($_POST["member_id"] ?? 0);
$member_name = $_POST["member_name"] ?? "";
$member_kana = $_POST["member_kana"] ?? "";
$buy_course = $_POST["buy_course"] ?? "";
$buy_color = $_POST["buy_color"] ?? "";
$buy_price = $_POST["buy_price"] ?? "";
$buy_practitioner = (int) ($_POST["buy_practitioner"] ?? 0);
$nail_image = $_POST["nail_image"] ?? "";
$buy_text = $_POST["buy_text"] ?? "";
$buy_date = !empty($_POST["buy_date"]) ? $_POST["buy_date"] : null;

// 画像処理（アップロードがあれば圧縮保存）
$nail_image = "";
if (!empty($_FILES["nail_image"]["name"])) {
  try {
    $nail_image = uploadNailImage($_FILES["nail_image"]);
  } catch (Exception $e) {
    die(
      "画像エラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8")
    );
  }
}
// -----------------------------------------------------------------------------
// course_list に新規INSERT
// -----------------------------------------------------------------------------
try {
  $dbh = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("INSERT INTO course_list (
      member_id, member_name, member_kana, buy_course, buy_price,
      buy_practitioner, buy_color, nail_image, buy_text, buy_date, upd_date, del_flg
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 1)");

  $stmt->bindValue(1, $member_id, PDO::PARAM_INT);
  $stmt->bindValue(2, $member_name, PDO::PARAM_STR);
  $stmt->bindValue(3, $member_kana, PDO::PARAM_STR);
  $stmt->bindValue(4, $buy_course, PDO::PARAM_STR);
  $stmt->bindValue(5, $buy_price, PDO::PARAM_STR);
  $stmt->bindValue(6, $buy_practitioner, PDO::PARAM_INT);
  $stmt->bindValue(7, $buy_color, PDO::PARAM_STR);
  $stmt->bindValue(8, $nail_image, PDO::PARAM_STR);
  $stmt->bindValue(9, $buy_text, PDO::PARAM_STR);
  $stmt->bindValue(
    10,
    $buy_date,
    is_null($buy_date) ? PDO::PARAM_NULL : PDO::PARAM_STR,
  );
  $stmt->execute();
  $dbh = null;
} catch (Exception $e) {
  die("エラー発生: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}

// ← redirectAfterAction() に変更
redirectAfterAction("add_history", $id, "登録が完了しました。");
