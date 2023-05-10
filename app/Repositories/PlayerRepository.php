<?php

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PlayerRepository implements RepositoryInterface
{
    private Player $player;

    /**
     * PlayerRepository constructor.
     *
     * @param Player $player
     */
    public function __construct(Player $player)
    {
        $this->player = $player;
    }

    /**
     * Create a new player for the given data.
     *
     * @param array $data
     * @return Player
     */
    public function create(array $data): Model
    {
        return $this->player->create($data);
    }

    /**
     * Update the player with the given data for the given id and conditions.
     *
     * @param $id
     * @param array $data
     * @param array $conditions
     * @return Player
     */
    public function update($id, array $data, array $conditions = []): Model
    {
        $player = $this->get($id, $conditions);
        $player->update($data);
        return $player;
    }

    /**
     * Get the player for the given id and conditions.
     *
     * @param $id
     * @param array $conditions
     * @return Player
     */
    public function get($id, array $conditions = []): Model
    {
        return $this->player->when($conditions, fn($query) => $query->where($conditions))->findOrFail($id);
    }

    /**
     * Delete all the players for the given team id.
     *
     * @param $teamId
     * @return int
     */
    public function deleteTeamPlayers($teamId): int
    {
        return $this->player->where('team_id', $teamId)->delete();
    }

    /**
     * Delete the player for the given id and condition.
     *
     * @param $id
     * @param array $conditions
     * @return int
     */
    public function delete($id, array $conditions = []): int
    {
        return $this->player->when($conditions, fn($query) => $query->where($conditions))->where('id', $id)->delete();
    }

    /**
     * Get all the player images for the given team id.
     *
     * @param $teamId
     * @return array
     */
    public function getAllPlayerImages($teamId): array
    {
        return $this->player->where('team_id', $teamId)->pluck('profile_image_path')->toArray();
    }

    /**
     * Get all the players for the given team model.
     *
     * @param Team $team
     * @return Collection|\Illuminate\Support\Collection
     */
    public function getAllPlayers(Team $team): Collection|\Illuminate\Support\Collection
    {
        return $this->all(['team_id' => $team->id])
            ->map(fn($player) => $player->setRelation('team', $team));
    }

    /**
     * Get all the players for the given conditions.
     *
     * @param array $conditions
     * @return Collection
     */
    public function all(array $conditions = []): Collection
    {
        return $this->player->when($conditions, fn($query) => $query->where($conditions))->get();
    }
}
