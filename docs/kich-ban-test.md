# Kịch bản kiểm thử toàn bộ chức năng

Tài liệu này liệt kê các bước kiểm thử thủ công cho mọi chức năng của UTH E-Learning, kèm kết
quả mong đợi. Mỗi dòng có một mã (ví dụ `TK-03`) để ghi lại kết quả vào cột cuối khi chạy thử.

Ngoài kiểm thử tay, dự án có bộ test tự động: chạy `php artisan test` (dùng SQLite trong bộ nhớ,
không đụng database thật). Tất cả test phải xanh trước khi push.

## 0. Chuẩn bị

1. Chạy dự án theo [huong-dan-chay.md](huong-dan-chay.md), sau đó `php artisan migrate --seed`
   (hoặc `php artisan migrate:fresh --seed` để có dữ liệu mẫu mới, **xoá sạch dữ liệu cũ**).
2. Sau khi sửa giao diện, chạy `npm run build` (hoặc giữ `npm run dev` chạy) để CSS có đủ class mới.
3. Tài khoản mẫu (khi chạy local; trên web thật mật khẩu đặt bằng biến `DEMO_*_PASSWORD`, xem
   [deploy-render.md](deploy-render.md)):

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Quản trị viên | `admin@example.com` | `Admin@123` |
| Giảng viên | `teacher@example.com` | `Teacher@123` |
| Giảng viên khác | `lan.tran@example.com`, `phuc.le@example.com` | `Teacher@123` |
| Học viên | `student@example.com` | `Student@123` |
| Học viên đang xin làm giảng viên | `giangvien.moi@example.com` | `Teacher@123` |

4. Nên mở 2 trình duyệt (hoặc 1 cửa sổ ẩn danh) để đăng nhập 2 vai trò cùng lúc.
5. Muốn thử gửi email ở máy: cấu hình Gmail SMTP theo [luu-anh-va-gui-email.md](luu-anh-va-gui-email.md).
   Không cấu hình thì đặt `MAIL_MAILER=log`, nội dung email nằm trong `storage/logs/laravel.log`.

Ký hiệu cột **KQ**: ✅ đạt, ❌ lỗi (ghi chú lại), ⏭ bỏ qua.

---

## 1. Tài khoản và đăng nhập

### 1.1 Đăng ký, đăng nhập bằng mật khẩu

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| TK-01 | Vào `/register`, nhập họ tên, email mới, mật khẩu `Hoc@123` hai lần, giữ vai trò **Học viên**, bấm Đăng ký | Vào Góc học tập, menu hiện tên mình, vai trò Học viên | |
| TK-02 | Đăng ký với mật khẩu `hoc123` (không chữ hoa, không ký tự đặc biệt) | Báo lỗi dưới ô mật khẩu: cần chữ in hoa và ký tự đặc biệt | |
| TK-03 | Đăng ký với mật khẩu `Ab@1` (4 ký tự) | Báo lỗi mật khẩu tối thiểu 6 ký tự | |
| TK-04 | Đăng ký với mật khẩu xác nhận khác mật khẩu | Báo lỗi xác nhận mật khẩu không khớp | |
| TK-05 | Đăng ký lại bằng email đã có (`student@example.com`) | Báo lỗi email đã được sử dụng | |
| TK-06 | Đăng xuất, vào `/login`, đăng nhập `student@example.com` / `Student@123` | Vào Góc học tập | |
| TK-07 | Đăng nhập sai mật khẩu (mật khẩu đúng định dạng, ví dụ `Sai@123`) | Báo "Email hoặc mật khẩu không đúng" | |
| TK-08 | Đăng nhập sai 5 lần liên tiếp | Lần thứ 6 báo đăng nhập sai quá nhiều lần, phải đợi | |
| TK-09 | Đăng nhập `admin@example.com` | Chuyển thẳng vào `/admin` | |
| TK-10 | Đăng nhập `teacher@example.com` | Chuyển thẳng vào `/instructor` | |
| TK-11 | Đăng nhập rồi bấm Đăng xuất | Về trang chủ, menu hiện nút Đăng nhập / Đăng ký | |

### 1.2 Đăng ký làm giảng viên (chờ duyệt)

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| GV-01 | Ở `/register` chọn vai trò **Giảng viên**, đăng ký email mới | Vào Góc học tập, thấy thông báo "Đã gửi yêu cầu làm giảng viên" và dải vàng "đang chờ quản trị viên duyệt" | |
| GV-02 | Tài khoản vừa tạo mở `/instructor` | Trang lỗi 403 tiếng Việt (chưa được duyệt nên vẫn là học viên) | |
| GV-03 | Tài khoản đó vẫn ghi danh và học được một khoá miễn phí | Ghi danh, học bài bình thường | |
| GV-04 | Admin vào `/admin`: | Dải vàng "N người dùng đang chờ duyệt làm giảng viên" | |
| GV-05 | Admin vào **Người dùng**, lọc trạng thái "Chờ duyệt giảng viên" | Chỉ hiện các tài khoản đang chờ, có nhãn vàng "Chờ duyệt giảng viên" | |
| GV-06 | Admin bấm nút ✓ (Duyệt) ở tài khoản đó | Báo "Đã duyệt … làm giảng viên"; vai trò đổi thành Giảng viên; người đó nhận email "Bạn đã trở thành giảng viên" | |
| GV-07 | Tài khoản vừa được duyệt đăng nhập lại / tải lại trang | Menu có "Kênh giảng viên", vào được `/instructor` | |
| GV-08 | Với `giangvien.moi@example.com` (dữ liệu mẫu), admin bấm ✕ (Từ chối) | Báo đã từ chối; vẫn là Học viên; nhận email "chưa được chấp nhận" | |
| GV-09 | `giangvien.moi@example.com` vào **Hồ sơ cá nhân** | Có khung "Trở thành giảng viên", ghi yêu cầu trước chưa được chấp nhận, có nút gửi lại | |
| GV-10 | Bấm **Gửi yêu cầu làm giảng viên** | Khung đổi thành "đang chờ quản trị viên duyệt"; admin thấy lại yêu cầu | |
| GV-11 | Học viên đăng nhập bằng Google (mục 1.4) rồi vào Hồ sơ, gửi yêu cầu | Yêu cầu xuất hiện ở trang admin như GV-05 | |
| GV-12 | Giảng viên / admin mở Hồ sơ | Không có khung "Trở thành giảng viên" | |
| GV-13 | Tắt mạng mail (sai mật khẩu SMTP) rồi duyệt một yêu cầu | Vẫn duyệt thành công, không hiện trang lỗi 500 | |

