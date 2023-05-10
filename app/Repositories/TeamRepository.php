<?php

namespace App\Repositories;

use App\Interfaces\RepositoryInterface;
use App\Models\Team;
use Illuminate\Database\Eloquent\Collection;

class TeamRepository implements RepositoryInterface
{
    private Team $team;

    /**
     * TeamRepository constructor.
     *
     * @param Team $team
     */
    public function __construct(Team $team)
    {
        $this->team = $team;
    }

    /**
     * Get all the teams for the given conditions.
     *
     * @param array $conditions
     * @return Collection
     */
    public function all(array $conditions = []): Collection
    {
        return $this->team->when($conditions, fn($query) => $query->where($conditions))->get();
    }

    /**
     * Create a new team for the given data.
     *
     * @param array $data
     * @return Team
     */
    public function create(array $data): Team
    {
        return $this->team->create($data);
    }

    /**
     * Get the team for the given id and conditions.
     *
     * @param $id
     * @param array $conditions
     * @return Team
     */
    public function get($id, array $conditions = []): Team
    {
        return $this->team->when($conditions, fn($query) => $query->where($conditions))->findOrFail($id);
    }

    /**
     * Update the team with the given data for the given id and conditions.
     *
     * @param $id
     * @param array $data
     * @param array $conditions
     * @return Team
     */
    public function update($id, array $data, array $conditions = []): Team
    {
        $team = $this->get($id, $conditions);
        $team->update($data);
        return $team;
    }

    /**
     * Delete the team for the given id and condition.
     *
     * @param $id
     * @param array $conditions
     * @return int
     */
    public function delete($id, array $conditions = []): int
    {
        return $this->team->when($conditions, fn($query) => $query->where($conditions))->where('id', $id)->delete();
    }
}
