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

class UpdateTeamTest extends TestCase
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
    public function test_pass_on_update_team(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert team logo exists in storage.
        Storage::assertExists($this->team->logo_path);

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $logoUrl = $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                ],
            ])->json('data.logo_url');

        // Assert team logo exists in storage.
        Storage::assertExists(Str::after($logoUrl, 'storage/'));

        // Assert team logo image has been deleted from storage as new image has been uploaded.
        Storage::assertMissing($this->team->logo_path);

        // Database should have the latest updated team data.
        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => $payload['name'],
            'logo_path' => Str::after($logoUrl, 'storage/'),
        ]);
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertForbidden()
            ->assertJson([
                'message' => 'Access denied.',
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/test', $payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
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

        // Database has only 1 team with team id 1.
        // Submitting request with team id 2 should return 404.
        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg'),
        ];
        $this->putJson($this->urlPrefix . '/teams/2', $payload)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have 1 entry in the teams table.
        $this->assertDatabaseCount('teams', 1);
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field is required.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field is required.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field must not be greater than 255 characters.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'name' => ['The name field must be a string.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
    }

    /**
     * Request should pass when the logo field is not available as logo is optional for update action.
     */
    public function test_pass_when_logo_is_not_available_as_logo_is_optional(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert team logo exists in storage.
        Storage::assertExists($this->team->logo_path);

        $payload = [
            'name' => fake()->name(),
        ];
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                ],
            ]);

        // Assert team logo exists in storage as logo wasn't updated in last request.
        Storage::assertExists($this->team->logo_path);

        // Database should have the latest updated team data.
        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => $payload['name'],
            'logo_path' => $this->team->logo_path,
        ]);
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'logo' => ['The logo field must not be greater than 2048 kilobytes.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
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
        $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'errors' => [
                    'logo' => ['The logo field must be an image.'],
                ],
            ]);

        // Database should have no change in team data.
        $this->assertDatabaseHas('teams', $this->team->only(['id', 'name', 'logo_path']));
    }

    /**
     * Request should pass when the logo field has image with exactly 2mb size.
     */
    public function test_pass_when_logo_image_has_exactly_2mb_size(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        // Assert team logo exists in storage.
        Storage::assertExists($this->team->logo_path);

        $payload = [
            'name' => fake()->name(),
            'logo' => UploadedFile::fake()->image('my-team-logo.jpg')->size(2048),
        ];
        $logoUrl = $this->putJson($this->urlPrefix . '/teams/' . $this->team->id, $payload)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'name' => $payload['name'],
                ],
            ])->json('data.logo_url');

        // Assert team logo exists in storage.
        Storage::assertExists(Str::after($logoUrl, 'storage/'));

        // Assert team logo image has been deleted from storage as new image has been uploaded.
        Storage::assertMissing($this->team->logo_path);

        // Database should have the latest updated team data.
        $this->assertDatabaseHas('teams', [
            'id' => $this->team->id,
            'name' => $payload['name'],
            'logo_path' => Str::after($logoUrl, 'storage/'),
        ]);
    }
}
