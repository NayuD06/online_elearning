<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The demo accounts used to be seeded with @gmail.com addresses that belong to real people.
 * Move them to the reserved example.com domain without wiping the rest of the data.
 */
return new class extends Migration
{
    private const array LOCAL_PARTS = ['admin', 'teacher', 'student', 'lan.tran', 'phuc.le'];

    public function up(): void
    {
        $this->move('gmail.com', 'example.com');
    }

    public function down(): void
    {
        $this->move('example.com', 'gmail.com');
    }

    private function move(string $from, string $to): void
    {
        foreach (self::LOCAL_PARTS as $name) {
            if (DB::table('users')->where('email', "{$name}@{$to}")->exists()) {
                continue;
            }

            DB::table('users')->where('email', "{$name}@{$from}")->update(['email' => "{$name}@{$to}"]);
        }
    }
};
