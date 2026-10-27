<x-admin-layout :title="'Học viên: '.$course->title">
    <a href="{{ route('instructor.courses.index') }}" class="mb-4 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> Khoá học của tôi</a>

    <div class="card">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo tên hoặc email học viên..." />
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Học viên</th><th>Ngày ghi danh</th><th class="w-64">Tiến độ</th><th class="text-center">Lượt làm quiz</th><th>Trạng thái</th></tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $enrollment->user->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $enrollment->user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap">{{ $enrollment->created_at->format('d/m/Y') }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-gray-200">
                                        <div class="h-full rounded-full bg-brand-600" style="width: {{ $enrollment->progress }}%"></div>
                                    </div>
                                    <span class="w-10 text-right text-sm font-semibold text-gray-700">{{ $enrollment->progress }}%</span>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ $enrollment->completed_lessons }} / {{ $totalLessons }} bài</p>
                            </td>
                            <td class="text-center">{{ $enrollment->quiz_attempts }}</td>
                            <td>
                                @if ($enrollment->completed_at)
                                    <span class="badge bg-emerald-100 text-emerald-700">Hoàn thành {{ $enrollment->completed_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-sky-100 text-sky-700">Đang học</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="users" title="Chưa có học viên ghi danh" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $enrollments->links() }}</div>
    </div>
</x-admin-layout>
