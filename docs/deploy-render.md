# Deploy nhánh `feature/demo` lên Render (miễn phí)

Render chạy app bằng `Dockerfile` có sẵn trong repo. Mỗi lần deploy, `docker/start.sh` tự chạy
`storage:link`, `migrate --force`, seed dữ liệu demo (chỉ khi database còn trống) và `optimize`.
Render không có MySQL miễn phí, nên dùng MySQL miễn phí của **Aiven**.

> Chỉ deploy nhánh `feature/demo`, không chọn `main`.

## 0. Chuẩn bị

Commit và push code lên GitHub:

```
git add -A
git commit -m "chore: add Render deployment config"
git push origin feature/demo
```

Tạo `APP_KEY` ở máy (copy cả chuỗi `base64:...`):

```
php artisan key:generate --show
```

## 1. Tạo MySQL miễn phí trên Aiven

1. Đăng ký tại https://console.aiven.io (đăng nhập bằng Google/GitHub được).
2. **Create service** → chọn **MySQL** → gói **Free** → chọn khu vực gần (vd. Singapore) → **Create**.
3. Chờ trạng thái **Running** (vài phút). Ở tab **Overview** → **Connection information**, ghi lại:
   - **Host** (vd. `mysql-xxxx.aivencloud.com`)
   - **Port** (vd. `12345`, không phải 3306)
   - **User**: `avnadmin`
   - **Password**: bấm hiện mật khẩu
   - **Database name**: `defaultdb`
4. Bấm **CA certificate** → **Show** và copy toàn bộ nội dung (từ `-----BEGIN CERTIFICATE-----`
   đến `-----END CERTIFICATE-----`). Aiven bắt buộc kết nối SSL nên cần chứng chỉ này.

## 2. Tạo web service trên Render

1. Đăng ký tại https://render.com bằng tài khoản GitHub, cho phép Render truy cập repo
   `anh-66/online_elearning`.
2. **New** → **Web Service** → chọn repo `online_elearning`.
3. Điền:
   - **Branch**: `feature/demo`
   - **Language / Runtime**: `Docker` (Render tự nhận ra `Dockerfile`)
   - **Instance Type**: `Free`
4. Mở **Advanced** → **Health Check Path**: `/up`.

> Cách khác: **New** → **Blueprint** → chọn repo, Render đọc file `render.yaml` và tự điền các
> mục trên; bạn chỉ cần nhập các biến có giá trị bí mật ở bước 3.

## 3. Biến môi trường

Ở mục **Environment Variables** → **Add from .env**, dán (thay giá trị trong `<...>`):

```
APP_NAME="UTH E-Learning"
APP_ENV=production
APP_DEBUG=false
APP_KEY=<chuỗi base64:... ở bước 0>
APP_URL=https://<tên-service>.onrender.com
APP_LOCALE=vi
APP_FALLBACK_LOCALE=en

DB_CONNECTION=mysql
DB_HOST=<Host của Aiven>
DB_PORT=<Port của Aiven>
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=<Password của Aiven>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=public
LOG_CHANNEL=stderr
MAIL_MAILER=log
```

Để ảnh upload không bị mất và gửi được email thật, thêm `CLOUDINARY_URL`, đổi `MAIL_MAILER=resend`
và thêm `RESEND_API_KEY`, `MAIL_FROM_ADDRESS`, `AUTH_VERIFY_EMAIL`: xem
[luu-anh-va-gui-email.md](luu-anh-va-gui-email.md).

Sau đó **Add Environment Variable** thêm riêng biến `DB_SSL_CA`, giá trị là toàn bộ nội dung
CA certificate đã copy ở bước 1.4 (dán nguyên nhiều dòng).

Không cần đặt `PORT`, Render tự cấp và `start.sh` tự dùng.

Muốn bật **Đăng nhập bằng Google**, thêm `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`,
`GOOGLE_REDIRECT_URI`: xem [dang-nhap-google.md](dang-nhap-google.md).

## 4. Deploy

Bấm **Create Web Service** (hoặc **Manual Deploy** → **Deploy latest commit**). Xem tab **Logs**:
build mất khoảng 5–10 phút lần đầu. Khi thấy các dòng migrate `DONE`, `Seeding database` và
trạng thái **Live** là xong.

Nếu tên miền thật khác với `APP_URL` đã điền, sửa lại `APP_URL` cho đúng rồi deploy lại.

Tài khoản demo:

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@example.com` | `Admin@123` |
| Giảng viên | `teacher@example.com` | `Teacher@123` |
| Học viên | `student@example.com` | `Student@123` |

## 5. Seed lại dữ liệu demo

Seed chỉ tự chạy khi database trống. Muốn xoá sạch và seed lại: thêm biến
`RESEED_DATABASE=true`, deploy một lần, rồi **xoá biến này** (nếu không mỗi lần deploy sẽ xoá dữ liệu).

## Lưu ý với gói Free

- Service tự ngủ sau ~15 phút không có ai truy cập; lần mở đầu tiên sau đó mất khoảng 1 phút.
  Nên mở web trước khi thuyết trình/demo vài phút.
- Ổ đĩa không được giữ: nếu chưa đặt `CLOUDINARY_URL`, ảnh upload (ảnh khoá học, avatar) sẽ mất sau
  mỗi lần deploy hoặc khởi động lại. Đặt `CLOUDINARY_URL` để lưu ảnh trên Cloudinary. Dữ liệu trong
  MySQL (Aiven) thì vẫn giữ nguyên.
- Cổng SMTP bị chặn: không gửi mail qua Gmail SMTP được, dùng Resend (`MAIL_MAILER=resend`).
- Mỗi lần `git push origin feature/demo`, Render tự build và deploy lại.

## Sửa lỗi thường gặp

- **`Connections using insecure transport are prohibited`**: chưa đặt `DB_SSL_CA` hoặc dán thiếu.
- **`Access denied`**: sai `DB_USERNAME`/`DB_PASSWORD`, hoặc `DB_PORT` đang để 3306.
- **Trang lỗi 500, CSS không load**: kiểm tra `APP_KEY` và `APP_URL` (phải là `https://...`).
