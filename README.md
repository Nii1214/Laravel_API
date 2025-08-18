# エンジニアの業務効率化を支援するAll-in-Oneツール『デブリオ　Devrio』
社内向けの業務アプリケーションをイメージして作成しました。
生成AIの台頭でさまざまなツールを使用することが考えられるため、各ツールの知見を集約して管理できるアプリケーションを目指しています。
将来的にはマネジメント思考のあるエンジニアを増やしたいと思い、WBSに関するデータも集約したいと考えており、このアプリでプロジェクト運営が完結できるようなアプリを目指しています。

## 主要機能
### グループ向けの機能
- wikiの作成・閲覧
- タスク管理機能
- 閲覧・編集権限の設定

### 個人向け機能
- ToDoリスト

## ディレクトリ構成
```
app/
 ├─ Http/
 │   ├─ Controllers/   ← APIの処理を行うクラス
 │   ├─ Requests/      ← リクエストのバリデーション定義
 │   └─ Resources/     ← APIレスポンス整形用クラス
 └─ Models/            ← DBテーブルと対応するモデル
database/
 ├─ migrations/        ← テーブル構造定義
 ├─ factories/         ← テストデータ生成用
 └─ seeders/           ← 初期データ投入用
routes/
 └─ api.php            ← API専用ルーティング設定
```
## 認証・認可
Laravel Sanctumを使用。

# 開発者用README

### アクセスURL
- **Laravel アプリケーション**: http://localhost:8000
- **PhpMyAdmin**: http://localhost:8080

```bash
# 全テスト実行
php artisan test

# 機能テストのみ
php artisan test --testsuite=Feature

# 単体テストのみ
php artisan test --testsuite=Unit

# 特定のテスト
php artisan test --filter test_todo_index_returns_paginated_todos
```