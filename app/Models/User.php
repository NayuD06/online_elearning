<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'email', 'google_id', 'password', 'role', 'avatar', 'phone', 'bio'])]
#[Hidden(['password', 'remember_token', 'google_id'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'student',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    /**
     * Treat every account as verified while email verification is turned off (auth.verify_email).
     */
    public function hasVerifiedEmail(): bool
    {
        return ! config('auth.verify_email') || $this->email_verified_at !== null;
    }

    /**
     * Determine whether the user has one of the given roles.
     */
    public function hasRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    /**
     * Accounts created through Google have no password until the user sets one.
     */
    public function hasPassword(): bool
    {
        return $this->password !== null;
    }

    /**
     * Determine whether the user can open the management area.
     */
    public function canManageCourses(): bool
    {
        return $this->hasRole(UserRole::Admin, UserRole::Teacher);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function taughtCourses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return BelongsToMany<Course, $this>
     */
    public function enrolledCourses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('completed_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Lesson, $this>
     */
    public function completedLessons(): BelongsToMany
    {
        return $this->belongsToMany(Lesson::class, 'lesson_completions')->withTimestamps();
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * The back-office home of the user: admins moderate, teachers manage their own courses.
     */
    public function managementRoute(): ?string
    {
        return match ($this->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Teacher => 'instructor.dashboard',
            default => null,
        };
    }

    public function isEnrolledIn(Course $course): bool
    {
        return $this->enrollments()->where('course_id', $course->id)->exists();
    }

    /**
     * Calculate the percentage of finished lessons in the given course.
     */
    public function progressIn(Course $course): int
    {
        $totalLessons = $course->lessons()->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = $this->completedLessons()->where('course_id', $course->id)->count();

        return (int) round($completedLessons / $totalLessons * 100);
    }

    /**
     * Get the avatar URL, falling back to a generated initials avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): string => match (true) {
            ! $this->avatar => 'https://ui-avatars.com/api/?background=0f766e&color=fff&name='.urlencode($this->name),
            // Google sign-in stores the full URL of the Google profile picture.
            str_starts_with($this->avatar, 'http') => $this->avatar,
            default => Storage::disk('media')->url($this->avatar),
        });
    }
}