### 1.3 Khoá / mở khoá tài khoản

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| KH-01 | Trình duyệt A đăng nhập `student@example.com`. Trình duyệt B đăng nhập admin, vào **Người dùng**, bấm biểu tượng ổ khoá ở dòng học viên, xác nhận | Báo "Đã khoá tài khoản"; dòng đó có nhãn đen "Đã khoá", nút đổi thành ổ khoá mở | |
| KH-02 | Ở trình duyệt A, bấm sang trang bất kỳ | Bị đăng xuất, về trang đăng nhập với thông báo "Tài khoản của bạn đã bị khoá…" | |
| KH-03 | Đăng nhập lại bằng đúng mật khẩu | Vẫn báo tài khoản đã bị khoá, không vào được | |
| KH-04 | Tài khoản bị khoá đăng nhập bằng Google (nếu email đó là Gmail và đã cấu hình Google) | Về trang đăng nhập, báo tài khoản đã bị khoá | |
| KH-05 | Lọc **Người dùng** theo trạng thái "Đang bị khoá" | Chỉ hiện tài khoản bị khoá | |
| KH-06 | Admin bấm ổ khoá mở ở tài khoản đó | Báo "Đã mở khoá"; học viên đăng nhập lại được | |
| KH-07 | Admin tìm dòng của chính mình | Không có nút khoá và nút xoá ở dòng của mình | |
| KH-08 | Khoá một giảng viên rồi mở `/giang-vien/{id}` của người đó | Trang 404 (hồ sơ công khai bị ẩn khi tài khoản bị khoá) | |

### 1.4 Đăng nhập bằng Google

Cần cấu hình theo [dang-nhap-google.md](dang-nhap-google.md). Ở máy phải mở bằng đúng
`APP_URL` (ví dụ `http://localhost:8000`, không dùng `127.0.0.1`).

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| GG-01 | Trang đăng nhập và đăng ký có nút **Đăng nhập bằng Google** | Nút hiển thị | |
| GG-02 | Bấm nút, chọn một tài khoản Google chưa từng dùng trên web | Tạo tài khoản **Học viên** mới, ảnh đại diện lấy từ Google, vào Góc học tập | |
| GG-03 | Đăng xuất, đăng nhập Google lại bằng tài khoản đó | Vào đúng tài khoản cũ, không tạo tài khoản mới | |
| GG-04 | Đăng ký bằng email + mật khẩu với một Gmail, sau đó đăng nhập Google bằng chính Gmail đó | Liên kết vào tài khoản sẵn có, giữ nguyên vai trò | |
| GG-05 | Trên màn hình Google bấm Huỷ | Về trang đăng nhập, báo đã huỷ đăng nhập bằng Google | |
| GG-06 | Xoá `GOOGLE_CLIENT_ID` khỏi `.env`, `php artisan config:clear`, bấm nút Google | Báo chưa cấu hình đăng nhập Google, không lỗi 500 | |
| GG-07 | Tài khoản Google tạo ra (không có mật khẩu) vào Hồ sơ → Đổi mật khẩu | Đặt mật khẩu mới mà không cần nhập mật khẩu cũ; sau đó đăng nhập bằng email + mật khẩu được | |
| GG-08 | Tài khoản Google (không mật khẩu) xoá tài khoản | Không bị hỏi mật khẩu, xoá thành công | |

### 1.5 Quên mật khẩu, xác minh email, hồ sơ

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| MK-01 | `/forgot-password`, nhập email đã có | Báo đã gửi link; email "Đặt lại mật khẩu" (tiếng Việt) đến hộp thư / log | |
| MK-02 | Mở link trong email, nhập mật khẩu mới đúng quy tắc | Đặt lại thành công, đăng nhập bằng mật khẩu mới được | |
| MK-03 | Nhập email không tồn tại | Báo không tìm thấy người dùng với email này | |
| MK-04 | Cấu hình mail sai (ví dụ sai `MAIL_PASSWORD`) rồi gửi quên mật khẩu | Hiện thông báo chưa gửi được email, **không** ra trang lỗi 500 | |
| XM-01 | Đặt `AUTH_VERIFY_EMAIL=true`, `config:clear`, đăng ký tài khoản mới | Bị chuyển tới trang yêu cầu xác minh email; nhận email xác minh | |
| XM-02 | Bấm **Gửi lại email xác minh** | Báo đã gửi lại (mail lỗi thì báo lỗi thân thiện) | |
| XM-03 | Bấm link xác minh trong email | Vào được Góc học tập. Thử xong đặt lại `AUTH_VERIFY_EMAIL=false` | |
| HS-01 | Hồ sơ: đổi họ tên, số điện thoại, giới thiệu, bấm Lưu | Báo đã lưu, thông tin mới hiển thị | |
| HS-02 | Đổi ảnh đại diện (ảnh < 2MB) | Ảnh mới hiển thị ở menu và hồ sơ | |
| HS-03 | Upload file không phải ảnh làm ảnh đại diện | Báo lỗi định dạng | |
| HS-04 | Đổi mật khẩu: nhập sai mật khẩu hiện tại | Báo lỗi mật khẩu hiện tại không đúng | |
| HS-05 | Đổi mật khẩu đúng | Báo đã lưu; đăng nhập lại bằng mật khẩu mới được | |
| HS-06 | Xoá tài khoản (nhập đúng mật khẩu) | Bị đăng xuất, tài khoản biến mất ở trang admin | |

