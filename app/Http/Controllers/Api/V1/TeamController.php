<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TeamController extends Controller
{
    /**
     * Display a listing of the team resource.
     */
    public function index()
    {
        return TeamResource::collection(Team::all());
    }

    /**
     * Store a newly created team resource in storage.
     */
    public function store(StoreTeamRequest $request)
    {
        try {
            $team = Team::create([
                'name' => $request->name,
                'logo_path' => storeImage($request->file('logo')),
            ]);

            return new TeamResource($team);
        } catch (Throwable $throwable) {
            // If logo has been saved and team is not created, then delete the logo if exists.
            if (!empty($logoPath) and empty($team) && Storage::exists($logoPath)) {
                Storage::delete($logoPath);
            }
            logError($throwable, 'Error while storing the team details.', 'TeamController@store', $request->all());
            return Response::json(['message' => 'Error while storing the team details.'], 500);
        }
    }

    /**
     * Display the specified team resource.
     */
    public function show(Team $team)
    {
        return new TeamResource($team);
    }

    /**
     * Update the specified team resource in storage.
     */
    public function update(UpdateTeamRequest $request, Team $team)
    {
        try {
            # Update logo if available in request.
            $oldLogo = $team->logo_path;
            if ($request->hasFile('logo')) {
                $team->logo_path = storeImage($request->file('logo'));
            }
            $team->name = $request->name;
            $team->save();

            // Delete the old logo if logo is changed.
            if ($oldLogo !== $team->logo_path) {
                Storage::delete($oldLogo);
            }

            return new TeamResource($team);
        } catch (Throwable $throwable) {
            // If new logo has been saved, and is different from the current team logo path, then delete the logo if exists.
            if (!empty($newLogoPath) && $team->logo_path !== $newLogoPath && Storage::exists($newLogoPath)) {
                Storage::delete($newLogoPath);
            }
            logError($throwable, 'Error while updating the team details.', 'TeamController@update', $request->all());
            return Response::json(['message' => 'Error while updating the team details.'], 500);
        }
    }

    /**
     * Remove the specified team resource from storage.
     */
    public function destroy(Team $team)
    {
        # Delete the team logo.
        Storage::delete($team->logo_path);

        # Delete the team.
        $team->delete();

        return Response::noContent();
    }
}
