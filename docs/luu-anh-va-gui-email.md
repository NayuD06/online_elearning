# Lưu ảnh lên Cloudinary và gửi email thật

Hai việc này giúp web chạy như một trang thật:

- **Ảnh không bị mất**: Render Free xoá ổ đĩa mỗi lần deploy hoặc khởi động lại, nên ảnh upload
  (avatar, ảnh khoá học, ảnh trong bài viết, ảnh thông báo) cần lưu ở **Cloudinary**.
- **Email thật**: quên mật khẩu, xác minh email khi đăng ký, báo cho giảng viên khi khoá học được
  duyệt hoặc bị từ chối.

> **Không điền gì cũng chạy được.** Khi chưa có key: ảnh lưu vào `storage/app/public`, email được
> ghi vào file `storage/logs/laravel.log` (mở file, tìm đường link trong email để bấm thử).

## Gửi email bằng gì?

| Chạy ở đâu | Dùng | Ghi chú |
|---|---|---|
| Máy của bạn | **Gmail** (`MAIL_MAILER=smtp`) | Gửi được tới mọi email, chỉ cần mật khẩu ứng dụng của Gmail (mục 2) |
| Render Free | **Resend** (`MAIL_MAILER=resend`) | Render Free **chặn cổng SMTP** (25, 465, 587) nên Gmail không gửi được từ Render. Resend gửi qua HTTPS (mục 3) |

**Giới hạn của Resend khi chưa có tên miền riêng:** chỉ gửi được từ `onboarding@resend.dev` và
**chỉ tới đúng email bạn dùng đăng ký Resend**. Đủ để tự demo (quên mật khẩu, duyệt khoá học với
tài khoản mang email của bạn), nhưng người khác sẽ không nhận được mail. Muốn gửi cho mọi người thì
cần một tên miền riêng (mục 3.4), hoặc nâng Render lên gói trả phí (gói trả phí mở cổng SMTP, dùng
Gmail như ở máy).

---

## 1. Cloudinary: lấy `CLOUDINARY_URL`

1. Đăng ký tại https://cloudinary.com/users/register_free (đăng nhập bằng Google được). Gói Free
   có 25 credit/tháng, đủ cho vài GB ảnh của đồ án.
2. Vào **Settings** (biểu tượng bánh răng ở góc trái dưới) → **API Keys**.
3. Bấm nút copy ở dòng **API environment variable** (không phải ô **API Secret**). Bạn được một
   chuỗi dạng:

   ```
   CLOUDINARY_URL=cloudinary://123456789012345:AbCdEf...@ten-cloud-cua-ban
   ```

   Đây là **bí mật** (chứa API secret), không dán vào chat hay commit lên GitHub.

Chỉ cần một biến này, và giá trị phải bắt đầu bằng `cloudinary://`. Nếu dán nhầm (vd. chỉ dán API
Secret), web bỏ qua và vẫn lưu ảnh trong máy. Web tự lưu ảnh vào thư mục `online-elearning/` trên Cloudinary (đổi tên thư
mục bằng biến `CLOUDINARY_FOLDER` nếu muốn), mở **Media Library** trên Cloudinary để xem.

Ảnh được Cloudinary tự nén và đổi sang định dạng nhẹ nhất trình duyệt hỗ trợ (WebP/AVIF), nên trang
tải nhanh hơn.

## 2. Gmail: gửi mail khi chạy ở máy

1. Bật **Xác minh 2 bước** cho Gmail: https://myaccount.google.com/signinoptions/two-step-verification
2. Tạo **Mật khẩu ứng dụng**: mở https://myaccount.google.com/apppasswords → đặt tên `elearning`
   → **Tạo** → copy 16 ký tự Google hiện ra (bỏ dấu cách). Đây là **bí mật**, không phải mật khẩu
   Gmail thường của bạn.
3. Sửa `.env` ở máy:

   ```
   MAIL_MAILER=smtp
   MAIL_SCHEME=null
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USERNAME=tenban@gmail.com
   MAIL_PASSWORD=<16 ký tự mật khẩu ứng dụng>
   MAIL_FROM_ADDRESS=tenban@gmail.com
   MAIL_FROM_NAME="UTH E-Learning"
   ```

   `MAIL_USERNAME` và `MAIL_FROM_ADDRESS` phải là cùng một Gmail.
4. Chạy `php artisan config:clear`, rồi thử **Quên mật khẩu?** với một tài khoản có email thật.

Nếu báo lỗi `Username and Password not accepted`: kiểm tra lại mật khẩu ứng dụng (16 ký tự, không
có dấu cách) và chắc chắn đã bật xác minh 2 bước. Gmail cho gửi khoảng 500 email/ngày.

## 3. Resend: gửi mail từ Render

1. Đăng ký tại https://resend.com/signup (đăng nhập bằng Google hoặc GitHub được). Gói Free gửi
   3.000 email/tháng, tối đa 100 email/ngày.
2. **Tạo API key**: menu bên trái **API Keys** → **Create API Key** → tên `render`, quyền
   **Sending access** → **Add** → copy chuỗi `re_...` (chỉ hiện một lần). Đây là **bí mật**.
