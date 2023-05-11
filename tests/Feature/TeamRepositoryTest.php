<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Repositories\TeamRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class TeamRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var TeamRepository
     */
    protected TeamRepository $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teamRepository = App::make(TeamRepository::class);
    }

    /**
     * All method should return all the entries in the database.
     */
    public function test_all(): void
    {
        $teams = Team::factory(10)->create();

        $all = $this->teamRepository->all();

        $this->assertEquals($teams->toArray(), $all->toArray());
    }

    /**
     * All method should return all the entries in the database based on the condition.
     */
    public function test_all_should_return_records_based_on_condition(): void
    {
        $teams = Team::factory(10)->create();

        $team = $teams->first();
        $all = $this->teamRepository->all(['id' => $team->id]);

        $this->assertEquals([$team->toArray()], $all->toArray());
    }

    /**
     * All method should return all the entries in the database.
     */
    public function test_create_should_add_new_record_to_team_table(): void
    {
        $team = $this->teamRepository->create([
            'name' => fake()->name(),
            'logo_path' => fake()->filePath(),
        ]);

        $this->assertDatabaseHas('teams', $team->toArray());
    }

    /**
     * Get method should return single entry in the database.
     */
    public function test_get_should_return_single_record_for_id(): void
    {
        $teams = Team::factory(10)->create();

        $team = $teams->last();
        $get = $this->teamRepository->get($team->id);

        $this->assertEquals($team->toArray(), $get->toArray());
    }

    /**
     * Get method should return single entry in the database based on the condition.
     */
    public function test_get_should_return_single_record_based_on_condition(): void
    {
        $teams = Team::factory(10)->create();

        $team = $teams->last();
        $get = $this->teamRepository->get($team->id, ['name' => $team->name]);

        $this->assertEquals($team->toArray(), $get->toArray());
    }

    /**
     * Get method should throw exception if record doesn't exist.
     */
    public function test_get_should_throw_error_if_record_doesnt_exists(): void
    {
        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('teams', ['id' => $id]);

        $this->expectException(ModelNotFoundException::class);

        $this->teamRepository->get($id);
    }

    /**
     * Update method should update the record in DB.
     */
    public function test_update_should_update_record_in_db(): void
    {
        $team = Team::factory()->create();

        $updated = $this->teamRepository->update($team->id, [
            'name' => fake()->name(),
            'logo_path' => fake()->filePath(),
        ]);

        $this->assertDatabaseMissing('teams', $team->toArray());
        $this->assertDatabaseHas('teams', $updated->toArray());
    }

    /**
     * Update method should throw exception if given id record doesn't exist in DB.
     */
    public function test_update_should_throw_error_if_record_doesnt_exist(): void
    {
        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('teams', ['id' => $id]);

        $this->expectException(ModelNotFoundException::class);

        $this->teamRepository->update($id, [
            'name' => fake()->name(),
            'logo_path' => fake()->filePath(),
        ]);
    }

    /**
     * Delete method should delete the record from the database.
     */
    public function test_delete_should_delete_the_record(): void
    {
        $teams = Team::factory(10)->create();

        $team = $teams->last();
        $this->teamRepository->delete($team->id);

        $this->assertDatabaseMissing('teams', $team->toArray());
    }

    /**
     * Delete method should return single entry in the database based on the condition.
     */
    public function test_delete_should_delete_record_based_on_condition(): void
    {
        $teams = Team::factory(10)->create();

        $team = $teams->last();
        $this->teamRepository->delete($team->id, ['name' => $team->name]);

        $this->assertDatabaseMissing('teams', $team->toArray());
    }
}
