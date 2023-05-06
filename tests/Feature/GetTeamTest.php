<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTeamTest extends TestCase
{
    use RefreshDatabase;

    public string $urlPrefix = 'api/v1';

    /**
     * A basic feature test example.
     */
    public function test_success_with_valid_team(): void
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

        $this->assertDatabaseCount('teams', 10);
    }

    /**
     * A basic feature test example.
     */
    public function test_fails_for_string_value_as_team_id(): void
    {
        $this->getJson($this->urlPrefix . '/teams/test')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        $this->assertDatabaseEmpty('teams');
    }

    /**
     * A basic feature test example.
     */
    public function test_fails_for_invalid_team_id(): void
    {
        $team = Team::factory()->create();
        $this->assertEquals($team->id, 1);

        // Database has only 1 team with team id 1.
        // Submitting request with team id 2 will return 404.
        $this->getJson($this->urlPrefix . '/teams/2')
            ->assertNotFound()
            ->assertJson([
                'message' => 'Team not found.'
            ]);

        $this->assertDatabaseCount('teams', 1);
    }
}
