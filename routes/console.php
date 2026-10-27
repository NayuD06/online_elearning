<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:seed-if-empty', function () {
    if (User::query()->exists()) {
        $this->info('Database already has users, skipping seed.');

        return;
    }

    $this->call('db:seed', ['--force' => true]);
})->purpose('Seed demo data only when the database is empty (used on deploy)');

Artisan::command('app:secure-demo-accounts {--force : Run even when the passwords did not change since the last run}', function () {
    $domains = config('demo.email_domains');
    $passwords = collect(UserRole::cases())->mapWithKeys(fn (UserRole $role) => [$role->value => config("demo.{$role->value}_password")]);

    // Checking passwords is slow (bcrypt), so a deploy only does the work when DEMO_*_PASSWORD changed.
    $fingerprint = hash_hmac('sha256', $passwords->toJson(), (string) config('app.key'));

    if (! $this->option('force') && Cache::get('demo-accounts-secured') === $fingerprint) {
        $this->info('Demo accounts already secured, skipping.');

        return;
    }

    foreach (UserRole::cases() as $role) {
        $password = $passwords[$role->value];
        // Without DEMO_*_PASSWORD only the accounts still on a password published in the repository are changed.
        $publicPasswords = config("demo.public_passwords.{$role->value}", []);
        $changed = 0;

        User::where('role', $role)
            ->whereNotNull('password')
            ->where(fn (Builder $query) => collect($domains)->each(fn (string $domain) => $query->orWhere('email', 'like', "%@{$domain}")))
            ->each(function (User $user) use ($password, $publicPasswords, &$changed): void {
                $needsChange = $password
                    ? ! Hash::check($password, $user->password)
                    : collect($publicPasswords)->contains(fn (string $public) => Hash::check($public, $user->password));

                if (! $needsChange) {
                    return;
                }

                // A new password also signs the account out of every "remember me" cookie.
                $user->forceFill(['password' => $password ?: Str::password(24), 'remember_token' => null])->save();
                $changed++;
            });

        $this->info("Demo {$role->value}: đã đổi mật khẩu {$changed} tài khoản".($password ? '.' : ' (mật khẩu ngẫu nhiên vì chưa đặt DEMO_'.strtoupper($role->value).'_PASSWORD).'));
    }

    Cache::forever('demo-accounts-secured', $fingerprint);
})->purpose('Replace the public demo passwords with DEMO_*_PASSWORD, or random ones (used on deploy)');
