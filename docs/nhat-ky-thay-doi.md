# Nhật ký những việc Claude đã làm

Tài liệu này ghi lại các thay đổi Claude thực hiện trên nhánh `feature/demo`, theo thứ tự thời
gian. Nhánh `main` không bị động đến.

## 28/10/2026: CI/CD bằng GitHub Actions

- `.github/workflows/ci.yml`: mỗi push/PR vào `main` chạy toàn bộ PHPUnit và build + chạy thử image
  Docker với MySQL 8 (giống Render + Aiven). Lỗi hiện tên test/dòng lỗi hoặc log container ở trang
  Summary của lần chạy. Hướng dẫn: mục 6 của [deploy-render.md](deploy-render.md).
- `render.yaml`: nhánh `main`, `autoDeployTrigger: checksPass` (chỉ deploy khi CI xanh).

## 27/10/2026: chứng chỉ PDF, chuông thông báo, thanh toán VNPay/MoMo, SEO

Hướng dẫn cấu hình và cách hoạt động: [thanh-toan.md](thanh-toan.md). Ca kiểm thử: mục 10–13 của
[kich-ban-test.md](kich-ban-test.md). Ba migration mới chỉ **thêm** bảng (`notifications`,
`certificates`, `orders`), dữ liệu cũ giữ nguyên.

- **Chứng chỉ**: học xong mọi bài → cấp chứng chỉ có mã `UTH-XXXX-XXXX`, tải PDF (A4 ngang), trang
  xác minh công khai `/chung-chi/{mã}` và `/xac-minh-chung-chi`, menu "Chứng chỉ của tôi", nút chứng
  chỉ ở Góc học tập và trang học bài.
- **Chuông thông báo**: chuông có số chưa đọc ở thanh trên (cả trang quản trị), trang
  `/thong-bao-cua-toi`. Báo khi: khoá được duyệt / từ chối, có khoá chờ duyệt (admin), học viên mới,
  câu hỏi mới, câu hỏi được trả lời, duyệt giảng viên, nhận chứng chỉ, thanh toán thành công.
- **Thanh toán**: khoá trả phí phải mua qua VNPay hoặc MoMo (sandbox), kiểm tra chữ ký + số tiền, xử
  lý cả URL trả về và IPN; lịch sử thanh toán cho học viên; menu **Đơn hàng** cho admin; khoá đã mua
  không tự huỷ ghi danh được. `PAYMENT_DEMO=true` để chạy thử khi chưa có khoá.
- **SEO**: meta description, Open Graph, canonical, ảnh chia sẻ mặc định, JSON-LD cho khoá học,
  `noindex` cho trang riêng tư, `/sitemap.xml`, `robots.txt`.
- `CourseFactory` mặc định giá 0 (thêm `->paid()`), vì khoá có giá giờ phải thanh toán.
- Test mới: `CertificateTest`, `NotificationBellTest`, `PaymentTest`, `SeoTest`.

## 27/10/2026: video bài học tải lên và banner trang chủ

Làm trên nhánh `main`. Hai migration mới chỉ **thêm** cột/bảng (`lessons.video_path`, bảng
`banners`), dữ liệu cũ giữ nguyên.

**Video bài học**

- Form bài học có mục **Video bài giảng**: tải lên MP4 (khuyên dùng) hoặc WebM, tối đa 100 MB (giới
  hạn video của Cloudinary Free), hoặc dán link YouTube như cũ. Có cả hai thì trang học phát video
  tải lên. Chọn file mới thay video cũ (file cũ bị xoá), có ô "Xoá video đã tải lên".
- File lưu ở disk `media`: có `CLOUDINARY_URL` → Cloudinary (thư mục `online-elearning/lesson-videos`,
  không mất khi Render deploy lại); không có → `storage/app/public/lesson-videos`. Tên file ngẫu nhiên
  40 ký tự, chỉ hiện trong trang học (đã ghi danh hoặc bài học thử).
