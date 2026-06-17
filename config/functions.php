<?php
/**
 * functions.php
 *
 * 共通関数・定数管理
 *
 * @create  2024/07/24
 * バージョン2.0.0
 * @Update  2026/03/23


*  ## 全体像まとめ
*functions.php
*├── STAFF_MASTER        // スタッフ一元管理
*├── REDIRECT_MAP        // 遷移先一元管理
*├── getDbh()            // DB接続
*├── getStaffName()      // スタッフ名取得
*├── getActiveStaffs()   // 在籍スタッフ取得
*└── redirectAfterAction() // alert + リダイレクト
**/

// =====================================================
// スタッフマスタ（ここだけ編集すればOK）
// =====================================================
// functions.php の呼び出し//
require_once __DIR__ . "/elfin_config.php";

const STAFF_MASTER = [
  // 指名スタッフ（member_nomination）
  "nomination" => [
    0 => ["name" => "----", "is_active" => 1],
    1 => ["name" => "Mayu", "is_active" => 1],
    2 => ["name" => "Maki", "is_active" => 1],
    3 => ["name" => "Asuka", "is_active" => 0], // 退職スタッフ
    4 => ["name" => "Shiena", "is_active" => 0], // 退職スタッフ
    5 => ["name" => "Yume", "is_active" => 0], // 退職スタッフ
    6 => ["name" => "Misaki", "is_active" => 1],
    7 => ["name" => "Yuna", "is_active" => 0], // 退職スタッフ
    8 => ["name" => "Yuuki", "is_active" => 0], // 退職スタッフ
    11 => ["name" => "Sae", "is_active" => 0], // 退職スタッフ
    12 => ["name" => "Kitamori", "is_active" => 0], // 退職スタッフ
    13 => ["name" => "Akane", "is_active" => 1],
    14 => ["name" => "Seiko", "is_active" => 1],
    15 => ["name" => "Moe", "is_active" => 1],
    99 => ["name" => "Others(退店者)", "is_active" => 1],
  ],

  // 施術者・記入者（practitioner / author 共通）
  "staff" => [
    0 => ["name" => "Mayu", "is_active" => 1],
    1 => ["name" => "Maki", "is_active" => 1],
    2 => ["name" => "Asuka", "is_active" => 0], // 退職スタッフ
    3 => ["name" => "Shiena", "is_active" => 0], // 退職スタッフ
    4 => ["name" => "Yume", "is_active" => 0],
    5 => ["name" => "Misaki", "is_active" => 1],
    6 => ["name" => "Yuna", "is_active" => 0], // 退職スタッフ
    7 => ["name" => "Yuuki", "is_active" => 0], // 退職スタッフ
    8 => ["name" => "Ayana", "is_active" => 0], // 退職スタッフ
    11 => ["name" => "Sae", "is_active" => 0], // 退職スタッフ
    12 => ["name" => "Kitamori", "is_active" => 0], // 退職スタッフ
    13 => ["name" => "Akane", "is_active" => 1],
    14 => ["name" => "Seiko", "is_active" => 1],
    15 => ["name" => "Moe", "is_active" => 1],
    99 => ["name" => "Others(退店者)", "is_active" => 1],
  ],
];

// カラム名 → マスタキーの対応
const COLUMN_MAP = [
  "member_nomination" => "nomination",
  "practitioner" => "staff",
  "author" => "staff",
  "buy_practitioner" => "staff",
];

// =====================================================
// きっかけ（store_trigger）マスタ
// ※ DBには数値で保存される。番号は変更しないこと（追加時は新番号を採番）
//   表示順 = 配列の並び順 / 内部値 = キーの数値
// =====================================================
const STORE_TRIGGERS = [
  0 => "----",
  1 => "ホットペッパー",
  2 => "ご紹介",
  3 => "SNS",
  7 => "Instagram",
  8 => "TikTok",
  4 => "ブログ",
  6 => "ミニモ",
  5 => "その他",
];

/**
 * きっかけIDからラベルを取得
 *
 * @param  int    $id  store_trigger の数値
 * @return string      ラベル（未定義の場合は '不明'）
 */
function getTriggerLabel(int $id): string
{
  return STORE_TRIGGERS[$id] ?? "不明";
}

// =====================================================
// リダイレクト先マップ（ここだけ編集すればOK）
// =====================================================
const REDIRECT_MAP = [
  // 顧客管理
  "add_customer" => "admin_edit.php", // 顧客新規登録後
  "update_customer" => "admin_edit.php", // 顧客情報更新後
  "delete_customer" => "admin_list.php", // 顧客削除後

  // カルテ管理
  "add_history" => "admin_history_list.php", // カルテ新規登録後
  "update_history" => "admin_history_detail.php", // カルテ更新後
  "delete_history" => "admin_history_list.php", // カルテ削除後
];

