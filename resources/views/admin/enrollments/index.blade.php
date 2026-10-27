<x-admin-layout title="Ghi danh">
    <div class="card">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo học viên, email hoặc khoá học..." />
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Học viên</th><th>Khoá học</th><th>Ngày ghi danh</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($enrollments as $enrollment)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-900">{{ $enrollment->user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $enrollment->user->email }}</p>
                            </td>
                            <td class="max-w-xs truncate">{{ $enrollment->course->title }}</td>
                            <td class="whitespace-nowrap">{{ $enrollment->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if ($enrollment->completed_at)
                                    <span class="badge bg-emerald-100 text-emerald-700">Hoàn thành {{ $enrollment->completed_at->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge bg-sky-100 text-sky-700">Đang học</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <x-delete-button :action="route('admin.enrollments.destroy', $enrollment)" confirm="Huỷ ghi danh của học viên này?" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="clipboard" title="Chưa có lượt ghi danh" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $enrollments->links() }}</div>
    </div>
</x-admin-layout>
