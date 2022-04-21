<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'slug' => $this->faker->unique()->ean8(),
            'title' => $this->faker->sentence(),
            'content' => $this->faker->realText(1200),
            'excerpt' => $this->faker->paragraph(),
        ];
    }
}
