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

class UpdatePlayerTest extends TestCase
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
     * Player model instance.
     *
     * @var Player
     */
    public Player $player;

    /**
     * Setup the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->player = Player::factory()->for($this->team)->create();
    }

    /**
     * Request should pass with valid request payload and permission access.
     */
    public function test_pass_on_update_team(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert player profile image exists in storage.
        Storage::assertExists($this->player->profile_image_path);

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $profileImageUrl = $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'first_name' => $payload['first_name'],
                ],
            ])->json('data.profile_image_url');

        // Assert player profile image exists in storage.
        Storage::assertExists(Str::after($profileImageUrl, 'storage/'));

        // Assert player profile image has been deleted from storage as new image has been uploaded.
        Storage::assertMissing($this->player->profile_image_path);

        // Database should have the latest updated player data.
        $this->assertDatabaseHas('players', [
            'id' => $this->team->id,
            'first_name' => $payload['first_name'],
            'profile_image_path' => Str::after($profileImageUrl, 'storage/'),
        ]);
    }

    /**
     * Request should fail when the request is not authenticated.
     */
    public function test_fails_if_unauthenticated(): void
    {
        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied.',
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
        $this->putJson($this->urlPrefix . '/teams/test/players' . $this->player->id,$payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Route not found.'
            ]);
    }

    /**
     * Request should fail when invalid id for team id which doesn't exist in db.
     */
    public function test_fails_for_invalid_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->assertEquals($this->team->id, 1);

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];

        // Database has only 1 team with team id 1.
        // Submitting request with team id 2 should return 404.
        $this->putJson($this->urlPrefix . '/teams/2/players/' . $this->player->id, $payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have 1 entry in the teams table.
        $this->assertDatabaseCount('players', 1);
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field is required.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field is required.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'first_name' => ['The first name field must be a string.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field is required.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field is required.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'last_name' => ['The last name field must be a string.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
    }

    /**
     * Request should pass when the profile image field is not available as logo is optional for update action.
     */
    public function test_pass_when_profile_image_is_not_available_as_profile_image_is_optional(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert player profile image exists in storage.
        Storage::assertExists($this->player->profile_image_path);

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'first_name' => $payload['first_name'],
                ],
            ]);

        // Assert player profile image exists in storage as logo wasn't updated in last request.
        Storage::assertExists($this->player->profile_image_path);

        // Database should have the latest updated player data.
        $this->assertDatabaseHas('players', [
            'id' => $this->team->id,
            'first_name' => $payload['first_name'],
            'profile_image_path' => $this->player->profile_image_path,
        ]);
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
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg')->size(2049),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'profile_image' => ['The profile image field must not be greater than 2048 kilobytes.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'profile_image' => ['The profile image field must be an image.'],
                ],
            ]);

        // Database should have no change in player data.
        $this->assertDatabaseHas('players', $this->player->only(['id', 'first_name', 'last_name', 'profile_image_path']));
    }

    /**
     * Request should pass when the profile image field has image with exactly 2mb size.
     */
    public function test_pass_when_profile_image_has_exactly_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert player profile image exists in storage.
        Storage::assertExists($this->player->profile_image_path);

        $payload = [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image' => UploadedFile::fake()->image('my-profile-image.jpg')->size(2048),
        ];
        $profileImageUrl = $this->putJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $this->player->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'first_name' => $payload['first_name'],
                ],
            ])->json('data.profile_image_url');

        // Assert player profile image exists in storage.
        Storage::assertExists(Str::after($profileImageUrl, 'storage/'));

        // Assert player profile image has been deleted from storage as new image has been uploaded.
        Storage::assertMissing($this->player->profile_image_path);

        // Database should have the latest updated player data.
        $this->assertDatabaseHas('players', [
            'id' => $this->team->id,
            'first_name' => $payload['first_name'],
            'profile_image_path' => Str::after($profileImageUrl, 'storage/'),
        ]);
    }
}
