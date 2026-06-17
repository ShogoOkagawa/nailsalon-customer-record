<?php
/**
 * admin_history_update.php
 *
 * 顧客カルテの内容更新
 *
 * @create  2024/08/02
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php'; 

// -----------------------------------------------------------------------------
// IDチェック
// -----------------------------------------------------------------------------
if (empty($_POST['id'])) die('ID不正');
$id = (int) $_POST['id'];

// -----------------------------------------------------------------------------
// POST値を変数に代入
// -----------------------------------------------------------------------------
$member_name      = $_POST['member_name']      ?? '';
$member_kana      = $_POST['member_kana']      ?? '';
$buy_course       = $_POST['buy_course']       ?? '';
$buy_price        = $_POST['buy_price']        ?? '';
$buy_practitioner = (int)($_POST['buy_practitioner'] ?? 0);
$buy_color        = $_POST['buy_color']        ?? '';
// 画像処理
// 新しい画像がアップロードされた場合のみ更新・なければ現在の画像を維持
$nail_image = $_POST['current_nail_image'] ?? ''; // ← 現在の画像をデフォルトに

if (!empty($_FILES['nail_image']['name'])) {
    try {
        // 古い画像を削除しつつ新しい画像を保存
        $nail_image = uploadNailImage($_FILES['nail_image'], $nail_image);
    } catch (Exception $e) {
        die('画像エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}
// $nail_image には新画像URL or 現在の画像URLが入っている
$buy_text         = $_POST['buy_text']         ?? '';
$buy_date         = !empty($_POST['buy_date']) ? $_POST['buy_date'] : null;

// -----------------------------------------------------------------------------
// DB更新処理
// -----------------------------------------------------------------------------
try {
  $dbh  = getDbh(); // ← getDbh() に変更

  $stmt = $dbh->prepare("UPDATE course_list SET
      buy_course       = ?,
      buy_price        = ?,
      buy_practitioner = ?,
      buy_color        = ?,
      nail_image       = ?,
      buy_text         = ?,
      buy_date         = ?,
      upd_date         = NOW()
    WHERE id = ?");

  $stmt->bindValue(1, $buy_course,       PDO::PARAM_STR);
  $stmt->bindValue(2, $buy_price,        PDO::PARAM_STR);
  $stmt->bindValue(3, $buy_practitioner, PDO::PARAM_INT);
  $stmt->bindValue(4, $buy_color,        PDO::PARAM_STR);
  $stmt->bindValue(5, $nail_image,       PDO::PARAM_STR);
  $stmt->bindValue(6, $buy_text,         PDO::PARAM_STR);
  $stmt->bindValue(7, $buy_date,         is_null($buy_date) ? PDO::PARAM_NULL : PDO::PARAM_STR);
  $stmt->bindValue(8, $id,               PDO::PARAM_INT);
  $stmt->execute();

  $dbh = null;

} catch (Exception $e) {
  die('エラー発生: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

redirectAfterAction('update_history', $id, '内容を変更しました。');