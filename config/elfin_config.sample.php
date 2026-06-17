<?php
/**
 * elfin_config.sample.php  ← サンプル（雛形）
 *
 * このファイルをコピーして「elfin_config.php」を作成し、各環境の値を設定する。
 *   cp config/elfin_config.sample.php config/elfin_config.php
 *
 * ⚠️ 実ファイル elfin_config.php は認証情報を含むため .gitignore で除外している。
 *    （リポジトリにはこの sample のみを置く）
 *
 * DB接続設定 + ログイン認証設定 + IP制限設定
 **/

// -----------------------------------------------------------------------------
// 環境判定（ローカル or 本番を自動判定）
// -----------------------------------------------------------------------------
$is_local = in_array($_SERVER["HTTP_HOST"], ["localhost", "localhost:8888"]);

if ($is_local) {
  // =========================================================
  // ローカル環境（MAMP）
  // =========================================================
  define("DB_HOST", "localhost");
  define("DB_NAME", "your_local_db");
  define("DB_USER", "your_local_user");
  define("DB_PASSWORD", "your_local_password");
  define("BASE_URL", "http://localhost:8888/elfin/csm/");
} else {
  // =========================================================
  // 本番環境（Xserver）
  // =========================================================
  define("DB_HOST", "your-db-host.xserver.jp");
  define("DB_NAME", "your_db_name");
  define("DB_USER", "your_db_user");
  define("DB_PASSWORD", "your_db_password");
  // 開発環境
  // define("BASE_URL", "https://develop-01.lamure.store/csm/");
  // 本番環境
  define("BASE_URL", "https://elfin-customer.lamure.store/csm/");
}

// -----------------------------------------------------------------------------
// DSN・接続変数
// -----------------------------------------------------------------------------
$dsn = "mysql:dbname=" . DB_NAME . ";host=" . DB_HOST . ";charset=utf8mb4";
$member = DB_USER;
$pass = DB_PASSWORD;

// =============================================================================
// ① ログイン認証設定（ID / パスワード管理）
// =============================================================================
//
// パスワードを変更したい場合の手順:
//   1. change_pass.php をブラウザで開く
//   2. 新しいパスワードを入力すると「ハッシュ文字列」が表示される
//   3. 表示された文字列を下の LOGIN_PASS_HASH にコピーして上書き保存
//   4. change_pass.php はサーバーから削除する（セキュリティ上必須）
// =============================================================================

define("LOGIN_ID", "your_login_id");

// ↓ change_pass.php で生成した bcrypt ハッシュ文字列に置き換える
define("LOGIN_PASS_HASH", '$2y$12$REPLACE_WITH_YOUR_OWN_BCRYPT_HASH');

// セッショントークン（LOGIN_PASS_HASH から自動生成）
// LOGIN_PASS_HASH を書き換えるだけで全端末のセッションが自動的に無効になる
define("SESSION_TOKEN", hash("sha256", LOGIN_PASS_HASH));

// =============================================================================
// ② IP制限設定（店舗のIPアドレス以外からのアクセスを拒否）
// =============================================================================
//   1. 店舗のグローバルIPアドレスを調べる
//   2. 下の ALLOWED_IPS 配列に追加する
//   3. auth_check.php の IP_RESTRICTION_ENABLED を true にする
// =============================================================================

define("ALLOWED_IPS", [
  "0.0.0.0", // ← ここを店舗のグローバルIPアドレスに書き換える
]);

// IP制限を有効にするか（true = 有効 / false = 無効）
define("IP_RESTRICTION_ENABLED", false);
