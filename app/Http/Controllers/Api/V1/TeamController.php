<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use App\Repositories\PlayerRepository;
use App\Repositories\TeamRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TeamController extends Controller
{
    /**
     * @var TeamRepository
     */
    private TeamRepository $teamRepository;

    /**
     * @var PlayerRepository
     */
    private PlayerRepository $playerRepository;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct(TeamRepository $teamRepository, PlayerRepository $playerRepository)
    {
        $this->teamRepository = $teamRepository;
        $this->playerRepository = $playerRepository;
        $this->middleware([
            'auth:sanctum',
            'admin',
        ])->only(['store', 'update', 'delete']);
    }

    /**
     * Get all the team resources from the database.
     *
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index(): AnonymousResourceCollection|JsonResponse
    {
        try {
            return TeamResource::collection($this->teamRepository->all());
        } catch (Throwable $throwable) {
            logError($throwable, 'Error while getting all the team details.', 'TeamController@index');
            return Response::json(['message' => 'Error while getting all the team details.'], 500);
        }
    }

    /**
     * Store a new team resource in database.
     *
     * @param StoreTeamRequest $request
     * @return JsonResponse|TeamResource
     */
    public function store(StoreTeamRequest $request): JsonResponse|TeamResource
    {
        try {
            $logoPath = storeImage($request->file('logo'), Team::LOGO_PATH);
            $team = $this->teamRepository->create([
                'name' => $request->name,
                'logo_path' => $logoPath,
            ]);

            return new TeamResource($team);
        } catch (Throwable $throwable) {
            // If logo has been saved and team is not created, then delete the logo if exists.
            if (!empty($logoPath) && empty($team) && Storage::exists($logoPath)) {
                Storage::delete($logoPath);
            }
            logError($throwable, 'Error while storing the team details.', 'TeamController@store', [
                'request' => $request->all(),
            ]);
            return Response::json(['message' => 'Error while storing the team details.'], 500);
        }
    }

    /**
     * Get the team resource from the database.
     *
     * @param $team
     * @return TeamResource|JsonResponse
     */
    public function show($team): TeamResource|JsonResponse
    {
        try {
            return new TeamResource($this->teamRepository->get($team));
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            logError($throwable, 'Error while getting the specified team.', 'TeamController@show');
            return Response::json(['message' => 'Error while getting the specified team.'], 500);
        }
    }

    /**
     * Update the specified team resource in database.
     *
     * @param UpdateTeamRequest $request
     * @param $team
     * @return JsonResponse|TeamResource
     */
    public function update(UpdateTeamRequest $request, $team): JsonResponse|TeamResource
    {
        DB::beginTransaction();
        try {
            $team = $this->teamRepository->get($team);
            $oldLogoPath = $team->logo_path;
            $data = ['name' => $request->name];
            if ($request->hasFile('logo')) {
                # Update logo if available in request.
                $data['logo_path'] = storeImage($request->file('logo'), Team::LOGO_PATH);
            }
            $team = $this->teamRepository->update($team->id, $data);

            // Delete the old logo if logo is changed.
            if ($oldLogoPath !== $team->logo_path) {
                Storage::delete($oldLogoPath);
            }
            DB::commit();

            return new TeamResource($team);
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();
            // Delete the new logo if saved.
            if (!empty($data) && !empty($data['logo_path'])) {
                Storage::delete($data['logo_path']);
            }
            logError($throwable, 'Error while updating the team details.', 'TeamController@update', [
                'team_id' => $team instanceof Team ? $team->id : $team,
                'request' => $request->all(),
            ]);
            return Response::json(['message' => 'Error while updating the team details.'], 500);
        }
    }

    /**
     * Remove the specified team resource from database.
     *
     * @param $team
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function destroy($team): \Illuminate\Http\Response|JsonResponse
    {
        DB::beginTransaction();
        try {
            $team = $this->teamRepository->get($team);
            # Prepare the image paths to delete.
            $imagesToDelete = $this->playerRepository->getAllPlayerImages($team->id);
            $imagesToDelete[] = $team->logo_path;

            # Delete the players and then the team.
            $this->playerRepository->deleteTeamPlayers($team->id);
            $this->teamRepository->delete($team->id);

            # Delete the images.
            Storage::delete($imagesToDelete);
            DB::commit();

            return Response::noContent();
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();
            logError($throwable, 'Error while deleting the team.', 'TeamController@delete', [
                'team_id' => $team instanceof Team ? $team->id : $team,
            ]);
            return Response::json(['message' => 'Error while deleting the team.'], 500);
        }
    }
}
