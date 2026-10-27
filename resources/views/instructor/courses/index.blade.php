<x-admin-layout title="Khoá học của tôi">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm theo tên khoá học..." class="flex-1">
                <select name="status" class="form-input mt-0 sm:w-40">
                    <option value="">Mọi trạng thái</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </x-search-bar>
            <a href="{{ route('instructor.courses.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm khoá học</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Khoá học</th><th>Nội dung</th><th class="text-center">Học viên</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($courses as $course)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-course-thumbnail :course="$course" compact class="h-12 w-20 shrink-0 rounded-md" />
                                    <div class="min-w-0">
                                        <p class="max-w-xs truncate font-medium text-gray-900">{{ $course->title }}</p>
                                        <p class="text-xs text-gray-500">{{ $course->category?->name ?? 'Chưa phân loại' }} · {{ $course->level->label() }} · {{ $course->price_label }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap text-sm">{{ $course->sections_count }} chương · {{ $course->lessons_count }} bài<span class="block text-xs text-gray-500">{{ $course->quizzes_count }} bài kiểm tra</span></td>
                            <td class="text-center">{{ $course->enrollments_count }}</td>
                            <td>
                                <span class="badge {{ $course->status->badgeClasses() }}">{{ $course->status->label() }}</span>
                                @if ($course->status === App\Enums\CourseStatus::Rejected && $course->rejection_reason)
                                    <p class="mt-1 max-w-[12rem] truncate text-xs text-rose-600" title="{{ $course->rejection_reason }}">{{ $course->rejection_reason }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @if ($course->status->canBeSubmitted())
                                    <form method="POST" action="{{ route('instructor.courses.submit', $course) }}" class="inline">
                                        @csrf
                                        <button class="btn-ghost btn-sm text-brand-700" title="Gửi duyệt"><x-icon name="check-circle" class="h-4 w-4" /></button>
                                    </form>
                                @endif
                                <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn-ghost btn-sm" title="Xem trang khoá học"><x-icon name="eye" class="h-4 w-4" /></a>
                                <a href="{{ route('instructor.courses.curriculum', $course) }}" class="btn-ghost btn-sm" title="Chương, bài học & quiz"><x-icon name="list" class="h-4 w-4" /></a>
                                <a href="{{ route('instructor.courses.students', $course) }}" class="btn-ghost btn-sm" title="Học viên"><x-icon name="users" class="h-4 w-4" /></a>
                                <a href="{{ route('instructor.courses.edit', $course) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                <x-delete-button :action="route('instructor.courses.destroy', $course)" confirm="Xoá khoá học này cùng toàn bộ chương, bài học, quiz, ghi danh và đánh giá?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="book" title="Bạn chưa có khoá học nào" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $courses->links() }}</div>
    </div>
</x-admin-layout>
