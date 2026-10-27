# Đăng nhập bằng Google

Trang đăng nhập và đăng ký có nút **Đăng nhập bằng Google** (dùng Laravel Socialite). Để nút này
hoạt động, bạn cần tạo một *OAuth client* trên Google Cloud Console rồi đặt 3 biến môi trường
`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` ở máy và trên Render.

## Cách hoạt động

- Email Google **chưa có** trong hệ thống: tạo tài khoản mới với vai trò **Học viên**, email được
  coi là đã xác thực, ảnh đại diện lấy từ Google. Tài khoản này **chưa có mật khẩu**.
- Email Google **trùng** với một tài khoản đã có (vd. đã đăng ký bằng email + mật khẩu): đăng
  nhập vào đúng tài khoản đó và liên kết với Google. Vai trò giữ nguyên (admin vẫn là admin,
  giảng viên vẫn là giảng viên), mật khẩu cũ vẫn dùng được.
- Quy tắc mật khẩu (≥ 6 ký tự, 1 chữ in hoa, 1 ký tự đặc biệt) không áp dụng khi đăng nhập
  bằng Google. Người dùng Google muốn đăng nhập bằng mật khẩu thì vào **Hồ sơ → Cập nhật mật
  khẩu**: ô "Mật khẩu hiện tại" được ẩn, chỉ cần nhập mật khẩu mới (vẫn phải đúng quy tắc trên).
- Tài khoản chưa có mật khẩu xoá tài khoản ở trang Hồ sơ mà không cần nhập mật khẩu.

Địa chỉ dùng trong code:

| Việc | Đường dẫn |
|---|---|
| Chuyển sang Google | `/auth/google/redirect` |
| Google gửi kết quả về (redirect URI) | `/auth/google/callback` |

## 1. Cập nhật code ở máy

Sau khi lấy code mới về (`git pull` hoặc đã có sẵn các thay đổi):

```
composer install
php artisan migrate
```

`composer install` cài thêm gói `laravel/socialite`. Migration thêm cột `google_id` vào bảng
`users` và cho phép cột `password` để trống (cho tài khoản Google).

## 2. Tạo OAuth client trên Google Cloud Console

1. Mở https://console.cloud.google.com và đăng nhập bằng tài khoản Google của bạn.
2. Tạo project: bấm ô chọn project ở thanh trên cùng → **New Project** → đặt tên (vd.
   `UTH E-Learning`) → **Create**. Chờ tạo xong rồi chọn đúng project đó.
3. Vào menu ☰ → **APIs & Services** → **OAuth consent screen** (giao diện mới tên là
   **Google Auth Platform**) → **Get started**, điền:
   - **App name**: `UTH E-Learning`
   - **User support email**: chọn email của bạn
   - **Audience**: chọn **External**
   - **Contact information**: email của bạn
   - Tick đồng ý điều khoản → **Create**.
4. Mở mục **Audience** (Đối tượng):
   - Lúc mới tạo, app ở trạng thái **Testing**: chỉ những email có trong danh sách
     **Test users** mới đăng nhập được. Bấm **Add users** và thêm email Google của bạn và của
     những người sẽ thử.
   - Muốn ai cũng đăng nhập được (vd. khi demo cho cả lớp), bấm **Publish app** → **Confirm**
     để chuyển sang **In production**. App chỉ xin quyền email và tên cơ bản nên Google không
     bắt xác minh.
5. Mở mục **Clients** (hoặc **APIs & Services → Credentials → Create credentials → OAuth client
   ID**) → **Create client**:
   - **Application type**: `Web application`
   - **Name**: `UTH E-Learning Web`
   - **Authorized JavaScript origins** → **Add URI** từng dòng:
     ```
     http://127.0.0.1:8000
     http://localhost:8000
     https://online-elearning.onrender.com
     ```
   - **Authorized redirect URIs** → **Add URI** từng dòng:
     ```
     http://127.0.0.1:8000/auth/google/callback
     http://localhost:8000/auth/google/callback
     https://online-elearning.onrender.com/auth/google/callback
     ```
   - Bấm **Create**.
6. Hộp thoại hiện **Client ID** (dạng `xxxx.apps.googleusercontent.com`) và **Client secret**
   (dạng `GOCSPX-...`). **Copy cả hai ngay** (hoặc bấm **Download JSON**): Google có thể không
   cho xem lại client secret sau khi đóng hộp thoại. Nếu lỡ mất, mở client đó → **Add secret**
   để tạo secret mới.

> Thay đổi redirect URI trên Google có thể mất vài phút mới có hiệu lực.

Nếu tên miền Render của bạn khác `online-elearning.onrender.com`, thay đúng tên miền thật ở cả
hai mục trên.

## 3. Đặt biến môi trường ở máy (local)

Mở file `.env` ở thư mục dự án, thêm vào cuối (thay bằng giá trị thật ở bước 2.6):

