FROM php:8.1-cli

# 必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# composer.json と composer.lock を先にコピーして依存関係をインストール
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# ソースコードをコピー
COPY . .

# Laravel が書き込むディレクトリの権限設定
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

# 環境変数
ENV APP_ENV=production
ENV APP_DEBUG=false

# ポートは固定
EXPOSE 8000
