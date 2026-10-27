# Thanh toán khoá học (VNPay, MoMo), chứng chỉ, chuông thông báo, SEO

## 1. Thanh toán khoá học trả phí

Khoá học có giá > 0 không ghi danh thẳng được nữa: học viên bấm **Mua khoá học** → chọn cổng →
sang trang VNPay / MoMo → trả tiền → quay về web, hệ thống tự ghi danh. Giảng viên của khoá và admin
vẫn vào học không cần trả tiền.

Cổng nào chưa điền khoá trong `.env` thì **không hiện** ở trang thanh toán. Mọi khoá dưới đây là
môi trường **thử (sandbox)**, không trừ tiền thật.

### 1.1 VNPay sandbox

1. Đăng ký tại <https://sandbox.vnpayment.vn/devreg/> (điền email, tên web, URL web). VNPay gửi
   email có **`vnp_TmnCode`** (mã website) và **`vnp_HashSecret`** (chuỗi bí mật).
2. Điền vào `.env`:

   ```env
   VNPAY_TMN_CODE=mã_trong_email
   VNPAY_HASH_SECRET=chuỗi_bí_mật_trong_email
   ```

3. Thẻ test (chọn ngân hàng **NCB** ở trang VNPay; nếu email VNPay gửi thẻ khác thì dùng thẻ trong email):

   | Thông tin | Giá trị |
   | --- | --- |
   | Số thẻ | `9704198526191432198` |
   | Tên chủ thẻ | `NGUYEN VAN A` |
   | Ngày phát hành | `07/15` |
   | Mật khẩu OTP | `123456` |

4. **IPN** (VNPay gọi ngầm về server để báo kết quả, kể cả khi người dùng tắt trình duyệt giữa chừng):
   đăng nhập trang quản lý merchant sandbox <https://sandbox.vnpayment.vn/merchantv2/> → cấu hình IPN
   URL thành `https://online-elearning.onrender.com/thanh-toan/vnpay/ipn`. Ở máy (`localhost`) không
   cần IPN: trình duyệt quay về `/thanh-toan/vnpay/ket-qua` là đủ.

### 1.2 MoMo sandbox

