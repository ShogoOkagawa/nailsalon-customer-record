<?php
/**
 * admin_list.php
 * 登録顧客情報の一覧
 * @create 2024/07/20
 * @Update 2026/06/13
 *
 * 変更点(2026/06/13):
 *  - 検索/ソート機能を全面リニューアル（すべてクライアントサイドJSで動作）
 *    ・リアルタイム絞り込み検索（入力で即時フィルタ・リロード無し）
 *    ・並び替え切替（フリガナ順／登録が新しい順／古い順／指名順）
 *    ・きっかけ・指名スタッフでの絞り込み
 *    ・該当件数の表示／検索対象にメモも追加
 *    ・あかさたな索引も即時フィルタ化（キーワード等と併用可）
 *  - 上記に伴い一覧は常に全件を読み込む方式へ変更
 **/
require_once __DIR__ . "/auth_check.php";
require_once "../config/elfin_config.php";
require_once "../config/functions.php";

// -----------------------------------------------------------------------------
// あかさたな索引マップ（先頭文字 → グループ判定に使用）
// -----------------------------------------------------------------------------
$indexMap = [
  "あ" => ["あ", "い", "う", "え", "お", "ア", "イ", "ウ", "エ", "オ"],
  "か" => ["か","き","く","け","こ","が","ぎ","ぐ","げ","ご","カ","キ","ク","ケ","コ","ガ","ギ","グ","ゲ","ゴ"],
  "さ" => ["さ","し","す","せ","そ","ざ","じ","ず","ぜ","ぞ","サ","シ","ス","セ","ソ","ザ","ジ","ズ","ゼ","ゾ"],
  "た" => ["た","ち","つ","て","と","だ","ぢ","づ","で","ど","タ","チ","ツ","テ","ト","ダ","ヂ","ヅ","デ","ド"],
  "な" => ["な", "に", "ぬ", "ね", "の", "ナ", "ニ", "ヌ", "ネ", "ノ"],
  "は" => ["は","ひ","ふ","へ","ほ","ば","び","ぶ","べ","ぼ","ハ","ヒ","フ","ヘ","ホ","バ","ビ","ブ","ベ","ボ"],
  "ま" => ["ま", "み", "む", "め", "も", "マ", "ミ", "ム", "メ", "モ"],
  "や" => ["や", "ゆ", "よ", "ヤ", "ユ", "ヨ"],
  "ら" => ["ら", "り", "る", "れ", "ろ", "ラ", "リ", "ル", "レ", "ロ"],
  "わ" => ["わ", "を", "ん", "ワ", "ヲ", "ン"],
  "a-z" => range("a", "z"),
];

/**
 * フリガナ先頭文字から索引グループ名を返す
 * （JSでの索引フィルタ用に各顧客へ data-group として付与する）
 */
function kanaGroup(string $kana): string
{
  global $indexMap;
  $first = mb_substr($kana, 0, 1);
  foreach ($indexMap as $g => $chars) {
    if (in_array($first, $chars, true)) {
      return $g;
    }
  }
  if (preg_match('/^[A-Za-z]$/u', $first)) {
    return "a-z";
  }
  return "その他";
}

// -----------------------------------------------------------------------------
// pageflag の判定（detail＝右カラムに顧客詳細を表示 / それ以外は一覧）
// -----------------------------------------------------------------------------
$id =
  isset($_GET["id"]) && $_GET["id"] !== "" ? (int) $_GET["id"] : "";
$pageflag = $id !== "" ? "detail" : "list";

// 一覧で強調表示する顧客No
//  detail時は表示中の顧客（$id）、
//  list時はタブレットで「一覧に戻る」した直後の選択顧客（?focus=）を引き継ぐ
$focus_no =
  $id !== ""
    ? $id
    : (isset($_GET["focus"]) && $_GET["focus"] !== ""
      ? (int) $_GET["focus"]
      : "");