```
GOOGLE_CLIENT_ID=xxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxx
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

Rồi xoá cache cấu hình và chạy web:

```
php artisan config:clear
php artisan serve
```

Mở **đúng** địa chỉ http://127.0.0.1:8000/login (cùng host với `GOOGLE_REDIRECT_URI`) và bấm
**Đăng nhập bằng Google**.

> Quan trọng: nếu mở web bằng `http://localhost:8000` mà `GOOGLE_REDIRECT_URI` lại là
> `127.0.0.1` (hoặc ngược lại), trình duyệt coi đó là hai trang khác nhau nên mất phiên đăng
> nhập và báo "Không thể đăng nhập bằng Google". Mẹo: để trống `GOOGLE_REDIRECT_URI=` thì app
> tự dùng đúng địa chỉ bạn đang mở.

## 4. Đặt biến môi trường trên Render

1. Vào https://dashboard.render.com → chọn service `online-elearning` → tab **Environment**.
2. **Add Environment Variable**, thêm 3 biến:

   | Key | Value |
   |---|---|
   | `GOOGLE_CLIENT_ID` | Client ID ở bước 2.6 |
   | `GOOGLE_CLIENT_SECRET` | Client secret ở bước 2.6 |
   | `GOOGLE_REDIRECT_URI` | `https://online-elearning.onrender.com/auth/google/callback` |

3. Kiểm tra `APP_URL` là `https://online-elearning.onrender.com` (có `https`).
4. Bấm **Save, rebuild, and deploy** (hoặc **Save changes** rồi **Manual Deploy → Deploy latest
   commit**).

Khi bạn push code lên nhánh `feature/demo`, Render tự build lại; `docker/start.sh` tự chạy
`migrate --force` nên cột `google_id` được thêm vào database Aiven, không cần làm gì thêm. Dữ
liệu cũ vẫn giữ nguyên.

> Nếu tạo service bằng Blueprint (`render.yaml`), file này đã khai báo sẵn 3 biến; bạn chỉ cần
> điền giá trị cho `GOOGLE_CLIENT_ID` và `GOOGLE_CLIENT_SECRET`.

## 5. Kiểm tra

1. Mở trang `/login` → bấm **Đăng nhập bằng Google** → chọn tài khoản Google.
2. Lần đầu Google hỏi cho phép chia sẻ tên, email, ảnh → **Continue**.
3. Được đưa về **Góc học tập** (`/dashboard`) với tên và ảnh đại diện từ Google. Trong
   **Quản trị → Người dùng**, tài khoản mới có vai trò **Học viên**.
4. Thử với email trùng tài khoản có sẵn: đăng nhập xong vào đúng tài khoản cũ, vai trò không đổi.

## Lỗi thường gặp

| Lỗi | Cách sửa |
|---|---|
| Google báo **Error 400: redirect_uri_mismatch** | Redirect URI trong `GOOGLE_REDIRECT_URI` phải khớp từng ký tự với một dòng trong **Authorized redirect URIs** (đúng `http`/`https`, `127.0.0.1`/`localhost`, cổng `8000`, không có `/` thừa ở cuối). Sửa xong chờ vài phút. |
| Google báo **Error 403: access_denied** / "app is being tested" | App đang ở **Testing**: thêm email vào **Test users**, hoặc **Publish app** (bước 2.4). |
| Web báo **"Đăng nhập bằng Google chưa được cấu hình"** | Thiếu `GOOGLE_CLIENT_ID` hoặc `GOOGLE_CLIENT_SECRET`. Ở máy: kiểm tra `.env` rồi `php artisan config:clear`. Trên Render: kiểm tra tab Environment rồi deploy lại. |
| Web báo **"Không thể đăng nhập bằng Google, vui lòng thử lại"** | Thường do mở web bằng host khác với redirect URI (xem lưu ý ở bước 3), sai client secret, hoặc để trang Google quá lâu. Xem chi tiết trong `storage/logs/laravel.log` (máy) hoặc tab **Logs** (Render), dòng `Google sign-in failed`. |
| **Error 401: invalid_client** | Sai `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` (thừa dấu cách, copy thiếu). |
| Lỗi SQL `Unknown column 'google_id'` | Chưa chạy `php artisan migrate`. |

## File liên quan trong code

- `app/Http/Controllers/Auth/GoogleController.php`: chuyển sang Google, xử lý kết quả, tạo hoặc
  liên kết tài khoản.
- `routes/auth.php`: hai route `auth.google.redirect` và `auth.google.callback`.
- `config/services.php`: mục `google` đọc 3 biến môi trường.
- `database/migrations/2026_10_26_000004_add_google_id_to_users_table.php`.
- `resources/views/components/google-sign-in-button.blade.php`: nút Google ở trang đăng nhập,
  đăng ký.
- `tests/Feature/Auth/GoogleLoginTest.php`: kiểm thử (chạy `php artisan test --filter=Google`).