// =====================================================
// スタッフ関連関数
// =====================================================

/**
 * IDからスタッフ名を取得
 * is_active に関係なく名前を返す（過去データの表示用）
 *
 * @param  string $column  カラム名（'member_nomination' / 'practitioner' / 'author' / 'buy_practitioner'）
 * @param  int    $id      スタッフID
 * @return string          スタッフ名（未登録の場合は '不明'）
 */
function getStaffName(string $column, int $id): string
{
  $key = COLUMN_MAP[$column] ?? null;
  if (!$key) {
    return "不明";
  }

  return STAFF_MASTER[$key][$id]["name"] ?? "不明";
}

/**
 * セレクトボックス用：在籍中スタッフのみ取得
 * is_active = 1 のみ返す（新規選択用）
 *
 * @param  string $column  カラム名
 * @return array           ['id' => ['name' => ..., 'is_active' => 1], ...]
 */
function getActiveStaffs(string $column): array
{
  $key = COLUMN_MAP[$column] ?? null;
  if (!$key) {
    return [];
  }

  return array_filter(
    STAFF_MASTER[$key],
    fn($staff) => $staff["is_active"] === 1,
  );
}

/**
 * セレクトボックスのHTML生成
 * 退職者が保存済みの場合も「名前（退職）」として選択状態を維持する
 *
 * @param  string   $column     カラム名
 * @param  int|null $currentId  現在選択されているスタッフID
 * @return string               <option>タグのHTML文字列
 */
function buildStaffOptions(string $column, ?int $currentId): string
{
  $staffs = getActiveStaffs($column);

  // 現在の値がアクティブ一覧にない（退職者）場合は追加して選択状態を維持
  if (!is_null($currentId) && !isset($staffs[$currentId])) {
    $name = getStaffName($column, $currentId);
    $staffs[$currentId] = ["name" => $name . "（退職）", "is_active" => 0];
  }

  $html = "";
  foreach ($staffs as $id_s => $staff) {
    $selected = $currentId == $id_s ? " selected" : "";
    $name = htmlspecialchars($staff["name"], ENT_QUOTES, "UTF-8");
    $html .= "<option value=\"{$id_s}\"{$selected}>{$name}</option>\n";
  }
  return $html;
}

// =====================================================
// DB接続関数
// =====================================================

/**
 * PDO接続を返す
 * 各ファイルで new PDO を書く代わりにこれを呼ぶ
 *
 * @return PDO
 * @throws Exception 接続失敗時
 */
function getDbh(): PDO
{
  global $dsn, $member, $pass;
  $dbh = new PDO($dsn, $member, $pass);
  $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $dbh;
}

// =====================================================
// リダイレクト関数
// =====================================================

/**
 * alert を表示して指定アクションの遷移先へリダイレクト
 *
 * @param  string   $action  REDIRECT_MAPのキー
 * @param  int|null $id      遷移先に渡すID（不要な場合はnullを指定）
 * @param  string   $alert   表示メッセージ
 */
function redirectAfterAction(string $action, ?int $id, string $alert): void
{
  if (!isset(REDIRECT_MAP[$action])) {
    die(
      "リダイレクト先が定義されていません: " .
        htmlspecialchars($action, ENT_QUOTES, "UTF-8")
    );
  }

  // ← id が null の場合は ?id= を付与しない
  $url = BASE_URL . REDIRECT_MAP[$action];
  if (!is_null($id)) {
    $url .= "?id=" . $id;
  }

  $alert_js = addslashes($alert);

  echo "<!doctype html>
<html lang='ja'>
<head>
  <meta charset='utf-8'>
  <script>
    alert('{$alert_js}');
    location.href = '{$url}';
  </script>
</head>
<body></body>
</html>";
  exit();
}

// =====================================================
// 画像アップロード設定（ここだけ編集すればOK）
// =====================================================

define("IMAGE_DIR", __DIR__ . "/../images/nails/"); // 保存先の絶対パス
define("IMAGE_URL", "/images/nails/"); // 表示用URLパス
define("IMAGE_QUALITY", 55); // 圧縮品質（0〜100）
define("IMAGE_MAX_WIDTH", 600); // 最大横幅（px）

// =====================================================
// 画像関連関数
// =====================================================

/**
 * 画像をアップロードして圧縮保存
 * HEIC / JPG / PNG / GIF に対応
 *
 * @param  array       $file       $_FILES['nail_image']
 * @param  string|null $old_image  更新時の古い画像URL（削除用）
 * @return string                  保存した画像のURL
 * @throws Exception               エラー時
 */
