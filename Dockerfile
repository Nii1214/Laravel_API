# Render公式サンプルを参考にしたDockerfile
FROM php:8.1-cli

# システムパッケージの更新と必要なパッケージのインストール
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

# Composerのインストール
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 作業ディレクトリの設定
WORKDIR /var/www

# composer.jsonとcomposer.lockをコピー
COPY composer.json composer.lock ./

# 依存関係のインストール
RUN composer install --no-dev --optimize-autoloader --no-scripts

# アプリケーションファイルのコピー
COPY . .

# 依存関係のインストール（スクリプト実行）
RUN composer install --no-dev --optimize-autoloader

# 権限の設定
RUN chown -R www-data:www-data /var/www \
    && chmod -R 755 /var/www/storage \
    && chmod -R 755 /var/www/bootstrap/cache

# 環境変数の設定
ENV APP_ENV=production
ENV APP_DEBUG=false

# ポートの公開
EXPOSE $PORT

# PHP built-in serverの起動（Render用）
CMD php -S 0.0.0.0:$PORT -t public 