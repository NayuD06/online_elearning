{{-- Rendered by dompdf (A4 landscape). Keep to simple CSS: no flexbox or grid. DejaVu fonts ship with dompdf and cover Vietnamese. --}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Chứng chỉ {{ $certificate->code }}</title>
    <style>
        @page { margin: 0; }
        body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #1f2937; background: #f0fdfa; }
        /* Fixed millimetre boxes: dompdf does not handle height: 100% well. Page is 297 x 210 mm. */
        .frame { position: absolute; top: 8mm; left: 8mm; width: 279mm; height: 192mm; border: 1mm solid #0f766e; background: #ffffff; }
        .inner { position: absolute; top: 11mm; left: 11mm; width: 273mm; height: 186mm; border: 0.3mm solid #5eead4; }
        .content { position: absolute; top: 24mm; left: 20mm; width: 257mm; text-align: center; }
        .brand { font-size: 13pt; font-weight: bold; letter-spacing: 3px; color: #0f766e; }
        .brand-sub { font-size: 9pt; color: #6b7280; margin-top: 1mm; }
        .title { font-family: "DejaVu Serif", serif; font-size: 34pt; font-weight: bold; color: #134e4a; margin-top: 8mm; letter-spacing: 2px; }
        .subtitle { font-size: 11pt; color: #6b7280; margin-top: 1mm; letter-spacing: 4px; }
        .lead { font-size: 11pt; color: #4b5563; margin-top: 9mm; }
        .name { font-family: "DejaVu Serif", serif; font-size: 28pt; font-weight: bold; color: #0f172a; margin-top: 2mm; }
        .rule { width: 120mm; border-top: 0.3mm solid #99f6e4; margin: 2mm auto 0; }
        .course-lead { font-size: 11pt; color: #4b5563; margin-top: 6mm; }
        .course { font-size: 17pt; font-weight: bold; color: #0f766e; margin-top: 2mm; }
        table.footer { position: absolute; top: 148mm; left: 20mm; width: 257mm; border-collapse: collapse; }
        table.footer td { width: 33%; vertical-align: bottom; text-align: center; font-size: 10pt; color: #374151; }
        .sign-line { border-top: 0.3mm solid #9ca3af; margin: 0 10mm; padding-top: 2mm; }
        .label { font-size: 8.5pt; color: #6b7280; }
        .seal { width: 24mm; height: 15mm; padding-top: 9mm; margin: 0 auto; border: 0.6mm solid #0f766e; border-radius: 12mm; text-align: center; color: #0f766e; font-weight: bold; font-size: 7.5pt; line-height: 1.3; }
        .verify { position: absolute; top: 186mm; left: 20mm; width: 257mm; text-align: center; font-size: 8.5pt; color: #6b7280; }
        .code { font-weight: bold; color: #0f766e; letter-spacing: 1px; }
    </style>
</head>
<body>
    <div class="frame"></div>
    <div class="inner"></div>

    <div class="content">
        <div class="brand">UTH E-LEARNING</div>
        <div class="brand-sub">Hệ thống học trực tuyến</div>

        <div class="title">CHỨNG NHẬN</div>
        <div class="subtitle">HOÀN THÀNH KHOÁ HỌC</div>

        <div class="lead">Chứng nhận học viên</div>
        <div class="name">{{ $certificate->student_name }}</div>
        <div class="rule"></div>

        <div class="course-lead">đã hoàn thành toàn bộ nội dung khoá học</div>
        <div class="course">{{ $certificate->course_title }}</div>
    </div>

    <table class="footer">
        <tr>
            <td>
                <div class="sign-line">
                    {{ $certificate->issued_at->format('d/m/Y') }}
                    <div class="label">Ngày cấp</div>
                </div>
            </td>
            <td>
                <div class="seal">UTH<br>ĐÃ XÁC THỰC</div>
            </td>
            <td>
                <div class="sign-line">
                    {{ $certificate->instructor_name ?? 'UTH E-Learning' }}
                    <div class="label">Giảng viên phụ trách</div>
                </div>
            </td>
        </tr>
    </table>

    <div class="verify">
        Mã chứng chỉ: <span class="code">{{ $certificate->code }}</span>
        &nbsp;·&nbsp; Xác minh tại {{ route('certificates.show', $certificate) }}
    </div>
</body>
</html>
