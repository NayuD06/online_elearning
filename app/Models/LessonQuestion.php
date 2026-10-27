<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A learner's question under a lesson (parent_id is null) or an answer to one (parent_id is the question).
 */
#[Fillable(['lesson_id', 'user_id', 'parent_id', 'body'])]
class LessonQuestion extends Model
{
    /**
     * @return BelongsTo<Lesson, $this>
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LessonQuestion, $this>
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<LessonQuestion, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->oldest();
    }

    public function isAnswer(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Only questions, not their answers.
     *
     * @param  Builder<LessonQuestion>  $query
     */
    public function scopeQuestions(Builder $query): void
    {
        $query->whereNull('parent_id');
    }
}
