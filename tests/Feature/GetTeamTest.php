<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Url prefix which is common among all the test requests.
     *
     * @var string
     */
    public string $urlPrefix = 'api/v1';

    /**
     * Request should pass with valid request.
     */
    public function test_pass_with_valid_team(): void
    {
        $team = Team::factory(10)->create()->last();

        $this->getJson($this->urlPrefix . '/teams/' . $team->id)
            ->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $team->id,
                    'name' => $team->name,
                    'logo_url' => $team->logoUrl(),
                ],
            ]);

        // Database should have 10 entry in the teams table.
        $this->assertDatabaseCount('teams', 10);
    }

    /**
     * Request should fail when string value passed in place of team id.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        $this->getJson($this->urlPrefix . '/teams/test')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have no entry in the teams table.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Request should fail when invalid id for team id which doesn't exist in db.
     */
    public function test_fails_for_invalid_team_id(): void
    {
        $team = Team::factory()->create();
        $this->assertEquals($team->id, 1);

        // Database has only 1 team with team id 1.
        // Submitting request with team id 2 should return 404.
        $this->getJson($this->urlPrefix . '/teams/2')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        // Database should have 1 entry in the teams table.
        $this->assertDatabaseCount('teams', 1);
    }
}
