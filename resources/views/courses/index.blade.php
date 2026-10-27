<x-app-layout title="Khoá học">
    <section class="bg-brand-700 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="text-sm text-brand-100"><a href="{{ route('home') }}" class="hover:text-white">Trang chủ</a> / Khoá học</nav>
            <h1 class="mt-2 text-3xl font-bold">Thư viện khoá học</h1>
            <p class="mt-1 text-brand-100">Tìm thấy {{ $courses->total() }} khoá học phù hợp</p>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-4 lg:px-8">
        {{-- Filters --}}
        <aside x-data="{ open: false }" class="lg:col-span-1">
            <button @click="open = ! open" class="btn-secondary w-full lg:hidden">
                <x-icon name="list" class="h-4 w-4" /> Bộ lọc
            </button>

            <form method="GET" action="{{ route('courses.index') }}" :class="open ? 'block' : 'hidden'" class="card mt-3 space-y-5 p-5 lg:mt-0 lg:block">
                <div>
                    <label for="q" class="form-label">Từ khoá</label>
                    <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Tên khoá học..." class="form-input">
                </div>

                <div>
                    <label for="category" class="form-label">Danh mục</label>
                    <select id="category" name="category" class="form-input">
                        <option value="">Tất cả danh mục</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <p class="form-label">Trình độ</p>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="radio" name="level" value="" class="text-brand-600 focus:ring-brand-500" @checked(! request('level'))> Tất cả
                        </label>
                        @foreach ($levels as $level)
                            <label class="flex items-center gap-2 text-sm">
                                <input type="radio" name="level" value="{{ $level->value }}" class="text-brand-600 focus:ring-brand-500" @checked(request('level') === $level->value)> {{ $level->label() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <label for="price" class="form-label">Học phí</label>
                    <select id="price" name="price" class="form-input">
                        <option value="">Tất cả</option>
                        <option value="free" @selected(request('price') === 'free')>Miễn phí</option>
                        <option value="paid" @selected(request('price') === 'paid')>Có phí</option>
                    </select>
                </div>

                <div>
                    <label for="sort" class="form-label">Sắp xếp</label>
                    <select id="sort" name="sort" class="form-input">
                        <option value="newest" @selected(request('sort', 'newest') === 'newest')>Mới nhất</option>
                        <option value="popular" @selected(request('sort') === 'popular')>Phổ biến nhất</option>
                        <option value="price_asc" @selected(request('sort') === 'price_asc')>Học phí tăng dần</option>
                        <option value="price_desc" @selected(request('sort') === 'price_desc')>Học phí giảm dần</option>
                    </select>
                </div>

                <div class="flex gap-2">
                    <button class="btn-primary flex-1">Lọc</button>
                    <a href="{{ route('courses.index') }}" class="btn-secondary">Xoá lọc</a>
                </div>
            </form>
        </aside>

        {{-- Results --}}
        <div class="lg:col-span-3">
            @if ($courses->isEmpty())
                <div class="card">
                    <x-empty-state icon="search" title="Không tìm thấy khoá học">Hãy thử thay đổi từ khoá hoặc bộ lọc.</x-empty-state>
                </div>
            @else
                <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($courses as $course)
                        <x-course-card :course="$course" />
                    @endforeach
                </div>

                <div class="mt-8">{{ $courses->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
