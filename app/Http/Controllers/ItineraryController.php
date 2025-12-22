<?php

namespace App\Http\Controllers;

use App\Models\ItineraryData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ItineraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get itineraries only for the authenticated user
        $userId = $request->userId ??  $request->user()->userId;
        $itineraries = ItineraryData::where('userId', $userId)->get();
        
        return response()->json([
            'data' => $itineraries
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request->all());
        Log::info('Current user:', ['user' => $request->user()]);

        // Validation updated to reflect table columns from the provided structure (including all fields)
        $validated = $request->validate([
            // Required fields
            'userId'             => 'required|integer',
            'date'               => 'required|date',
            
            // These fields are nullable in DB but may be required by your business logic (keep as required for now)
            'airline'            => 'required|string|max:255',
            'origin'             => 'required|string|max:255',
            'destination'        => 'required|string|max:255',
            'class'              => 'required|string|max:255',
            'passengers'         => 'required|integer',
            'tripType'           => 'required|string|max:255',
            'distance'           => 'required|string|max:255',

            // New/updated optional fields from DB structure
            'flightcode'         => 'nullable|string|max:255',
            'originCity'         => 'nullable|string|max:255',
            'destinationCity'    => 'nullable|string|max:255',
            'emission'           => 'nullable|integer',
            'offsetAmount'       => 'nullable|integer',
            'offsetPercentage'   => 'nullable|integer',
            'numberOfTrees'      => 'nullable|integer',
            'status'             => 'nullable|string|max:255',
            'approvelStatus'     => 'nullable|string|max:255',
        ]);

        // Check if userId in request matches the current authenticated userId
        if ($request->user()->userId != $validated['userId']) {
            return response()->json([
                'message' => 'Unauthorized: userId does not match the authenticated user.'
            ], 403);
        }

        $itinerary = ItineraryData::create($validated);
        $itineraries = ItineraryData::all();
        return response()->json([
            'message' => 'Itinerary created successfully',
            'data' => $itineraries
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        // Fetch the itinerary
        $itinerary = ItineraryData::find($id);

        if (!$itinerary) {
            return response()->json([
                'message' => 'Itinerary not found'
            ], 404);
        }

        // Only allow access if this itinerary belongs to the authenticated user
        if ($itinerary->userId != $request->user()->userId) {
            return response()->json([
                'message' => 'Unauthorized: You do not have access to this itinerary.'
            ], 403);
        }

        return response()->json([
            'data' => $itinerary
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Find the itinerary
        $itinerary = ItineraryData::find($id);

        if (!$itinerary) {
            return response()->json([
                'message' => 'Itinerary not found'
            ], 404);
        }

        // Only allow update if this itinerary belongs to the authenticated user
        if ($itinerary->userId != $request->user()->userId) {
            return response()->json([
                'message' => 'Unauthorized: You do not have permission to update this itinerary.'
            ], 403);
        }

        // Validate the request based on allowed updatable fields
        $validated = $request->validate([
            'date'               => 'sometimes|date',
            'airline'            => 'sometimes|string|max:255',
            'origin'             => 'sometimes|string|max:255',
            'destination'        => 'sometimes|string|max:255',
            'class'              => 'sometimes|string|max:255',
            'passengers'         => 'sometimes|integer',
            'tripType'           => 'sometimes|string|max:255',
            'distance'           => 'sometimes|string|max:255',
            'flightcode'         => 'nullable|string|max:255',
            'originCity'         => 'nullable|string|max:255',
            'destinationCity'    => 'nullable|string|max:255',
            'emission'           => 'nullable|integer',
            'offsetAmount'       => 'nullable|integer',
            'offsetPercentage'   => 'nullable|integer',
            'numberOfTrees'      => 'nullable|integer',
            'status'             => 'nullable|string|max:255',
            'approvelStatus'     => 'nullable|string|max:255',
        ]);

        $itinerary->update($validated);

        return response()->json([
            'message' => 'Itinerary updated successfully',
            'data' => $itinerary
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        // Find the itinerary
        $itinerary = ItineraryData::find($id);

        if (!$itinerary) {
            return response()->json([
                'message' => 'Itinerary not found'
            ], 404);
        }

        // Only allow delete if this itinerary belongs to the authenticated user
        if ($itinerary->userId != $request->user()->userId) {
            return response()->json([
                'message' => 'Unauthorized: You do not have permission to delete this itinerary.'
            ], 403);
        }

        $itinerary->delete();

        return response()->json([
            'message' => 'Itinerary deleted successfully'
        ]);
    }
}
