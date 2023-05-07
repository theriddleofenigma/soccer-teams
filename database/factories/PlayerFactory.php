<?php

namespace Database\Factories;

use App\Models\Player;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Player>
 */
class PlayerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $profileImagePath = storeImage(UploadedFile::fake()->image(fake()->name() . '.jpg'), Player::PROFILE_IMAGE_PATH);
        return [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image_path' => $profileImagePath,
        ];
    }
}
