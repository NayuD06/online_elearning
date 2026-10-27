@php
    $user = Auth::user();
    $isInstructorArea = request()->routeIs('instructor.*');
    $homeRoute = $isInstructorArea ? 'instructor.dashboard' : 'admin.dashboard';
    $menuGroups = $isInstructorArea ? [
        'Tổng quan' => [
            ['label' => 'Bảng điều khiển', 'route' => 'instructor.dashboard', 'active' => 'instructor.dashboard', 'icon' => 'chart'],
        ],
        'Giảng dạy' => [
            ['label' => 'Khoá học của tôi', 'route' => 'instructor.courses.index', 'active' => 'instructor.courses.*', 'icon' => 'book'],
        ],
    ] : [
        'Tổng quan' => [
            ['label' => 'Bảng điều khiển', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'chart'],
        ],
        'Đào tạo' => [
            ['label' => 'Duyệt khoá học', 'route' => 'admin.courses.index', 'active' => 'admin.courses.*', 'icon' => 'book'],
            ['label' => 'Danh mục', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*', 'icon' => 'tag'],
            ['label' => 'Ghi danh', 'route' => 'admin.enrollments.index', 'active' => 'admin.enrollments.*', 'icon' => 'clipboard'],
            ['label' => 'Đánh giá', 'route' => 'admin.reviews.index', 'active' => 'admin.reviews.*', 'icon' => 'chat'],
        ],
        'Hệ thống' => [
            ['label' => 'Người dùng', 'route' => 'admin.users.index', 'active' => 'admin.users.*', 'icon' => 'users'],
            ['label' => 'Thông báo', 'route' => 'admin.announcements.index', 'active' => 'admin.announcements.*', 'icon' => 'bell'],
            ['label' => 'Ảnh đã lưu', 'route' => 'admin.media.index', 'active' => 'admin.media.*', 'icon' => 'photo'],
        ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts.partials.head', ['title' => $title])
    </head>
    <body class="bg-slate-100 font-sans text-gray-800 antialiased">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
            {{-- Sidebar --}}
            <div x-show="sidebarOpen" x-transition.opacity x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"></div>

            <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col bg-brand-900 text-brand-50 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0">
                <a href="{{ route($homeRoute) }}" class="flex h-16 items-center gap-2 border-b border-white/10 px-5">
                    <x-application-logo class="h-9 w-9" />
                    <span class="leading-tight">
                        <span class="block font-bold tracking-wide text-white">UTH E-LEARNING</span>
                        <span class="block text-[11px] text-brand-200">{{ $isInstructorArea ? 'Kênh giảng viên' : 'Trang quản trị' }}</span>
                    </span>
                </a>

                <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-5">
                    @foreach ($menuGroups as $groupLabel => $items)
                        @continue(empty($items))
                        <div>
                            <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-brand-300">{{ $groupLabel }}</p>
                            <div class="mt-2 space-y-1">
                                @foreach ($items as $item)
                                    <a href="{{ route($item['route']) }}"
                                       @class([
                                           'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                           'bg-white text-brand-900 shadow' => request()->routeIs($item['active']),
                                           'text-brand-100 hover:bg-white/10' => ! request()->routeIs($item['active']),
                                       ])>
                                        <x-icon :name="$item['icon']" class="h-5 w-5" /> {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="border-t border-white/10 p-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm text-brand-100 hover:bg-white/10">
                        <x-icon name="external" class="h-5 w-5" /> Xem trang người dùng
                    </a>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col">
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between gap-4 border-b border-gray-200 bg-white px-4 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button @click="sidebarOpen = true" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 lg:hidden" aria-label="Mở menu">
                            <x-icon name="menu" class="h-6 w-6" />
                        </button>
                        <h1 class="truncate text-lg font-semibold text-gray-900">{{ $title }}</h1>
                    </div>

                    <x-dropdown align="right" width="w-56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2 text-sm hover:bg-gray-100">
                                <img src="{{ $user->avatar_url }}" alt="" class="h-8 w-8 rounded-full object-cover">
                                <span class="hidden text-left sm:block">
                                    <span class="block max-w-[160px] truncate font-medium text-gray-800">{{ $user->name }}</span>
                                    <span class="block text-xs text-gray-500">{{ $user->role->label() }}</span>
                                </span>
                                <x-icon name="chevron-down" class="h-4 w-4 text-gray-400" />
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">Hồ sơ cá nhân</x-dropdown-link>
                            <x-dropdown-link :href="route('home')">Trang người dùng</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">Đăng xuất</x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </header>

                <main class="flex-1 p-4 sm:p-6">
                    <x-flash />

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            Vui lòng kiểm tra lại các trường được đánh dấu bên dưới.
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
