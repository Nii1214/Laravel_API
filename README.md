
## Docker環境での実行

このプロジェクトはDockerを使用して簡単にセットアップできます。

### 前提条件
- Docker
- Docker Compose

### セットアップ手順

```bash
# コンテナをビルドして起動
docker-compose build
docker-compose up -d

# Laravelの初期設定
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan storage:link
```
   ```bash
   # コンテナをビルドして起動
   docker-compose build
   docker-compose up -d
   
   # Laravelの初期設定
   docker-compose exec app composer install
   docker-compose exec app php artisan key:generate
   docker-compose exec app php artisan migrate
   docker-compose exec app php artisan storage:link
   ```

### アクセスURL
- **Laravel アプリケーション**: http://localhost:8000
- **PhpMyAdmin**: http://localhost:8080

### データベース情報
- **Host**: localhost
- **Port**: 3306
- **Database**: laravel_db
- **Username**: laravel_user
- **Password**: laravel_password

### 便利なコマンド
```bash
# コンテナの起動
docker-compose up -d

# コンテナの停止
docker-compose down

# ログの表示
docker-compose logs -f

# アプリコンテナに入る
docker-compose exec app bash

# データベースコンテナに入る
docker-compose exec mysql mysql -u laravel_user -p laravel_db
```

