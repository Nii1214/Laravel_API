# PHP 8.2 CLI ベース
FROM php:8.2-cli

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリ
WORKDIR /var/www

# Laravel が書き込むディレクトリの権限設定（先に）
RUN mkdir -p /var/www/storage /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# .env をコピー（APP_KEY は Render の環境変数で上書き）
COPY .env.example .env

# composer.json と composer.lock をコピーして依存関係インストール
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# ソースコードコピー
COPY . .

# 環境変数（Docker ビルド内用。Render の envVars で上書きされます）
ENV APP_ENV=production
ENV APP_DEBUG=false

# ポート
EXPOSE 8000

# サーバ起動
CMD php -S 0.0.0.0:8000 -t public
