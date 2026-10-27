<?php

namespace Database\Factories;

use App\Enums\CourseLevel;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(4), '.');

        return [
            'category_id' => Category::factory(),
            'instructor_id' => User::factory()->state(['role' => UserRole::Teacher]),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(4)),
            'summary' => fake()->sentence(15),
            'description' => '<p>'.fake()->paragraph(5).'</p>',
            // Free by default so tests can enrol directly; use paid() for courses bought through checkout.
            'price' => 0,
            'level' => fake()->randomElement(CourseLevel::cases()),
            'status' => CourseStatus::Published,
            'published_at' => now(),
        ];
    }

    public function paid(int $price = 299000): static
    {
        return $this->state(fn (array $attributes) => ['price' => $price]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CourseStatus::Draft,
            'published_at' => null,
        ]);
    }
}
