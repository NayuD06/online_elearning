<x-admin-layout :title="$banner->exists ? 'Sửa banner' : 'Thêm banner'">
    <a href="{{ route('admin.banners.index') }}" class="mb-4 flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700"><x-icon name="chevron-left" class="h-4 w-4" /> Banner trang chủ</a>

    <form method="POST" enctype="multipart/form-data" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" class="grid gap-6 xl:grid-cols-3">
        @csrf
        @if ($banner->exists) @method('PUT') @endif

        <div class="min-w-0 space-y-6 xl:col-span-2">
            <div class="card p-6" x-data="imagePreview">
                <h2 class="font-semibold text-gray-900">Ảnh banner @unless ($banner->exists) <span class="text-rose-500">*</span> @endunless</h2>
                <p class="mt-1 text-xs text-gray-500">Ảnh ngang, nên dùng 1920 × 600 px (JPG, PNG hoặc WebP, tối đa 4 MB). Phần giữa ảnh luôn được giữ lại trên điện thoại.</p>
                <template x-if="previews.length">
                    <img :src="previews[0].url" alt="" class="mt-3 aspect-[16/5] w-full rounded-lg object-cover">
                </template>
                @if ($banner->image_url)
                    <img x-show="! previews.length" src="{{ $banner->image_url }}" alt="" class="mt-3 aspect-[16/5] w-full rounded-lg object-cover">
                @endif
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" @change="pick($event)" @required(! $banner->exists)
                       class="mt-3 block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                @error('image') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="card space-y-5 p-6">
                <div>
                    <label for="title" class="form-label">Tiêu đề <span class="text-rose-500">*</span></label>
                    <input id="title" name="title" value="{{ old('title', $banner->title) }}" required maxlength="255" class="form-input">
                    @error('title') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="subtitle" class="form-label">Mô tả ngắn</label>
                    <textarea id="subtitle" name="subtitle" rows="2" maxlength="500" class="form-input">{{ old('subtitle', $banner->subtitle) }}</textarea>
                    @error('subtitle') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid gap-5 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <label for="link_url" class="form-label">Đường dẫn khi bấm</label>
                        <input id="link_url" name="link_url" value="{{ old('link_url', $banner->link_url) }}" placeholder="/khoa-hoc?category=lap-trinh hoặc https://..." class="form-input">
                        <p class="mt-1 text-xs text-gray-500">Trang trong web bắt đầu bằng <code>/</code>; trang ngoài mở ở tab mới. Để trống nếu không cần nút.</p>
                        @error('link_url') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="button_label" class="form-label">Chữ trên nút</label>
                        <input id="button_label" name="button_label" value="{{ old('button_label', $banner->button_label) }}" placeholder="Xem ngay" maxlength="50" class="form-input">
                        @error('button_label') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="card space-y-5 p-6">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active)) class="rounded text-brand-600 focus:ring-brand-500">
                    Bật hiển thị trên trang chủ
                </label>
                <div>
                    <label for="position" class="form-label">Thứ tự</label>
                    <input id="position" type="number" min="0" name="position" value="{{ old('position', $banner->position) }}" class="form-input">
                    <p class="mt-1 text-xs text-gray-500">Số nhỏ hiện trước.</p>
                    @error('position') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="starts_at" class="form-label">Hiện từ</label>
                    <input id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at', $banner->starts_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                    @error('starts_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="ends_at" class="form-label">Đến hết</label>
                    <input id="ends_at" type="datetime-local" name="ends_at" value="{{ old('ends_at', $banner->ends_at?->format('Y-m-d\TH:i')) }}" class="form-input">
                    <p class="mt-1 text-xs text-gray-500">Để trống hai ô này thì banner hiện liên tục.</p>
                    @error('ends_at') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="card flex gap-2 p-6">
                <button class="btn-primary flex-1">{{ $banner->exists ? 'Lưu thay đổi' : 'Thêm banner' }}</button>
                <a href="{{ route('admin.banners.index') }}" class="btn-secondary">Huỷ</a>
            </div>
        </div>
    </form>
</x-admin-layout>
