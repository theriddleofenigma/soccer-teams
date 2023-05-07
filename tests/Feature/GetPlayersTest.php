<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetPlayersTest extends TestCase
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
     * Test should pass for valid request.
     * With no teams record in db should return empty data array.
     */
    public function test_pass_with_no_players(): void
    {
        $this->getJson($this->urlPrefix . '/teams/' . $this->team->id . '/players')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Database should have no entry in the teams table.
        $this->assertDatabaseEmpty('players');
    }

    /**
     * Test should pass for valid request.
     * With teams record exists in db should return all the player records against the specified team.
     */
    public function test_pass_with_teams(): void
    {
        $players = Player::factory(10)->for($this->team)->create()->map(fn($player) => [
            'id' => $player->id,
            'first_name' => $player->first_name,
            'last_name' => $player->last_name,
            'profile_image_url' => $player->profileImageUrl(),
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
        ])->toArray();

        $this->getJson($this->urlPrefix . '/teams/' . $this->team->id . '/players')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJson([
                'data' => $players,
            ]);

        // Database should have 10 entry in the teams table.
        $this->assertDatabaseCount('players', 10);
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        $this->getJson($this->urlPrefix . '/teams/test/players')
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
        // Database has only 1 team with team id 1.
        $this->assertDatabaseHas('teams', ['id' => 1]);

        // Submitting request with team id 2 should return 404.
        $this->getJson($this->urlPrefix . '/teams/2/players')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database don't have any entry for id:2 in the teams table.
        $this->assertDatabaseMissing('teams', ['id' => 2]);
    }
}
