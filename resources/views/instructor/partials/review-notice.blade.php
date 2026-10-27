@if ($course->isPublished())
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        <x-icon name="eye-slash" class="mt-0.5 h-5 w-5 shrink-0" />
        <p>Khoá học đang xuất bản. Khi bạn lưu thay đổi thông tin khoá học, bài học hoặc tài liệu, khoá học sẽ chuyển về <strong>Chờ duyệt</strong> và tạm ẩn khỏi danh sách cho đến khi quản trị viên duyệt lại. Học viên đã ghi danh vẫn học được.</p>
    </div>
@endif
