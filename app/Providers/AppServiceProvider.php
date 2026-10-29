<?php

namespace App\Providers;

use App\Filesystem\CloudinaryAdapter;
use Closure;
use Cloudinary\Cloudinary;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use League\Flysystem\Filesystem;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // "cloudinary" disk driver, used by the "media" disk when CLOUDINARY_URL is set.
        Storage::extend('cloudinary', function (Application $app, array $config) {
            $adapter = new CloudinaryAdapter(new Cloudinary($config['url']), $config['folder'] ?? '', $config['type'] ?? 'upload');

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        $this->configureRateLimiting();

        // Every password: at least 6 characters, one uppercase letter and one special character.
        Password::defaults(fn () => Password::min(6)->symbols()->rules([
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! preg_match('/\p{Lu}/u', (string) $value)) {
                    $fail(':Attribute phải chứa ít nhất một chữ in hoa.');
                }
            },
        ]));
    }

    /**
     * Limits against bots: mass sign-ups, password reset emails (Resend free sends 100 a day) and spam.
     */
    private function configureRateLimiting(): void
    {
        $tooManyAttempts = fn (Request $request) => back()
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors(['email' => 'Bạn thao tác quá nhiều lần. Vui lòng đợi vài phút rồi thử lại.']);

        // Each limit of one limiter needs its own key, hence the "minute:" / "hour:" prefixes.
        // Sign-up: a whole class behind one school IP can still register together.
        RateLimiter::for('register', fn (Request $request) => [
            Limit::perMinute(20)->by('minute:'.$request->ip())->response($tooManyAttempts),
            Limit::perHour(100)->by('hour:'.$request->ip())->response($tooManyAttempts),
        ]);

        // Every request here may send an email.
        RateLimiter::for('password-email', fn (Request $request) => [
            Limit::perMinute(5)->by('minute:'.$request->ip())->response($tooManyAttempts),
            Limit::perHour(20)->by('hour:'.$request->ip())->response($tooManyAttempts),
            Limit::perHour(3)->by('email:'.mb_strtolower((string) $request->input('email')))->response($tooManyAttempts),
        ]);

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(10)->by('ip:'.$request->ip())->response($tooManyAttempts));

        // Questions, answers, reviews, wishlist and enrolments of a signed-in user.
        RateLimiter::for('user-actions', fn (Request $request) => [
            Limit::perMinute(20)->by('minute:'.($request->user()?->id ?? $request->ip())),
            Limit::perHour(200)->by('hour:'.($request->user()?->id ?? $request->ip())),
        ]);

        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by('user:'.($request->user()?->id ?? $request->ip())));
    }
}
