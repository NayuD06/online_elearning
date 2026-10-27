<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => rtrim(fake()->sentence(5), '.'),
            'content' => '<p>'.fake()->paragraph(6).'</p>',
            'video_url' => null,
            'duration_minutes' => fake()->numberBetween(5, 45),
            'position' => fake()->numberBetween(1, 20),
            'is_preview' => false,
        ];
    }
}
