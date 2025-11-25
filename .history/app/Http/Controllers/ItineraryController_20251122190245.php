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
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        Log::info($request->all());
        Log::info('Current user:', ['user' => $request->user()]);

        $validated = $request->validate([
            'date'        => 'required|date',
            'origin'      => 'required|string|max:10',
            'destination' => 'required|string|max:10',
            'class'       => 'required|string',
            'airline'     => 'required|string',
            'passengers'  => 'required|integer',
            'tripType'    => 'required|string',
            'distance'    => 'required|numeric',            
            'userId'      => 'required',
        ]);

        // Check if userId in request matches the current authenticated userId
        if ($request->user()->userId != $validated['userId']) {
            return response()->json([
                'message' => 'Unauthorized: userId does not match the authenticated user.'
            ], 403);
        }

        try {
            $itinerary = ItineraryData::create($validated);
            $result = $itinerary ? $itinerary->toArray() : [];

            // Merge original payload with created itinerary data
            $responseData = array_merge($validated, $result ?? []);

            return response()->json([
                'message' => 'Itinerary created successfully',
                'data' => $responseData
            ]);
        } catch (\Exception $e) {
            // Defensive fallback, returns empty result and error if failed
            return response()->json([
                'message' => 'Failed to create itinerary',
                'data' => [],
                'error' => $e->getMessage()
            ], 500);
        }
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