## 2. Phân quyền và khu vực

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| PQ-01 | Chưa đăng nhập, mở `/dashboard`, `/admin`, `/instructor`, `/khoa-hoc-yeu-thich` | Đều chuyển về trang đăng nhập | |
| PQ-02 | Học viên mở `/admin` và `/instructor` | Trang lỗi **403** tiếng Việt có nút Về trang chủ | |
| PQ-03 | Giảng viên mở `/admin` | 403 | |
| PQ-04 | Admin mở `/instructor` | 403 | |
| PQ-05 | Giảng viên A mở link sửa khoá học của giảng viên B (`/instructor/courses/{slug-của-B}/edit`) | 403 | |
| PQ-06 | Học viên chưa ghi danh mở link bài học không phải học thử `/hoc/{khoá}/{bài}` | 403 "Bạn cần ghi danh khoá học…" | |
| PQ-07 | Mở `/khoa-hoc/khong-co-that` hoặc `/abc-xyz` | Trang lỗi **404** tiếng Việt, có nút Về trang chủ và Xem khoá học | |
| PQ-08 | Mở trang có form (ví dụ Hồ sơ), đợi hết phiên (hoặc xoá cookie phiên) rồi bấm Lưu | Trang **419** "Phiên làm việc đã hết hạn" | |
| PQ-09 | Đặt `APP_DEBUG=false`, gây lỗi máy chủ (ví dụ tắt MySQL) rồi mở trang chủ | Trang **500** tiếng Việt thay vì trang lỗi kỹ thuật | |

## 3. Trang công khai

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| CK-01 | Mở trang chủ `/` | Có banner, ô tìm kiếm, số liệu, danh mục, khoá học nổi bật, thông báo mới | |
| CK-02 | Gõ từ khoá ở ô tìm kiếm trên menu (ví dụ "Laravel") | Thư viện khoá học chỉ hiện khoá khớp từ khoá | |
| CK-03 | `/khoa-hoc`: lọc theo danh mục, trình độ, Miễn phí / Có phí | Kết quả đúng bộ lọc, giữ bộ lọc khi chuyển trang | |
| CK-04 | Sắp xếp Phổ biến nhất, Học phí tăng / giảm dần | Thứ tự đúng | |
| CK-05 | Thư viện chỉ hiện khoá **Đã xuất bản** | Không thấy khoá nháp / chờ duyệt / bị từ chối / đã ẩn (ví dụ "Docker cho lập trình viên") | |
| CK-06 | Mở chi tiết một khoá học | Có ảnh, mô tả, giảng viên, học phí, chương, bài, quiz, đánh giá; bài "Học thử" có nhãn vàng | |
| CK-07 | Bấm tên giảng viên ở chi tiết khoá học | Sang trang **hồ sơ giảng viên** `/giang-vien/{id}` | |
| CK-08 | Hồ sơ giảng viên | Có ảnh, tên, số khoá học, số học viên, điểm đánh giá, phần giới thiệu, danh sách khoá **đã xuất bản** của giảng viên | |
| CK-09 | Mở `/giang-vien/{id}` của một học viên hoặc admin | 404 | |
| CK-10 | `/thong-bao` và mở một thông báo | Danh sách và chi tiết thông báo đã xuất bản | |
| CK-11 | Mở trang trên điện thoại (DevTools, chiều rộng 375px) | Menu thu gọn, bố cục 1 cột, không tràn ngang | |

## 4. Học viên: ghi danh, học bài, tiến độ

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| HV-01 | Chưa đăng nhập, ở chi tiết khoá học | Khoá miễn phí: nút "Đăng nhập để ghi danh"; khoá trả phí: "Đăng nhập để mua khoá học" | |
| HV-02 | Học viên bấm **Ghi danh khoá học** ở một khoá **miễn phí** | Chuyển vào trang học bài đầu tiên; khoá xuất hiện ở Góc học tập | |
| HV-03 | Bấm **Đánh dấu hoàn thành** | Trang **không tải lại** (không nháy trắng): hiện thông báo nhỏ ở dưới, % tiến độ tăng, bài cũ có dấu ✓ ở cột phải, rồi chuyển sang bài kế tiếp (địa chỉ trên thanh URL đổi theo) | |
| HV-04 | Bấm lại "Đã hoàn thành (bỏ đánh dấu)" ở bài đã xong | Không tải lại trang, vẫn ở bài đó: bỏ dấu ✓, % giảm, nút trở lại "Đánh dấu hoàn thành" | |
| HV-05 | Hoàn thành mọi bài | Chuyển sang trang chứng chỉ, báo "Chúc mừng! … nhận được chứng chỉ"; Góc học tập ghi 100%, nút đổi thành Ôn tập (xem thêm mục 10) | |
| HV-06 | Nút Bài trước / Bài tiếp và danh sách bài bên phải | Điều hướng đúng thứ tự chương → bài mà không tải lại trang; cột danh sách giữ nguyên vị trí cuộn; nút Back/Forward của trình duyệt quay đúng bài | |
| HV-07 | Bài có link YouTube | Video nhúng phát được | |
| HV-08 | **Huỷ ghi danh** ở chi tiết khoá học, xác nhận | Khoá biến mất khỏi Góc học tập; bài không học thử bị khoá lại | |
| HV-09 | Người chưa ghi danh mở bài **Học thử** | Xem được nội dung, nút "Ghi danh để học tiếp" | |
| HV-10 | Góc học tập | Thẻ số liệu đúng: khoá đã ghi danh, đã hoàn thành, số bài đã học, tiến độ trung bình | |

### 4.1 Bài kiểm tra (quiz)

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| QZ-01 | Học viên đã ghi danh bấm một quiz ở cuối chương | Hiện các câu hỏi trắc nghiệm, lịch sử làm bài (nếu có) | |
| QZ-02 | Chọn đáp án, nộp bài | Trang kết quả: điểm, Đạt / Chưa đạt theo % qua bài, đáp án đúng/sai từng câu | |
| QZ-03 | Làm lại quiz | Lần làm mới được lưu, lịch sử có nhiều lần | |
| QZ-04 | Nộp bài bỏ trống hết | Vẫn chấm, 0 điểm, không lỗi | |
| QZ-05 | Người chưa ghi danh mở link quiz | 403 | |
| QZ-06 | Học viên B mở link kết quả `/kiem-tra/.../ket-qua/{id}` của học viên A | 403 | |

