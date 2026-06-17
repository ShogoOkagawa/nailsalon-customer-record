<?php
/**
 * admin_history_delete.php
 *
 * カルテの論理削除（del_flg = 0 に更新）
 *
 * @create  2026/03/26
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php';

if (empty($_GET['id'])) die('ID不正');
$id = (int) $_GET['id'];

try {
  $dbh  = getDbh();

  // member_id を先に取得（削除後のリダイレクト用）
  $stmt = $dbh->prepare("SELECT member_id FROM course_list WHERE id = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) throw new Exception('該当するカルテが見つかりません。');
  $member_id = (int)$row['member_id'];

  // del_flg = 0 に更新（論理削除）
  $stmt = $dbh->prepare("UPDATE course_list SET del_flg = 0 WHERE id = ?");
  $stmt->bindValue(1, $id, PDO::PARAM_INT);
  $stmt->execute();

  $dbh = null;

} catch (Exception $e) {
  die('エラー発生: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// カルテ一覧へリダイレクト
redirectAfterAction('delete_history', $member_id, 'カルテを削除しました。');