- Trình duyệt chặn file > 100 MB ngay khi chọn, nút Lưu báo "Đang lưu..." trong lúc upload. Xoá bài
  học / khoá học xoá luôn video. Dockerfile nâng giới hạn upload lên 110 MB (`post_max_size` 130 MB);
  thêm trang lỗi 413 tiếng Việt khi gửi quá lớn.

**Banner trang chủ** (Admin → **Banner trang chủ**, `/admin/banners`)

- Thêm/sửa/xoá banner: ảnh ngang (JPG/PNG/WebP ≤ 4 MB, nên 1920×600), tiêu đề, mô tả, đường dẫn và
  chữ trên nút, thứ tự, bật/tắt, thời gian hiện từ ... đến hết ... (để trống là luôn hiện).
- Trang chủ hiện các banner đang chạy thành khung trượt trên cùng: tự chuyển 6 giây, nút ‹ ›, chấm
  tròn, vuốt trên điện thoại, dừng khi rê chuột. Không có banner thì trang chủ như cũ.
- Đường dẫn chỉ nhận trang trong web (`/...`) hoặc `http(s)://` (chặn `javascript:`); link ngoài mở
  tab mới. Ảnh banner nằm ở thư mục `banners` và hiện trong **Ảnh đã lưu**.

Test mới: `tests/Feature/LessonVideoTest.php`, `tests/Feature/BannerTest.php`. Kịch bản kiểm thử tay:
mục 6.3 (VD-01 → VD-12) và 7.3 (BN-01 → BN-16) trong `docs/kich-ban-test.md`.

## 27/10/2026: vá lỗi bảo mật (mật khẩu demo, chiếm tài khoản, giới hạn tần suất, duyệt lại, header)

Theo báo cáo kiểm tra bảo mật ngày 27/10 (lỗi số 1, 3, 4, 5, 6).

**Mật khẩu demo không mở được web thật**

- Mật khẩu demo lấy từ `config/demo.php`: chạy local vẫn là `Admin@123`, `Teacher@123`,
  `Student@123`; trên production lấy từ biến `DEMO_ADMIN_PASSWORD`, `DEMO_TEACHER_PASSWORD`,
  `DEMO_STUDENT_PASSWORD`, không đặt thì ngẫu nhiên.
- Lệnh mới `php artisan app:secure-demo-accounts`, chạy mỗi lần deploy (`docker/start.sh`): đổi mật
  khẩu tài khoản demo theo các biến trên, hoặc sang mật khẩu ngẫu nhiên nếu tài khoản còn dùng mật
  khẩu công khai. Chi tiết ở [deploy-render.md](deploy-render.md) mục 4. Đã thêm 3 biến vào `render.yaml`.

**Chặn chiếm tài khoản trước qua đăng nhập Google**

- Trước đây: khi tắt xác minh email, kẻ xấu đăng ký (hoặc đổi email hồ sơ) bằng email của người
  khác; khi chủ thật đăng nhập Google, tài khoản được gộp nhưng mật khẩu của kẻ xấu vẫn dùng được.
- Nay: đăng nhập Google vào tài khoản **chưa xác minh email** sẽ xoá mật khẩu cũ, huỷ cookie "ghi
  nhớ", xoá mọi phiên đăng nhập khác của tài khoản và huỷ yêu cầu làm giảng viên đang chờ. Chủ tài
  khoản đặt lại mật khẩu ở trang Hồ sơ nếu muốn.
- Bật middleware `AuthenticateSession`: đổi hoặc đặt lại mật khẩu thì mọi phiên khác của tài khoản
  bị đăng xuất.

**Giới hạn tần suất** (`AppServiceProvider::configureRateLimiting`)

| Chức năng | Giới hạn |
| --- | --- |
| Đăng ký | 20 lần/phút và 100 lần/giờ mỗi IP |
| Quên mật khẩu (gửi email) | 5 lần/phút và 20 lần/giờ mỗi IP, 3 lần/giờ mỗi email |
| Đặt lại mật khẩu | 10 lần/phút mỗi IP |
| Ghi danh, đánh giá, yêu thích, hỏi đáp, xin làm giảng viên | 20 lần/phút và 200 lần/giờ mỗi người |
| Upload ảnh trong trình soạn thảo | 30 lần/phút mỗi người |