### 4.2 Đánh giá khoá học

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| DG-01 | Học viên đã ghi danh chọn số sao, viết nhận xét, bấm Gửi đánh giá | Đánh giá hiện ở chi tiết khoá học, điểm trung bình cập nhật | |
| DG-02 | Gửi đánh giá lần nữa | Cập nhật đánh giá cũ, không tạo đánh giá thứ hai | |
| DG-03 | Người chưa ghi danh | Không thấy form đánh giá | |
| DG-04 | Admin vào **Đánh giá**, bấm Ẩn một đánh giá | Đánh giá biến mất ở trang công khai; bấm Hiện thì xuất hiện lại | |
| DG-05 | Admin xoá đánh giá | Đánh giá bị xoá | |

### 4.3 Khoá học yêu thích

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| YT-01 | Học viên mở chi tiết một khoá đã xuất bản, bấm **Thêm vào yêu thích** | Báo đã thêm, nút đổi thành tim đỏ "Đã yêu thích (bỏ chọn)" | |
| YT-02 | Menu tài khoản → **Khoá học yêu thích** (`/khoa-hoc-yeu-thich`) | Khoá vừa thêm hiện dạng thẻ, có nút tim ở góc | |
| YT-03 | Bấm nút tim trên thẻ | Khoá biến mất khỏi danh sách | |
| YT-04 | Bấm "Đã yêu thích (bỏ chọn)" ở chi tiết khoá học | Bỏ khỏi danh sách yêu thích | |
| YT-05 | Danh sách trống | Hiện "Bạn chưa lưu khoá học nào" + link khám phá khoá học | |
| YT-06 | Admin ẩn một khoá đang nằm trong danh sách yêu thích | Khoá không còn hiện ở trang yêu thích (vì không còn công khai) | |
| YT-07 | `student@example.com` (dữ liệu mẫu mới) mở trang yêu thích | Có sẵn khoá "Nhập môn Machine Learning" | |

## 5. Hỏi đáp dưới bài học

Dữ liệu mẫu: khoá "Laravel 13 cho người mới bắt đầu", bài 1 có một câu hỏi đã được trả lời và một
câu chưa trả lời của `student@example.com`.

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| HD-01 | Học viên đã ghi danh mở bài học, kéo xuống **Hỏi đáp về bài học**, nhập câu hỏi, bấm Gửi câu hỏi | Câu hỏi hiện đầu danh sách, nhãn vàng "Chưa trả lời" | |
| HD-02 | Gửi câu hỏi trống | Báo lỗi bắt buộc nhập | |
| HD-03 | Gửi câu hỏi có `<script>alert(1)</script>` | Hiện nguyên văn dạng chữ, **không** bật hộp thoại | |
| HD-04 | Người chưa ghi danh mở bài học thử | Thấy câu hỏi của người khác, không có ô đặt câu hỏi ("Ghi danh khoá học để đặt câu hỏi") | |
| HD-05 | Giảng viên phụ trách khoá vào **Kênh giảng viên → Hỏi đáp** | Thấy câu hỏi mới (mặc định lọc "Chưa trả lời"), có tên khoá + bài | |
| HD-06 | Giảng viên nhập câu trả lời, bấm Trả lời | Báo đã gửi; câu hỏi rời khỏi bộ lọc "Chưa trả lời", có ở "Tất cả câu hỏi" | |
| HD-07 | Học viên tải lại bài học | Câu trả lời hiện dưới câu hỏi, có nhãn vai trò "Giảng viên"; học viên nhận email "Câu hỏi của bạn … đã có trả lời" | |
| HD-08 | Giảng viên cũng có thể trả lời ngay trên trang bài học (ô "Trả lời học viên…") | Trả lời thành công | |
| HD-09 | Giảng viên **khác** (không phụ trách khoá) vào Hỏi đáp | Không thấy câu hỏi của khoá không phải của mình | |
| HD-10 | Admin vào **Trang quản trị → Hỏi đáp** | Thấy câu hỏi của mọi khoá, trả lời được | |
| HD-11 | Học viên xoá câu hỏi của chính mình (biểu tượng thùng rác) | Câu hỏi và các câu trả lời biến mất | |
| HD-12 | Học viên khác | Không có nút xoá ở câu hỏi của người khác | |
| HD-13 | Giảng viên / admin xoá một câu trả lời hoặc câu hỏi | Xoá thành công | |
| HD-14 | Tắt mail rồi giảng viên trả lời | Câu trả lời vẫn lưu, không lỗi 500 | |
| HD-15 | Bài có hơn 10 câu hỏi | Có phân trang, bấm trang 2 vẫn ở khu hỏi đáp | |

## 6. Giảng viên: khoá học, chương, bài học, tài liệu, quiz

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| GD-01 | `/instructor`: bảng điều khiển | Số khoá, số học viên, điểm đánh giá, biểu đồ ghi danh của **riêng** mình | |
| GD-02 | **Khoá học của tôi → Tạo khoá học**: nhập tên, tóm tắt, mô tả (CKEditor), học phí, trình độ, danh mục, ảnh bìa, vài ảnh thư viện | Tạo thành công ở trạng thái **Bản nháp** | |
| GD-03 | Tạo khoá với ảnh bìa > 2MB hoặc file không phải ảnh | Báo lỗi | |
| GD-04 | Trong CKEditor chèn ảnh từ máy | Ảnh upload và hiện trong nội dung | |
| GD-05 | Sửa khoá học: đổi tên, xoá bớt ảnh thư viện, thêm ảnh mới | Lưu đúng, ảnh bị xoá không còn | |
| GD-06 | **Nội dung khoá học**: thêm chương, đổi tên chương, xoá chương | Thao tác đúng | |
| GD-07 | Thêm bài học trong một chương: tên, nội dung, link YouTube, thời lượng, thứ tự, tick Học thử | Bài hiện đúng chương, đúng thứ tự | |
| GD-08 | Gửi duyệt khi khoá **chưa có bài học** | Báo cần ít nhất một bài học | |
| GD-09 | Bấm **Gửi duyệt** khi đã có bài | Trạng thái **Chờ duyệt**; admin thấy ở bảng điều khiển | |
| GD-10 | Danh sách học viên của khoá | Tên học viên, ngày ghi danh, % tiến độ | |
| GD-11 | Xoá khoá học của mình | Khoá, bài học, ảnh, video và tài liệu đính kèm bị xoá | |

