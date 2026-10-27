<x-admin-layout title="Duyệt khoá học">
    @if ($pendingCount)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <span>Có <strong>{{ $pendingCount }}</strong> khoá học đang chờ duyệt.</span>
            <a href="{{ route('admin.courses.index', ['status' => 'pending']) }}" class="font-semibold underline">Xem danh sách chờ duyệt</a>
        </div>
    @endif

    <div class="card">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo tên khoá học..." class="flex-1">
                <select name="category_id" class="form-input mt-0 sm:w-48">
                    <option value="">Tất cả danh mục</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request()->integer('category_id') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-input mt-0 sm:w-40">
                    <option value="">Mọi trạng thái</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </x-search-bar>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Khoá học</th><th>Giảng viên</th><th class="text-center">Bài học</th><th class="text-center">Học viên</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
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
                            <td class="whitespace-nowrap">{{ $course->instructor->name }}</td>
                            <td class="text-center">{{ $course->lessons_count }}<span class="block text-xs text-gray-400">{{ $course->quizzes_count }} quiz</span></td>
                            <td class="text-center">{{ $course->enrollments_count }}</td>
                            <td>
                                <span class="badge {{ $course->status->badgeClasses() }}">{{ $course->status->label() }}</span>
                                @if ($course->status === App\Enums\CourseStatus::Pending && $course->submitted_at)
                                    <p class="mt-1 text-xs text-gray-500">Gửi {{ $course->submitted_at->diffForHumans() }}</p>
                                @elseif ($course->rejection_reason)
                                    <p class="mt-1 max-w-[10rem] truncate text-xs text-rose-600" title="{{ $course->rejection_reason }}">{{ $course->rejection_reason }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right" x-data="{ rejecting: false }">
                                <a href="{{ route('courses.show', $course) }}" target="_blank" class="btn-ghost btn-sm" title="Xem nội dung khoá học"><x-icon name="eye" class="h-4 w-4" /></a>

                                @if (in_array($course->status, [App\Enums\CourseStatus::Pending, App\Enums\CourseStatus::Hidden], true))
                                    <form method="POST" action="{{ route('admin.courses.approve', $course) }}" class="inline">
                                        @csrf @method('PATCH')
                                        <button class="btn-ghost btn-sm text-emerald-700 hover:bg-emerald-50" title="{{ $course->status === App\Enums\CourseStatus::Hidden ? 'Hiện lại' : 'Duyệt' }}"><x-icon name="check-circle" class="h-4 w-4" /></button>
                                    </form>
                                @endif

                                @if ($course->status === App\Enums\CourseStatus::Pending)
                                    <button type="button" @click="rejecting = ! rejecting" class="btn-ghost btn-sm text-rose-600 hover:bg-rose-50" title="Từ chối"><x-icon name="x" class="h-4 w-4" /></button>
                                @endif

                                @if ($course->status === App\Enums\CourseStatus::Published)
                                    <form method="POST" action="{{ route('admin.courses.hide', $course) }}" class="inline" onsubmit="return confirm('Ẩn khoá học này khỏi trang công khai?')">
                                        @csrf @method('PATCH')
                                        <button class="btn-ghost btn-sm" title="Ẩn"><x-icon name="eye-slash" class="h-4 w-4" /></button>
                                    </form>
                                @endif

                                <x-delete-button :action="route('admin.courses.destroy', $course)" confirm="Xoá khoá học này cùng toàn bộ bài học, ghi danh và đánh giá?" />

                                @if ($course->status === App\Enums\CourseStatus::Pending)
                                    <form x-show="rejecting" x-cloak method="POST" action="{{ route('admin.courses.reject', $course) }}" class="mt-2 flex items-center justify-end gap-2">
                                        @csrf @method('PATCH')
                                        <input name="rejection_reason" required maxlength="1000" placeholder="Lý do từ chối..." class="form-input mt-0 w-56 text-sm">
                                        <button class="btn-danger btn-sm">Từ chối</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="book" title="Không có khoá học nào" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @error('rejection_reason') <p class="form-error px-4">{{ $message }}</p> @enderror
        <div class="p-4">{{ $courses->links() }}</div>
    </div>
</x-admin-layout>
