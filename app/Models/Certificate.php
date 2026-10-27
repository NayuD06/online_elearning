<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Proof that a learner finished every lesson of a course. Anyone can check it by its code.
 */
#[Fillable(['user_id', 'course_id', 'code', 'student_name', 'course_title', 'instructor_name', 'issued_at'])]
class Certificate extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Issue the certificate of a finished course, or return the one issued before.
     */
    public static function issueFor(User $user, Course $course): self
    {
        return self::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            [
                'code' => self::newCode(),
                'student_name' => $user->name,
                'course_title' => $course->title,
                'instructor_name' => $course->instructor?->name,
                'issued_at' => now(),
            ],
        );
    }

    /**
     * A short code that is easy to type, e.g. UTH-7K2Q-9MXD (no 0/O or 1/I to avoid typos).
     */
    public static function newCode(): string
    {
        do {
            $random = collect(range(1, 8))->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])->implode('');
            $code = 'UTH-'.substr($random, 0, 4).'-'.substr($random, 4);
        } while (self::where('code', $code)->exists());

        return $code;
    }

    /**
     * Normalise what a visitor typed into the verification form.
     */
    public static function normaliseCode(string $code): string
    {
        return Str::upper(preg_replace('/\s+/', '', $code));
    }
}