// -----------------------------------------------------------------------------
// DB処理
// -----------------------------------------------------------------------------
try {
  $dbh = getDbh();

  // 右カラム：選択中の顧客詳細
  $d_result = null;
  if ($pageflag === "detail") {
    $d_stmt = $dbh->prepare(
      "SELECT * FROM customer_list WHERE no = ? AND del_flg = 1",
    );
    $d_stmt->bindValue(1, $id, PDO::PARAM_INT);
    $d_stmt->execute();
    $d_result = $d_stmt->fetch(PDO::FETCH_ASSOC);
  }

  // 中央リスト：常に全件取得（絞り込み・並び替え・検索はJS側で実行）
  $stmt = $dbh->query(
    "SELECT * FROM customer_list WHERE del_flg = 1 ORDER BY member_kana ASC",
  );
  $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // detail表示時のみ：その顧客の画像一覧を取得
  $image_result = [];
  if ($pageflag == "detail") {
    $stmt = $dbh->prepare("SELECT id, buy_date, nail_image
      FROM course_list
      WHERE member_id = ?
      AND del_flg = 1
      AND nail_image IS NOT NULL
      AND nail_image != ''
      ORDER BY buy_date DESC");
    $stmt->bindValue(1, $id, PDO::PARAM_INT);
    $stmt->execute();
    $image_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  $dbh = null;
} catch (Exception $e) {
  echo "エラー発生: " .
    htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8") .
    "<br>";
  die();
}

// ② getStaffName() でスタッフ名を変換
$nomination = isset($d_result["member_nomination"])
  ? getStaffName("member_nomination", (int) $d_result["member_nomination"])
  : "フリー";

$practitioner = isset($d_result["practitioner"])
  ? getStaffName("practitioner", (int) $d_result["practitioner"])
  : "フリー";
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>顧客一覧</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
  <link href="./css/offcanvas.css" rel="stylesheet">
  <style>
    .name_list { transition: all 0.3s ease 0s; cursor: pointer; }
    .name_list:hover { border-right: 5px solid rgb(120,83,178); background-color: rgb(232,232,232); }
    /* 現在表示中／一覧に戻った直後の顧客をハイライト */
    #setFocus { background-color: rgb(243,238,250); border-right: 5px solid rgb(120,83,178); }
    .search-item { transition: all 0.1s ease 0s; cursor: pointer; }
    .search-item:hover { background-color: rgb(120,83,177); }
    .search-text { color: gray; }
    .search-text:hover { color: white; }
    .search-text.active-index .search-item { background-color: rgb(120,83,178); color:#fff; border-radius:4px; }
    .head { height: 50px; }
    /* 検索ツールバー */
    .list-toolbar { padding: 8px 10px; background:#fff; border-bottom:1px solid #e3e3e3; }
    .list-toolbar .form-control-sm, .list-toolbar .custom-select-sm { font-size:0.8rem; }
    .list-toolbar select { cursor:pointer; }
    .result-count { font-size:0.75rem; color:rgb(120,83,178); font-weight:bold; }
    .no-hit { padding:20px; text-align:center; color:#999; font-size:0.85rem; }

    /* ===================================================== */
    /* レスポンシブ対応（iPad / iPad mini 縦持ち＝992px未満） */
    /*  ・3カラム → 単一ペインのマスター/詳細切替           */
    /*  ・あかさたな索引を横並びバーに                       */
    /*  ・詳細のラベルを縦積みに                             */
    /* ===================================================== */
    @media (max-width: 991.98px) {
      /* 各カラムを全幅に */
      #customer-list { display:block !important; }
      .index-col,
      #namelist,
      #customer_detail {
        max-width:100% !important;
        flex:0 0 100% !important;
        width:100% !important;
        margin-left:0 !important;
      }

      /* 一覧表示中は詳細を隠す／詳細表示中は索引・一覧を隠す */
      .view-list  #customer_detail { display:none !important; }
      .view-detail .index-col,
      .view-detail #namelist { display:none !important; }

      /* あかさたな索引：縦の細カラム → 横並びバー */
      .index-col { border:0 !important; margin-bottom:6px; }
      .index-col .head { display:none !important; }
      .index-col .search-strings {
        display:flex !important; flex-wrap:wrap; justify-content:center;
        gap:4px; padding:8px 4px; margin:0;
      }
      .index-col .search-strings .search-text { display:inline-block; }
      .index-col .search-item {
        min-width:40px; padding:10px 8px !important;
        border:1px solid #e3e3e3; border-radius:8px; font-size:1rem;
      }

      /* 顧客リストの高さをビューポートに合わせる */
      .customers { height: calc(100vh - 280px) !important; }

      /* タッチしやすいようにツールバーのフォント/高さを拡大 */
      .list-toolbar .form-control-sm,
      .list-toolbar .custom-select-sm {
        font-size:1rem !important; height:calc(1.7em + .75rem + 2px) !important;
      }

      /* 詳細：ラベル(dt)を上、値(dd)を下の縦積みに */
      .datail dt {
        float:none !important; display:block !important;
        padding:14px 0 0 16px !important; font-size:13px !important; color:#888;
      }
      .datail dd { padding:2px 16px 10px 16px !important; }

      .back-to-list { font-size:1rem; }
    }
  </style>
</head>

<body class="bg-light <?= $pageflag === "detail" ? "view-detail" : "view-list" ?>">

  <!-- ナビバー -->
  <nav class="navbar navbar-expand-lg fixed-top navbar-dark" style="background-color: rgb(120,83,178);">
    <div class="d-flex mt-3 mb-3" style="width:100%;">
      <div class="">　　</div>
      <div class="">
        <a href="<?= BASE_URL ?>admin_new.php" data-toggle="tooltip" data-placement="bottom" title="新規登録">
          <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-person-plus-fill text-white" viewBox="0 0 16 16">
            <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1H1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
            <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
          </svg>
        </a>
      </div>
      <div style="position:absolute; top:50%;left:50%;transform: translate(-50%, -50%);">
        <h1 class="text-white text-center">顧客台帳</h1>
      </div>
    </div>
  </nav>

  <!-- メイン -->
  <div class="container-fluid mr-2 ml-2" style="margin-top: 100px!important;">
    <div id="customer-list" class="row rounded-lg" style="width:100%;">

      <!-- 左カラム：あかさたな索引（即時フィルタ） -->
      <div class="col-1 index-col ml-2 p-0 border flex-row" style="width:40px;">
        <div class="head border" style="width:100%; height:50px;"></div>
        <ul class="search-strings text-center" style="list-style-type:none; display:inline;">
          <?php
          $indexes = [
            "ALL" => "",
            "あ" => "あ",
            "か" => "か",
            "さ" => "さ",
            "た" => "た",
            "な" => "な",
            "は" => "は",
            "ま" => "ま",
            "や" => "や",
            "ら" => "ら",
            "わ" => "わ",
            "a-z" => "a-z",
            "その他" => "その他",
          ];
          foreach ($indexes as $label => $val): ?>
            <a class="search-text text-decoration-none<?= $val === ""
              ? " active-index"
              : "" ?>" href="#" data-group="<?= htmlspecialchars(
  $val,
  ENT_QUOTES,
  "UTF-8",
) ?>">
              <li class="search-item pt-1 pb-1"><?= htmlspecialchars(
                $label,
                ENT_QUOTES,
                "UTF-8",
              ) ?></li>
            </a>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- 中央カラム：顧客名一覧 -->
      <div id="namelist" class="col-4 p-0 content border">

        <!-- 検索・絞り込みツールバー -->
        <div class="list-toolbar">
          <form id="flt-form" onsubmit="return false;" autocomplete="off">
            <div class="input-group input-group-sm mb-2">
              <input type="text" id="flt-q" class="form-control form-control-sm"
                placeholder="名前・フリガナ・電話・メール・メモで検索" aria-label="Search">
              <div class="input-group-append">
                <button type="submit" id="flt-search" class="btn btn-primary btn-sm">検索</button>
                <button type="button" id="flt-clear" class="btn btn-outline-secondary btn-sm" title="クリア">×</button>
              </div>
            </div>
            <div class="row no-gutters" style="gap:4px 0;">
              <div class="col-12 mb-1">
                <select id="flt-sort" class="custom-select custom-select-sm">
                  <option value="kana">並び順：フリガナ順</option>
                  <option value="no_desc">並び順：登録が新しい順</option>
                  <option value="no_asc">並び順：登録が古い順</option>
                  <option value="nom">並び順：指名スタッフ順</option>
                </select>
              </div>
              <div class="col-6 pr-1">
                <select id="flt-trigger" class="custom-select custom-select-sm">
                  <option value="">きっかけ：すべて</option>
                  <option value="0">（未設定）</option>
                  <?php foreach (STORE_TRIGGERS as $tid => $tlabel):
                    if ($tid === 0) {
                      continue;
                    } ?>
                    <option value="<?= $tid ?>"><?= htmlspecialchars(
  $tlabel,
  ENT_QUOTES,
  "UTF-8",
) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 pl-1">
                <select id="flt-nomination" class="custom-select custom-select-sm">
                  <option value="">指名：すべて</option>
                  <?php foreach (STAFF_MASTER["nomination"] as $nid => $st): ?>
                    <option value="<?= $nid ?>"><?= htmlspecialchars(
  $st["name"],
  ENT_QUOTES,
  "UTF-8",
) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </form>
        </div>

        <div class="w-100% border-bottom d-flex align-items-center justify-content-between" style="padding:8px 20px;">
          <div class="text-body font-weight-bold" style="font-size:1.1rem;">
            お名前<br>
            <span style="color:rgb(123,123,123); font-size:0.5rem;">フリガナ</span>
          </div>
          <div class="result-count">該当 <span id="flt-count">0</span> 件 / 全 <?= count(
            $result,
          ) ?> 件</div>
        </div>
        <div class="customers" style="overflow-x:hidden; overflow-y:scroll; height: calc(100vh - 330px);">
          <ul id="customer-ul" class="pl-0" style="list-style-type:none;">
            <?php foreach ($result as $row):
              $nom_id = (int) ($row["member_nomination"] ?? 0);
              $nom_name = getStaffName("member_nomination", $nom_id);
              // 検索対象テキスト（名前・フリガナ・電話・メール・メモ）
              $search_src = mb_strtolower(
                trim(
                  ($row["member_name"] ?? "") .
                    " " .
                    ($row["member_kana"] ?? "") .
                    " " .
                    ($row["member_phone"] ?? "") .
                    " " .
                    ($row["member_email"] ?? "") .
                    " " .
                    ($row["others"] ?? ""),
                ),
                "UTF-8",
              );
              ?>
              <li <?= $focus_no !== "" && $focus_no == $row["no"]
                ? "id='setFocus'"
                : "" ?> class="name_list border-bottom" tabIndex="0"
                data-no="<?= htmlspecialchars($row["no"], ENT_QUOTES, "UTF-8") ?>"
                data-group="<?= htmlspecialchars(
                  kanaGroup($row["member_kana"] ?? ""),
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>"
                data-kana="<?= htmlspecialchars(
                  $row["member_kana"] ?? "",
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>"
                data-trigger="<?= (int) ($row["store_trigger"] ?? 0) ?>"
                data-nomination="<?= $nom_id ?>"
                data-nom-name="<?= htmlspecialchars(
                  $nom_name,
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>"
                data-search="<?= htmlspecialchars(
                  $search_src,
                  ENT_QUOTES,
                  "UTF-8",
                ) ?>">
                <a class="text-decoration-none" href="<?= BASE_URL ?>admin_list.php?id=<?= htmlspecialchars(
  $row["no"],
  ENT_QUOTES,
  "UTF-8",
) ?>">
                  <div class="text-body font-weight-bold" style="padding:15px 20px; font-size:1.2rem;">
                    <?= htmlspecialchars(
                      $row["member_name"],
                      ENT_QUOTES,
                      "UTF-8",
                    ) ?>　様<br>
                    <span style="color:rgb(123,123,123); font-size:0.5rem;">
                      <?= htmlspecialchars(
                        $row["member_kana"],
                        ENT_QUOTES,
                        "UTF-8",
                      ) ?>
                    </span>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
          <div id="flt-nohit" class="no-hit" style="display:none;">該当する顧客が見つかりません。</div>
        </div>
      </div>

      <!-- 右カラム：顧客詳細 -->
      <div id="customer_detail" class="col p-0 border">
        <!-- iPad等の縦持ち（単一ペイン）時のみ表示：一覧へ戻る -->
        <a href="<?= BASE_URL ?>admin_list.php?focus=<?= htmlspecialchars(
  $id,
  ENT_QUOTES,
  "UTF-8",
) ?>" class="btn btn-outline-secondary btn-sm m-2 d-lg-none back-to-list">← 一覧に戻る</a>
        <div class="head border d-flex bd-highlight mb-3">
          <div class="mr-auto p-2 bd-highlight">
            <?php if ($pageflag == "detail"): ?>
              <a class="btn btn-success btn-sm mx-2 my-auto"
                href="<?= BASE_URL ?>admin_history_list.php?id=<?= htmlspecialchars(
  $d_result["no"],
  ENT_QUOTES,
  "UTF-8",
) ?>">カルテを見る</a>
            <?php else: ?>
              <a href="<?= BASE_URL ?>admin_new.php" class="btn btn-success btn-sm my-auto ml-2">新規登録</a>
            <?php endif; ?>
          </div>
          <div class="p-2 bd-highlight">
            <?php if ($pageflag == "detail"): ?>
              <a class="btn btn-outline-info btn-sm mx-2 my-auto"
                href="<?= BASE_URL ?>admin_edit.php?id=<?= htmlspecialchars(
  $d_result["no"],
  ENT_QUOTES,
  "UTF-8",
) ?>">詳細</a>
              <button type="button" class="btn btn-danger btn-sm mx-2 my-auto"
                data-toggle="modal" data-target="#exampleModal">削除</button>
            <?php else: ?>
              <a class="btn btn-outline-secondary btn-sm mx-2 my-auto disabled" href="<?= BASE_URL ?>admin_list.php">編集</a>
              <a class="btn btn-danger btn-sm mx-2 my-auto" href="<?= BASE_URL ?>admin_list.php">削除</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- 詳細内容 -->
        <div class="datail ml-2">
          <dl>
            <dt style="float:left; padding-top:10px;">No.</dt>
            <dd class="m-0" style="padding:8px 20px 0 10px; font-size:18px; display:block;">
              <?= $pageflag == "detail"
                ? htmlspecialchars(
                  str_pad($d_result["no"], 5, 0, STR_PAD_LEFT),
                  ENT_QUOTES,
                  "UTF-8",
                )
                : "--" ?>
            </dd>

            <dt style="float:left; font-size:14px; padding-top:12px;">フリガナ</dt>
            <dd id="detail_hurigana" class="hurigana border-bottom" style="padding:10px 20px 0 150px; font-size:16px; display:block;">
              <?= $pageflag == "detail"
                ? htmlspecialchars(
                  $d_result["member_kana"],
                  ENT_QUOTES,
                  "UTF-8",
                )
                : "---" ?>
            </dd>

            <dt style="float:left; padding-top:18px;">お名前</dt>
            <dd class="border-bottom" style="padding:10px 20px 6px 150px; font-size:18px; display:block;">
              <?= $pageflag == "detail"
                ? htmlspecialchars(
                    $d_result["member_name"],
                    ENT_QUOTES,
                    "UTF-8",
                  ) . "　様"
                : "---" ?>
            </dd>

            <!-- getStaffName() で変換した $nomination を表示 -->
            <dt style="float:left; padding-top:18px;">指名</dt>
            <dd id="detail_id" class="border-bottom" style="padding:10px 20px 6px 150px; font-size:18px; display:block;">
              <?php if ($pageflag == "detail"): ?>
                <?= isset($d_result["member_nomination"])
                  ? htmlspecialchars($nomination, ENT_QUOTES, "UTF-8")
                  : "ご指名なし" ?>
              <?php else: ?>
                指名スタッフ
              <?php endif; ?>
            </dd>

            <dt style="float:left; padding-top:18px;">連絡先</dt>
            <dd id="detail_phone" class="border-bottom" style="padding:10px 20px 6px 150px; font-size:18px; display:block;">
              <?php if ($pageflag == "detail"): ?>
                <?= !empty($d_result["member_phone"])
                  ? htmlspecialchars(
                    $d_result["member_phone"],
                    ENT_QUOTES,
                    "UTF-8",
                  )
                  : "ご連絡先情報記載なし" ?>
              <?php else: ?>
                電話番号
              <?php endif; ?>
            </dd>

            <dt style="float:left; padding-top:18px;">アレルギーなど</dt>
            <dd class="border-bottom" style="padding:10px 20px 6px 150px; font-size:18px; display:block;">
              <?php if ($pageflag == "detail"): ?>
                <?= !empty($d_result["allergy_text"])
                  ? htmlspecialchars(
                    $d_result["allergy_text"],
                    ENT_QUOTES,
                    "UTF-8",
                  )
                  : "アレルギー情報なし" ?>
              <?php else: ?>
                アレルギー情報
              <?php endif; ?>
            </dd>

            <dt style="float:left; padding-top:18px;">店舗記入メモ</dt>
            <dd id="detail_memo" class="border-bottom" style="padding:15px 20px 10px 150px; font-size:18px; display:block;">
              <?php if ($pageflag == "detail"): ?>
                <?= !empty($d_result["others"])
                  ? htmlspecialchars($d_result["others"], ENT_QUOTES, "UTF-8")
                  : "記入事項なし" ?>
              <?php else: ?>
                スタッフより記入事項があればここに記入する
              <?php endif; ?>
            </dd>
          </dl>
          <!-- 画像一覧 -->
          <?php if ($pageflag == "detail" && !empty($image_result)): ?>
            <div class="ml-2 mt-3 mb-3">
              <hr>
              <p class="font-weight-bold mb-2" style="font-size:14px; color:rgb(120,83,178);">
                📷 施術画像
                <span class="text-muted" style="font-size:12px; font-weight:normal;">
                  （<?= count($image_result) ?>枚）
                </span>
              </p>

              <!-- 画像グリッド -->
              <div id="image-gallery" class="d-flex flex-wrap" style="gap:8px; min-height:90px;">
                <?php foreach ($image_result as $index => $img): ?>
                  <div class="gallery-item text-center"
                    data-page="<?= floor($index / 5) + 1 ?>"
                    style="display:none; flex-shrink:0;">
                    <a href="<?= BASE_URL ?>admin_history_detail.php?id=<?= htmlspecialchars(
  $img["id"],
  ENT_QUOTES,
  "UTF-8",
) ?>"
                      title="<?= htmlspecialchars(
                        $img["buy_date"],
                        ENT_QUOTES,
                        "UTF-8",
                      ) ?>">
                      <img
                        src="<?= htmlspecialchars(
                          $img["nail_image"],
                          ENT_QUOTES,
                          "UTF-8",
                        ) ?>"
                        style="width:120px; height:120px; object-fit:cover; border-radius:6px; border:2px solid #ddd; transition: border 0.2s;"
                        onmouseover="this.style.border='2px solid rgb(120,83,178)'"
                        onmouseout="this.style.border='2px solid #ddd'"
                      >
                    </a>
                    <div style="font-size:0.55rem; color:rgb(123,123,123); margin-top:2px;">
                      <?= htmlspecialchars(
                        $img["buy_date"],
                        ENT_QUOTES,
                        "UTF-8",
                      ) ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>

              <!-- ページネーション -->
              <div id="image-pagination" class="d-flex align-items-center mt-2" style="gap:4px;">
              </div>

            </div>

          <?php elseif ($pageflag == "detail" && empty($image_result)): ?>
            <div class="ml-2 mt-3 mb-2">
              <hr>
              <p class="font-weight-bold mb-1" style="font-size:14px; color:rgb(120,83,178);">
                📷 施術画像
              </p>
              <span class="text-muted small">画像はありません</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </div>

  <!-- 削除モーダル -->
  <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">顧客の削除</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          本当に『<?= htmlspecialchars(
            $d_result["member_name"] ?? "",
            ENT_QUOTES,
            "UTF-8",
          ) ?>様』を削除してよろしいですか？
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">キャンセル</button>
          <a class="btn btn-danger"
            href="<?= BASE_URL ?>admin_delete.php?id=<?= htmlspecialchars(
  $d_result["no"] ?? "",
  ENT_QUOTES,
  "UTF-8",
) ?>">削除する</a>
        </div>
      </div>
    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
  <script src="offcanvas.js"></script>

  <!-- ===================================================== -->
  <!-- 顧客一覧 検索・並び替え・絞り込み（クライアントサイド） -->
  <!-- ===================================================== -->
  <script>
  (function() {
    const listEl  = document.getElementById('customer-ul');
    if (!listEl) return;

    const items     = Array.from(listEl.querySelectorAll('li.name_list'));
    const formEl    = document.getElementById('flt-form');
    const qInput    = document.getElementById('flt-q');
    const clearBtn  = document.getElementById('flt-clear');
    const sortSel   = document.getElementById('flt-sort');
    const trigSel   = document.getElementById('flt-trigger');
    const nomSel    = document.getElementById('flt-nomination');
    const countEl   = document.getElementById('flt-count');
    const noHitEl   = document.getElementById('flt-nohit');
    const indexLinks = Array.from(document.querySelectorAll('.search-text'));

    let group = ''; // あかさたな索引（'' = ALL）

    function norm(s) { return (s || '').toString().toLowerCase(); }

    // 並び替え
    function sortItems(visible) {
      const mode = sortSel.value;
      visible.sort(function(a, b) {
        switch (mode) {
          case 'no_desc': return (+b.dataset.no) - (+a.dataset.no);
          case 'no_asc':  return (+a.dataset.no) - (+b.dataset.no);
          case 'nom':
            return (a.dataset.nomName || '').localeCompare(b.dataset.nomName || '', 'ja')
                || (a.dataset.kana || '').localeCompare(b.dataset.kana || '', 'ja');
          default:
            return (a.dataset.kana || '').localeCompare(b.dataset.kana || '', 'ja');
        }
      });
      // 表示順をDOMへ反映
      visible.forEach(function(li) { listEl.appendChild(li); });
    }

    // 絞り込み＋並び替え＋件数更新
    function apply() {
      const q    = norm(qInput.value).trim();
      const trig = trigSel.value;
      const nom  = nomSel.value;
      const visible = [];

      items.forEach(function(li) {
        let ok = true;
        if (group && li.dataset.group !== group) ok = false;
        if (ok && trig !== '' && li.dataset.trigger !== trig) ok = false;
        if (ok && nom  !== '' && li.dataset.nomination !== nom) ok = false;
        if (ok && q && li.dataset.search.indexOf(q) === -1) ok = false;
        li.style.display = ok ? '' : 'none';
        if (ok) visible.push(li);
      });

      sortItems(visible);
      countEl.textContent = visible.length;
      noHitEl.style.display = visible.length === 0 ? '' : 'none';
    }

    // イベント
    // テキスト検索は「検索ボタン」または Enter（form submit）でのみ実行
    // → IME変換中などの毎キー入力で並び替えが走るのを防ぎ動作を軽くする
    formEl.addEventListener('submit', function(e) {
      e.preventDefault();
      apply();
    });
    // 並び替え・絞り込み・索引は従来どおり即時反映（離散操作のため軽い）
    sortSel.addEventListener('change', apply);
    trigSel.addEventListener('change', apply);
    nomSel.addEventListener('change', apply);
    clearBtn.addEventListener('click', function() {
      qInput.value = '';
      qInput.focus();
      apply();
    });

    // あかさたな索引（即時フィルタ）
    indexLinks.forEach(function(a) {
      a.addEventListener('click', function(e) {
        e.preventDefault();
        group = a.dataset.group || '';
        indexLinks.forEach(function(x) { x.classList.remove('active-index'); });
        a.classList.add('active-index');
        apply();
      });
    });

    apply();
  })();
  </script>

  <script>
    ['detail_id','detail_hurigana','detail_phone','detail_memo'].forEach(function(id) {
      const el = document.getElementById(id);
      if (el && el.textContent.trim().length === 0) {
        el.textContent = '---';
      }
    });

    window.onload = function() {
      const focus = document.getElementById('setFocus');
      if (focus) focus.scrollIntoView({ block: 'center' });
    };
  </script>
  <script>
  (function() {
    const PER_PAGE   = 5;    // 1ページに表示する画像枚数
    const COLOR_MAIN = 'rgb(120,83,178)'; // テーマカラー
    let currentPage  = 1;

    const items      = document.querySelectorAll('.gallery-item');
    const pagination = document.getElementById('image-pagination');

    // 画像が存在しない場合は何もしない
    if (!items.length || !pagination) return;

    const totalPages = Math.ceil(items.length / PER_PAGE);

    // =====================
    // 指定ページを表示
    // =====================
    function showPage(page) {
      currentPage = page;

      // 画像の表示・非表示切り替え
      items.forEach(function(item) {
        item.style.display = (parseInt(item.dataset.page) === page) ? '' : 'none';
      });

      // ページネーションボタンを再描画
      renderPagination();
    }

    // =====================
    // ページネーションボタン生成
    // =====================
    function renderPagination() {
      pagination.innerHTML = '';

      // 1ページしかない場合は表示しない
      if (totalPages <= 1) return;

      // 「前へ」ボタン
      const prevBtn = makeBtn('‹', currentPage === 1, function() {
        showPage(currentPage - 1);
      });
      pagination.appendChild(prevBtn);

      // ページ番号ボタン
      for (let i = 1; i <= totalPages; i++) {
        const isActive = (i === currentPage);
        const btn = makeBtn(String(i), false, function() {
          showPage(i);
        });

        // アクティブページのスタイル
        if (isActive) {
          btn.style.backgroundColor = COLOR_MAIN;
          btn.style.color           = '#fff';
          btn.style.borderColor     = COLOR_MAIN;
        }
        pagination.appendChild(btn);
      }

      // 「次へ」ボタン
      const nextBtn = makeBtn('›', currentPage === totalPages, function() {
        showPage(currentPage + 1);
      });
      pagination.appendChild(nextBtn);
    }

    // =====================
    // ボタン要素を生成
    // =====================
    function makeBtn(label, disabled, onClick) {
      const btn = document.createElement('button');
      btn.type        = 'button';
      btn.textContent = label;
      btn.style.cssText = [
        'width:32px',
        'height:32px',
        'font-size:14px',
        'border:1px solid #ddd',
        'border-radius:4px',
        'background:#fff',
        'cursor:pointer',
        'padding:0',
        'line-height:1',
      ].join(';');

      if (disabled) {
        btn.disabled        = true;
        btn.style.opacity   = '0.4';
        btn.style.cursor    = 'not-allowed';
      } else {
        btn.addEventListener('click', onClick);
      }
      return btn;
    }

    // 初期表示
    showPage(1);
  })();
  </script>
</body>
</html>
