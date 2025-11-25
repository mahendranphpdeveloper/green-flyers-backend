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
        Log::info
        $validated = $request->validate([
            'date'        => 'required|date',
            'origin'      => 'required|string|max:10',
            'destination' => 'required|string|max:10',
            'class'       => 'required|string',
            'airline'     => 'required|string',
            'passengers'  => 'required|integer',
            'tripType'    => 'required|string',
            'distance'    => 'required|numeric',
        ]);
    
        $itinerary = ItineraryData::create($validated);
    
        return response()->json([
            'message' => 'Itinerary created successfully',
            'data' => $itinerary
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