Vượt giới hạn ở form đăng ký/quên mật khẩu thì quay lại form kèm thông báo; ở các chức năng khác thì
hiện trang lỗi 429.

**Sửa khoá học đã duyệt phải duyệt lại**

- Giảng viên sửa thông tin khoá học đang xuất bản, hoặc thêm/sửa/xoá bài học, tài liệu của nó →
  khoá học chuyển về **Chờ duyệt** (tạm ẩn khỏi danh sách, học viên đã ghi danh vẫn học được) cho
  đến khi admin duyệt lại. Bấm Lưu mà không đổi gì thì giữ nguyên trạng thái.
- Trang sửa khoá học, bài học và trang Chương & bài học hiện cảnh báo khi khoá học đang xuất bản.
- Chưa áp dụng cho chương và bài kiểm tra (ít rủi ro hơn).

**Header bảo mật** (`app/Http/Middleware/AddSecurityHeaders.php`, mọi phản hồi)

- `X-Frame-Options: SAMEORIGIN` và CSP `frame-ancestors 'self'`: trang web khác không nhúng được
  trang của mình vào iframe để lừa bấm nút (clickjacking).
- `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy`, và HSTS (khi truy cập
  qua HTTPS: trình duyệt luôn dùng HTTPS trong 1 năm).
- CSP chỉ có các luật không làm hỏng trang (không chặn script, style, YouTube, Cloudinary).

Test: `tests/Feature/SecurityHardeningTest.php`.

## 27/10/2026: giảng viên chờ duyệt, khoá tài khoản, hỏi đáp, tài liệu, yêu thích, trang lỗi

Làm trên nhánh `main`. Bốn migration mới chỉ **thêm** cột/bảng, dữ liệu đang có trên Aiven giữ
nguyên; Render tự chạy `migrate` khi deploy.

**Đăng ký giảng viên và khoá tài khoản**

- Trang đăng ký có lựa chọn **Học viên / Giảng viên**. Chọn Giảng viên → tài khoản vẫn là học viên
  và có yêu cầu "chờ duyệt". Học viên (kể cả tài khoản Google) gửi yêu cầu ở trang Hồ sơ.
- Admin → Người dùng: lọc "Chờ duyệt giảng viên", nút Duyệt / Từ chối (gửi email kết quả, mail lỗi
  vẫn lưu). Bảng điều khiển admin báo số yêu cầu đang chờ.
- Nút khoá / mở khoá tài khoản. Tài khoản bị khoá không đăng nhập được bằng mật khẩu hay Google, và
  đang đăng nhập thì bị đăng xuất ở lần tải trang kế tiếp. Admin không tự khoá mình được.
- Cột mới ở `users`: `instructor_status`, `instructor_requested_at`, `locked_at`.

**Hỏi đáp dưới bài học** (bảng `lesson_questions`)

- Học viên đã ghi danh đặt câu hỏi dưới bài học; giảng viên phụ trách hoặc admin trả lời (ngay ở
  trang học hoặc ở mục **Hỏi đáp** mới trong `/instructor` và `/admin`, mặc định lọc câu chưa trả
  lời). Người hỏi nhận email khi có trả lời. Người hỏi xoá được câu của mình, giảng viên/admin xoá
  được mọi câu.

**Tài liệu đính kèm bài học** (bảng `lesson_attachments`)

- Form bài học có mục Tài liệu đính kèm: PDF, Word, PowerPoint, Excel, TXT, ZIP; tối đa 10 tệp mỗi
  lần, 10 MB mỗi tệp (giới hạn file của Cloudinary Free).
- Lưu ở disk mới `attachments`: có `CLOUDINARY_URL` → Cloudinary loại **private** (thư mục
  `online-elearning/attachments`, không có link công khai); không có → `storage/app/private/attachments`.
