# Image dùng để deploy lên Render/Railway (hoặc bất kỳ nền tảng chạy Docker nào).
# Ảnh gốc lấy qua mirror.gcr.io (bản sao Docker Hub của Google): Docker Hub hay trả lỗi
# 429 Too Many Requests cho máy build dùng chung IP như Render, làm build thất bại.

# 1) Cài thư viện PHP (không có dev dependencies)
FROM mirror.gcr.io/library/composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2) Build asset bằng Vite
FROM mirror.gcr.io/library/node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --ignore-scripts
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3) Image chạy ứng dụng: PHP 8.4 + Apache
FROM mirror.gcr.io/library/php:8.4-apache

COPY --from=mirror.gcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql gd zip intl bcmath opcache exif \
    && a2enmod rewrite headers \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "upload_max_filesize=110M\npost_max_size=130M\nmemory_limit=256M\n" > "$PHP_INI_DIR/conf.d/app.ini"

COPY --from=mirror.gcr.io/library/composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

CMD ["docker/start.sh"]
