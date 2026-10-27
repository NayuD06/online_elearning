<x-app-layout title="Thông báo">
    <section class="bg-brand-700 text-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <nav class="text-sm text-brand-100"><a href="{{ route('home') }}" class="hover:text-white">Trang chủ</a> / Thông báo</nav>
            <h1 class="mt-2 text-3xl font-bold">Tin tức & Thông báo</h1>
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if ($announcements->isEmpty())
            <div class="card"><x-empty-state icon="bell" title="Chưa có thông báo nào" /></div>
        @else
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($announcements as $announcement)
                    <a href="{{ route('announcements.show', $announcement) }}" class="card group flex flex-col overflow-hidden transition hover:-translate-y-1 hover:shadow-lg">
                        @if ($announcement->image_url)
                            <img src="{{ $announcement->image_url }}" alt="" class="aspect-video w-full object-cover">
                        @else
                            <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-brand-100 to-brand-200 text-brand-700">
                                <x-icon name="bell" class="h-12 w-12" />
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <p class="flex items-center gap-1 text-xs text-gray-500"><x-icon name="calendar" class="h-4 w-4" /> {{ $announcement->published_at->format('d/m/Y') }}</p>
                            <h2 class="mt-2 line-clamp-2 font-semibold text-gray-900 group-hover:text-brand-700">{{ $announcement->title }}</h2>
                            <p class="mt-2 line-clamp-3 text-sm text-gray-500">{{ Str::limit(strip_tags($announcement->content), 160) }}</p>
                            <span class="mt-auto flex items-center gap-1 pt-4 text-sm font-semibold text-brand-700">Xem chi tiết <x-icon name="arrow-right" class="h-4 w-4" /></span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8">{{ $announcements->links() }}</div>
        @endif
    </div>
</x-app-layout>