function uploadNailImage(array $file, ?string $old_image = null): string
{
  if ($file["error"] !== UPLOAD_ERR_OK) {
    throw new Exception("画像のアップロードに失敗しました。");
  }
  if ($file["size"] > 10 * 1024 * 1024) {
    throw new Exception("画像は10MB以下にしてください。");
  }

  $mime_type = mime_content_type($file["tmp_name"]);
  $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

  // MIMEタイプが正しく取れない場合は拡張子で補完
  if ($mime_type === "application/octet-stream") {
    if (in_array($extension, ["heic", "heif"])) {
      throw new Exception(
        "HEICファイルはアップロードできません。" .
          "\n" .
          "iPhoneの設定 → カメラ → フォーマット → 「互換性優先」に変更してください。",
      );
    }
  }

  // HEIC が届いた場合は案内メッセージを表示
  if (in_array($mime_type, ["image/heic", "image/heif"])) {
    throw new Exception(
      "HEICファイルはアップロードできません。" .
        "\n" .
        "iPhoneの設定 → カメラ → フォーマット → 「互換性優先」に変更してください。",
    );
  }

  $allow_types = ["image/jpeg", "image/jpg", "image/png", "image/gif"];
  if (!in_array($mime_type, $allow_types)) {
    throw new Exception("jpg / png / gif のみアップロードできます。");
  }

  if (!is_dir(IMAGE_DIR)) {
    mkdir(IMAGE_DIR, 0755, true);
  }

  $ext = $mime_type === "image/png" ? "png" : "jpg";
  $file_name =
    "nail_" . date("Ymd") . "_" . bin2hex(random_bytes(4)) . "." . $ext;
  $save_path = IMAGE_DIR . $file_name;

  compressAndSave($file["tmp_name"], $save_path, $mime_type);

  if (!empty($old_image)) {
    deleteNailImage($old_image);
  }

  return IMAGE_URL . $file_name;
}

/**
 * 画像を圧縮・リサイズして保存
 *
 * @param string $src_path   アップロード元の一時ファイルパス
 * @param string $save_path  保存先パス
 * @param string $mime_type  MIMEタイプ
 * @throws Exception         画像読み込み失敗時
 */
function compressAndSave(
  string $src_path,
  string $save_path,
  string $mime_type,
): void {
  $src = match ($mime_type) {
    "image/png" => imagecreatefrompng($src_path),
    "image/gif" => imagecreatefromgif($src_path),
    default => imagecreatefromjpeg($src_path),
  };

  if (!$src) {
    throw new Exception("画像の読み込みに失敗しました。");
  }

  // リサイズ処理
  $orig_w = imagesx($src);
  $orig_h = imagesy($src);

  if ($orig_w > IMAGE_MAX_WIDTH) {
    $ratio = IMAGE_MAX_WIDTH / $orig_w;
    $new_w = IMAGE_MAX_WIDTH;
    $new_h = (int) ($orig_h * $ratio);
    $resized = imagecreatetruecolor($new_w, $new_h);

    // PNG・GIF の透過を保持
    if (in_array($mime_type, ["image/png", "image/gif"])) {
      imagealphablending($resized, false);
      imagesavealpha($resized, true);
    }

    imagecopyresampled(
      $resized,
      $src,
      0,
      0,
      0,
      0,
      $new_w,
      $new_h,
      $orig_w,
      $orig_h,
    );
    imagedestroy($src);
    $src = $resized;
  }

  // スマホ写真の向き補正（EXIF対応）
  if (function_exists("exif_read_data") && $mime_type === "image/jpeg") {
    $exif = @exif_read_data($src_path);
    if (!empty($exif["Orientation"])) {
      $src = fixImageOrientation($src, (int) $exif["Orientation"]);
    }
  }

  // 圧縮して保存
  match ($mime_type) {
    "image/png" => imagepng($src, $save_path, (int) (9 - IMAGE_QUALITY / 11)),
    "image/gif" => imagegif($src, $save_path),
    default => imagejpeg($src, $save_path, IMAGE_QUALITY),
  };

  imagedestroy($src);
}

/**
 * スマホ写真の向き補正（EXIFのOrientation対応）
 *
 * @param  resource $image        GD画像リソース
 * @param  int      $orientation  EXIFのOrientationの値
 * @return resource               補正後のGD画像リソース
 */
function fixImageOrientation($image, int $orientation)
{
  return match ($orientation) {
    3 => imagerotate($image, 180, 0),
    6 => imagerotate($image, -90, 0),
    8 => imagerotate($image, 90, 0),
    default => $image,
  };
}

/**
 * 画像ファイルを削除
 *
 * @param string $image_url  IMAGE_URL形式のパス（例: /images/nails/nail_xxx.jpg）
 */
function deleteNailImage(string $image_url): void
{
  $file_path = __DIR__ . "/../" . ltrim($image_url, "/");
  if (file_exists($file_path)) {
    unlink($file_path);
  }
}
