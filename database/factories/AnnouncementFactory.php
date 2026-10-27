<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim(fake()->unique()->sentence(6), '.');

        return [
            'user_id' => User::factory()->state(['role' => UserRole::Admin]),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => '<p>'.fake()->paragraph(4).'</p>',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ];
    }
}
