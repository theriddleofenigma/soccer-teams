<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPlayerTest extends TestCase
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
    public function test_pass_with_valid_player(): void
    {
        $player = Player::factory(10)->for($this->team)->create()->last();

        $this->getJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/' . $player->id)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $player->id,
                    'first_name' => $player->first_name,
                    'last_name' => $player->last_name,
                    'profile_image_url' => $player->profileImageUrl(),
                ],
            ]);

        // Database should have 10 entry in the teams table.
        $this->assertDatabaseCount('players', 10);
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        $player = Player::factory()->for($this->team)->create();

        $this->getJson($this->urlPrefix . '/teams/test/players/' . $player->id)
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
        $player = Player::factory()->for($this->team)->create();

        // Database has only 1 team with team id 1.
        $this->assertDatabaseHas('teams', ['id' => 1]);

        // Submitting request with team id 2 should return 404.
        $this->getJson($this->urlPrefix . '/teams/2/players/' . $player->id)
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database don't have any entry for id:2 in the teams table.
        $this->assertDatabaseMissing('teams', ['id' => 2]);
    }

    /**
     * Request should fail when string value passed in place of player id.
     */
    public function test_fails_for_string_value_as_player_id(): void
    {
        $this->getJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/test')
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
        $player = Player::factory()->for($this->team)->create();
        $this->assertEquals($player->id, 1);

        // Database has only 1 player with player id 1.
        // Submitting request with player id 2 should return 404.
        $this->getJson($this->urlPrefix . '/teams/' . $this->team->id . '/players/2')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Player not found.'
            ]);

        // Database should have 1 entry in the teams table.
        $this->assertDatabaseMissing('players', ['id' => 2]);
    }
}
