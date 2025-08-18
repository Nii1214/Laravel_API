FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev libpq-dev \
    && docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# 先に .env をコピー
COPY .env.example .env

# composer インストール
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction

# APP_KEY 生成
RUN php artisan key:generate

# ソースコードコピー
COPY . .

RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 755 /var/www/storage /var/www/bootstrap/cache

ENV APP_ENV=production
ENV APP_DEBUG=false

EXPOSE 8000

CMD php -S 0.0.0.0:8000 -t public
