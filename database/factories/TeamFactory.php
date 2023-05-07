<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Http\UploadedFile;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $logoPath = storeImage(UploadedFile::fake()->image(fake()->name() . '.jpg'), Team::LOGO_PATH);
        return [
            'name' => fake()->name(),
            'logo_path' => $logoPath,
        ];
    }
}