### 6.1 Tài liệu đính kèm bài học

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| TL-01 | Sửa (hoặc thêm) một bài học, ở mục **Tài liệu đính kèm** chọn 1–2 file PDF/PPTX, bấm Lưu | Lưu thành công; mở lại form thấy danh sách tài liệu kèm dung lượng | |
| TL-02 | Học viên đã ghi danh mở bài học | Khung **Tài liệu bài học** liệt kê file, nút **Tải về** | |
| TL-03 | Bấm Tải về | Tải được file, **tên file giữ nguyên** như lúc upload (kể cả tiếng Việt có dấu) | |
| TL-04 | Người chưa ghi danh mở bài học thử có tài liệu | Thấy tên file nhưng hiện ổ khoá "Ghi danh để tải", không có link | |
| TL-05 | Copy link Tải về, mở bằng tài khoản chưa ghi danh | 403 | |
| TL-06 | Mở link Tải về khi chưa đăng nhập | Chuyển về trang đăng nhập | |
| TL-07 | Upload file `.exe`, `.php` hoặc ảnh `.jpg` | Báo lỗi định dạng; chỉ nhận PDF, Word, PowerPoint, Excel, TXT, ZIP | |
| TL-08 | Upload file > 10 MB | Báo lỗi dung lượng tối đa 10 MB | |
| TL-09 | Chọn hơn 10 file một lần | Báo lỗi tối đa 10 tệp | |
| TL-10 | Bấm thùng rác cạnh một tài liệu, xác nhận | Tài liệu biến mất ở form và trang học | |
| TL-11 | Xoá cả bài học có tài liệu | Bài và tài liệu bị xoá (file trong `storage/app/private/attachments` hoặc trên Cloudinary cũng mất) | |
| TL-12 | Ở máy (không có `CLOUDINARY_URL`) | File nằm trong `storage/app/private/attachments/lesson-{id}/`, **không** mở được qua `/storage/...` | |
| TL-13 | Trên Render (có `CLOUDINARY_URL`) upload và tải về một PDF | Tải được; trong Cloudinary file nằm ở thư mục `online-elearning/attachments`, loại **private** (mở link công khai không được) | |

### 6.2 Quiz của giảng viên

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| QG-01 | Ở một chương bấm thêm quiz: tên, mô tả, % để đạt | Quiz hiện cuối chương | |
| QG-02 | Thêm câu hỏi với 4 đáp án, chọn 1 đáp án đúng | Lưu thành công | |
| QG-03 | Thêm câu hỏi chỉ nhập 1 đáp án | Báo cần ít nhất 2 đáp án | |
| QG-04 | Chọn đáp án đúng là ô để trống | Báo lỗi đáp án đúng phải có nội dung | |
| QG-05 | Sửa / xoá câu hỏi, xoá quiz | Thao tác đúng | |

### 6.3 Video bài học (tải lên)

Ở máy, PHP mặc định chỉ cho upload 2–40 MB. Muốn thử video lớn, sửa `php.ini` (đường dẫn xem bằng
`php --ini`): `upload_max_filesize = 110M`, `post_max_size = 130M`, rồi chạy lại `php artisan serve`.
Trên Render đã đặt sẵn trong Dockerfile.

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| VD-01 | Giảng viên sửa một bài học, ở mục **Video bài giảng** chọn file MP4 (vài MB), bấm Lưu | Nút đổi thành "Đang lưu...", có dòng "Đang tải video ... lên"; lưu xong về trang Nội dung khoá học, bài có nhãn **Bài học video** | |
| VD-02 | Mở lại form bài đó | Thấy khung xem trước video, ô **Xoá video đã tải lên** | |
| VD-03 | Học viên đã ghi danh mở bài học | Video phát ngay trong trang (tua, toàn màn hình được), không có nút tải về trong menu của trình phát | |
| VD-04 | Bài có cả video tải lên và link YouTube | Trang học phát video tải lên, không hiện YouTube | |
| VD-05 | Ở form bài có video, chọn file video khác rồi Lưu | Video mới thay video cũ; file cũ bị xoá khỏi `storage/app/public/lesson-videos` (hoặc Cloudinary) | |
| VD-06 | Tick **Xoá video đã tải lên**, Lưu | Bài quay lại dùng link YouTube (nếu có) hoặc không có video | |
| VD-07 | Chọn file `.avi`, `.mov`, `.pdf` hoặc đổi đuôi một file `.txt` thành `.mp4` | Báo "Video phải là tệp MP4 hoặc WebM", không lưu file | |
| VD-08 | Chọn file MP4 > 100 MB | Báo ngay dưới ô chọn file "Video vượt quá 100 MB", form không gửi | |
| VD-09 | Người chưa ghi danh mở bài (không phải học thử) có video | 403, không thấy video | |
| VD-10 | Bài **học thử** có video, đăng nhập tài khoản chưa ghi danh | Xem được video | |
| VD-11 | Xoá bài học có video; xoá cả khoá học có video | File video bị xoá theo | |
| VD-12 | Trên Render (có `CLOUDINARY_URL`) upload video MP4 rồi mở trang học | Link video bắt đầu bằng `https://res.cloudinary.com/.../video/upload/`; trong Cloudinary file nằm ở `online-elearning/lesson-videos`; deploy lại vẫn còn | |