3. Chưa có tên miền thì dùng người gửi `onboarding@resend.dev`. Mail chỉ tới được email bạn dùng
   đăng ký Resend, nên khi demo hãy đổi email của tài khoản giảng viên/học viên demo thành email đó
   (ở trang **Hồ sơ**).
4. **(Không bắt buộc) Gửi cho mọi người**: cần một tên miền của riêng bạn (mua ở nhà cung cấp tên
   miền bất kỳ). Trong Resend → **Domains** → **Add Domain** → nhập tên miền → Resend đưa ra vài
   bản ghi DNS (MX, TXT) → thêm các bản ghi đó ở trang quản lý DNS của tên miền → quay lại bấm
   **Verify**. Khi trạng thái là **Verified**, đổi `MAIL_FROM_ADDRESS` thành
   `no-reply@<tên-miền-của-bạn>`.

Lịch sử gửi xem ở Resend → **Emails** (ghi rõ đã gửi, bị từ chối và lý do).

## 4. Biến môi trường trên Render

Vào https://dashboard.render.com → service `online-elearning` → **Environment** → **Edit** →
thêm hoặc sửa:

| Biến | Giá trị |
|---|---|
| `CLOUDINARY_URL` | Chuỗi `cloudinary://...` ở mục 1 (bỏ phần `CLOUDINARY_URL=` phía trước) |
| `MAIL_MAILER` | `resend` |
| `RESEND_API_KEY` | Chuỗi `re_...` ở mục 3.2 |
| `MAIL_FROM_ADDRESS` | `onboarding@resend.dev` (hoặc `no-reply@<tên-miền>` nếu đã xác minh tên miền) |
| `MAIL_FROM_NAME` | `UTH E-Learning` |
| `AUTH_VERIFY_EMAIL` | `false` khi chưa có tên miền; `true` khi đã xác minh tên miền |
| `CLOUDINARY_FOLDER` | (không bắt buộc) mặc định `online-elearning` |

Không cần sửa `FILESYSTEM_DISK`. Bấm **Save, rebuild, and deploy**.

Chỉ bật `AUTH_VERIFY_EMAIL=true` khi mail gửi được tới **mọi** email (đã xác minh tên miền ở
Resend). Nếu bật sớm, người khác đăng ký sẽ không nhận được link xác minh và bị kẹt ở trang
"Vui lòng xác minh email".

## 5. Kiểm tra sau khi deploy

1. **Ảnh**: đăng nhập `teacher@example.com`, sửa một khoá học, chọn ảnh bìa mới → lưu. Bấm chuột
   phải vào ảnh → **Mở ảnh trong thẻ mới**: địa chỉ phải bắt đầu bằng `https://res.cloudinary.com/`.
   Deploy lại lần nữa, ảnh vẫn còn. Trang admin **Ảnh đã lưu** (`/admin/media`) cho biết ảnh đang
   lưu ở Cloudinary hay trong máy, và liệt kê mọi ảnh kèm nơi đang dùng.
2. **Quên mật khẩu**: đăng xuất → **Quên mật khẩu?** → nhập email của bạn (email đăng ký Resend,
   và đã là email của một tài khoản trên web) → mở hộp thư, bấm link, đặt mật khẩu mới.
3. **Xác minh email** (chỉ khi đã bật `AUTH_VERIFY_EMAIL=true`): đăng ký tài khoản mới bằng email
   thật → web chuyển sang trang yêu cầu xác minh → bấm link trong email → vào được trang học.
4. **Duyệt khoá học**: đăng nhập giảng viên (đổi email giảng viên thành email thật của bạn ở
   **Hồ sơ** để nhận được mail) → **Gửi duyệt** một khoá học → đăng nhập admin → **Duyệt** hoặc
   **Từ chối** kèm lý do → giảng viên nhận email.

## Ghi chú

- Ảnh upload **trước khi** bật Cloudinary nằm trên ổ đĩa cũ của Render và đã bị mất, chỉ cần upload
  lại. Dữ liệu demo seed sẵn không dùng ảnh upload nên không bị ảnh hưởng.
- Tài khoản tạo bằng Google, tài khoản demo và tài khoản do admin tạo được coi là đã xác minh email.
- Nếu gửi mail lỗi khi admin duyệt/từ chối khoá học, việc duyệt vẫn được lưu, lỗi ghi vào **Logs**
  của Render. Riêng trang quên mật khẩu và đăng ký sẽ báo lỗi 500 nếu không gửi được mail (vd.
  Resend chưa có tên miền mà gửi tới email khác): kiểm tra `RESEND_API_KEY`, `MAIL_FROM_ADDRESS`
  và mục **Emails** trên Resend.
- Video bài học hiện vẫn dùng link YouTube. Cloudinary lưu được cả video (Free giới hạn 100 MB mỗi
  file) nếu sau này thêm chức năng upload video.

## Thử Cloudinary ở máy

Thêm `CLOUDINARY_URL=...` vào `.env` rồi chạy `php artisan config:clear`. Xoá dòng đó để quay lại
lưu ảnh trong máy.
