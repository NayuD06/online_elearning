<x-app-layout :title="$announcement->title">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
        <article class="card overflow-hidden lg:col-span-2">
            @if ($announcement->image_url)
                <img src="{{ $announcement->image_url }}" alt="" class="aspect-[21/9] w-full object-cover">
            @endif
            <div class="p-6 sm:p-8">
                <nav class="text-sm text-gray-500"><a href="{{ route('announcements.index') }}" class="hover:text-brand-700">Thông báo</a> / Chi tiết</nav>
                <h1 class="mt-3 text-2xl font-bold text-gray-900 sm:text-3xl">{{ $announcement->title }}</h1>
                <p class="mt-2 flex items-center gap-3 text-sm text-gray-500">
                    <span class="flex items-center gap-1"><x-icon name="calendar" class="h-4 w-4" /> {{ $announcement->published_at->format('d/m/Y H:i') }}</span>
                    <span class="flex items-center gap-1"><x-icon name="user" class="h-4 w-4" /> {{ $announcement->author->name }}</span>
                </p>
                <div class="rich-content mt-6">{!! \App\Support\HtmlSanitizer::clean($announcement->content) !!}</div>
            </div>
        </article>

        <aside class="card h-fit overflow-hidden">
            <h2 class="bg-brand-700 px-5 py-3 font-semibold text-white">Thông báo khác</h2>
            <ul class="divide-y divide-gray-100">
                @forelse ($otherAnnouncements as $other)
                    <li>
                        <a href="{{ route('announcements.show', $other) }}" class="block px-5 py-3 hover:bg-brand-50/50">
                            <p class="line-clamp-2 text-sm font-medium text-gray-800">{{ $other->title }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $other->published_at->format('d/m/Y') }}</p>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-4 text-sm text-gray-500">Không có thông báo khác.</li>
                @endforelse
            </ul>
        </aside>
    </div>
</x-app-layout>
