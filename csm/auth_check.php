<?php
/**
 * auth_check.php
 *
 * 認証チェック（セッション確認 + トークン検証 + IP制限）
 * csm/ フォルダ内の全PHPファイルの先頭に以下の1行を追加するだけでOK:
 *   require_once __DIR__ . "/auth_check.php";
 *
 * @create 2026/05/07
 **/

// session_start() がまだの場合だけ開始（二重呼び出し防止）
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . "/../config/elfin_config.php";

// ログインページのURL
// 本番環境
define("LOGIN_URL", "https://elfin-customer.lamure.store/index.php");
// 開発環境
// define("LOGIN_URL", "https://develop-01.lamure.store/index.php");

// セッションを破棄してログインページへ飛ばす関数
function forceLogout(): void
{
  $_SESSION = [];
  session_destroy();
  header("Location: " . LOGIN_URL);
  exit();
}

// =============================================================================
// IP制限チェック（elfin_config.php で IP_RESTRICTION_ENABLED = true にすると有効）
// =============================================================================
if (defined("IP_RESTRICTION_ENABLED") && IP_RESTRICTION_ENABLED === true) {
  $client_ip = $_SERVER["REMOTE_ADDR"] ?? "";

  if (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
    $client_ip = trim(explode(",", $_SERVER["HTTP_X_FORWARDED_FOR"])[0]);
  }

  if (!in_array($client_ip, ALLOWED_IPS, true)) {
    http_response_code(403);
    die("<h2>このIPアドレスからのアクセスは許可されていません。</h2>");
  }
}

// =============================================================================
// 未ログインチェック
// =============================================================================
if (empty($_SESSION["login_id"])) {
  forceLogout();
}

// =============================================================================
// セッショントークン検証（パスワード変更で全端末を強制ログアウト）
// LOGIN_PASS_HASH が変わると SESSION_TOKEN も変わり、古いセッションは無効になる
// =============================================================================
if (
  !isset($_SESSION["session_token"]) ||
  $_SESSION["session_token"] !== SESSION_TOKEN
) {
  forceLogout();
}
