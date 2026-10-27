<?php

namespace App\Models;

use Database\Factories\QuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_id', 'section_id', 'title', 'description', 'pass_percent'])]
class Quiz extends Model
{
    /** @use HasFactory<QuizFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pass_percent' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return HasMany<Question, $this>
     */
    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<QuizAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Grade the submitted choices (question id => answer id) and store the attempt.
     *
     * @param  array<int|string, int|string|null>  $choices
     */
    public function grade(User $user, array $choices): QuizAttempt
    {
        $questions = $this->questions()->with('answers')->get();

        $answers = $questions->mapWithKeys(fn (Question $question) => [
            $question->id => $question->answers->firstWhere('id', (int) ($choices[$question->id] ?? 0))?->id,
        ]);

        $score = $questions->filter(
            fn (Question $question) => $question->answers->firstWhere('id', $answers[$question->id])?->is_correct
        )->count();

        return $this->attempts()->create([
            'user_id' => $user->id,
            'score' => $score,
            'total_questions' => $questions->count(),
            'answers' => $answers->all(),
        ]);
    }
}
