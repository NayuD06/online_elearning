<x-admin-layout title="Banner trang chủ">
    <div class="card">
        <div class="flex flex-col gap-3 border-b border-gray-100 p-4 lg:flex-row lg:items-center lg:justify-between">
            <p class="text-sm text-gray-600">
                Đang hiển thị <strong class="text-gray-900">{{ $showingCount }}</strong> banner trên trang chủ, xếp theo cột thứ tự.
                Không có banner nào thì trang chủ giữ phần giới thiệu mặc định.
            </p>
            <div class="flex shrink-0 gap-2">
                <a href="{{ route('home') }}" target="_blank" class="btn-secondary"><x-icon name="eye" class="h-4 w-4" /> Xem trang chủ</a>
                <a href="{{ route('admin.banners.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" /> Thêm banner</a>
            </div>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th class="w-16 text-center">Thứ tự</th><th>Banner</th><th>Đường dẫn</th><th>Thời gian</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($banners as $banner)
                        <tr>
                            <td class="text-center font-semibold text-brand-700">{{ $banner->position }}</td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <img src="{{ $banner->image_url }}" alt="" class="h-12 w-28 shrink-0 rounded object-cover">
                                    <span class="min-w-0">
                                        <span class="block max-w-xs truncate font-medium text-gray-900">{{ $banner->title }}</span>
                                        @if ($banner->subtitle)
                                            <span class="block max-w-xs truncate text-xs text-gray-500">{{ $banner->subtitle }}</span>
                                        @endif
                                    </span>
                                </div>
                            </td>
                            <td class="max-w-[14rem] truncate text-gray-500">{{ $banner->link_url ?: '—' }}</td>
                            <td class="whitespace-nowrap text-xs text-gray-600">
                                @if ($banner->starts_at || $banner->ends_at)
                                    {{ $banner->starts_at?->format('d/m/Y H:i') ?? '…' }} → {{ $banner->ends_at?->format('d/m/Y H:i') ?? '…' }}
                                @else
                                    Luôn hiển thị
                                @endif
                            </td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-emerald-100 text-emerald-700' => $banner->status_label === 'Đang hiển thị',
                                    'bg-amber-100 text-amber-700' => $banner->status_label === 'Chờ đến ngày',
                                    'bg-gray-100 text-gray-600' => in_array($banner->status_label, ['Đang tắt', 'Đã hết hạn'], true),
                                ])>{{ $banner->status_label }}</span>
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="btn-ghost btn-sm" title="Sửa"><x-icon name="pencil" class="h-4 w-4" /></a>
                                <x-delete-button :action="route('admin.banners.destroy', $banner)" confirm="Xoá banner này? Ảnh banner cũng bị xoá." />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="photo" title="Chưa có banner" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $banners->links() }}</div>
    </div>
</x-admin-layout>
