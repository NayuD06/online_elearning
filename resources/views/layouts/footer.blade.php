<footer class="mt-16 bg-brand-950 text-brand-100">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
        <div class="lg:col-span-2">
            <div class="flex items-center gap-2">
                <x-application-logo class="h-9 w-9" />
                <span class="text-lg font-bold text-white">UTH E-LEARNING</span>
            </div>
            <p class="mt-3 max-w-md text-sm leading-relaxed text-brand-200">
                Nền tảng học trực tuyến giúp sinh viên tiếp cận kiến thức mọi lúc, mọi nơi với lộ trình rõ ràng và theo dõi tiến độ học tập.
            </p>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-white">Liên kết</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li><a href="{{ route('courses.index') }}" class="hover:text-white">Tất cả khoá học</a></li>
                <li><a href="{{ route('announcements.index') }}" class="hover:text-white">Thông báo</a></li>
                @guest
                    <li><a href="{{ route('register') }}" class="hover:text-white">Đăng ký tài khoản</a></li>
                @endguest
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-white">Liên hệ</h3>
            <ul class="mt-3 space-y-2 text-sm">
                <li class="flex items-start gap-2"><x-icon name="map" class="mt-0.5 h-4 w-4 shrink-0" /> 70 Tô Ký, P. Trung Mỹ Tây, TP. Hồ Chí Minh</li>
                <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4" /> (028) 3512 0000</li>
                <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4" /> elearning@uth.edu.vn</li>
            </ul>
        </div>
    </div>
    <div class="border-t border-white/10 py-4 text-center text-xs text-brand-300">
        © {{ now()->year }} UTH E-Learning. Đồ án môn Lập trình Web.
    </div>
</footer>
