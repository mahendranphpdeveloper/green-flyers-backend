<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;
use App\Models\ItineraryData;
use Illuminate\Support\Facades\Storage;

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
            'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // up to 5MB
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
            'comments'        => 'nullable|string|max:1000', // Added comments column
        ]);

        // Ensure that the ItineraryId belongs to the authenticated user
        $itinerary = ItineraryData::where('ItineraryId', $validatedData['ItineraryId'])
            ->where('userId', $user->userId)
            ->first();

        if (!$itinerary) {
            return response()->json(['message' => 'Unauthorized: ItineraryId does not belong to the authenticated user.'], 403);
        }

        // Handle certificateFile upload
        if ($request->hasFile('certificateFile')) {
            $file = $request->file('certificateFile');
            $path = $file->store('certificates', 'public');
            $validatedData['certificateFile'] = $path;
        } else {
            unset($validatedData['certificateFile']);
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
            'certificateFile' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // up to 5MB
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|numeric',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
            'comments'        => 'nullable|string|max:1000', // Added comments column
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

        // Handle certificateFile upload
        if ($request->hasFile('certificateFile')) {
            // Delete the old certificate file if exists
            if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
                Storage::disk('public')->delete($singleItinerary->certificateFile);
            }

            $file = $request->file('certificateFile');
            $path = $file->store('certificates', 'public');
            $validatedData['certificateFile'] = $path;
        } else {
            // If not uploading a new file, do not overwrite the previous value (let the model keep its existing cert path)
            unset($validatedData['certificateFile']);
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

        // Delete certificateFile if exists
        if ($singleItinerary->certificateFile && Storage::disk('public')->exists($singleItinerary->certificateFile)) {
            Storage::disk('public')->delete($singleItinerary->certificateFile);
        }

        $singleItinerary->delete();

        return response()->json(['message' => 'SingleItinerary deleted successfully.']);
    }
}