- Chỉ học viên đã ghi danh, giảng viên phụ trách và admin tải được (route
  `/hoc/{khoá}/{bài}/tai-lieu/{id}`), file tải về giữ tên gốc. Xoá bài học / khoá học xoá luôn file.

**Khoá học yêu thích** (bảng `wishlists`): nút ở chi tiết khoá học, trang `/khoa-hoc-yeu-thich`
(menu tài khoản).

**Hồ sơ giảng viên** `/giang-vien/{id}`: giới thiệu, số khoá học, số học viên, điểm đánh giá, các
khoá đã xuất bản. Tên giảng viên ở chi tiết khoá học dẫn tới trang này.

**Trang lỗi riêng** 403, 404, 419, 429, 500, 503 bằng tiếng Việt.

**Khác**

- Dữ liệu mẫu (chỉ khi seed database trống hoặc `RESEED_DATABASE=true`): tài khoản
  `giangvien.moi@example.com` / `Teacher@123` đang chờ duyệt giảng viên, câu hỏi mẫu ở bài 1 khoá
  "Laravel 13 cho người mới bắt đầu", một khoá yêu thích của `student@example.com`.
- Kịch bản kiểm thử thủ công toàn bộ chức năng: [kich-ban-test.md](kich-ban-test.md).
- Thêm 33 test tự động (tổng 129 test, đều qua). Đã thử migrate trên MariaDB với database cũ đã có
  dữ liệu.

Cần chạy `php artisan migrate` và `npm run build` (có class CSS mới). Không thêm gói mới.

## 26/10/2026: rà soát bảo mật trước khi push

- Lịch sử git và các file sắp commit không chứa mật khẩu/khoá thật. `.gitignore` chặn thêm `.env.*`
  (trừ `.env.example`), `*.pem`, `*.sql`.
- Lọc HTML của CKEditor chặt hơn (chặn `java<tab>script:`, iframe `srcdoc`, thẻ `svg`/`math`...).
- Đăng nhập Google không tự liên kết vào tài khoản **Quản trị** có cùng email, và từ chối tài khoản
  Google chưa xác minh email.
- Tài khoản demo đổi sang `admin@example.com`, `teacher@example.com`, `student@example.com` (mật khẩu
  giữ nguyên), vì các địa chỉ `@gmail.com` cũ là Gmail thật của người khác. Migration mới tự đổi
  email của các tài khoản demo cũ trong database đã seed (dữ liệu khác giữ nguyên).
- Render: `SESSION_SECURE_COOKIE=true` để cookie đăng nhập chỉ gửi qua HTTPS.

## 26/10/2026: lưu ảnh trên Cloudinary, gửi email thật, đổi bí mật bị lộ

- Ảnh upload (avatar, ảnh bìa và ảnh khoá học, ảnh trong CKEditor, ảnh thông báo) lưu qua disk mới
  `media`. Có `CLOUDINARY_URL` → lưu trên Cloudinary (không mất khi Render deploy lại); không có →
  lưu trong `storage/app/public` như trước. Thêm gói `cloudinary/cloudinary_php`.
- Gửi email: ở máy dùng Gmail SMTP (mật khẩu ứng dụng); trên Render dùng Brevo
  (`MAIL_MAILER=brevo`, `BREVO_API_KEY`) vì Render Free chặn cổng SMTP. Thêm gói
  `symfony/brevo-mailer`, `symfony/http-client`. Gửi mail lỗi thì hiện thông báo thay vì trang lỗi 500.
- Giảng viên nhận email khi khoá học được duyệt hoặc bị từ chối (kèm lý do). Mail lỗi thì việc
  duyệt vẫn được lưu.
- Bật xác minh email khi đăng ký bằng biến `AUTH_VERIFY_EMAIL=true` (mặc định tắt, ở máy không cần
  bật). Email quên mật khẩu/xác minh đã dịch sang tiếng Việt.
- Hướng dẫn: [luu-anh-va-gui-email.md](luu-anh-va-gui-email.md),
  [doi-mat-khau-bi-lo.md](doi-mat-khau-bi-lo.md).

