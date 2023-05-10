<?php

namespace App\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface RepositoryInterface
{
    /**
     * Get all the resources.
     *
     * @param array $conditions
     * @return Collection
     */
    public function all(array $conditions = []): Collection;

    /**
     * Create a new model resource.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model;

    /**
     * Get the model resource for the specified id.
     *
     * @param $id
     * @param array $conditions
     * @return Model
     */
    public function get($id, array $conditions = []): Model;

    /**
     * Update the model resource for the specified id.
     *
     * @param $id
     * @param array $data
     * @param array $conditions
     * @return Model
     */
    public function update($id, array $data, array $conditions = []): Model;

    /**
     * Delete the model resource for the specified id.
     *
     * @param $id
     * @param array $conditions
     * @return int
     */
    public function delete($id, array $conditions = []): int;
}
