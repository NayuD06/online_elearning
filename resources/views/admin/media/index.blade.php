<x-admin-layout title="Ảnh đã lưu">
    @if ($onCloudinary)
        <div class="mb-4 flex flex-col gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800 sm:flex-row sm:items-center sm:justify-between">
            <p>
                <x-icon name="check-circle" class="mr-1 inline h-5 w-5" />
                Ảnh đang lưu trên <strong>Cloudinary</strong> (cloud <strong>{{ $cloudName }}</strong>, thư mục <strong>{{ $cloudFolder }}</strong>), không bị mất khi deploy lại.
            </p>
            <a href="https://console.cloudinary.com/console/media_library" target="_blank" rel="noopener" class="btn-secondary btn-sm shrink-0">
                <x-icon name="external" class="h-4 w-4" /> Mở Cloudinary
            </a>
        </div>
    @else
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            Ảnh đang lưu trong máy chủ (<code>storage/app/public</code>). Trên Render, ảnh sẽ mất mỗi lần deploy lại.
            Đặt biến <code>CLOUDINARY_URL</code> dạng <code>cloudinary://...</code> để lưu trên Cloudinary (xem <code>docs/luu-anh-va-gui-email.md</code>).
        </div>
    @endif

    @if ($error)
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            Không đọc được danh sách ảnh: {{ $error }}
        </div>
    @endif

    <div class="mb-4 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['photo', $stats['count'], 'Tổng số ảnh'],
            ['squares', \Illuminate\Support\Number::fileSize($stats['size'], 1), 'Dung lượng'],
            ['eye-slash', $stats['unused'], 'Ảnh không còn dùng'],
        ] as [$icon, $value, $label])
            <div class="card flex items-center gap-4 p-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><x-icon :name="$icon" class="h-6 w-6" /></div>
                <div>
                    <p class="text-xl font-bold text-gray-900">{{ $value }}</p>
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="card">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo tên file, tên khoá học, người dùng...">
                <select name="folder" class="form-input mt-0 sm:w-48">
                    <option value="">Tất cả loại ảnh</option>
                    @foreach ($folders as $value => $label)
                        <option value="{{ $value }}" @selected(request('folder') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 whitespace-nowrap text-sm text-gray-600">
                    <input type="checkbox" name="unused" value="1" class="rounded border-gray-300 text-brand-600" @checked(request()->boolean('unused'))>
                    Chỉ ảnh không còn dùng
                </label>
            </x-search-bar>
        </div>

        @if ($images->isEmpty())
            <x-empty-state icon="photo" title="Chưa có ảnh nào">Ảnh upload ở avatar, khoá học, bài viết và thông báo sẽ hiện ở đây.</x-empty-state>
        @else
            <div class="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($images as $image)
                    <div x-data="{ copied: false }" class="flex flex-col overflow-hidden rounded-lg border border-gray-200">
                        <a href="{{ $image['url'] }}" target="_blank" rel="noopener" class="block bg-gray-100">
                            <img src="{{ $image['url'] }}" alt="{{ $image['name'] }}" loading="lazy" class="h-40 w-full object-cover">
                        </a>
                        <div class="flex flex-1 flex-col gap-2 p-3 text-sm">
                            <p class="truncate font-medium text-gray-900" title="{{ $image['path'] }}">{{ $image['path'] }}</p>
                            <p class="text-xs text-gray-500">
                                {{ $folders[$image['folder']] ?? 'Khác' }}
                                · {{ $image['size'] !== null ? \Illuminate\Support\Number::fileSize($image['size'], 1) : '—' }}
                                · {{ $image['modified'] ? \Illuminate\Support\Carbon::createFromTimestamp($image['modified'], config('app.timezone'))->format('d/m/Y H:i') : '—' }}
                            </p>
                            <div class="flex-1 space-y-1">
                                @forelse ($image['usages'] as $usage)
                                    <a href="{{ $usage['url'] }}" class="block truncate text-xs text-brand-700 hover:underline" title="{{ $usage['label'] }}">{{ $usage['label'] }}</a>
                                @empty
                                    <span class="badge bg-gray-100 text-gray-600">Không còn dùng</span>
                                @endforelse
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ $image['url'] }}" target="_blank" rel="noopener" class="btn-secondary btn-sm flex-1"><x-icon name="eye" class="h-4 w-4" /> Xem</a>
                                <button type="button" class="btn-secondary btn-sm flex-1"
                                        @click="navigator.clipboard.writeText(@js($image['url'])); copied = true; setTimeout(() => copied = false, 1500)">
                                    <x-icon name="clipboard" class="h-4 w-4" /> <span x-text="copied ? 'Đã copy' : 'Copy link'">Copy link</span>
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="p-4">{{ $images->links() }}</div>
    </div>
</x-admin-layout>
