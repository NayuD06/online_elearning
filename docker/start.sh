#!/bin/sh
set -e

cd /var/www/html

# Railway cấp cổng qua biến PORT
PORT="${PORT:-8080}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Volume (nếu có) được gắn lúc chạy nên cần tạo thư mục và cấp quyền ở đây
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

# MySQL bên ngoài bắt buộc SSL (vd. Aiven): dán nội dung file CA vào biến DB_SSL_CA
if [ -n "${DB_SSL_CA:-}" ]; then
    printf '%s\n' "$DB_SSL_CA" > /var/www/html/storage/db-ca.pem
    export MYSQL_ATTR_SSL_CA=/var/www/html/storage/db-ca.pem
fi

php artisan storage:link --force || true
# Đặt RESEED_DATABASE=true để xoá sạch và seed lại dữ liệu demo (nhớ xoá biến sau khi deploy xong)
if [ "${RESEED_DATABASE:-false}" = "true" ]; then
    php artisan migrate:fresh --seed --force
else
    php artisan migrate --force
    php artisan app:seed-if-empty
fi
php artisan optimize

exec apache2-foreground
