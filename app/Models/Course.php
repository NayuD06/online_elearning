<?php

namespace App\Models;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'category_id', 'instructor_id', 'title', 'slug', 'summary', 'description',
    'thumbnail', 'price', 'level', 'status', 'rejection_reason', 'submitted_at', 'published_at',
])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'level' => CourseLevel::class,
            'status' => CourseStatus::class,
            'price' => 'integer',
            'submitted_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /**
     * @return HasMany<CourseImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(CourseImage::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<Section, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Lesson, $this>
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position');
    }

    /**
     * Lessons in learning order: by chapter first, then by their position inside it.
     * Only meant for fetching rows (aggregates should go through lessons()).
     *
     * @return HasMany<Lesson, $this>
     */
    public function curriculumLessons(): HasMany
    {
        return $this->hasMany(Lesson::class)
            ->orderBy(Section::select('position')->whereColumn('sections.id', 'lessons.section_id'))
            ->orderBy('lessons.position')
            ->orderBy('lessons.id');
    }

    /**
     * @return HasMany<Quiz, $this>
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * @return HasMany<Enrollment, $this>
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'enrollments')->withTimestamps();
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Limit the query to courses visible on the public site.
     *
     * @param  Builder<Course>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', CourseStatus::Published);
    }

    /**
     * Apply the catalog filters (keyword, category, level).
     *
     * @param  Builder<Course>  $query
     * @param  array{q?: ?string, category?: ?string, level?: ?string}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['q'] ?? null, fn (Builder $query, string $keyword) => $query->where(
                fn (Builder $query) => $query->where('title', 'like', "%{$keyword}%")
                    ->orWhere('summary', 'like', "%{$keyword}%")
            ))
            ->when($filters['category'] ?? null, fn (Builder $query, string $slug) => $query->whereHas(
                'category', fn (Builder $query) => $query->where('slug', $slug)
            ))
            ->when($filters['level'] ?? null, fn (Builder $query, string $level) => $query->where('level', $level));
    }

    public function isPublished(): bool
    {
        return $this->status === CourseStatus::Published;
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    protected function thumbnailUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->thumbnail
            ? Storage::disk('media')->url($this->thumbnail)
            : null);
    }

    protected function priceLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->isFree()
            ? 'Miễn phí'
            : number_format($this->price, 0, ',', '.').' đ');
    }
}
