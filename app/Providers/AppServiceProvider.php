<?php

namespace App\Providers;

use App\Filesystem\CloudinaryAdapter;
use Closure;
use Cloudinary\Cloudinary;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\FilesystemAdapter;
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
            $adapter = new CloudinaryAdapter(new Cloudinary($config['url']), $config['folder'] ?? '');

            return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
        });

        // Every password: at least 6 characters, one uppercase letter and one special character.
        Password::defaults(fn () => Password::min(6)->symbols()->rules([
            function (string $attribute, mixed $value, Closure $fail): void {
                if (! preg_match('/\p{Lu}/u', (string) $value)) {
                    $fail(':Attribute phải chứa ít nhất một chữ in hoa.');
                }
            },
        ]));
    }
}
