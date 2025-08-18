# Render へのデプロイ手順

## 概要
Laravel APIアプリケーションをRenderにデプロイする手順を説明します。

## 前提条件
- GitHubアカウント
- Renderアカウント
- PostgreSQL対応のLaravelアプリケーション

## デプロイ手順

### 1. GitHubリポジトリの準備
```bash
# 現在のブランチをmainにマージ
git checkout main
git merge feature/todo-api

# リモートにプッシュ
git push origin main
```

### 2. Renderでの設定

#### 2.1 アカウント作成
1. [Render](https://render.com) にアクセス
2. GitHubアカウントでサインアップ

#### 2.2 新しいWebサービスを作成
1. Dashboard → "New +" → "Web Service"
2. GitHubリポジトリを選択
3. 以下の設定を行う：

**基本設定**
- **Name**: `laravel-api`
- **Environment**: `PHP`
- **Region**: `Oregon (US West)`
- **Branch**: `main`
- **Root Directory**: 空白（ルートディレクトリ）

**ビルド設定**
- **Build Command**: 
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- **Start Command**:
```bash
php artisan migrate --force
php -S 0.0.0.0:$PORT -t public
```

#### 2.3 環境変数の設定
以下の環境変数を設定：

| キー | 値 |
|------|-----|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `LOG_CHANNEL` | `stack` |
| `CACHE_DRIVER` | `file` |
| `SESSION_DRIVER` | `file` |
| `QUEUE_CONNECTION` | `sync` |
| `DB_CONNECTION` | `pgsql` |

#### 2.4 PostgreSQLデータベースの作成
1. Dashboard → "New +" → "PostgreSQL"
2. 以下の設定：
   - **Name**: `laravel-db`
   - **Database**: `laravel_db`
   - **User**: `laravel_user`
   - **Plan**: `Starter`

#### 2.5 データベース接続情報の設定
PostgreSQL作成後、以下の環境変数を設定：

| キー | 値 |
|------|-----|
| `DB_HOST` | PostgreSQLのホスト |
| `DB_PORT` | PostgreSQLのポート |
| `DB_DATABASE` | `laravel_db` |
| `DB_USERNAME` | `laravel_user` |
| `DB_PASSWORD` | PostgreSQLのパスワード |

### 3. デプロイの実行
1. "Create Web Service" をクリック
2. デプロイが開始される（5-10分程度）
3. デプロイ完了後、URLが表示される

### 4. デプロイ後の確認

#### 4.1 APIエンドポイントの確認
```bash
# ヘルスチェック
curl https://your-app-name.onrender.com/up

# APIエンドポイント
curl https://your-app-name.onrender.com/api/todos
```

#### 4.2 ログの確認
- Render Dashboard → サービス → "Logs" タブ

## トラブルシューティング

### よくある問題

#### 1. マイグレーションエラー
```bash
# ログでエラーを確認
# データベース接続情報を再確認
```

#### 2. 環境変数の問題
```bash
# 環境変数が正しく設定されているか確認
# 特にデータベース接続情報
```

#### 3. ビルドエラー
```bash
# composer install が失敗する場合
# PHP拡張の確認
```

## 本番環境での注意点

### セキュリティ
- `APP_DEBUG=false` を必ず設定
- 本番用のAPP_KEYを生成
- データベースパスワードを強固に設定

### パフォーマンス
- キャッシュを有効化
- ログレベルを適切に設定
- 不要なデバッグ情報を無効化

### 監視
- Renderのログを定期的に確認
- アプリケーションのレスポンス時間を監視
- データベース接続の安定性を確認

## 参考リンク
- [Render Documentation](https://render.com/docs)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [PostgreSQL on Render](https://render.com/docs/databases)
