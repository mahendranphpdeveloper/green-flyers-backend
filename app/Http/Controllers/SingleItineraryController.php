<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;
use App\Models\ItineraryData;

class SingleItineraryController extends Controller
{
    // GET /api/v1/SingleItinerary/
    public function index(Request $request)
    {
        // Only get SingleItinerary records that belong to the authenticated user
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }
        $singleItineraries = SingleItineraryData::where('userId', $user->userId)->get();
        return response()->json($singleItineraries);
    }

    // POST /api/v1/SingleItinerary/store
    public function store(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $validatedData = $request->validate([
            'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
            // userId should not be in request: always use authenticated user ID
            'uploadDate'      => 'nullable|date',
            'certificateFile' => 'nullable|string|max:255',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
        ]);

        // Ensure that the ItineraryId belongs to the authenticated user
        $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
            ->where('userId', $user->userId)
            ->first();

        if (!$itinerary) {
            return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
        }

        // Add authenticated userId to the data
        $validatedData['userId'] = $user->userId;

        $singleItinerary = SingleItineraryData::create($validatedData);

        return response()->json([
            'message' => 'SingleItinerary created successfully.',
            'data' => $singleItinerary
        ], 201);
    }

    // GET /api/v1/SingleItinerary/{id}
    public function show(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        // Only allow access if this SingleItinerary belongs to the authenticated user
        if ($singleItinerary->userId !== $user->userId) {
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        return response()->json($singleItinerary);
    }

    // PUT /api/v1/SingleItinerary/{id}
    public function update(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        // Only allow update if this SingleItinerary belongs to the authenticated user
        if ($singleItinerary->userId !== $user->userId) {
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        $validatedData = $request->validate([
            'ItineraryId'     => 'sometimes|integer|exists:itinerarydata,ItineraryId',
            // userId should not be updatable: always use authenticated user ID
            'uploadDate'      => 'nullable|date',
            'certificateFile' => 'nullable|string|max:255',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
        ]);

        // If trying to update ItineraryId, ensure it belongs to the authenticated user
        if (isset($validatedData['ItineraryId'])) {
            $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
                ->where('userId', $user->userId)
                ->first();
            if (!$itinerary) {
                return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
            }
        }

        // Always enforce userId in update (paranoid/pure) - but not updatable, so not set here

        $singleItinerary->update($validatedData);

        return response()->json([
            'message' => 'SingleItinerary updated successfully.',
            'data' => $singleItinerary
        ]);
    }

    // DELETE /api/v1/SingleItinerary/{id}
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        // Only allow delete if this SingleItinerary belongs to the authenticated user
        if ($singleItinerary->userId !== $user->userId) {
            return response()->json(['message' => 'Unauthorized: You do not have access to this resource.'], 403);
        }

        $singleItinerary->delete();

        return response()->json(['message' => 'SingleItinerary deleted successfully.']);
    }
}
