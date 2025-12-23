<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SingleItineraryData;

class SingleItineraryController extends Controller
{
    // GET /api/v1/SingleItinerary/
    public function index()
    {
        $singleItineraries = SingleItineraryData::all();
        return response()->json($singleItineraries);
    }

    // POST /api/v1/SingleItinerary/store
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'ItineraryId'     => 'required|integer|exists:itinerarydata,ItineraryId',
            'userId'          => 'required|integer|exists:userdata,userId',
            'uploadData'      => 'nullable|date',
            'certificateFile' => 'nullable|string|max:255',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|integer',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
        ]);

        $singleItinerary = SingleItineraryData::create($validatedData);

        return response()->json([
            'message' => 'SingleItinerary created successfully.',
            'data' => $singleItinerary
        ], 201);
    }

    // GET /api/v1/SingleItinerary/{id}
    public function show($id)
    {
        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        return response()->json($singleItinerary);
    }

    // PUT /api/v1/SingleItinerary/{id}
    public function update(Request $request, $id)
    {
        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        $validatedData = $request->validate([
            'ItineraryId'     => 'sometimes|integer|exists:itinerarydata,ItineraryId',
            'userId'          => 'sometimes|integer|exists:userdata,userId',
            'uploadData'      => 'nullable|date',
            'certificateFile' => 'nullable|string|max:255',
            'approvelStatus'  => 'nullable|string|max:255',
            'emissionOffset'  => 'nullable|integer',
            'treesPlanted'    => 'nullable|integer',
            'projectTypes'    => 'nullable|string|max:255',
        ]);

        $singleItinerary->update($validatedData);

        return response()->json([
            'message' => 'SingleItinerary updated successfully.',
            'data' => $singleItinerary
        ]);
    }

    // DELETE /api/v1/SingleItinerary/{id}
    public function destroy($id)
    {
        $singleItinerary = SingleItineraryData::find($id);

        if (!$singleItinerary) {
            return response()->json(['message' => 'SingleItinerary not found.'], 404);
        }

        $singleItinerary->delete();

        return response()->json(['message' => 'SingleItinerary deleted successfully.']);
    }
}
