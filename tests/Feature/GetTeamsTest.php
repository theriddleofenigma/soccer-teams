<?php

namespace Tests\Feature;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTeamsTest extends TestCase
{
    use RefreshDatabase;

    public string $urlPrefix = 'api/v1';

    /**
     * A basic feature test example.
     */
    public function test_success_with_no_teams(): void
    {
        $this->getJson($this->urlPrefix . '/teams')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertDatabaseEmpty('teams');
    }

    /**
     * A basic feature test example.
     */
    public function test_success_with_teams(): void
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

        $this->assertDatabaseCount('teams', 10);
    }
}
