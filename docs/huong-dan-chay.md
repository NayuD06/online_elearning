# Hướng dẫn chạy code

Hướng dẫn chạy UTH E-Learning trên máy cá nhân (ví dụ Windows dùng XAMPP, Laragon hoặc PHP cài
riêng). Muốn đưa lên mạng thì xem [deploy-railway.md](deploy-railway.md).

## 1. Cần cài sẵn

| Phần mềm | Phiên bản | Kiểm tra |
| --- | --- | --- |
| PHP | 8.3 trở lên (khuyên dùng 8.4) | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 22 trở lên (kèm npm) | `node -v` |
| MySQL | 8.x (hoặc MariaDB đi kèm XAMPP) | |

### Bật extension PHP

Mở file `php.ini` (xem đường dẫn bằng `php --ini`) và bỏ dấu `;` ở đầu các dòng sau:

```ini
extension=gd
extension=zip
extension=pdo_mysql
extension=mbstring
extension=fileinfo
extension=intl
```

`gd` bắt buộc phải bật, nếu không `composer install` sẽ báo thiếu `ext-gd`.

## 2. Tạo database

Trong MySQL (phpMyAdmin hoặc dòng lệnh):

```sql
CREATE DATABASE online_elearning CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 3. Cài đặt lần đầu

Mở terminal ở thư mục dự án và chạy lần lượt:

```bash
# 1. Cài thư viện PHP
composer install

# 2. Cài thư viện JavaScript
npm install

# 3. Tạo file cấu hình
cp .env.example .env        # Windows CMD: copy .env.example .env

# 4. Tạo khoá ứng dụng
php artisan key:generate
```

Mở file `.env` và điền thông tin MySQL của bạn:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=online_elearning
DB_USERNAME=root
DB_PASSWORD=          # mật khẩu MySQL trên máy bạn
```

Sau đó:

```bash
# 5. Tạo bảng và dữ liệu mẫu
php artisan migrate --seed

# 6. Liên kết thư mục ảnh upload ra public
php artisan storage:link

# 7. Build CSS/JS
npm run build
```

## 4. Chạy trang web

```bash
php artisan serve
```

Mở trình duyệt vào http://localhost:8000.

Khi đang sửa giao diện, chạy thêm `npm run dev` ở một terminal khác để CSS/JS tự cập nhật
mà không cần `npm run build` lại.

## 5. Tài khoản mẫu

Mật khẩu phải có ít nhất 6 ký tự, 1 chữ in hoa và 1 ký tự đặc biệt.

| Email | Mật khẩu | Vai trò |
| --- | --- | --- |
| `admin@example.com` | `Admin@123` | Quản trị viên, vào `/admin` để duyệt khoá học, quản lý người dùng, danh mục |
| `teacher@example.com` | `Teacher@123` | Giảng viên, vào `/instructor` để quản lý khoá học, chương, bài học, quiz của mình |
| `student@example.com` | `Student@123` | Học viên |

## 6. Lệnh hay dùng

| Việc cần làm | Lệnh |
| --- | --- |
| Xoá sạch database và seed lại | `php artisan migrate:fresh --seed` |
| Chạy kiểm thử | `php artisan test` |
| Xoá cache cấu hình sau khi sửa `.env` | `php artisan config:clear` |
| Định dạng code PHP | `vendor/bin/pint` |

## 7. Lỗi thường gặp

| Lỗi | Cách sửa |
| --- | --- |
| `ext-gd is missing` khi `composer install` | Bật `extension=gd` trong `php.ini` rồi mở lại terminal |
| `SQLSTATE[HY000] [1049] Unknown database` | Chưa tạo database `online_elearning` |
| `SQLSTATE[HY000] [1045] Access denied` | Sai `DB_USERNAME` hoặc `DB_PASSWORD` trong `.env` |
| `No application encryption key has been specified` | Chạy `php artisan key:generate` |
| `Vite manifest not found` | Chạy `npm run build` (hoặc để `npm run dev` đang chạy) |
| Ảnh khoá học hoặc ảnh đại diện không hiện | Chạy `php artisan storage:link` |
| Sửa `.env` mà không có tác dụng | Chạy `php artisan config:clear` |