## 7. Quản trị viên

### 7.1 Duyệt khoá học

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| DK-01 | `/admin`: bảng điều khiển | Thẻ số liệu, biểu đồ ghi danh 6 tháng, biểu đồ theo danh mục, danh sách khoá chờ duyệt | |
| DK-02 | **Duyệt khoá học**, lọc "Chờ duyệt", bấm xem khoá "Lập trình Python nâng cao" | Mở được trang khoá (có dải vàng "chưa hiển thị công khai") | |
| DK-03 | Bấm **Duyệt** | Khoá thành **Đã xuất bản**, hiện ở thư viện; giảng viên nhận email "đã được duyệt" | |
| DK-04 | Với khoá chờ duyệt khác, bấm **Từ chối** và nhập lý do | Trạng thái Bị từ chối; giảng viên thấy lý do ở trang khoá học và nhận email kèm lý do | |
| DK-05 | Từ chối mà không nhập lý do | Báo lỗi bắt buộc nhập lý do | |
| DK-06 | Giảng viên sửa khoá bị từ chối rồi gửi duyệt lại | Trạng thái lại Chờ duyệt, lý do cũ được xoá | |
| DK-07 | Admin bấm **Ẩn** một khoá đang xuất bản | Khoá biến mất khỏi trang công khai; bấm Duyệt lại thì hiện lại (không gửi email lần nữa) | |
| DK-08 | Admin xoá một khoá học | Khoá bị xoá cùng bài học, ảnh, tài liệu | |
| DK-09 | Cấu hình mail sai rồi duyệt / từ chối | Vẫn lưu trạng thái, không lỗi 500 | |

### 7.2 Người dùng, danh mục, ghi danh, thông báo, ảnh

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| ND-01 | **Người dùng**: tìm theo tên/email, lọc theo vai trò | Kết quả đúng, có phân trang | |
| ND-02 | **Thêm người dùng**: nhập đủ thông tin, chọn vai trò Giảng viên | Tạo được, đăng nhập được ngay | |
| ND-03 | Sửa người dùng: đổi vai trò, đặt mật khẩu mới | Lưu đúng | |
| ND-04 | Đổi vai trò một người đang chờ duyệt giảng viên thành Giảng viên qua form Sửa | Thành Giảng viên, nhãn "Chờ duyệt" biến mất | |
| ND-05 | Admin sửa chính mình | Không đổi được vai trò của chính mình | |
| ND-06 | Xoá một người dùng | Bị xoá cùng dữ liệu liên quan | |
| DM-01 | **Danh mục**: thêm, sửa, xoá | Thao tác đúng, danh mục mới hiện ở bộ lọc thư viện | |
| GH-01 | **Ghi danh**: tìm, xoá một lượt ghi danh | Học viên đó không còn trong khoá | |
| TB-01 | **Thông báo**: đăng thông báo có ảnh, tick xuất bản | Hiện ở `/thong-bao`, trang chủ, Góc học tập | |
| TB-02 | Bỏ tick xuất bản | Thông báo không còn hiện công khai | |
| AN-01 | **Ảnh đã lưu** (`/admin/media`) | Liệt kê ảnh đã upload, cho biết ảnh đang dùng ở đâu; lọc ảnh không còn dùng; mở ảnh và copy link | |
| AN-02 | Không có `CLOUDINARY_URL` | Trang ghi đang lưu trong máy (`storage/app/public`) | |

### 7.3 Banner trang chủ

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| BN-01 | Chưa có banner nào, mở trang chủ | Trang chủ giữ phần giới thiệu mặc định (không có khung trượt) | |
| BN-02 | Admin → **Banner trang chủ** → **Thêm banner**: chọn ảnh ngang (1920×600), tiêu đề, mô tả, đường dẫn `/khoa-hoc`, chữ nút "Xem khoá học", tick Bật hiển thị | Báo "Đã thêm banner"; danh sách có ảnh nhỏ, nhãn xanh **Đang hiển thị** | |
| BN-03 | Mở trang chủ | Banner hiện trên cùng: ảnh, tiêu đề, mô tả, nút vàng; bấm nút sang `/khoa-hoc` ở cùng tab | |
| BN-04 | Thêm banner thứ hai, đường dẫn `https://ut.edu.vn` | Trang chủ tự chuyển banner sau 6 giây; có nút ‹ › và chấm tròn; rê chuột lên thì dừng; điện thoại vuốt trái/phải được; nút banner này mở tab mới | |
| BN-05 | Đổi **Thứ tự** (số nhỏ hiện trước) | Thứ tự trên trang chủ đổi theo | |
| BN-06 | Bỏ tick Bật hiển thị | Banner biến mất khỏi trang chủ; danh sách ghi **Đang tắt** | |
| BN-07 | Đặt "Hiện từ" là ngày mai | Không hiện trên trang chủ; danh sách ghi **Chờ đến ngày** | |
| BN-08 | Đặt "Đến hết" là hôm qua | Không hiện; danh sách ghi **Đã hết hạn** | |
| BN-09 | "Đến hết" trước "Hiện từ" | Báo "Ngày kết thúc phải sau ngày bắt đầu" | |
| BN-10 | Đường dẫn `javascript:alert(1)` hoặc `khoa-hoc` (thiếu `/`) | Báo lỗi đường dẫn, không lưu | |
| BN-11 | Thêm banner không chọn ảnh; chọn file PDF hoặc ảnh > 4 MB | Báo lỗi ảnh | |
| BN-12 | Sửa banner không chọn ảnh mới | Ảnh cũ giữ nguyên; chọn ảnh mới thì ảnh cũ bị xoá | |
| BN-13 | Xoá banner | Banner và ảnh bị xoá; **Ảnh đã lưu** không còn ảnh đó | |
| BN-14 | **Ảnh đã lưu**, lọc thư mục "Banner trang chủ" | Thấy ảnh banner, ghi đang dùng ở `Banner "..."` | |
| BN-15 | Giảng viên/học viên mở `/admin/banners` | 403 | |
| BN-16 | Xem trang chủ trên điện thoại (hoặc DevTools chế độ mobile) | Banner không tràn ngang, chữ đọc được, nút bấm được | |

