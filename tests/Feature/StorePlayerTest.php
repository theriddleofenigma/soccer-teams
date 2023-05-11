<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StorePlayerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Url prefix which is common among all the test requests.
     *
     * @var string
     */
    public string $urlPrefix = 'api/v1';

    /**
     * Team model instance.
     *
     * @var Team
     */
    public Team $team;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
    }

    /**
     * Request should pass with valid request payload and permission access.
     */
    public function test_pass_on_store_player(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $response = $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                ],
            ])->json('data');

        // Assert player profile image exists in storage.
        Storage::assertExists(Str::after($response['profile_image_url'], 'storage/'));

        // Database should have 1 entry in the players table.
        // The corresponding player id should match the newly created one.
        $this->assertDatabaseCount('players', 1);
        $this->assertEquals($response['id'], Player::first()->id);
    }

    /**
     * Request should fail when the request is not authenticated.
     */
    public function test_fails_if_unauthenticated(): void
    {
        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when the authenticated user is not an admin.
     */
    public function test_fails_if_auth_user_is_not_admin(): void
    {
        // Non admin user - Authenticated.
        Sanctum::actingAs(User::factory()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied.',
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/test/players', $payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database don't have any entry for id:test in the teams table.
        $this->assertDatabaseMissing('teams', ['id' => 'test']);
    }

    /**
     * Request should fail when invalid id for team id which doesn't exist in db.
     */
    public function test_fails_for_invalid_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];

        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('teams', ['id' => $id]);

        // Submitting request with team id that doesn't exist, should return 404.
        $this->postJson($this->urlPrefix . '/teams/' . $id . '/players', $payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);
    }

    /**
     * Request should fail when the first name field is not available.
     */
    public function test_fails_when_first_name_is_not_available(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the first name field has empty string.
     */
    public function test_fails_if_first_name_has_empty_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => '',
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the first name field has more than 255 characters.
     */
    public function test_fails_if_first_name_has_more_than_255_chars(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->sentence(256),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the first name field has value other than string.
     */
    public function test_fails_if_first_name_should_be_a_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->numberBetween(100, 999),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field must be a string.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when the last name field is not available.
     */
    public function test_fails_when_last_name_is_not_available(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the last name field has empty string.
     */
    public function test_fails_if_last_name_has_empty_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => '',
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the last name field has more than 255 characters.
     */
    public function test_fails_if_last_name_has_more_than_255_chars(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->sentence(256),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail if the last name field has value other than string.
     */
    public function test_fails_if_last_name_should_be_a_string(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->numberBetween(100, 999),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg'),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field must be a string.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when the profile image field is not available.
     */
    public function test_fails_when_profile_image_is_not_available(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'profile_image' => ['The profile image field is required.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when the profile image field has image with more 2mb size.
     */
    public function test_fails_when_profile_image_more_than_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg')->size(2049),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'profile_image' => ['The profile image field must not be greater than 2048 kilobytes.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should fail when the profile image has string value instead of image.
     */
    public function test_fails_when_profile_image_has_string_value_instead_of_image(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => fake()->text(),
        ];
        $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'profile_image' => ['The profile image field must be an image.'],
                ],
            ]);

        // Database shouldn't have any entry in teams as request failed.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Request should pass when the profile image field has image with exactly 2mb size.
     */
    public function test_pass_when_profile_image_has_exactly_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile.jpg')->size(2048),
        ];
        $response = $this->postJson($this->urlPrefix . '/teams/' . $this->team->id . '/players', $payload)
            ->assertCreated()
            ->assertJson([
                'data' => [
                    'first_name' => $payload['first_name'],
                    'last_name' => $payload['last_name'],
                ],
            ])->json('data');

        // Assert player profile image exists in storage.
        Storage::assertExists(Str::after($response['profile_image_url'], 'storage/'));

        // Database should have 1 entry in the players table.
        // The corresponding player id should match the newly created one.
        $this->assertDatabaseCount('players', 1);
        $this->assertEquals($response['id'], Player::first()->id);
    }
}
