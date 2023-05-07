<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlayerRequest;
use App\Http\Requests\UpdatePlayerRequest;
use App\Http\Resources\PlayerResource;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PlayerController extends Controller
{
    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        $this->middleware([
            'auth:sanctum',
            'admin'
        ])->only(['store', 'update', 'delete']);
    }

    /**
     * Get all the player resources under the specified team from the database.
     *
     * @param Team $team
     * @return AnonymousResourceCollection
     */
    public function index(Team $team): AnonymousResourceCollection
    {
        return PlayerResource::collection(
            $team->players->map(fn($player) => $player->setRelation('team', $team))
        );
    }


    /**
     * Store a new player resource under the specified team in database.
     *
     * @param StorePlayerRequest $request
     * @param Team $team
     * @return PlayerResource|JsonResponse
     */
    public function store(StorePlayerRequest $request, Team $team): PlayerResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            $profileImagePath = storeImage($request->file('profile_image'), Player::PROFILE_IMAGE_PATH);
            $player = $team->players()->create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'profile_image_path' => $profileImagePath,
            ]);
            DB::commit();

            return new PlayerResource($player->setRelation('team', $team));
        } catch (Throwable $throwable) {
            DB::rollBack();
            // If profile image has been saved and player is not created, then delete the profile image if exists.
            if (!empty($profileImagePath) && empty($player) && Storage::exists($profileImagePath)) {
                Storage::delete($profileImagePath);
            }
            logError($throwable, 'Error while storing the player details.', 'PlayerController@store', $request->all());
            return Response::json(['message' => 'Error while storing the player details.'], 500);
        }
    }

    /**
     * Get the player resource under the specified team from the database.
     *
     * @param Team $team
     * @param Player $player
     * @return PlayerResource
     */
    public function show(Team $team, Player $player): PlayerResource
    {
        return new PlayerResource($player->setRelation('team', $team));
    }


    /**
     * Update the specified player resource under the specified team in database.
     *
     * @param UpdatePlayerRequest $request
     * @param Team $team
     * @param Player $player
     * @return PlayerResource|JsonResponse
     */
    public function update(UpdatePlayerRequest $request, Team $team, Player $player): PlayerResource|JsonResponse
    {
        DB::beginTransaction();
        try {
            # Update profile image if available in request.
            $oldProfileImagePath = $player->profile_image_path;
            if ($request->hasFile('profile_image')) {
                $newProfileImagePath = storeImage($request->file('profile_image'), Player::PROFILE_IMAGE_PATH);
                $player->profile_image_path = $newProfileImagePath;
            }
            $player->first_name = $request->first_name;
            $player->last_name = $request->last_name;
            $player->save();

            // Delete the old profile image if profile image is changed.
            if ($oldProfileImagePath !== $player->profile_image_path) {
                Storage::delete($oldProfileImagePath);
            }
            DB::commit();

            return new PlayerResource($player->setRelation('team', $team));
        } catch (Throwable $throwable) {
            DB::rollBack();
            // Delete the new profile image if saved.
            if (!empty($newProfileImagePath)) {
                Storage::delete($newProfileImagePath);
            }
            logError($throwable, 'Error while updating the player details.', 'PlayerController@update', $request->all());
            return Response::json(['message' => 'Error while updating the player details.'], 500);
        }
    }

    /**
     * Remove the specified team resource under the specified team from database.
     *
     * @param Team $team
     * @param Player $player
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function destroy(Team $team, Player $player): \Illuminate\Http\Response|JsonResponse
    {
        DB::beginTransaction();
        try {
            $imagePath = $player->profile_image_path;

            # Delete the player.
            $player->delete();

            # Delete the profile image.
            Storage::delete($imagePath);
            DB::commit();

            return Response::noContent();
        } catch (Throwable $throwable) {
            DB::rollBack();
            logError($throwable, 'Error while deleting the player.', 'PlayerController@delete', [
                'player_id' => $player->id,
            ]);
            return Response::json(['message' => 'Error while deleting the player.'], 500);
        }
    }
}