## 8. Lưu ảnh và gửi email trên Render

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| RD-01 | Trên Render (có `CLOUDINARY_URL`), đổi ảnh đại diện | Link ảnh bắt đầu bằng `https://res.cloudinary.com/` | |
| RD-02 | Render deploy lại (Manual Deploy) rồi mở lại trang | Ảnh và tài liệu đính kèm vẫn còn | |
| RD-03 | Có `RESEND_API_KEY`, `MAIL_MAILER=resend`: gửi quên mật khẩu tới email đã đăng ký Resend (hoặc email bất kỳ nếu đã xác minh tên miền) | Email đến hộp thư (xem cả mục Spam) | |
| RD-04 | Chưa đặt `RESEND_API_KEY` | Các chức năng gửi mail báo chưa gửi được, không lỗi 500 | |
| RD-05 | Sau khi deploy bản mới | Render chạy `migrate` tự động; dữ liệu cũ (người dùng, khoá học) vẫn còn | |

## 9. Bảo mật

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| BM-01 | Trong CKEditor (chế độ mã nguồn hoặc dán HTML) chèn `<script>`, `<img onerror=…>`, link `javascript:` rồi lưu, xem trang công khai | Không có mã nào chạy; phần nguy hiểm bị lọc bỏ | |
| BM-02 | Nhập `<b>đậm</b>` vào nhận xét đánh giá, câu hỏi | Hiện nguyên chữ `<b>đậm</b>`, không in đậm | |
| BM-03 | Gửi form bằng công cụ khác (Postman) mà không có token CSRF | Lỗi 419 | |
| BM-04 | Đăng ký với `account_type=admin` (sửa HTML bằng DevTools) | Báo lỗi loại tài khoản không hợp lệ, không tạo tài khoản | |
| BM-05 | Tài khoản Google có email trùng email **admin** | Không đăng nhập được vào admin; báo phải dùng email + mật khẩu | |
| BM-06 | Học viên gửi `PATCH /admin/users/{id}/lock` (sửa form bằng DevTools) | 403 | |

## 10. Chứng chỉ hoàn thành khoá học

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| CC-01 | Học viên ghi danh một khoá miễn phí, bấm **Đánh dấu hoàn thành** đến bài cuối | Chuyển sang trang `/chung-chi/UTH-XXXX-XXXX`: dải xanh "Chứng chỉ hợp lệ", bản xem trước có tên học viên, tên khoá, ngày cấp, giảng viên | |
| CC-02 | Bấm chuông thông báo | Có "Chúc mừng! Bạn đã nhận chứng chỉ", bấm vào mở đúng trang chứng chỉ | |
| CC-03 | Bấm **Tải chứng chỉ (PDF)** | Tải file `chung-chi-UTH-….pdf`, khổ A4 ngang, tiếng Việt có dấu hiển thị đúng, cuối trang có mã và link xác minh | |
| CC-04 | Bỏ đánh dấu một bài rồi đánh dấu lại | Không cấp chứng chỉ thứ hai, mã chứng chỉ giữ nguyên | |
| CC-05 | Menu tài khoản → **Chứng chỉ của tôi** | Liệt kê chứng chỉ, có nút Tải PDF và Xem và chia sẻ | |
| CC-06 | Góc học tập, khoá đã 100% | Có nút "Chứng chỉ" dưới nút Ôn tập; trang học bài có dải vàng "Bạn đã hoàn thành khoá học này" | |
| CC-07 | Mở link chứng chỉ ở cửa sổ ẩn danh (chưa đăng nhập) | Xem được trang xác minh nhưng **không** có nút tải PDF | |
| CC-08 | Chân trang → **Xác minh chứng chỉ**, nhập mã đúng (gõ chữ thường, có dấu cách) | Chuyển tới trang chứng chỉ đó | |
| CC-09 | Nhập mã sai `UTH-0000-0000` | Báo "Không tìm thấy chứng chỉ có mã …" | |
| CC-10 | Người khác (đã đăng nhập) mở `/chung-chi/{mã}/tai-ve` của học viên khác | 403; admin mở thì tải được | |
| CC-11 | Tài khoản đã hoàn thành khoá **trước khi có tính năng này** (dữ liệu mẫu) mở **Chứng chỉ của tôi** | Chứng chỉ được cấp bù cho các khoá đã 100% | |
| CC-12 | Giảng viên tự học thử khoá của mình đến hết | Không cấp chứng chỉ (chỉ cấp cho người ghi danh) | |

## 11. Chuông thông báo trong web

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| CH-01 | Đăng nhập bất kỳ, nhìn thanh trên cùng (máy tính và điện thoại) | Có biểu tượng chuông cạnh tên tài khoản; trên điện thoại chuông nằm cạnh nút menu | |
| CH-02 | Giảng viên bấm **Gửi duyệt** một khoá | Admin thấy số đỏ trên chuông, mục "Khoá học mới chờ duyệt"; bấm vào mở trang Duyệt khoá học lọc "Chờ duyệt" | |
| CH-02b | Giảng viên sửa một khoá **đã xuất bản** (khoá tự về "Chờ duyệt") | Admin có "Khoá học đã sửa, chờ duyệt lại" | |
| CH-03 | Admin duyệt khoá đó | Giảng viên có thông báo "Khoá học đã được duyệt" (vẫn nhận email như cũ) | |
| CH-04 | Admin từ chối một khoá kèm lý do | Giảng viên có "Khoá học chưa được duyệt" kèm lý do; bấm vào mở trang sửa khoá | |
| CH-05 | Học viên ghi danh khoá miễn phí của giảng viên | Giảng viên có "Có học viên mới"; bấm vào mở danh sách học viên của khoá | |
| CH-06 | Học viên đặt câu hỏi dưới bài học | Giảng viên có "… vừa đặt câu hỏi"; bấm vào nhảy tới đúng câu hỏi | |
| CH-07 | Giảng viên trả lời | Học viên có "Câu hỏi của bạn đã có trả lời" | |
| CH-08 | Admin duyệt / từ chối yêu cầu làm giảng viên | Người gửi yêu cầu có thông báo tương ứng | |
| CH-09 | Bấm một thông báo chưa đọc | Mở đúng trang; quay lại thì thông báo đó hết nền xanh, số đỏ giảm 1 | |
| CH-10 | Bấm **Đánh dấu đã đọc hết** | Số đỏ biến mất | |
| CH-11 | Bấm **Xem tất cả** | Trang `/thong-bao-cua-toi` liệt kê mọi thông báo, có phân trang | |
| CH-12 | Admin / giảng viên ở khu quản trị | Chuông nằm trên thanh tiêu đề, cạnh ảnh đại diện | |
| CH-13 | Cấu hình mail sai rồi duyệt một khoá | Duyệt vẫn thành công, thông báo trong chuông vẫn có (chỉ email không gửi được) | |

