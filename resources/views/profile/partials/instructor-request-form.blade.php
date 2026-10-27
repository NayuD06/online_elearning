<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">Trở thành giảng viên</h2>
        <p class="mt-1 text-sm text-gray-600">Giảng viên được tạo khoá học, chương, bài học, bài kiểm tra và trả lời câu hỏi của học viên. Quản trị viên sẽ duyệt yêu cầu của bạn.</p>
    </header>

    @if ($user->hasPendingInstructorRequest())
        <p class="mt-4 flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <x-icon name="clock" class="h-5 w-5 shrink-0" />
            Yêu cầu gửi lúc {{ $user->instructor_requested_at?->format('H:i d/m/Y') }} đang chờ quản trị viên duyệt.
        </p>
    @else
        @if ($user->instructor_status === 'rejected')
            <p class="mt-4 rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-800">Yêu cầu trước đó chưa được chấp nhận. Bạn có thể bổ sung phần giới thiệu bản thân rồi gửi lại.</p>
        @endif
        <form method="POST" action="{{ route('profile.instructor-request') }}" class="mt-4">
            @csrf
            <button class="btn-primary"><x-icon name="users" class="h-4 w-4" /> Gửi yêu cầu làm giảng viên</button>
        </form>
    @endif
</section>
