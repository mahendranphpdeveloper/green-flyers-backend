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

        $validated = $request->validate([
            // Required fields
            'userId'             => 'required|integer',
            'date'               => 'required|date',
            'airline'            => 'required|string|max:255',
            'origin'             => 'required|string|max:255',
            'destination'        => 'required|string|max:255',
            'class'              => 'required|string|max:255',
            'passengers'         => 'required|integer',
            'tripType'           => 'required|string|max:255',
            'distance'           => 'required|string|max:255',

            // Optional or nullable fields
            'emission'           => 'nullable|numeric',
            'offsetAmount'       => 'nullable|numeric',
            'offsetPercentage'   => 'nullable|numeric',
            'status'             => 'nullable|integer',
            'approvelStatus'     => 'nullable',
            'numberOfTrees'      => 'nullable|integer',
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
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