- Trang quản trị **Ảnh đã lưu** (`/admin/media`): xem toàn bộ ảnh đã upload (trên Cloudinary hoặc
  trong máy), ảnh đang dùng ở đâu (avatar, ảnh bìa, ảnh khoá học, ảnh trong bài viết, ảnh thông
  báo), lọc ảnh không còn dùng, mở ảnh và copy link.

Cần chạy `composer install`. Không có migration mới.

## 26/10/2026: đăng nhập bằng Google

- Thêm gói `laravel/socialite`, nút **Đăng nhập bằng Google** ở trang đăng nhập và đăng ký.
- Email mới → tạo tài khoản **Học viên** (không có mật khẩu); email trùng tài khoản có sẵn → liên
  kết và giữ nguyên vai trò.
- Migration thêm cột `users.google_id`, cột `password` được để trống. Trang Hồ sơ: tài khoản
  Google đặt mật khẩu mới không cần mật khẩu cũ, xoá tài khoản không cần nhập mật khẩu.
- Cấu hình: `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI`. Hướng dẫn chi
  tiết ở [dang-nhap-google.md](dang-nhap-google.md).

Cần chạy `composer install` và `php artisan migrate`.

## 26/10/2026: bổ sung theo tài liệu phân tích thiết kế

**Tách khu giảng viên và khu quản trị**

- Giảng viên dùng `/instructor`: bảng điều khiển (số học viên, điểm đánh giá trung bình, biểu đồ
  ghi danh), khoá học của tôi, chương & bài học, quiz, danh sách học viên kèm % tiến độ.
- Quản trị viên dùng `/admin`: duyệt khoá học, danh mục, ghi danh, đánh giá, người dùng, thông báo.
  Giảng viên không vào được `/admin` và ngược lại.

**Chương (sections)**: mỗi khoá học chia thành chương, bài học thuộc một chương. Trang học và
trang chi tiết khoá học hiển thị nội dung theo chương.

**Quiz trắc nghiệm**: giảng viên tạo quiz cho từng chương, thêm câu hỏi (2 đến 6 đáp án, một đáp
án đúng), đặt điểm đạt. Học viên đã ghi danh làm bài, được chấm điểm tự động, xem lại đáp án và
lịch sử các lần làm.

**Duyệt khoá học**: khoá mới là bản nháp; giảng viên bấm "Gửi duyệt" (chờ duyệt); quản trị viên
duyệt (xuất bản), từ chối kèm lý do, hoặc ẩn. Khoá bị từ chối được sửa rồi gửi duyệt lại.

**Mật khẩu**: đăng ký, đăng nhập, đổi mật khẩu đều yêu cầu ít nhất 6 ký tự, 1 chữ in hoa và 1 ký
tự đặc biệt.

**Dữ liệu mẫu**: mỗi khoá có 3 chương, 6 bài, 3 quiz; có sẵn lượt làm quiz, một khoá chờ duyệt,
một bản nháp và một khoá bị từ chối của `teacher@example.com`. Mật khẩu mới: `Admin@123`,
`Teacher@123`, `Student@123`.

Cần chạy lại `php artisan migrate:fresh --seed` và `npm run build`.

## 24/10/2026: xây dựng trang web UTH E-Learning

Commit `a727e82` (*feat: build UTH e-learning site with admin panel and learning flow*).

**Trang người dùng**

- Trang chủ, thư viện khoá học (tìm kiếm, lọc theo danh mục, trình độ, học phí, sắp xếp,
  phân trang), chi tiết khoá học (thư viện ảnh, nội dung bài học, đánh giá), thông báo.
- Ghi danh và huỷ ghi danh, trang học bài có video YouTube, đánh dấu hoàn thành bài, thanh
  tiến độ.
- Góc học tập (`/dashboard`) với thống kê và % hoàn thành từng khoá.
- Hồ sơ cá nhân: thêm số điện thoại, giới thiệu, upload ảnh đại diện.

**Trang quản trị** (`/admin`)