## 12. Mua khoá học trả phí (VNPay / MoMo)

Cần điền khoá sandbox trong `.env` trước, xem [thanh-toan.md](thanh-toan.md). Thanh toán trên máy
(`localhost`) vẫn chạy được vì trình duyệt tự quay về; riêng IPN (cổng gọi ngầm về server) chỉ tới
được web thật trên Render.

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| TT-01 | Học viên mở một khoá có giá (ví dụ 299.000 đ) | Nút **Mua khoá học**, dòng "Thanh toán qua VNPay hoặc ví MoMo" | |
| TT-02 | Bấm **Mua khoá học** | Trang thanh toán: thông tin khoá, giá, chỉ hiện các cổng đã điền khoá trong `.env` | |
| TT-03 | Chưa điền khoá cổng nào | Trang thanh toán báo "Trang web chưa bật cổng thanh toán nào" | |
| TT-04 | Chọn **VNPay**, bấm Thanh toán | Sang `sandbox.vnpayment.vn`, số tiền đúng | |
| TT-05 | Ở VNPay chọn ngân hàng NCB, nhập thẻ test (xem thanh-toan.md), OTP `123456` | Quay về trang "Thanh toán thành công"; khoá vào Góc học tập; chuông có "Thanh toán thành công" | |
| TT-06 | Giảng viên của khoá đó xem chuông | Có "Có học viên mua khoá học" | |
| TT-07 | Thanh toán VNPay rồi bấm **Huỷ** ở trang VNPay | Trang "Thanh toán không thành công: Bạn đã huỷ giao dịch."; chưa được ghi danh; có nút "Thử thanh toán lại" | |
| TT-08 | Chọn **Ví MoMo**, bấm Thanh toán | Sang trang MoMo test có mã QR / form thẻ | |
| TT-09 | Thanh toán MoMo bằng app MoMo test hoặc thẻ ATM test | Quay về "Thanh toán thành công", được ghi danh | |
| TT-10 | Ở trang MoMo bấm huỷ | "Thanh toán không thành công", chưa ghi danh | |
| TT-11 | Sau khi trả tiền, ở chi tiết khoá học | Không còn nút "Huỷ ghi danh", thay bằng "Bạn đã mua khoá học này" | |
| TT-12 | Học viên chưa mua gửi form ghi danh khoá trả phí (DevTools) | Bị chuyển về trang thanh toán, không được ghi danh | |
| TT-13 | Sửa số tiền trên URL trả về (`vnp_Amount`) rồi mở lại | Báo "Kết quả thanh toán không hợp lệ (sai chữ ký)", đơn không đổi | |
| TT-14 | Menu tài khoản → **Lịch sử thanh toán** | Có các đơn kèm trạng thái; bấm vào xem chi tiết (mã đơn, mã giao dịch) | |
| TT-15 | Học viên khác mở `/don-hang/{id}` của người khác | 404 | |
| TT-16 | Admin → **Đơn hàng** | Thẻ doanh thu, doanh thu tháng này, số đơn; lọc theo trạng thái, cổng; tìm theo mã đơn / email | |
| TT-17 | Giảng viên hoặc admin mở khoá trả phí của chính khoá mình | Vào học không cần trả tiền | |
| TT-18 | `PAYMENT_DEMO=true` trong `.env` | Có thêm "Thanh toán thử (demo)": bấm là thành công ngay (chỉ dùng khi chưa có khoá sandbox) | |

## 13. SEO và chia sẻ link

| Mã | Bước thực hiện | Kết quả mong đợi | KQ |
| --- | --- | --- | --- |
| SEO-01 | Mở chi tiết khoá học, **Xem nguồn trang** (Ctrl+U) | Có `<title>Tên khoá | UTH E-Learning</title>`, `meta description` = mô tả ngắn, các thẻ `og:` và khối `application/ld+json` kiểu Course | |
| SEO-02 | Dán link khoá học vào Facebook / Zalo / Messenger (trên web thật) | Thẻ xem trước có ảnh bìa khoá học, tên và mô tả | |
| SEO-03 | Dán link trang chủ | Ảnh xem trước là ảnh mặc định `public/images/og-default.png` | |
| SEO-04 | Mở `/sitemap.xml` | Danh sách trang chủ, thư viện, các khoá **đã xuất bản**, thông báo, hồ sơ giảng viên; không có khoá nháp | |
| SEO-05 | Mở `/robots.txt` | Chặn `/admin`, `/instructor`, `/hoc/`, `/thanh-toan/`… | |
| SEO-06 | Xem nguồn Góc học tập / trang học bài / trang quản trị | Có `<meta name="robots" content="noindex, nofollow">` | |
| SEO-07 | Kiểm tra link khoá học bằng [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) và [Rich Results Test](https://search.google.com/test/rich-results) | Không báo lỗi thiếu thẻ | |

---

Khi một bước lỗi, ghi lại: mã bước, tài khoản đang dùng, đường dẫn, ảnh chụp màn hình và nội dung
`storage/logs/laravel.log` (nếu có) để sửa nhanh.
