<?php
/**
 * admin_new.php
 * 新規登録
 * @create  2024/07/24
 * @Update  2026/06/15
 *
 * 変更点(2026/06/15):
 *  - ご利用規約を表示し、同意チェック（必須）を追加
 **/
require_once __DIR__ . "/auth_check.php";
require_once '../config/elfin_config.php';
require_once '../config/functions.php';
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>新規お客様登録画面</title>
  <link rel="stylesheet" href="css/floating-labels.css">
  <link rel="stylesheet" href="css/bootstrap.css">
  <style>
    .terms-box {
      max-height: 260px;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      font-size: 0.82rem;
      line-height: 1.75;
      background: #f8f9fa;
    }
    .terms-box h2 {
      font-size: 0.92rem;
      font-weight: bold;
      margin: 14px 0 6px;
      color: rgb(120,83,178);
    }
    .terms-box h2:first-child { margin-top: 0; }
    .terms-box ul { padding-left: 1.1em; margin-bottom: 8px; }
    .terms-box ul ul { margin-top: 4px; }
    .terms-box .note { color: #666; font-size: 0.78rem; }
  </style>
</head>
<body>
  <form action="admin_add_2.php" method="POST" class="form-signin" id="newCustomerForm">
    <div class="text-center mb-4">
      <h1 class="h3 mb-3 font-weight-normal">お客様情報登録</h1>
    </div>

    <div class="form-label-group">
      <input type="text" name="member_name" id="inputId" class="form-control" required autofocus>
      <label for="inputId">お名前</label>
    </div>
    <div class="form-label-group">
      <input type="text" name="member_kana" class="form-control" required>
      <label>フリガナ</label>
    </div>

    <!-- 作成者セレクト（getActiveStaffs）-->
    <div class="form-group mt-4">
      <label>作成者 / 担当者</label>
      <select id="author" class="col-6 custom-select" name="author" required>
        <?php foreach (getActiveStaffs('author') as $id_s => $staff): ?>
          <option value="<?= $id_s ?>">
            <?= htmlspecialchars($staff['name'], ENT_QUOTES, 'UTF-8') ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- ご利用規約 -->
    <div class="form-group mt-4">
      <label class="font-weight-bold mb-2">ご利用規約</label>
      <div id="termsBox" class="terms-box border rounded p-3">
        <h2>◾️ご利用規約</h2>
        <p class="mb-2">当サロンをご利用いただくすべてのお客様に、気持ちよくご利用いただくため、下記内容をご確認のうえご予約をお願いいたします。</p>

        <h2>◾️ご予約・キャンセルについて</h2>
        <ul>
          <li>キャンセル・日時変更のご連絡は、ご予約日前日18:00までにお願いいたします。</li>
          <li>前日18:00以降のキャンセル、およびご予約当日のキャンセル・日時変更につきましては、キャンセル料2,000円を申し受けます。</li>
          <li>キャンセル料は次回来店時にお支払いいただきます。</li>
          <li>10分以上遅刻された場合は、ご予約状況によりデザインや施術内容を変更・制限させていただく場合がございます。なお、大幅な遅刻の場合は施術をお断りする場合がございます。</li>
          <li>ご連絡なく15分以上遅刻された場合は、無断キャンセル扱いとさせていただく場合がございます。</li>
          <li>無断キャンセルをされた場合、または当日キャンセルを繰り返された場合は、今後のご予約をお断りさせていただく場合がございます。</li>
          <li>当サロンは完全予約制です。一人でも多くのお客様をご案内できるよう、ご理解とご協力をお願いいたします。</li>
        </ul>

        <h2>◾️お直し（無料保証）について</h2>
        <ul>
          <li>施術後1週間以内にご連絡いただいた場合に限り、無料でお直しいたします。</li>
          <li>以下の場合は無料保証の対象となります。
            <ul>
              <li>ジェルの浮き</li>
              <li>ストーン・パーツが取れた場合</li>
              <li>長さ出しが折れた場合</li>
            </ul>
          </li>
        </ul>
        <p class="note mb-1">※カラー・デザイン変更など、お客様都合によるお直しは有料となります。</p>
        <p class="note mb-1">※施術時に補強をされていないお爪の亀裂補強は有料となります。</p>
        <p class="note mb-2">※minimoからのご予約は保証対象外となり、お直しはお受けしておりません。</p>

        <h2>◾️返金について</h2>
        <ul class="mb-0">
          <li>施術後の返金はいたしかねますので、あらかじめご了承ください。</li>
        </ul>
      </div>
    </div>

    <!-- 同意チェック（必須／規約を最後までスクロールで有効化）-->
    <div class="form-group mb-4">
      <div class="form-check">
        <input type="checkbox" class="form-check-input" id="agree" name="agree" value="1" required disabled>
        <label class="form-check-label" for="agree">
          上記のご利用規約に同意します <span class="text-danger">（必須）</span>
        </label>
      </div>
      <small id="agreeHint" class="text-muted d-block mt-1">
        ※ご利用規約を最後までスクロールすると、チェックできるようになります。
      </small>
    </div>

    <button class="btn btn-lg btn-primary btn-block" type="submit">登録</button>

    <div class="text-center mt-3">
      <a title="もどる" class="btn btn-outline-secondary" href="admin_list.php">もどる</a>
    </div>

    <p class="mt-5 mb-3 text-muted text-center">&copy; Nail Salon elfin / le ciel 2024-</p>
  </form>

  <script>
    // ご利用規約を最後までスクロールしたら同意チェックを有効化する
    (function () {
      var box  = document.getElementById('termsBox');
      var chk  = document.getElementById('agree');
      var hint = document.getElementById('agreeHint');
      if (!box || !chk) return;

      function enableAgree() {
        chk.disabled = false;
        if (hint) {
          hint.textContent = '✓ ご確認ありがとうございます。同意にチェックしてください。';
          hint.classList.remove('text-muted');
          hint.classList.add('text-success');
        }
      }

      function onScroll() {
        // 下端まで（2px許容）到達したら有効化
        if (box.scrollTop + box.clientHeight >= box.scrollHeight - 2) {
          enableAgree();
          box.removeEventListener('scroll', onScroll);
        }
      }

      box.addEventListener('scroll', onScroll);

      // 内容がボックス内に収まりスクロール不要な場合は最初から有効化
      window.addEventListener('load', function () {
        if (box.scrollHeight <= box.clientHeight + 2) enableAgree();
      });
    })();
  </script>
</body>
</html>
