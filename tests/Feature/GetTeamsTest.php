<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTeamsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Url prefix which is common among all the test requests.
     *
     * @var string
     */
    public string $urlPrefix = 'api/v1';

    /**
     * Test should pass for valid request.
     * With no teams record in db should return empty data array.
     */
    public function test_pass_with_no_teams(): void
    {
        $this->getJson($this->urlPrefix . '/teams')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        // Database should have no entry in the teams table.
        $this->assertDatabaseEmpty('teams');
    }

    /**
     * Test should pass for valid request.
     * With teams record exists in db should return all the team records.
     */
    public function test_pass_with_teams(): void
    {
        $teams = Team::factory(10)->create()->map(fn($team) => [
            'id' => $team->id,
            'name' => $team->name,
            'logo_url' => $team->logoUrl(),
        ])->toArray();

        $this->getJson($this->urlPrefix . '/teams')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJson([
                'data' => $teams,
            ]);

        // Database should have 10 entry in the teams table.
        $this->assertDatabaseCount('teams', 10);
    }
}
