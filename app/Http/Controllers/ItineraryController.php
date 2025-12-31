<?php

namespace App\Http\Controllers;

use App\Models\ItineraryData;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\AdminData;

class ItineraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Get itineraries only for the authenticated user
        $userId = $request->userId ?? $request->user()->userId;
        $itineraries = ItineraryData::where('userId', $userId)->get();

        // Attach country names from country table if country_id exists
        $itineraries->transform(function ($itinerary) {
            if ($itinerary->country) {
                $country = Country::where('country_id', $itinerary->country)
                    ->orWhere('country_name', $itinerary->country)
                    ->first();
                $itinerary->country_name = $country ? $country->country_name : null;
                $itinerary->country_id = $country ? $country->country_id : null;
            } else {
                $itinerary->country_name = null;
                $itinerary->country_id = null;
            }
            return $itinerary;
        });

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

            'airline'            => 'required|string|max:255',
            'origin'             => 'required|string|max:255',
            'destination'        => 'required|string|max:255',
            'class'              => 'required|string|max:255',
            'passengers'         => 'required|integer',
            'tripType'           => 'required|string|max:255',
            'distance'           => 'required|string|max:255',

            'flightcode'         => 'nullable|string|max:255',
            'originCity'         => 'nullable|string|max:255',
            'destinationCity'    => 'nullable|string|max:255',
            'emission'           => 'nullable|numeric',
            'offsetAmount'       => 'nullable|integer',
            'offsetPercentage'   => 'nullable|integer',
            'numberOfTrees'      => 'nullable|integer',
            'country' => [
                'nullable',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    // Accept either country name or primary key (country_id)
                    if (
                        $value &&
                        !Country::where('country_name', $value)
                            ->orWhere('country_id', $value)
                            ->exists()
                    ) {
                        $fail('The selected country is invalid.');
                    }
                }
            ],

            'status'             => 'nullable|string|max:255',
            'approvelStatus'     => 'nullable|string|max:255',
        ]);

        // If a country is provided as a name, convert to country_id for storage if needed
        if (!empty($validated['country'])) {
            $country = Country::where('country_name', $validated['country'])
                ->orWhere('country_id', $validated['country'])
                ->first();
            // Store the country_id in the DB if it's available, otherwise store the original value as fallback
            $validated['country'] = $country ? $country->country_id : $validated['country'];
        }

        // Check if userId in request matches the current authenticated userId
        if ($request->user()->userId != $validated['userId']) {
            return response()->json([
                'message' => 'Unauthorized: userId does not match the authenticated user.'
            ], 403);
        }

        $itinerary = ItineraryData::create($validated);

        // Attach country name and id if exists
        if ($itinerary->country) {
            $country = Country::where('country_id', $itinerary->country)
                ->orWhere('country_name', $itinerary->country)
                ->first();
            $itinerary->country_name = $country ? $country->country_name : null;
            $itinerary->country_id = $country ? $country->country_id : null;
        } else {
            $itinerary->country_name = null;
            $itinerary->country_id = null;
        }

        $itineraries = ItineraryData::where('userId', $request->user()->userId)->get();
        // Attach country names to the list
        $itineraries->transform(function ($itinerary) {
            if ($itinerary->country) {
                $country = Country::where('country_id', $itinerary->country)
                    ->orWhere('country_name', $itinerary->country)
                    ->first();
                $itinerary->country_name = $country ? $country->country_name : null;
                $itinerary->country_id = $country ? $country->country_id : null;
            } else {
                $itinerary->country_name = null;
                $itinerary->country_id = null;
            }
            return $itinerary;
        });

        return response()->json([
            'message' => 'Itinerary created successfully',
            'data' => $itineraries
        ]);
    }

    /**
     * Display the specified resource.
     */
    
     public function show(Request $request, string $userId)
     {
         Log::info('Admin user itineraries list called', [
             'admin_auth_id' => optional($request->user())->id,
             'passed_user_id' => $userId,
         ]);
     
         // Get authenticated admin
         $admin = $request->user();
     
         if (!$admin) {
             return response()->json([
                 'message' => 'Unauthenticated'
             ], 401);
         }
     
         // Verify admin
         if (!AdminData::where('id', $admin->id)->exists()) {
             Log::warning('Non-admin attempted itinerary access', [
                 'auth_id' => $admin->id,
             ]);
     
             return response()->json([
                 'message' => 'Unauthorized - Not an admin'
             ], 403);
         }
     
         // Fetch all itineraries for that user
         $itineraries = ItineraryData::where('userId', $userId)
             ->orderBy('userId', 'desc')
             ->get();
     
         //  Attach country name & id
         $itineraries->transform(function ($itinerary) {
             if (!empty($itinerary->country)) {
                 $country = Country::where('country_id', $itinerary->country)
                     ->orWhere('country_name', $itinerary->country)
                     ->first();
     
                 $itinerary->country_name = $country?->country_name;
                 $itinerary->country_id   = $country?->country_id;
             } else {
                 $itinerary->country_name = null;
                 $itinerary->country_id   = null;
             }
             return $itinerary;
         });
     
         Log::info('Admin itineraries fetched successfully', [
             'admin_id' => $admin->id,
             'user_id' => $userId,
             'count' => $itineraries->count(),
         ]);
     
         return response()->json([
             'status' => true,
             'data' => $itineraries
         ]);
     }


    /**
     * Update the specified resource in storage.
     */
    // public function update(Request $request, string $id)
    // {
    //     // Find the itinerary
    //     $itinerary = ItineraryData::find($id);

    //     if (!$itinerary) {
    //         return response()->json([
    //             'message' => 'Itinerary not found'
    //         ], 404);
    //     }

    //     // Only allow update if this itinerary belongs to the authenticated user
    //     if ($itinerary->userId != $request->user()->userId) {
    //         return response()->json([
    //             'message' => 'Unauthorized: You do not have permission to update this itinerary.'
    //         ], 403);
    //     }

    //     // Validate the request based on allowed updatable fields
    //     $validated = $request->validate([
    //         'date'               => 'sometimes|date',
    //         'airline'            => 'sometimes|string|max:255',
    //         'origin'             => 'sometimes|string|max:255',
    //         'destination'        => 'sometimes|string|max:255',
    //         'class'              => 'sometimes|string|max:255',
    //         'passengers'         => 'sometimes|integer',
    //         'tripType'           => 'sometimes|string|max:255',
    //         'distance'           => 'sometimes|string|max:255',
    //         'flightcode'         => 'nullable|string|max:255',
    //         'originCity'         => 'nullable|string|max:255',
    //         'destinationCity'    => 'nullable|string|max:255',
    //         'emission'           => 'nullable|numeric',
    //         'offsetAmount'       => 'nullable|integer',
    //         'offsetPercentage'   => 'nullable|integer',
    //         'numberOfTrees'      => 'nullable|integer',
    //         'country' => [
    //             'nullable',
    //             'string',
    //             'max:255',
    //             function ($attribute, $value, $fail) {
    //                 if (
    //                     $value &&
    //                     !Country::where('country_name', $value)
    //                         ->orWhere('country_id', $value)
    //                         ->exists()
    //                 ) {
    //                     $fail('The selected country is invalid.');
    //                 }
    //             }
    //         ],

    //         'status'             => 'nullable|string|max:255',
    //         'approvelStatus'     => 'nullable|string|max:255',
    //     ]);

    //     // Convert country to country_id if needed
    //     if (!empty($validated['country'])) {
    //         $country = Country::where('country_name', $validated['country'])
    //             ->orWhere('country_id', $validated['country'])
    //             ->first();
    //         $validated['country'] = $country ? $country->country_id : $validated['country'];
    //     }

    //     $itinerary->update($validated);

    //     // Attach country name and id after update
    //     if ($itinerary->country) {
    //         $country = Country::where('country_id', $itinerary->country)
    //             ->orWhere('country_name', $itinerary->country)
    //             ->first();
    //         $itinerary->country_name = $country ? $country->country_name : null;
    //         $itinerary->country_id = $country ? $country->country_id : null;
    //     } else {
    //         $itinerary->country_name = null;
    //         $itinerary->country_id = null;
    //     }

    //     return response()->json([
    //         'message' => 'Itinerary updated successfully',
    //         'data' => $itinerary
    //     ]);
    // }

    public function update(Request $request, string $id)
{
    Log::info('Admin itinerary update() called', [
        'admin_auth_id' => optional($request->user())->id,
        'itinerary_id' => $id
    ]);

    // Get authenticated admin
    $admin = $request->user();

    if (!$admin) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    // Verify admin
    if (!AdminData::where('id', $admin->id)->exists()) {
        Log::warning('Non-admin attempted itinerary update', [
            'auth_id' => $admin->id
        ]);

        return response()->json([
            'message' => 'Unauthorized - Not an admin'
        ], 403);
    }

    // Find the itinerary
    $itinerary = ItineraryData::find($id);

    if (!$itinerary) {
        return response()->json([
            'message' => 'Itinerary not found'
        ], 404);
    }

    // Validation (same as before)
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
        'emission'           => 'nullable|numeric',
        'offsetAmount'       => 'nullable|integer',
        'offsetPercentage'   => 'nullable|integer',
        'numberOfTrees'      => 'nullable|integer',
        'country' => [
            'nullable',
            'string',
            'max:255',
            function ($attribute, $value, $fail) {
                if (
                    $value &&
                    !Country::where('country_name', $value)
                        ->orWhere('country_id', $value)
                        ->exists()
                ) {
                    $fail('The selected country is invalid.');
                }
            }
        ],
        'status'         => 'nullable|string|max:255',
        'approvelStatus' => 'nullable|string|max:255',
    ]);

    // Convert country to country_id if needed
    if (!empty($validated['country'])) {
        $country = Country::where('country_name', $validated['country'])
            ->orWhere('country_id', $validated['country'])
            ->first();

        $validated['country'] = $country
            ? $country->country_id
            : $validated['country'];
    }

    // Update itinerary
    $itinerary->update($validated);

    // Attach country name and id after update
    if ($itinerary->country) {
        $country = Country::where('country_id', $itinerary->country)
            ->orWhere('country_name', $itinerary->country)
            ->first();

        $itinerary->country_name = $country?->country_name;
        $itinerary->country_id   = $country?->country_id;
    } else {
        $itinerary->country_name = null;
        $itinerary->country_id   = null;
    }

    Log::info('Admin itinerary updated successfully', [
        'admin_id' => $admin->id,
        'itinerary_id' => $id
    ]);

    return response()->json([
        'message' => 'Itinerary updated successfully',
        'data' => $itinerary
    ]);
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $userId, string $itineraryId)
    {
        Log::info('Admin delete itinerary called', [
            'admin_auth_id'   => optional($request->user())->id,
            'passed_user_id'  => $userId,
            'itinerary_id'    => $itineraryId,
        ]);
    
        // Get authenticated admin
        $admin = $request->user();
    
        if (!$admin) {
            return response()->json([
                'message' => 'Unauthenticated'
            ], 401);
        }
    
        // Verify admin
        if (!AdminData::where('id', $admin->id)->exists()) {
            Log::warning('Non-admin attempted itinerary delete', [
                'auth_id' => $admin->id,
            ]);
    
            return response()->json([
                'message' => 'Unauthorized - Not an admin'
            ], 403);
        }
    
        // Find itinerary belongs to user
        $itinerary = ItineraryData::where('ItineraryId', $itineraryId)
            ->where('userId', $userId)
            ->first();
    
        if (!$itinerary) {
            Log::warning('Itinerary not found for delete', [
                'user_id'      => $userId,
                'itinerary_id' => $itineraryId,
            ]);
    
            return response()->json([
                'status'  => false,
                'message' => 'Itinerary not found for this user'
            ], 404);
        }
    
        // Delete itinerary
        $itinerary->delete();
    
        Log::info('Admin itinerary deleted successfully', [
            'admin_id'     => $admin->id,
            'user_id'      => $userId,
            'itinerary_id' => $itineraryId,
        ]);
    
        return response()->json([
            'status'  => true,
            'message' => 'Itinerary deleted successfully'
        ]);
    }
    
}
