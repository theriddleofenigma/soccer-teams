<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TeamController extends Controller
{
    /**
     * Get all the team resources from the database.
     *
     * @return AnonymousResourceCollection
     */
    public function index(): AnonymousResourceCollection
    {
        return TeamResource::collection(Team::all());
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
            $logoPath = storeImage($request->file('logo'), 'logos');
            $team = Team::create([
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
                'request' =>  $request->all(),
            ]);
            return Response::json(['message' => 'Error while storing the team details.'], 500);
        }
    }

    /**
     * Get the team resource from the database.
     *
     * @param Team $team
     * @return TeamResource
     */
    public function show(Team $team): TeamResource
    {
        return new TeamResource($team);
    }


    /**
     * Update the specified team resource in database.
     *
     * @param UpdateTeamRequest $request
     * @param Team $team
     * @return JsonResponse|TeamResource
     */
    public function update(UpdateTeamRequest $request, Team $team): JsonResponse|TeamResource
    {
        DB::beginTransaction();
        try {
            # Update logo if available in request.
            $oldLogo = $team->logo_path;
            if ($request->hasFile('logo')) {
                $newLogo = storeImage($request->file('logo'), 'logos');
                $team->logo_path = $newLogo;
            }
            $team->name = $request->name;
            $team->save();

            // Delete the old logo if logo is changed.
            if ($oldLogo !== $team->logo_path) {
                Storage::delete($oldLogo);
            }
            DB::commit();

            return new TeamResource($team);
        } catch (Throwable $throwable) {
            DB::rollBack();
            // Delete the new logo if saved.
            if (!empty($newLogo) && Storage::exists($newLogo)) {
                Storage::delete($newLogo);
            }
            logError($throwable, 'Error while updating the team details.', 'TeamController@update', [
                'team_id' => $team->id,
                'request' => $request->all(),
            ]);
            return Response::json(['message' => 'Error while updating the team details.'], 500);
        }
    }

    /**
     * Remove the specified team resource from database.
     *
     * @param Team $team
     * @return \Illuminate\Http\Response|JsonResponse
     */
    public function destroy(Team $team): \Illuminate\Http\Response|JsonResponse
    {
        DB::beginTransaction();
        try {
            # Prepare the image paths to delete.
            $imagesToDelete = $team->players()->pluck('profile_image_path')->toArray();
            $imagesToDelete[] = $team->logo_path;

            # Delete the players and then the team.
            $team->players()->delete();
            $team->delete();

            # Delete the images.
            Storage::delete($imagesToDelete);
            DB::commit();

            return Response::noContent();
        } catch (Throwable $throwable) {
            DB::rollBack();
            logError($throwable, 'Error while deleting the team.', 'TeamController@delete', [
                'team_id' => $team->id,
            ]);
            return Response::json(['message' => 'Error while deleting the team.'], 500);
        }
    }
}
