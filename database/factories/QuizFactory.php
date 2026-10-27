<?php

namespace Database\Factories;

use App\Models\Quiz;
use App\Models\Section;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quiz>
 */
class QuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'course_id' => fn (array $attributes) => Section::find($attributes['section_id'])->course_id,
            'title' => 'Kiểm tra: '.rtrim(fake()->sentence(3), '.'),
            'description' => fake()->sentence(),
            'pass_percent' => 50,
        ];
    }

    /**
     * Add questions that each have one correct answer (the first one) and three wrong ones.
     */
    public function withQuestions(int $count = 3): static
    {
        return $this->afterCreating(function (Quiz $quiz) use ($count): void {
            foreach (range(1, $count) as $position) {
                $question = $quiz->questions()->create(['content' => fake()->sentence().'?', 'position' => $position]);

                foreach (range(0, 3) as $index) {
                    $question->answers()->create(['content' => fake()->words(3, true), 'is_correct' => $index === 0]);
                }
            }
        });
    }
}
