# 勤怠管理アプリ

  このリポジトリは、ある企業の勤怠管理業務を想定した勤怠管理システムをLaravelで実装したプロジェクトです。一般ユーザーの勤怠登録・修正申請、管理者による勤怠管理・CSVエクスポート、および外部連携用の公開APIを実装しています。

  ## 動作環境

  - Docker
  - Docker Compose

  ※ Windowsの場合はWSL2の利用を推奨します。

  ## 環境構築手順

  1. **リポジトリをクローン**

     ```bash
     git clone git@github.com:en6113/attendance-app.git
     cd attendance-app
     ```

  2. **`.env`ファイルの準備**

     `.env.example` をコピーして `.env` を作成します。

     ```bash
     cp .env.example .env
     ```

     `.env` ファイルを開き、DB接続情報等を以下の値に変更してください。

     ```bash
     DB_CONNECTION=mysql
     DB_HOST=mysql
     DB_PORT=3306
     DB_DATABASE=laravel
     DB_USERNAME=sail
     DB_PASSWORD=password

     MAIL_MAILER=smtp
     MAIL_HOST=mailpit
     MAIL_PORT=1025
     ```

  3. **Composer依存パッケージのインストール**

     初回セットアップ時は `vendor` ディレクトリが存在せず `sail` コマンドが使えないため、以下のDockerコマンドでコンテナ内から `composer install` を実行します。

     ```bash
      docker run --rm \
          -u "$(id -u):$(id -g)" \
          -v "$(pwd):/var/www/html" \
          -w /var/www/html \
          laravelsail/php85-composer:latest \
          composer install --ignore-platform-reqs
     ```

  4. **Laravel Sailの起動**
    以下のコマンドでDockerコンテナを起動します。

     ```bash
     ./vendor/bin/sail up -d
     ```

  #### エイリアスの設定（推奨）
  毎回 `./vendor/bin/sail` と入力するのは手間なので、エイリアスを設定すると便利です。

  ```bash
  alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'
  ```

  5. **アプリケーションキーの生成**

     ```bash
     sail artisan key:generate
     ```

  6. **データベースのマイグレーションと初期データ投入**
    以下のコマンドでテーブルを作成し、ダミーデータを投入します。

     ```bash
     sail artisan migrate:fresh --seed
     ```
  7. **フロントエンドのビルド**

     ```bash
     sail npm install
     sail npm run dev
     ```

     > 注意: `npm run dev` は開発中は起動したままにしてください。

  8. **アプリケーションへのアクセス**

     ブラウザで http://localhost にアクセスします。

 ## 開発環境URL

  - アプリケーション: http://localhost
  - 管理者ログイン: http://localhost/admin/login
  - phpMyAdmin: http://localhost:8080
  - Mailpit（メール確認用）: http://localhost:8025

  ## ログイン情報

  👤一般ユーザー
  - メールアドレス: user1@example.com
  - パスワード: password

  ⚙️管理者
  - メールアドレス: user3@example.com
  - パスワード: password

  ## テスト実行

  ```bash
  sail artisan test
  ```

  ## 機能一覧

  👤一般ユーザー向け

  - ユーザー認証（会員登録、ログイン、ログアウト、メール認証、パスワードリセット）
  - 出勤・退勤・休憩入/出の記録
  - 勤怠一覧の確認（月次）
  - 勤怠詳細の確認・修正申請
  - 修正申請一覧の確認
  - 勤怠統計レポートの確認

  ⚙️管理者向け

  - 管理者ログイン
  - 日次勤怠一覧の確認
  - スタッフ一覧・スタッフ別月次勤怠の確認
  - 勤怠の直接修正
  - 勤怠情報のCSVエクスポート
  - 修正申請の承認

  👨‍👩‍👧‍👦公開API

  - 勤怠情報のCRUD（RESTful JSON API、Laravel Sanctumによるトークン認証）

  ## APIエンドポイント一覧

  | メソッド | パス | 概要 | Sanctum認証 |
  | :--- | :--- | :--- | :--- |
  | GET | `/api/v1/attendance-records` | 勤怠一覧取得 | 不要 |
  | GET | `/api/v1/attendance-records/{id}` | 勤怠詳細取得 | 不要 |
  | POST | `/api/v1/attendance-records` | 勤怠登録 | 必須 |
  | PUT / PATCH | `/api/v1/attendance-records/{id}` | 勤怠更新 | 必須 |
  | DELETE | `/api/v1/attendance-records/{id}` | 勤怠削除 | 必須 |

  ## 使用技術

  - PHP: 8.5
  - Laravel: 10.x
  - MySQL: 8.4
  - Docker / Laravel Sail: 開発環境コンテナ化
  - Vite: フロントエンドビルド
  - Laravel Fortify: 認証機能
  - Laravel Sanctum: API認証
  - Laravel Pint: コード整形
  - phpMyAdmin: DB管理ツール
  - Mailpit: 開発用メール確認ツール

  ## ER図

  ```mermaid
  erDiagram
      users ||--o{ attendance_records : "has"
      attendance_records ||--o{ breaks : "has"
      attendance_records ||--o{ attendance_correct_requests : "has"
      attendance_correct_requests ||--o{ proposal_breaks : "has"

      users {
          bigint id PK
          string name
          string email UK
          timestamp email_verified_at
          string password
          string remember_token
          boolean admin_status "管理者フラグ"
          timestamps timestamps
      }

      attendance_records {
          bigint id PK
          bigint user_id FK
          date date UK "user_idとの複合ユニーク"
          datetime clock_in_time "nullable"
          datetime clock_out_time "nullable"
          string comment "nullable"
          timestamps timestamps
      }

      breaks {
          bigint id PK
          bigint attendance_record_id FK
          datetime break_start_time "nullable"
          datetime break_end_time "nullable"
          timestamps timestamps
      }

      attendance_correct_requests {
          bigint id PK
          bigint attendance_record_id FK
          boolean is_direct_edit "直接修正フラグ"
          date old_date "nullable"
          datetime old_clock_in "nullable"
          datetime old_clock_out "nullable"
          string old_comment "nullable"
          json old_breaks "nullable"
          date new_date
          string new_clock_in "9:00等の入力形式を許容するため文字列"
          string new_clock_out "9:00等の入力形式を許容するため文字列"
          string comment
          timestamp approved_at "承認済みならセット"
          date application_date
          timestamps timestamps
      }

      proposal_breaks {
          bigint id PK
          bigint attendance_correct_request_id FK
          string break_in
          string break_out "nullable"
          timestamps timestamps
      }
  ```

## 作成者

en6113