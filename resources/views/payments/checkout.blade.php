<x-app-layout :title="'Thanh toán: '.$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('courses.show', $course) }}" class="flex items-center gap-1 text-sm text-gray-500 hover:text-brand-700">
            <x-icon name="chevron-left" class="h-4 w-4" /> Quay lại khoá học
        </a>
        <h1 class="mt-3 text-2xl font-bold text-gray-900">Thanh toán khoá học</h1>

        <div class="mt-6 grid gap-6 lg:grid-cols-5">
            <form method="POST" action="{{ route('payments.store', $course) }}" class="card p-6 lg:col-span-3">
                @csrf
                <h2 class="font-semibold text-gray-900">Chọn phương thức thanh toán</h2>

                @if (empty($gateways))
                    <p class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Trang web chưa bật cổng thanh toán nào. Quản trị viên cần điền khoá VNPay hoặc MoMo trong file <code>.env</code> (xem <code>docs/thanh-toan.md</code>).
                    </p>
                @else
                    <div class="mt-4 space-y-3">
                        @foreach ($gateways as $gateway)
                            <label class="flex cursor-pointer items-center gap-4 rounded-xl border border-gray-200 p-4 transition has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60">
                                <input type="radio" name="gateway" value="{{ $gateway->value }}" @checked(old('gateway', $gateways[0]->value) === $gateway->value) class="h-4 w-4 text-brand-600 focus:ring-brand-500">
                                <span @class([
                                    'flex h-10 w-14 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white',
                                    'bg-[#005baa]' => $gateway === \App\Enums\PaymentGateway::Vnpay,
                                    'bg-[#a50064]' => $gateway === \App\Enums\PaymentGateway::Momo,
                                    'bg-gray-500' => $gateway === \App\Enums\PaymentGateway::Demo,
                                ])>{{ $gateway === \App\Enums\PaymentGateway::Demo ? 'DEMO' : strtoupper($gateway->value) }}</span>
                                <span>
                                    <span class="block font-medium text-gray-900">{{ $gateway->label() }}</span>
                                    <span class="block text-sm text-gray-500">{{ $gateway->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('gateway') <p class="form-error">{{ $message }}</p> @enderror

                    <button class="btn-primary mt-6 w-full py-3">Thanh toán {{ $course->price_label }}</button>
                    <p class="mt-3 text-center text-xs text-gray-500">Bạn sẽ được chuyển sang trang của cổng thanh toán. Thanh toán xong, hệ thống tự ghi danh bạn vào khoá học.</p>
                @endif
            </form>

            <aside class="card h-fit overflow-hidden lg:col-span-2">
                <x-course-thumbnail :course="$course" class="aspect-video w-full" />
                <div class="p-5">
                    <p class="font-semibold text-gray-900">{{ $course->title }}</p>
                    <p class="mt-1 text-sm text-gray-500">{{ $course->instructor->name }} · {{ $course->lessons_count }} bài học</p>
                    <dl class="mt-4 space-y-2 border-t border-gray-100 pt-4 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Giá khoá học</dt><dd>{{ $course->price_label }}</dd></div>
                        <div class="flex justify-between text-base font-bold text-gray-900"><dt>Tổng thanh toán</dt><dd class="text-brand-800">{{ $course->price_label }}</dd></div>
                    </dl>
                    <ul class="mt-4 space-y-2 text-sm text-gray-600">
                        <li class="flex items-center gap-2"><x-icon name="check" class="h-4 w-4 text-brand-600" /> Học trọn đời, không giới hạn số lần</li>
                        <li class="flex items-center gap-2"><x-icon name="trophy" class="h-4 w-4 text-brand-600" /> Nhận chứng chỉ khi hoàn thành</li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
