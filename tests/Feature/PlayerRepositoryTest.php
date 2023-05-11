<?php

namespace Tests\Feature;

use App\Models\Player;
use App\Models\Team;
use App\Repositories\PlayerRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class PlayerRepositoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var PlayerRepository
     */
    protected PlayerRepository $playerRepository;

    /**
     * @var Team
     */
    protected Team $team;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();

        $this->playerRepository = App::make(PlayerRepository::class);
    }

    /**
     * All method should return all the entries in the database.
     */
    public function test_all(): void
    {
        $players = Player::factory(10)->for($this->team)->create();

        $all = $this->playerRepository->all();

        $this->assertEquals($players->toArray(), $all->toArray());
    }

    /**
     * All method should return all the entries in the database based on the condition.
     */
    public function test_all_should_return_records_based_on_condition(): void
    {
        // 10 Players in other team.
        Player::factory(10)->for(Team::factory()->create())->create();

        // 10 Players in $this->team.
        $players = Player::factory(10)->for($this->team)->create();

        $all = $this->playerRepository->all(['team_id' => $this->team->id]);

        $this->assertEquals($players->toArray(), $all->toArray());
        $this->assertCount(10, $all);
    }

    /**
     * All method should return all the entries in the database.
     */
    public function test_create_should_add_new_record_to_player_table(): void
    {
        $player = $this->playerRepository->create([
            'team_id' => $this->team->id,
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image_path' => fake()->filePath(),
        ]);

        $this->assertDatabaseHas('players', $player->toArray());
    }

    /**
     * Get method should return single entry in the database.
     */
    public function test_get_should_return_single_record_for_id(): void
    {
        $players = Player::factory(10)->for($this->team)->create();

        $player = $players->last();
        $get = $this->playerRepository->get($player->id);

        $this->assertEquals($player->toArray(), $get->toArray());
    }

    /**
     * Get method should return single entry in the database based on the condition.
     */
    public function test_get_should_return_single_record_based_on_condition(): void
    {
        $players = Player::factory(10)->for($this->team)->create();

        $player = $players->last();
        $get = $this->playerRepository->get($player->id, ['team_id' => $this->team->id]);

        $this->assertEquals($player->toArray(), $get->toArray());
    }

    /**
     * Get method should throw exception if record doesn't exist.
     */
    public function test_get_should_throw_error_if_record_doesnt_exists(): void
    {
        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('players', ['id' => $id]);

        $this->expectException(ModelNotFoundException::class);

        $this->playerRepository->get($id);
    }

    /**
     * Update method should update the record in DB.
     */
    public function test_update_should_update_record_in_db(): void
    {
        $player = Player::factory()->for($this->team)->create();

        $updated = $this->playerRepository->update($player->id, [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image_path' => fake()->filePath(),
        ], ['team_id' => $this->team->id]);

        $this->assertDatabaseMissing('players', $player->toArray());
        $this->assertDatabaseHas('players', $updated->toArray());
    }

    /**
     * Update method should throw exception if given id record doesn't exist in DB.
     */
    public function test_update_should_throw_error_if_record_doesnt_exist(): void
    {
        $id = fake()->numberBetween(11111, 99999);
        $this->assertDatabaseMissing('players', ['id' => $id]);

        $this->expectException(ModelNotFoundException::class);

        $this->playerRepository->update($id, [
            'first_name' => fake()->name(),
            'last_name' => fake()->name(),
            'profile_image_path' => fake()->filePath(),
        ], ['team_id' => $this->team->id]);
    }

    /**
     * Delete method should delete the record from the database.
     */
    public function test_delete_should_delete_the_record(): void
    {
        $players = Player::factory(10)->for($this->team)->create();

        $player = $players->last();
        $this->playerRepository->delete($player->id);

        $this->assertDatabaseMissing('players', $player->toArray());
    }

    /**
     * Delete method should return single entry in the database based on the condition.
     */
    public function test_delete_should_delete_record_based_on_condition(): void
    {
        $players = Player::factory(10)->for($this->team)->create();

        $player = $players->last();
        $this->playerRepository->delete($player->id, ['team_id' => $this->team->id]);

        $this->assertDatabaseMissing('players', $player->toArray());
    }

    /**
     * Delete team players method should delete all the players in the given team id.
     */
    public function test_delete_team_players_should_delete_players_against_the_given_team_id(): void
    {
        // 10 Players in other team.
        $otherTeam = Team::factory()->create();
        Player::factory(10)->for($otherTeam)->create();

        // 10 Players in $this->team.
        Player::factory(10)->for($this->team)->create();

        $this->playerRepository->deleteTeamPlayers($this->team->id);

        $this->assertDatabaseMissing('players', ['team_id' => $this->team->id]);
        $this->assertDatabaseHas('players', ['team_id' => $otherTeam->id]);
    }

    /**
     * Get all player images method should return all the player images for the given team id.
     */
    public function test_get_all_player_images_should_return_all_images_from_the_given_team_id(): void
    {
        // 10 Players in other team.
        $otherTeam = Team::factory()->create();
        $otherTeamPlayers = Player::factory(10)->for($otherTeam)->create();

        // 10 Players in $this->team.
        $players = Player::factory(10)->for($this->team)->create();

        $images = $this->playerRepository->getAllPlayerImages($this->team->id);

        $this->assertEquals($players->pluck('profile_image_path')->toArray(), $images);
        $this->assertNotEquals($otherTeamPlayers->pluck('profile_image_path')->toArray(), $images);
    }

    /**
     * Get all players method should return all the players under the given team.
     */
    public function test_get_all_players_should_return_all_player_under_the_given_team(): void
    {
        // 10 Players in other team.
        $otherTeam = Team::factory()->create();
        $otherTeamPlayers = Player::factory(10)->for($otherTeam)->create();

        // 10 Players in $this->team.
        $players = Player::factory(10)->for($this->team)->create();
        $players->map(fn($player) => $player->setRelation('team', $this->team));

        $getAllPlayers = $this->playerRepository->getAllPlayers($this->team);

        $this->assertEquals($players->toArray(), $getAllPlayers->toArray());
        $this->assertNotEquals($otherTeamPlayers->toArray(), $getAllPlayers->toArray());
    }
}
