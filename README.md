# Nail Salon elfin 顧客管理システム

ネイルサロン **elfin** の顧客台帳・カルテ（施術履歴）管理 Web アプリケーション。
PHP + MySQL(PDO) + Bootstrap 4.5 で構築。店舗では **iPad / iPad mini を縦持ち固定**で利用。

- バージョン: **v1.2.0**
- 本番: https://elfin-customer.lamure.store/
- 開発: https://develop-01.lamure.store/

---

## 主な機能

| 機能 | 概要 |
|---|---|
| ログイン認証 | `index.php` でログイン。`csm/` 配下は `auth_check.php` で セッション + トークン + （任意で）IP 制限を検証。パスワードは bcrypt(cost=12) ハッシュ管理 |
| 顧客管理 | 顧客の登録・編集・削除・一覧（`admin_*`） |
| カルテ管理 | 顧客ごとの施術履歴の登録・編集・削除・詳細（`admin_history_*`） |
| 検索・並べ替え | 顧客一覧でリアルタイム絞り込み（検索ボタン/Enter発火）、フリガナ/登録順/指名順の並べ替え、きっかけ・指名スタッフでの絞り込み、件数表示、あかさたな索引 |
| スタッフマスタ | `config/functions.php` の `STAFF_MASTER` で指名/施術者を一元管理（退職者は `is_active=0`） |
| 画像アップロード | ネイル写真を自動圧縮・リサイズ・EXIF 向き補正して保存（HEIC 非対応の案内付き） |
| レスポンシブ | iPad 縦持ち（992px 未満）でマスター/詳細の単一ペイン切替。カルテ一覧は横スクロール対応 |

---

## ディレクトリ構成

```
elfin-customer_v1.2.0/
├── index.php                     ログイン画面（顧客台帳TOP）
├── change_pass.php               パスワードハッシュ生成ツール（※サーバーに残さない）
├── .user.ini                     PHP 設定
├── config/
│   ├── elfin_config.php           DB接続・ログイン認証・IP制限（★Git管理外／要手動作成）
│   ├── elfin_config.sample.php    上記の雛形（これをコピーして作成する）
│   └── functions.php              共通関数（スタッフ/きっかけ/DB接続/画像/リダイレクト）
├── css/ images/                  デザイン・画像（images/nails/ は顧客写真＝Git管理外）
└── csm/                          管理画面本体（要ログイン）
    ├── auth_check.php             認証チェック（各画面の先頭で読込）
    ├── admin_list.php             顧客一覧（メイン画面）
    ├── admin_new / edit / update / delete / add_2   顧客CRUD
    ├── admin_history_*            カルテCRUD
    └── js/                        ページネーション・モーダル等
```

---

## セットアップ

### 1. 取得

```bash
git clone git@github.com:ShogoOkagawa/nailsalon-customer-record.git
cd nailsalon-customer-record
```

### 2. 設定ファイルを作成

`config/elfin_config.php` は認証情報を含むため Git 管理外です。雛形からコピーして各環境の値を設定します。

```bash
cp config/elfin_config.sample.php config/elfin_config.php
```

`elfin_config.php` を開き、以下を設定:

- `DB_HOST` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` … 接続先 DB
- `BASE_URL` … 環境に応じて develop / 本番を切替
- `LOGIN_ID` / `LOGIN_PASS_HASH` … ログイン ID と bcrypt ハッシュ
- `ALLOWED_IPS` / `IP_RESTRICTION_ENABLED` … IP 制限（任意）

### 3. データベース

MySQL に以下のテーブルが必要です（既存スキーマに準拠）:

- `customer_list` … 顧客（`store_trigger` は INT で保存）
- `course_list` … カルテ／施術履歴（`nail_image` に画像URL）

### 4. ログインパスワードの変更

1. `change_pass.php` をブラウザで開く
2. 新パスワードを入力 → 表示された bcrypt ハッシュを `elfin_config.php` の `LOGIN_PASS_HASH` に貼り付け
3. **`change_pass.php` をサーバーから必ず削除**（残すと誰でも PW を変更できてしまう）

---

## デプロイ（本番反映）

本番サーバー（Xserver）へ **FTP / ファイルマネージャーで手動アップロード**。

1. `BASE_URL`（`config/elfin_config.php`）と `LOGIN_URL`（`csm/auth_check.php`）が本番向きか確認
2. 変更したファイルをアップロード
3. `change_pass.php` をサーバーに残さない

---

## 「きっかけ」マスタ

来店きっかけは `config/functions.php` の `STORE_TRIGGERS` で一元管理。
**DB には数値で保存されるため、既存の番号は変更しないこと**（追加時は新番号を採番）。

| 値 | ラベル |
|---|---|
| 0 | ---- |
| 1 | ホットペッパー |
| 2 | ご紹介 |
| 3 | SNS |
| 7 | Instagram |
| 8 | TikTok |
| 4 | ブログ |
| 6 | ミニモ |
| 5 | その他 |

---

## セキュリティ上の注意

- **`config/elfin_config.php`（DB認証情報）と `images/nails/`（顧客写真）は `.gitignore` で除外**。コミットしないこと。
- リポジトリは顧客管理システムの性質上 **Private 推奨**。
- `change_pass.php` を本番サーバーに残さない。
- 必要に応じて `IP_RESTRICTION_ENABLED = true` で店舗 IP 以外を遮断。

---

## 技術スタック

- PHP（PDO / GD）
- MySQL
- Bootstrap 4.5 / jQuery 3.x / paginathing
