<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // "pending" while a student waits for an admin to make them an instructor, "rejected" after a refusal.
            $table->string('instructor_status', 20)->nullable()->after('role');
            $table->timestamp('instructor_requested_at')->nullable()->after('instructor_status');
            // Locked accounts cannot sign in (password or Google) until an admin unlocks them.
            $table->timestamp('locked_at')->nullable()->after('instructor_requested_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['instructor_status', 'instructor_requested_at', 'locked_at']);
        });
    }
};
