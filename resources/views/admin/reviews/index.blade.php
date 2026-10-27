<x-admin-layout title="Đánh giá">
    <div class="card">
        <div class="border-b border-gray-100 p-4">
            <x-search-bar placeholder="Tìm theo nội dung hoặc tên khoá học...">
                <select name="rating" class="form-input mt-0 sm:w-36">
                    <option value="">Mọi số sao</option>
                    @for ($star = 5; $star >= 1; $star--)
                        <option value="{{ $star }}" @selected(request()->integer('rating') === $star)>{{ $star }} sao</option>
                    @endfor
                </select>
            </x-search-bar>
        </div>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>Học viên</th><th>Khoá học</th><th>Đánh giá</th><th>Nhận xét</th><th>Trạng thái</th><th class="text-right">Thao tác</th></tr>
                </thead>
                <tbody>
                    @forelse ($reviews as $review)
                        <tr>
                            <td class="whitespace-nowrap font-medium text-gray-900">{{ $review->user->name }}</td>
                            <td class="max-w-[14rem] truncate">{{ $review->course->title }}</td>
                            <td><x-stars :rating="$review->rating" /></td>
                            <td class="max-w-sm text-gray-600">{{ Str::limit($review->comment, 100) ?: '—' }}</td>
                            <td>
                                @if ($review->is_visible)
                                    <span class="badge bg-emerald-100 text-emerald-700">Hiển thị</span>
                                @else
                                    <span class="badge bg-gray-100 text-gray-600">Đã ẩn</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-right">
                                <form method="POST" action="{{ route('admin.reviews.update', $review) }}" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn-ghost btn-sm" title="{{ $review->is_visible ? 'Ẩn' : 'Hiện' }}">
                                        <x-icon :name="$review->is_visible ? 'eye-slash' : 'eye'" class="h-4 w-4" />
                                    </button>
                                </form>
                                <x-delete-button :action="route('admin.reviews.destroy', $review)" />
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty-state icon="chat" title="Chưa có đánh giá" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $reviews->links() }}</div>
    </div>
</x-admin-layout>
