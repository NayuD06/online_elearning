# Đổi lại mật khẩu và khoá bí mật đã bị lộ

Các giá trị dưới đây đã từng được dán vào khung chat, nên coi như **đã bị lộ** và cần đổi mới:

| Bí mật | Ai dùng được nếu lộ | Cách xử lý |
|---|---|---|
| Mật khẩu MySQL trên Aiven (`DB_PASSWORD`) | Đọc, sửa, xoá toàn bộ dữ liệu web | Đặt lại mật khẩu `avnadmin` (mục 1) |
| Google Client Secret (`GOOGLE_CLIENT_SECRET`) | Giả danh trang web khi đăng nhập Google | Tạo secret mới, xoá secret cũ (mục 2) |
| `APP_KEY` | Giả mạo cookie đăng nhập, link đặt lại mật khẩu, link xác minh email | Tạo khoá mới (mục 3) |

Những thứ **không** phải bí mật, không cần đổi: `GOOGLE_CLIENT_ID`, host/port Aiven, CA certificate
của Aiven, `APP_URL`.

> Nên làm cả 3 mục trong một lần, rồi deploy lại Render **một lần** ở cuối (mục 4).

Code trong repo không chứa giá trị thật nào: file `.env` đã nằm trong `.gitignore`, và lịch sử git
của nhánh `feature/demo` không có mật khẩu Aiven, Google secret hay `APP_KEY`. Vì vậy chỉ cần đổi ở
các trang quản trị và trong biến môi trường, không phải sửa code.

## 1. Đặt lại mật khẩu MySQL trên Aiven

1. Vào https://console.aiven.io → chọn service MySQL của web.
2. Mở tab **Users** (có nơi ghi **Users and roles**).
3. Ở dòng `avnadmin`, bấm nút **⋮** (hoặc **Actions**) → **Reset password** → xác nhận.
4. Bấm biểu tượng con mắt để hiện mật khẩu mới và copy lại.

Mật khẩu cũ hết hiệu lực ngay lập tức, nên web trên Render sẽ báo lỗi kết nối database cho tới khi
bạn cập nhật `DB_PASSWORD` ở mục 4. Dữ liệu trong database **không** bị ảnh hưởng.

Nếu máy bạn cũng kết nối thẳng tới Aiven (trong `.env` ở máy có `DB_HOST=...aivencloud.com`), sửa
luôn `DB_PASSWORD` trong file `.env` ở máy. Nếu máy dùng MySQL của XAMPP/Laragon thì bỏ qua.

## 2. Tạo Google Client Secret mới

1. Vào https://console.cloud.google.com → chọn đúng project → **APIs & Services** → **Credentials**
   (bản mới gọi là **Google Auth Platform** → **Clients**).
2. Bấm vào OAuth client đang dùng cho web (loại **Web application**).
3. Ở khung **Client secrets** bên phải, bấm **Add secret**. Google tạo secret mới dạng `GOCSPX-...`;
   copy lại ngay (sau này Google chỉ hiện vài ký tự cuối).
4. Cập nhật `GOOGLE_CLIENT_SECRET` ở Render (mục 4) và trong `.env` ở máy.
5. Sau khi deploy xong và thử đăng nhập Google thành công, quay lại trang này, ở dòng secret **cũ**
   bấm **Disable** rồi **Delete**.

Nếu không thấy nút **Add secret** (giao diện cũ), dùng **Reset secret** ở đầu trang: secret cũ mất
hiệu lực ngay, nên cập nhật Render và `.env` ngay sau đó.

`GOOGLE_CLIENT_ID` giữ nguyên, không cần sửa Authorized redirect URIs.

## 3. Tạo `APP_KEY` mới

Ở thư mục dự án trên máy, chạy:

```
php artisan key:generate --show
```

Lệnh này chỉ **in ra** một khoá mới dạng `base64:...`, không sửa file `.env`. Copy chuỗi đó để dán
vào Render ở mục 4.

Dùng **hai khoá khác nhau** cho máy và cho Render. Khoá trong `.env` ở máy chưa từng dán lên chat thì
không cần đổi; nếu muốn đổi luôn thì chạy `php artisan key:generate` (không có `--show`), lệnh này
ghi khoá mới thẳng vào `.env`.

### Đổi `APP_KEY` sẽ làm gì với web?

Laravel dùng `APP_KEY` để mã hoá và ký. Với web này, đổi khoá sẽ:

- **Đăng xuất mọi người** (cookie phiên và cookie "Ghi nhớ đăng nhập" cũ không còn đọc được).
  Mọi người chỉ cần đăng nhập lại, tài khoản và mật khẩu vẫn như cũ.
- **Làm hỏng các link đã gửi qua email nhưng chưa bấm**: link đặt lại mật khẩu và link xác minh
  email. Người dùng chỉ cần yêu cầu gửi lại.
- **Không** ảnh hưởng mật khẩu người dùng (mật khẩu được băm bằng bcrypt, không dùng `APP_KEY`),
  không ảnh hưởng khoá học, bài học, điểm kiểm tra hay ảnh đã upload.

Web này không lưu cột nào được mã hoá bằng `APP_KEY` trong database, nên không mất dữ liệu.

> Laravel có biến `APP_PREVIOUS_KEYS` để giữ khoá cũ cho phiên đăng nhập cũ còn chạy. **Không** dùng
> nó ở đây: khoá cũ đã lộ, giữ lại thì người có khoá cũ vẫn giả mạo được cookie.

## 4. Cập nhật biến môi trường trên Render

1. Vào https://dashboard.render.com → chọn web service `online-elearning`.
2. Mở **Environment**.
3. Bấm **Edit**, sửa giá trị 3 biến:
   - `DB_PASSWORD`: mật khẩu Aiven mới (mục 1)
   - `GOOGLE_CLIENT_SECRET`: secret Google mới (mục 2)
   - `APP_KEY`: chuỗi `base64:...` mới (mục 3)
4. Bấm **Save, rebuild, and deploy** (hoặc **Save changes** rồi **Manual Deploy** →
   **Deploy latest commit**).
5. Chờ trạng thái **Live**, rồi kiểm tra:
   - Mở trang chủ, danh sách khoá học hiện bình thường (database kết nối được).
   - Đăng nhập bằng `admin@example.com` được.
   - Bấm **Đăng nhập bằng Google** được.
6. Xoá secret Google cũ (mục 2, bước 5).

## 5. Từ nay về sau

- Không dán mật khẩu, secret, `APP_KEY`, API key vào chat, ảnh chụp màn hình, tài liệu nộp bài hay
  commit lên GitHub. Khi cần hỏi, thay giá trị bằng `***`.
- Chỉ lưu bí mật ở 2 nơi: file `.env` trên máy (không commit) và mục **Environment** trên Render.
- Các key mới thêm cho Cloudinary (`CLOUDINARY_URL`), Resend (`RESEND_API_KEY`) và mật khẩu ứng dụng
  Gmail (`MAIL_PASSWORD`) cũng là bí mật,
  làm theo cùng quy tắc. Nếu lỡ lộ: Cloudinary → **Settings** → **API Keys** → tạo key mới rồi xoá
  key cũ; Resend → **API Keys** → xoá key cũ, tạo key mới; Gmail → https://myaccount.google.com/apppasswords
  → xoá mật khẩu ứng dụng cũ, tạo cái mới.
