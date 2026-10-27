<x-app-layout title="Khoá học yêu thích">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <h1 class="flex items-center gap-2 text-2xl font-bold text-gray-900"><x-icon name="heart" class="h-7 w-7 fill-current text-rose-500" /> Khoá học yêu thích</h1>
        <p class="mt-1 text-sm text-gray-500">Những khoá học bạn đã lưu lại để ghi danh sau.</p>

        @if ($courses->isEmpty())
            <div class="card mt-6">
                <x-empty-state icon="heart" title="Bạn chưa lưu khoá học nào">
                    <a href="{{ route('courses.index') }}" class="font-semibold text-brand-700">Khám phá khoá học</a>
                </x-empty-state>
            </div>
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($courses as $course)
                    <div class="relative">
                        <x-course-card :course="$course" />
                        <form method="POST" action="{{ route('wishlist.toggle', $course) }}" class="absolute right-3 top-3">
                            @csrf
                            <button class="flex h-9 w-9 items-center justify-center rounded-full bg-white/95 text-rose-500 shadow hover:bg-rose-50" title="Bỏ khỏi yêu thích">
                                <x-icon name="heart" class="h-5 w-5 fill-current" />
                            </button>
                        </form>
                    </div>
                @endforeach
            </div>
            <div class="mt-6">{{ $courses->links() }}</div>
        @endif
    </div>
</x-app-layout>
