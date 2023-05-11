<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteTeamTest extends TestCase
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
     * Request should pass with valid request.
     */
    public function test_pass_with_valid_team(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $team = Team::factory(10)->create()->last();
        $this->assertDatabaseCount('teams', 11);

        // Assert team logo exists in storage.
        Storage::assertExists($team->logo_path);

        $this->deleteJson($this->urlPrefix . '/teams/' . $team->id)
            ->assertNoContent();

        // Assert team logo image has been deleted from storage.
        Storage::assertMissing($team->logo_path);

        // Database should have 10 entry in the teams table as 1 team has been deleted on last request.
        $this->assertDatabaseCount('teams', 10);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    /**
     * Request should pass with deleting the team and corresponding team players.
     */
    public function test_pass_with_deleting_both_team_and_corresponding_players(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $team = Team::factory(10)->has(Player::factory()->count(5))->create()->last();
        $this->assertDatabaseCount('teams', 11);
        $this->assertDatabaseCount('players', 10 * 5);

        // Assert team logo and players profile images exist in storage.
        $images = [$team->logo_path, ...$team->players->pluck('profile_image_path')->toArray()];
        Storage::assertExists($images);

        $this->deleteJson($this->urlPrefix . '/teams/' . $team->id)
            ->assertNoContent();

        // Assert team logo and players profile images has been deleted from storage.
        Storage::assertMissing($images);

        // Database should have 10 entry in the teams table as 1 team has been deleted on last request.
        $this->assertDatabaseCount('teams', 10);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);

        // All the players against the deleted team might have deleted.
        $this->assertDatabaseMissing('players', ['team_id' => $team->id]);

        // Total players record should be 5 player for 9 teams now.
        $this->assertDatabaseCount('players', 9 * 5);
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson($this->urlPrefix . '/teams/test')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have 1 entry in the teams table as request failed and no delete happened.
        $this->assertDatabaseCount('teams', 1);
    }

    /**
     * Request should fail when invalid id for team id which doesn't exist in db.
     */
    public function test_fails_for_invalid_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('teams', ['id' => $id]);

        // Submitting request with team id that doesn't exist, should return 404.
        $this->deleteJson($this->urlPrefix . '/teams/' . $id)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);
    }
}
