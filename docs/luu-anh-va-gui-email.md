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
| Máy của bạn | **Gmail** (`MAIL_MAILER=smtp`) | Chỉ cần mật khẩu ứng dụng của Gmail (mục 2) |
| Render Free | **Brevo** (`MAIL_MAILER=brevo`) | Render Free **chặn cổng SMTP** (25, 465, 587) nên Gmail không gửi được từ Render. Brevo gửi qua HTTPS, người gửi là Gmail của bạn, gửi được tới mọi email, không cần tên miền (mục 3) |

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

## 3. Brevo: gửi mail từ Render

1. Đăng ký tại https://www.brevo.com → **Sign up free** (đăng nhập bằng Google được). Gói Free gửi
   300 email/ngày. Phần thông tin công ty có thể ghi tên đồ án, website ghi địa chỉ Render.
2. **Xác minh email người gửi**: góc phải trên bấm tên tài khoản → **Senders, Domains & Dedicated IPs**
   → tab **Senders** → **Add sender**:
   - **From name**: `UTH E-Learning`
   - **From email**: Gmail của bạn (cùng Gmail dùng ở máy cũng được)

   Brevo gửi mã xác nhận tới Gmail đó; nhập mã để xác minh. Chỉ email đã xác minh mới được làm
   người gửi.
3. **Tạo API key**: góc phải trên bấm tên tài khoản → **SMTP & API** → tab **API Keys** →
   **Generate a new API key** → đặt tên `render` → copy chuỗi `xkeysib-...` (chỉ hiện một lần).
   Đây là **bí mật**. Lưu ý: dùng **API key** (`xkeysib-`), không phải SMTP key (`xsmtpsib-`).
4. **Tắt chặn IP lạ**: Render không có IP cố định, mà Brevo mặc định chặn API key khi gọi từ IP lạ.
   Mở https://app.brevo.com/security/authorised_ips (tên tài khoản → **Security** →
   **Authorized IPs**) → tắt chặn IP (**Deactivate blocking**).
5. Tài khoản Brevo mới đôi khi cần vài giờ để được duyệt gửi mail giao dịch. Nếu mail chưa tới,
   xem **Transactional** → **Logs** hoặc email từ Brevo.

Mail gửi từ địa chỉ `@gmail.com` qua Brevo đôi khi vào mục **Spam/Thư rác**: nhắc người nhận kiểm
tra Spam, hoặc bấm "Không phải thư rác" một lần.

## 4. Biến môi trường trên Render

Vào https://dashboard.render.com → service `online-elearning` → **Environment** → **Edit**:

**Thêm hoặc sửa:**

| Biến | Giá trị |
|---|---|
| `CLOUDINARY_URL` | Chuỗi `cloudinary://...` ở mục 1 (bỏ phần `CLOUDINARY_URL=` phía trước) |
| `MAIL_MAILER` | `brevo` |
| `BREVO_API_KEY` | Chuỗi `xkeysib-...` ở mục 3.3 |
| `MAIL_FROM_ADDRESS` | Gmail đã xác minh ở mục 3.2 |
| `MAIL_FROM_NAME` | `UTH E-Learning` |
| `AUTH_VERIFY_EMAIL` | `true` khi đã thử gửi mail thành công (để `false` trong lúc thử) |
| `CLOUDINARY_FOLDER` | (không bắt buộc) mặc định `online-elearning` |

**Xoá** (nếu có): `RESEND_API_KEY`, và các biến SMTP `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD` (Render không dùng tới, và không nên để mật khẩu Gmail trên Render).

Không cần sửa `FILESYSTEM_DISK`. Bấm **Save, rebuild, and deploy**.

## 5. Kiểm tra sau khi deploy

1. **Ảnh**: đăng nhập `teacher@example.com`, sửa một khoá học, chọn ảnh bìa mới → lưu. Bấm chuột
   phải vào ảnh → **Mở ảnh trong thẻ mới**: địa chỉ phải bắt đầu bằng `https://res.cloudinary.com/`.
   Deploy lại lần nữa, ảnh vẫn còn. Trang admin **Ảnh đã lưu** (`/admin/media`) cho biết ảnh đang
   lưu ở Cloudinary hay trong máy, và liệt kê mọi ảnh kèm nơi đang dùng.
2. **Quên mật khẩu**: đăng xuất → **Quên mật khẩu?** → nhập một email có thật (đã có tài khoản
   trên web) → mở hộp thư (cả mục Spam), bấm link, đặt mật khẩu mới.
3. **Xác minh email** (chỉ khi đã bật `AUTH_VERIFY_EMAIL=true`): đăng ký tài khoản mới bằng email
   thật → web chuyển sang trang yêu cầu xác minh → bấm link trong email → vào được trang học.
4. **Duyệt khoá học**: đăng nhập giảng viên (đổi email giảng viên thành email thật của bạn ở
   **Hồ sơ** để nhận được mail) → **Gửi duyệt** một khoá học → đăng nhập admin → **Duyệt** hoặc
   **Từ chối** kèm lý do → giảng viên nhận email.

## Ghi chú

- Ảnh upload **trước khi** bật Cloudinary nằm trên ổ đĩa cũ của Render và đã bị mất, chỉ cần upload
  lại. Dữ liệu demo seed sẵn không dùng ảnh upload nên không bị ảnh hưởng.
- Tài khoản tạo bằng Google, tài khoản demo và tài khoản do admin tạo được coi là đã xác minh email.
- Nếu gửi mail lỗi, web không báo lỗi 500 nữa: trang quên mật khẩu hiện "Chưa gửi được email...",
  đăng ký vẫn tạo được tài khoản, duyệt khoá học vẫn được lưu. Lỗi chi tiết ghi trong **Logs** của
  Render: kiểm tra `BREVO_API_KEY`, `MAIL_FROM_ADDRESS` (phải là email đã xác minh ở Brevo) và mục
  **Authorized IPs**.
- Video bài học hiện vẫn dùng link YouTube. Cloudinary lưu được cả video (Free giới hạn 100 MB mỗi
  file) nếu sau này thêm chức năng upload video.

## Thử Cloudinary ở máy

Thêm `CLOUDINARY_URL=...` vào `.env` rồi chạy `php artisan config:clear`. Xoá dòng đó để quay lại
lưu ảnh trong máy.
