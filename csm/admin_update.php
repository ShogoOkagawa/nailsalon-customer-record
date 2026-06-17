<?php
/**
 * admin_update.php
 * 顧客情報の内容更新
 * @create  2024/07/22
 * @Update  2026/03/23
 **/
require_once __DIR__ . "/auth_check.php";
require_once __DIR__ . '/../config/elfin_config.php';
require_once __DIR__ . '/../config/functions.php';

if (empty($_GET['id'])) die('ID不正');
$id = (int) $_GET['id'];

// POST値を変数に代入
$member_name       = $_POST['member_name']             ?? '';
$member_kana       = $_POST['member_kana']             ?? '';
$member_addr       = $_POST['member_addr']             ?? '';
$member_email      = $_POST['member_email']            ?? '';
$member_phone      = $_POST['member_phone']            ?? '';
$member_birthday   = !empty($_POST['member_birthday']) ? $_POST['member_birthday'] : null;
$store_trigger     = (int)($_POST['store_trigger']     ?? 0);
$introducer        = $_POST['introducer']              ?? '';
$nail_experience   = (int)($_POST['nail_experience']   ?? 0);
$allergy           = (int)($_POST['allergy']           ?? 0);
$allergy_text      = $_POST['allergy_text']            ?? '';
$favorite_color    = $_POST['favorite_color']          ?? '';
$request           = $_POST['request']                 ?? '';
$others            = $_POST['others']                  ?? '';
$member_nomination = (int)($_POST['member_nomination'] ?? 0);
$author            = (int)($_POST['author']            ?? 0);

date_default_timezone_set('Asia/Tokyo');
$upd_date = date('Y-m-d H:i:s');

// 画像処理（新しいアップロードがあれば更新・なければ現在の画像を維持）
$nail_image = $_POST['current_nail_image'] ?? '';
if (!empty($_FILES['nail_image']['name'])) {
    try {
        $nail_image = uploadNailImage($_FILES['nail_image'], $nail_image);
    } catch (Exception $e) {
        die('画像エラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
}

// -----------------------------------------------------------------------------
// DB更新処理
// -----------------------------------------------------------------------------
try {
  $dbh  = getDbh(); // ← getDbh() に変更
  $stmt = $dbh->prepare("UPDATE customer_list SET
      member_name       = ?,
      member_kana       = ?,
      member_phone      = ?,
      member_email      = ?,
      member_addr       = ?,
      member_birthday   = ?,
      store_trigger     = ?,
      introducer        = ?,
      nail_experience   = ?,
      allergy           = ?,
      allergy_text      = ?,
      favorite_color    = ?,
      request           = ?,
      others            = ?,
      member_nomination = ?,
      author            = ?,
      upd_date          = ?
    WHERE no = ?");

  $stmt->bindValue(1,  $member_name,       PDO::PARAM_STR);
  $stmt->bindValue(2,  $member_kana,       PDO::PARAM_STR);
  $stmt->bindValue(3,  $member_phone,      PDO::PARAM_STR);
  $stmt->bindValue(4,  $member_email,      PDO::PARAM_STR);
  $stmt->bindValue(5,  $member_addr,       PDO::PARAM_STR);
  $stmt->bindValue(6,  $member_birthday,   is_null($member_birthday) ? PDO::PARAM_NULL : PDO::PARAM_STR);
  $stmt->bindValue(7,  $store_trigger,     PDO::PARAM_INT);
  $stmt->bindValue(8,  $introducer,        PDO::PARAM_STR);
  $stmt->bindValue(9,  $nail_experience,   PDO::PARAM_INT);
  $stmt->bindValue(10, $allergy,           PDO::PARAM_INT);
  $stmt->bindValue(11, $allergy_text,      PDO::PARAM_STR);
  $stmt->bindValue(12, $favorite_color,    PDO::PARAM_STR);
  $stmt->bindValue(13, $request,           PDO::PARAM_STR);
  $stmt->bindValue(14, $others,            PDO::PARAM_STR);
  $stmt->bindValue(15, $member_nomination, PDO::PARAM_INT);
  $stmt->bindValue(16, $author,            PDO::PARAM_INT);
  $stmt->bindValue(17, $upd_date,          PDO::PARAM_STR);
  $stmt->bindValue(18, $id,               PDO::PARAM_INT);
  $stmt->execute();
  $dbh = null;

} catch (Exception $e) {
  die('エラー発生: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// ← redirectAfterAction() に変更
// redirectAfterAction('update_customer', $id, '内容を変更しました。');
redirectAfterAction('delete_customer', $id, '内容を変更しました。');