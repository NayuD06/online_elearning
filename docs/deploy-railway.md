# Deploy nhánh `feature/demo` lên Railway

Ứng dụng được build bằng `Dockerfile` ở thư mục gốc (Railway đọc cấu hình trong `railway.json`).
Mỗi lần deploy, `docker/start.sh` sẽ tự chạy: `storage:link`, `migrate --force`,
seed dữ liệu demo (chỉ khi database còn trống) và `optimize`.

## 1. Tạo project

1. Vào https://railway.com/new → **Deploy from GitHub repo** → chọn `anh-66/online_elearning`.
2. Mở service vừa tạo → **Settings** → **Source** → đổi **Branch** sang `feature/demo`
   (không chọn `main`).

## 2. Thêm MySQL

Trong project bấm **+ Create** → **Database** → **MySQL**. Railway sẽ tạo service tên `MySQL`.

## 3. Biến môi trường cho service web

Service web → **Variables** → **Raw Editor**, dán:

```
APP_NAME="UTH E-Learning"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # chạy `php artisan key:generate --show` ở máy để lấy
APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}
APP_LOCALE=vi
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
LOG_CHANNEL=stderr
MAIL_MAILER=log
```

## 4. Tạo tên miền

Service web → **Settings** → **Networking** → **Generate Domain**. Sau đó bấm **Deploy**
(hoặc Railway tự deploy lại khi đổi biến).

Tài khoản demo sau khi seed: `admin@example.com` / `Admin@123`, `teacher@example.com` / `Teacher@123`, `student@example.com` / `Student@123`.

## 5. (Tuỳ chọn) Giữ ảnh upload qua các lần deploy

Ổ đĩa của container bị xoá mỗi lần deploy. Muốn giữ ảnh khoá học/avatar đã upload, thêm
**Volume** cho service web với mount path `/var/www/html/storage/app/public`.

## 6. Seed lại dữ liệu demo trên Railway

Seed chỉ tự chạy khi database còn trống. Nếu database trên Railway đã có dữ liệu cũ
(ví dụ mật khẩu cũ, chưa có chương/quiz), thêm biến `RESEED_DATABASE=true` cho service web
và deploy lại một lần: app sẽ chạy `migrate:fresh --seed` (xoá toàn bộ dữ liệu cũ).
Deploy xong nhớ **xoá biến này**, nếu không mỗi lần deploy sẽ xoá dữ liệu lại.