- Bảng điều khiển có thẻ thống kê và biểu đồ Chart.js.
- CRUD khoá học (ảnh đại diện + nhiều ảnh), bài học, danh mục, người dùng, thông báo.
- Quản lý ghi danh, duyệt hoặc ẩn đánh giá.

**Nền tảng**

- Migration cho các bảng `categories`, `courses`, `course_images`, `lessons`, `enrollments`,
  `lesson_completions`, `reviews`, `announcements` và thêm cột hồ sơ vào `users`.
- Enum `UserRole`, `CourseLevel`, `CourseStatus`; middleware `role` (`EnsureUserHasRole`);
  `CoursePolicy`.
- Tích hợp CKEditor 5 kèm upload ảnh và bộ lọc `HtmlSanitizer` chống XSS.
- Việt hoá giao diện (`lang/vi`, `lang/vi.json`), giao diện màu xanh teal theo phong cách UTH,
  responsive cho điện thoại.
- Seeder dữ liệu mẫu: 6 danh mục, 12 khoá học, bài học, 25 học viên, ghi danh, đánh giá,
  thông báo.
- Factory và kiểm thử tính năng trong `tests/Feature` (`CourseManagementTest`, `LearningTest`,
  `PageRenderingTest`, ...).
- Tài liệu phân tích thiết kế [phan-tich-thiet-ke.md](phan-tich-thiet-ke.md) (use case, ERD).

## 26/10/2026: chuẩn bị deploy lên Railway

Commit `f849a4a` (*feat: add Docker deployment configuration and application documentation
screenshots*).

| File | Thay đổi |
| --- | --- |
| `Dockerfile` | Build 3 bước: cài thư viện PHP bằng Composer, build asset bằng Vite (Node 22), chạy bằng PHP 8.4 + Apache với các extension `pdo_mysql gd zip intl bcmath opcache exif` |
| `.dockerignore` | Bỏ `vendor`, `node_modules`, `.env`... khỏi image |
| `docker/start.sh` | Khi container khởi động: đổi cổng Apache theo biến `PORT`, tạo thư mục `storage`, chạy `storage:link`, `migrate --force`, seed nếu database trống, `optimize` |
| `railway.json` | Cho Railway build bằng `Dockerfile`, kiểm tra sức khoẻ ở `/up` |
| `bootstrap/app.php` | Thêm `trustProxies(at: '*')` để Laravel nhận đúng HTTPS khi chạy sau proxy của Railway |
| `routes/console.php` | Lệnh `php artisan app:seed-if-empty`: chỉ seed khi chưa có người dùng, tránh nhân đôi dữ liệu mỗi lần deploy |
| `composer.json`, `composer.lock` | Chuyển `fakerphp/faker` từ `require-dev` sang `require`, vì seeder dùng Faker trên server (môi trường production không cài gói dev) |
| `docs/deploy-railway.md` | Hướng dẫn deploy từng bước trên Railway |
| `docs/screenshots/` | 12 ảnh chụp màn hình các trang, cả bản máy tính và điện thoại |

Lưu ý: từ phía Claude, lệnh push lên GitHub bị từ chối (lỗi 403), nên code được ghi thẳng vào
thư mục dự án trên máy và bạn tự commit, push.

## 26/10/2026: cài đặt để chạy trên máy (Windows)

- Bật `extension=gd` trong `php.ini` (cần cho xử lý ảnh và thư viện Excel/PDF).
- Tạo database MySQL `online_elearning`.
- Chạy `php artisan migrate --seed` để tạo bảng và dữ liệu mẫu.
- Tài khoản quản trị sau khi seed: `admin@gmail.com` / `12345678`.

## 26/10/2026: viết tài liệu

Thêm vào thư mục `docs/`:

- [README.md](README.md): mục lục tài liệu.
- [mo-ta-trang-web.md](mo-ta-trang-web.md): mô tả tính năng và từng trang.
- [nhat-ky-thay-doi.md](nhat-ky-thay-doi.md): file này.
- [huong-dan-chay.md](huong-dan-chay.md): hướng dẫn cài đặt và chạy code.
