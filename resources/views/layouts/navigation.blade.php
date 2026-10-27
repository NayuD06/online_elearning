@php
    $menu = [
        ['label' => 'Trang chủ', 'route' => 'home', 'active' => 'home', 'icon' => 'home'],
        ['label' => 'Khoá học', 'route' => 'courses.index', 'active' => 'courses.*', 'icon' => 'book'],
        ['label' => 'Thông báo', 'route' => 'announcements.index', 'active' => 'announcements.*', 'icon' => 'bell'],
    ];

    if (auth()->check()) {
        $menu[] = ['label' => 'Góc học tập', 'route' => 'dashboard', 'active' => 'dashboard', 'icon' => 'academic'];
    }
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40">
    {{-- Top strip like a university portal --}}
    <div class="hidden bg-brand-900 text-xs text-brand-100 sm:block">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-1.5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><x-icon name="phone" class="h-3.5 w-3.5" /> (028) 3512 0000</span>
                <span class="flex items-center gap-1"><x-icon name="mail" class="h-3.5 w-3.5" /> elearning@uth.edu.vn</span>
            </div>
            <span>Hệ thống học trực tuyến dành cho sinh viên</span>
        </div>
    </div>

    <div class="bg-brand-700 text-white shadow-md">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
                <x-application-logo class="h-9 w-9" />
                <span class="leading-tight">
                    <span class="block text-base font-bold tracking-wide">UTH E-LEARNING</span>
                    <span class="hidden text-[11px] text-brand-100 sm:block">Học mọi lúc, mọi nơi</span>
                </span>
            </a>

            <div class="hidden items-center gap-1 lg:flex">
                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}"
                       @class([
                           'flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium transition',
                           'bg-white/15 text-white' => request()->routeIs($item['active']),
                           'text-brand-50 hover:bg-white/10' => ! request()->routeIs($item['active']),
                       ])>
                        <x-icon :name="$item['icon']" class="h-4 w-4" /> {{ $item['label'] }}
                    </a>
                @endforeach
            </div>

            <form action="{{ route('courses.index') }}" method="GET" class="hidden flex-1 md:block lg:max-w-xs">
                <label class="relative block">
                    <span class="sr-only">Tìm khoá học</span>
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-200" />
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm khoá học..."
                           class="w-full rounded-full border-0 bg-white/15 py-2 pl-9 pr-4 text-sm text-white placeholder:text-brand-100 focus:bg-white focus:text-gray-900 focus:ring-2 focus:ring-brand-300">
                </label>
            </form>

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <x-dropdown align="right" width="w-56">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm font-medium hover:bg-white/10">
                                <img src="{{ Auth::user()->avatar_url }}" alt="" class="h-8 w-8 rounded-full border-2 border-white/60 object-cover">
                                <span class="max-w-[140px] truncate">{{ Auth::user()->name }}</span>
                                <x-icon name="chevron-down" class="h-4 w-4" />
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-gray-100 px-4 py-2">
                                <p class="text-sm font-semibold text-gray-900">{{ Auth::user()->name }}</p>
                                <span class="badge {{ Auth::user()->role->badgeClasses() }} mt-1">{{ Auth::user()->role->label() }}</span>
                            </div>
                            @if (Auth::user()->managementRoute())
                                <x-dropdown-link :href="route(Auth::user()->managementRoute())">{{ Auth::user()->isAdmin() ? 'Trang quản trị' : 'Kênh giảng viên' }}</x-dropdown-link>
                            @endif
                            <x-dropdown-link :href="route('dashboard')">Góc học tập</x-dropdown-link>
                            <x-dropdown-link :href="route('profile.edit')">Hồ sơ cá nhân</x-dropdown-link>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                    Đăng xuất
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg px-3 py-2 text-sm font-medium hover:bg-white/10">Đăng nhập</a>
                    <a href="{{ route('register') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-brand-800 hover:bg-brand-50">Đăng ký</a>
                @endauth
            </div>

            <button @click="open = ! open" class="rounded-lg p-2 hover:bg-white/10 lg:hidden" aria-label="Mở menu">
                <x-icon name="menu" class="h-6 w-6" x-show="! open" />
                <x-icon name="x" class="h-6 w-6" x-show="open" x-cloak />
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-transition x-cloak class="border-t border-white/10 lg:hidden">
            <div class="space-y-1 px-4 py-3">
                <form action="{{ route('courses.index') }}" method="GET" class="mb-3 md:hidden">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Tìm khoá học..."
                           class="w-full rounded-lg border-0 bg-white/15 px-4 py-2 text-sm text-white placeholder:text-brand-100 focus:bg-white focus:text-gray-900">
                </form>

                @foreach ($menu as $item)
                    <a href="{{ route($item['route']) }}" @class(['flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium', 'bg-white/15' => request()->routeIs($item['active'])])>
                        <x-icon :name="$item['icon']" class="h-4 w-4" /> {{ $item['label'] }}
                    </a>
                @endforeach

                <div class="mt-2 border-t border-white/10 pt-3">
                    @auth
                        <div class="mb-2 flex items-center gap-3 px-3">
                            <img src="{{ Auth::user()->avatar_url }}" alt="" class="h-9 w-9 rounded-full object-cover">
                            <div>
                                <p class="text-sm font-semibold">{{ Auth::user()->name }}</p>
                                <p class="text-xs text-brand-100">{{ Auth::user()->email }}</p>
                            </div>
                        </div>
                        @if (Auth::user()->managementRoute())
                            <a href="{{ route(Auth::user()->managementRoute()) }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-white/10">{{ Auth::user()->isAdmin() ? 'Trang quản trị' : 'Kênh giảng viên' }}</a>
                        @endif
                        <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm hover:bg-white/10">Hồ sơ cá nhân</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="block w-full rounded-lg px-3 py-2 text-left text-sm hover:bg-white/10">Đăng xuất</button>
                        </form>
                    @else
                        <div class="flex gap-2">
                            <a href="{{ route('login') }}" class="flex-1 rounded-lg border border-white/40 px-3 py-2 text-center text-sm">Đăng nhập</a>
                            <a href="{{ route('register') }}" class="flex-1 rounded-lg bg-white px-3 py-2 text-center text-sm font-semibold text-brand-800">Đăng ký</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</nav>
