<x-admin-layout title="Thông báo">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <x-search-bar placeholder="Tìm theo tiêu đề..." class="flex-1" />
            <a href="{{ route('admin.announcements.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Viết thông báo</a>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Tiêu đề</th><th>Người đăng</th><th>Ngày đăng</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($announcements as $announcement)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    @if ($announcement->image_url)
                                        <img src="{{ $announcement->image_url }}" alt="" class="h-10 w-16 rounded object-cover">
                                    @endif
                                    <span class="max-w-md truncate font-medium text-gray-900">{{ $announcement->title }}</span>
                                </div>
                            </td>
                            <td>{{ $announcement->author->name }}</td>
                            <td class="whitespace-nowrap">{{ $announcement->published_at?->format('d/m/Y H:i') ?? '—' }}</td>
                            <td>
                                @if ($announcement->is_published)
                                    <span class="badge bg-emerald-100 text-emerald-700">Đã đăng</span>
                                @else
                                    <span class="badge bg-gray-100 text-gray-600">Nháp</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                @if ($announcement->is_published)
                                    <a href="{{ route('announcements.show', $announcement) }}" target="_blank" class="btn-ghost btn-sm" title="Xem"><x-icon name="eye" class="h-4 w-4" /></a>
                                @endif
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                <x-delete-button :action="route('admin.announcements.destroy', $announcement)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5"><x-empty-state icon="bell" title="Chưa có thông báo" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $announcements->links() }}</div>
    </div>
</x-admin-layout>
