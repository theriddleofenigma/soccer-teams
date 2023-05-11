<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Url prefix which is common among all the test requests.
     *
     * @var string
     */
    public string $urlPrefix = 'api/v1';

    /**
     * Request should pass with valid request payload and permission access.
     */
    public function test_pass_on_store_team(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $response = $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                ],
            ])->json('data');

        // Assert team logo exists in storage.
        Storage::assertExists(Str::after($response['logo_url'], 'storage/'));

        // Database should have 1 entry in the teams table.
        // The corresponding team id should match the newly created one.
        $this->assertDatabaseCount('teams', 1);
        $this->assertEquals($response['id'], Team::first()->id);
    }

    /**
     * Request should fail when the request is not authenticated.
     */
    public function test_fails_if_unauthenticated(): void
    {
        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when the authenticated user is not an admin.
     */
    public function test_fails_if_auth_user_is_not_admin(): void
    {
        // Non admin user - Authenticated.
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied.',
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when the name field is not available.
     */
    public function test_fails_when_name_is_not_available(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail if the name field has empty string.
     */
    public function test_fails_if_name_has_empty_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => '',
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail if the name field has more than 255 characters.
     */
    public function test_fails_if_name_has_more_than_255_chars(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->sentence(256),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail if the name field has value other than string.
     */
    public function test_fails_if_name_should_be_a_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->numberBetween(100, 999),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field must be a string.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when the logo field is not available.
     */
    public function test_fails_when_logo_is_not_available(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'logo' => ['The logo field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when the logo field has image with more 2mb size.
     */
    public function test_fails_when_logo_image_more_than_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg')->size(2049),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'logo' => ['The logo field must not be greater than 2048 kilobytes.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when the logo has string value instead of image.
     */
    public function test_fails_when_logo_has_string_value_instead_of_image(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => fake()->text(),
        ];
        $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'logo' => ['The logo field must be an image.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should pass when the logo field has image with exactly 2mb size.
     */
    public function test_pass_when_logo_image_has_exactly_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg')->size(2048),
        ];
        $response = $this->postJson($this->urlPrefix . '/teams', $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                ],
            ])->json('data');

        // Assert team logo exists in storage.
        Storage::assertExists(Str::after($response['logo_url'], 'storage/'));

        // Database should have 1 entry in the teams table.
        // The corresponding team id should match the newly created one.
        $this->assertDatabaseCount('teams', 1);
        $this->assertEquals($response['id'], Team::first()->id);
    }
}
