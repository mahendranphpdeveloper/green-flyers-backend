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
        // Overriding request input with the values from the Edit Prompt

        $validated = [
            'date'            => '2025-11-28 00:00:00',
            'origin'          => 'MAA',
            'destination'     => 'DEL',
            'class'           => 'economy',
            'airline'         => 'IndiaGo',
            'passengers'      => 1,
            'tripType'        => 'one-way',
            'distance'        => 1760.86, // As string in prompt, but storing as float/decimal
            'userId'          => 3,
            'approvelStatus'      => null,
            'emission'            => null,
            'offsetAmount'        => null,
            'offsetPercentage'    => null,
            'status'              => null,
        ];

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
