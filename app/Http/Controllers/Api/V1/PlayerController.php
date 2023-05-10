<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
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

class PlayerController extends Controller
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
            'admin'
        ])->only(['store', 'update', 'delete']);
    }

    /**
     * Get all the player resources under the specified team from the database.
     *
     * @param $team
     * @return AnonymousResourceCollection|JsonResponse
     */
    public function index($team): AnonymousResourceCollection|JsonResponse
    {
        try {
            $team = $this->teamRepository->get($team);
            return PlayerResource::collection(
                $this->playerRepository->getAllPlayers($team)
            );
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            logError($throwable, 'Error while getting all the player details.', 'PlayerController@index', [
                'team_id' => $team instanceof Team ? $team->id : $team,
            ]);
            return Response::json(['message' => 'Error while getting all the player details.'], 500);
        }
    }

    /**
     * Store a new player resource under the specified team in database.
     *
     * @param StorePlayerRequest $request
     * @param $team
     * @return PlayerResource|JsonResponse
     */
    public function store(StorePlayerRequest $request, $team): PlayerResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $team = $this->teamRepository->get($team);
            $profileImagePath = storeImage($request->file('profile_image'), Player::PROFILE_IMAGE_PATH);

            $data = [
                'team_id' => $team->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'profile_image_path' => $profileImagePath,
            ];
            $player = $this->playerRepository->create($data);
            DB::commit();

            return new PlayerResource($player->setRelation('team', $team));
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();
            // If profile image has been saved and player is not created, then delete the profile image if exists.
            if (!empty($profileImagePath) && empty($player) && Storage::exists($profileImagePath)) {
                Storage::delete($profileImagePath);
            }
            logError($throwable, 'Error while storing the player details.', 'PlayerController@store', [
                'team_id' => $team instanceof Team ? $team->id : $team,
                'request' => $request->all(),
            ]);
            return Response::json(['message' => 'Error while storing the player details.'], 500);
        }
    }

    /**
     * Get the player resource under the specified team from the database.
     *
     * @param $team
     * @param Player $player
     * @return PlayerResource|JsonResponse
     */
    public function show($team, $player): PlayerResource|JsonResponse
    {
        try {
            $team = $this->teamRepository->get($team);
            $player = $this->playerRepository->get($player, ['team_id' => $team->id]);
            return new PlayerResource($player->setRelation('team', $team));
        } catch (ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            logError($throwable, 'Error while getting all the player details.', 'PlayerController@show', [
                'team_id' => $team instanceof Team ? $team->id : $team,
                'player_id' => $player instanceof Player ? $player->id : $player,
            ]);
            return Response::json(['message' => 'Error while getting all the player details.'], 500);
        }
    }

    /**
     * Update the specified player resource under the specified team in database.
     *
     * @param UpdatePlayerRequest $request
     * @param $team
     * @param $player
     * @return PlayerResource|JsonResponse
     */
    public function update(UpdatePlayerRequest $request, $team, $player): PlayerResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $team = $this->teamRepository->get($team);
            $player = $this->playerRepository->get($player, ['team_id' => $team->id]);
            $oldProfileImagePath = $player->profile_image_path;

            $data = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
            ];
            # Update profile image if available in request.
            if ($request->hasFile('profile_image')) {
                $data['profile_image_path'] = storeImage($request->file('profile_image'), Player::PROFILE_IMAGE_PATH);
            }
            $player = $this->playerRepository->update($player->id, $data);

            // Delete the old profile image if profile image is changed.
            if ($oldProfileImagePath !== $player->profile_image_path) {
                Storage::delete($oldProfileImagePath);
            }
            DB::commit();

            return new PlayerResource($player->setRelation('team', $team));
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();
            // Delete the new profile image if saved.
            if (!empty($data) && !empty($data['profile_image_path'])) {
                Storage::delete($data['profile_image_path']);
            }
            logError($throwable, 'Error while updating the player details.', 'PlayerController@update', [
                'team_id' => $team instanceof Team ? $team->id : $team,
                'player_id' => $player instanceof Player ? $player->id : $player,
                'request' => $request->all(),
            ]);
            return Response::json(['message' => 'Error while updating the player details.'], 500);
        }
    }

    /**
     * Remove the specified team resource under the specified team from database.
     *
     * @param $team
     * @param $player
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function destroy($team, $player): \Illuminate\Http\Response|JsonResponse
    {
        DB::beginTransaction();
        try {
            $team = $this->teamRepository->get($team);
            $player = $this->playerRepository->get($player, ['team_id' => $team->id]);
            $imagePath = $player->profile_image_path;

            # Delete the player.
            $this->playerRepository->delete($player->id, ['team_id' => $team->id]);

            # Delete the profile image.
            Storage::delete($imagePath);
            DB::commit();

            return Response::noContent();
        } catch (ModelNotFoundException $exception) {
            DB::rollBack();
            throw $exception;
        } catch (Throwable $throwable) {
            DB::rollBack();
            logError($throwable, 'Error while deleting the player.', 'PlayerController@delete', [
                'team_id' => $team instanceof Team ? $team->id : $team,
                'player_id' => $player instanceof Player ? $player->id : $player,
            ]);
            return Response::json(['message' => 'Error while deleting the player.'], 500);
        }
    }
}
