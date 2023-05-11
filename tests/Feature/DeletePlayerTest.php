<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeletePlayerTest extends TestCase
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
     * Request should pass with valid request.
     */
    public function test_pass_with_valid_team_and_player(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $player = Player::factory(10)->for($this->team)->create()->last();
        $this->assertDatabaseCount('players', 11);

        // Assert profile image exists in storage.
        Storage::assertExists($player->profile_image_url);

        $this->deleteJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $player->id)
            ->assertNoContent();

        // Assert profile image image has been deleted from storage.
        Storage::assertMissing($player->profile_image_url);

        // Database should have 10 entry in the teams table as 1 team has been deleted on last request.
        $this->assertDatabaseCount('players', 10);
        $this->assertDatabaseMissing('players', ['id' => $player->id]);
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        // Admin user - Authenticated.
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->deleteJson($this->urlPrefix . '/teams/test/players/' . $this->player->id)
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
        $this->deleteJson($this->urlPrefix . '/teams/' . $id . '/players/' . $this->player->id)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have 1 entry in the teams table as request failed and no delete happened.
        $this->assertDatabaseCount('teams', 1);
    }

    /**
     * Request should fail when string value passed in place of player id.
     */
    public function test_fails_for_string_value_as_player_id(): void
    {
        $this->deleteJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/test')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Player not found.'
            ]);

        // Database should have no entry in the teams table.
        $this->assertDatabaseMissing('players', ['id' => 'test']);
    }

    /**
     * Request should fail when invalid id for player id which doesn't exist in db.
     */
    public function test_fails_for_invalid_player_id(): void
    {
        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('teams', ['id' => $id]);

        // Submitting request with team id that doesn't exist, should return 404.
        $this->deleteJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $id)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Player not found.'
            ]);

        // Database should have 1 entry in the teams table.
        $this->assertDatabaseMissing('players', ['id' => 2]);
    }
}