1. Cách nhanh: dùng bộ khoá test công khai trong code mẫu chính thức của MoMo
   (<https://github.com/momo-wallet/payment>):

   ```env
   MOMO_PARTNER_CODE=MOMO
   MOMO_ACCESS_KEY=F8BBA842ECF85
   MOMO_SECRET_KEY=K951B6PE1waDMi640xX08PD3vg6EkVlz
   ```

   Nếu MoMo báo sai chữ ký / partner không tồn tại thì bộ khoá này đã đổi: đăng ký tài khoản doanh
   nghiệp test tại <https://business.momo.vn> (mục **Tích hợp → Thông tin tích hợp**, môi trường TEST)
   rồi lấy `Partner code`, `Access key`, `Secret key` của riêng bạn.
2. Cách trả tiền ở trang MoMo test:
   - `MOMO_REQUEST_TYPE=captureWallet` (mặc định): quét QR bằng **app MoMo bản test (UAT)**, tải theo
     hướng dẫn ở <https://developers.momo.vn> → *Môi trường test*.
   - `MOMO_REQUEST_TYPE=payWithATM`: trả bằng thẻ ATM test (không cần app). Thông tin thẻ test có ở
     trang *Thông tin test* của tài liệu MoMo.
3. MoMo gọi IPN về `https://<web>/thanh-toan/momo/ipn` (đã gửi kèm mỗi giao dịch, không cần cấu hình).
   Số tiền mỗi giao dịch MoMo từ 1.000 đ đến 50.000.000 đ.

### 1.3 Chạy thử khi chưa có khoá

Đặt `PAYMENT_DEMO=true` → trang thanh toán có thêm **Thanh toán thử (demo)**, bấm là thành công ngay.
**Chỉ dùng ở máy / khi demo**, đừng bật trên web thật vì ai cũng mua được khoá miễn phí.

### 1.4 Trên Render

Vào service → **Environment**, thêm các biến ở trên (`VNPAY_*`, `MOMO_*`), lưu để Render deploy lại.
`APP_URL` phải là `https://online-elearning.onrender.com` để link trả về đúng.

### 1.5 Cách hoạt động (để bảo vệ đồ án)

| Bước | Chi tiết |
| --- | --- |
| Tạo đơn | Bảng `orders`: mã đơn `DH…`, học viên, khoá, số tiền, cổng, trạng thái `pending` |
| Sang cổng | VNPay: tạo URL có chữ ký HMAC-SHA512. MoMo: gọi API `create` (chữ ký HMAC-SHA256), nhận `payUrl` |
| Quay về | `/thanh-toan/{vnpay,momo}/ket-qua`: kiểm tra chữ ký và số tiền; sai chữ ký thì bỏ qua |
| IPN | `/thanh-toan/vnpay/ipn` (GET, trả JSON `RspCode`), `/thanh-toan/momo/ipn` (POST, trả 204, không cần CSRF) |
| Thành công | Đơn `paid`, ghi danh học viên, chuông báo cho học viên và giảng viên. Xử lý một lần duy nhất dù cả "quay về" và IPN cùng tới (khoá dòng bằng `lockForUpdate`) |
| Admin | Menu **Đơn hàng**: doanh thu, lọc theo trạng thái / cổng, tìm theo mã đơn, email |

Code: `app/Payments/` (VnpayGateway, MomoGateway, OrderProcessor), `PaymentController`, `config/payment.php`.
Test: `tests/Feature/PaymentTest.php`.

## 2. Chứng chỉ hoàn thành

- Học xong **mọi bài** của khoá đã ghi danh → cấp chứng chỉ (bảng `certificates`), mã dạng
  `UTH-7K2Q-9MXD`, lưu cả tên học viên / khoá / giảng viên tại ngày cấp. Bỏ đánh dấu một bài không
  thu hồi chứng chỉ.
- Trang công khai `/chung-chi/{mã}` để nhà tuyển dụng kiểm tra, có nút chia sẻ Facebook / thêm vào
  LinkedIn. Trang `/xac-minh-chung-chi` để gõ mã. Chỉ chủ chứng chỉ (và admin) tải được PDF.
- PDF tạo bằng `barryvdh/laravel-dompdf` (font DejaVu có sẵn, hiển thị đủ dấu tiếng Việt), khổ A4
  ngang. Mẫu ở `resources/views/certificates/pdf.blade.php`.
- Ai đã hoàn thành khoá **trước** khi có tính năng này được cấp bù khi mở **Chứng chỉ của tôi**.

## 3. Chuông thông báo trong web

Dùng kênh `database` của Laravel Notification (bảng `notifications`). Các email cũ (duyệt/từ chối
khoá, trả lời câu hỏi, duyệt giảng viên) giờ vừa gửi email vừa hiện ở chuông; thông báo luôn được lưu
**trước** nên email lỗi cũng không mất. Thêm thông báo mới (chỉ trong web):

| Ai nhận | Khi nào |
| --- | --- |
| Admin | Giảng viên gửi duyệt khoá học |
| Giảng viên | Có học viên ghi danh / mua khoá; học viên đặt câu hỏi dưới bài học |
| Học viên | Nhận chứng chỉ; thanh toán thành công |

Bấm một thông báo → đánh dấu đã đọc và mở trang liên quan. Trang đầy đủ: `/thong-bao-cua-toi`.

## 4. SEO

- Mọi trang có `<title>`, `meta description`, thẻ Open Graph / Twitter (ảnh xem trước khi chia sẻ
  link lên Facebook, Zalo…), `canonical`. Ảnh mặc định `public/images/og-default.png`; trang khoá học
  dùng ảnh bìa khoá, trang thông báo dùng ảnh thông báo.
- Trang khoá học có dữ liệu cấu trúc `schema.org/Course` (JSON-LD).
- Trang riêng tư (học bài, Góc học tập, quản trị, thanh toán…) có `noindex` và bị chặn trong
  `public/robots.txt`.
- `/sitemap.xml` tự liệt kê trang chủ, thư viện, khoá đã xuất bản, thông báo, hồ sơ giảng viên.
- Sau khi deploy: vào [Google Search Console](https://search.google.com/search-console), thêm web,
  mục **Sơ đồ trang web** gửi `https://online-elearning.onrender.com/sitemap.xml`.
